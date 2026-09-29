<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Database\Eloquent\Collection;

class RecordingManager
{
    public const STREAM_KEY_PATTERN = '/^[a-z0-9\-_]+$/';

    private RtspGenerator $rtspGenerator;

    private mixed $runner = null;

    public function __construct(?RtspGenerator $rtspGenerator = null, ?callable $runner = null)
    {
        $this->rtspGenerator = $rtspGenerator ?? new RtspGenerator;
        $this->runner = $runner;
    }

    /**
     * Mulai/kontinu rekaman satu kamera. Rekaman yang masih berjalan untuk
     * kamera yang sama dihentikan dulu (perilaku "mulai ulang").
     *
     * @return array{ok: bool, recordingId?: int, streamKey?: string, error?: string}
     */
    public function start(Camera $camera, string $mode = 'local'): array
    {
        $dvr = $camera->dvr;

        if ($mode === 'public' && blank($dvr->ip_public)) {
            return [
                'ok' => false,
                'error' => 'Mode Public tidak tersedia untuk DVR ini (IP public kosong).',
            ];
        }

        $active = $this->activeRecordingFor($camera);
        if ($active !== null) {
            $this->stop($active->id);
        }

        $recording = Recording::create([
            'camera_id' => $camera->id,
            'started_at' => now(),
            'status' => 'recording',
        ]);

        $streamKey = 'rec-'.$recording->id;
        if (! preg_match(self::STREAM_KEY_PATTERN, $streamKey)) {
            $recording->update(['status' => 'failed', 'ended_at' => now()]);

            return ['ok' => false, 'error' => 'Stream key rekaman tidak valid.'];
        }

        $rtspUrl = $this->rtspGenerator->generateForMode(
            $dvr,
            $camera->channel,
            (int) config('cctv.subtype'),
            $mode,
        );

        if ($rtspUrl === null) {
            $recording->update(['status' => 'failed', 'ended_at' => now()]);

            return ['ok' => false, 'error' => 'Tidak dapat membangkitkan URL stream untuk mode '.$mode.'.'];
        }

        $ffmpeg = $this->resolveFfmpegPath();
        if ($ffmpeg === null && $this->runner === null) {
            $recording->update(['status' => 'failed', 'ended_at' => now()]);

            return [
                'ok' => false,
                'error' => 'FFmpeg tidak ditemukan. Install FFmpeg lalu isi FFMPEG_PATH di .env '
                    .'dengan path lengkap (contoh Windows: "C:\ffmpeg\bin\ffmpeg.exe").',
            ];
        }
        $ffmpeg ??= (string) config('cctv.ffmpeg_path', 'ffmpeg');

        $recording->update(['stream_key' => $streamKey]);

        $this->clearHlsFiles($streamKey);

        $hlsDir = $this->hlsDir($streamKey);
        if (! is_dir($hlsDir)) {
            mkdir($hlsDir, 0755, true);
        }

        $playlist = $hlsDir.DIRECTORY_SEPARATOR.'index.m3u8';
        $segment = $hlsDir.DIRECTORY_SEPARATOR.'segment_%03d.ts';
        $logFile = storage_path('logs').DIRECTORY_SEPARATOR.'cctv-'.$streamKey.'.log';

        $cmd = [
            $ffmpeg,
            '-hide_banner', '-loglevel', 'warning',
            '-rtsp_transport', 'tcp',
            '-timeout', '10000000',
            '-use_wallclock_as_timestamps', '1',
            '-i', $rtspUrl,
            '-map', '0:v:0',
            '-map', '0:a?',
            '-c:v', 'libx264', '-preset', 'veryfast', '-tune', 'zerolatency',
            '-pix_fmt', 'yuv420p',
            '-g', '30', '-sc_threshold', '0',
            '-b:v', '2500k', '-maxrate', '3000k', '-bufsize', '6000k',
            '-c:a', 'aac', '-b:a', '128k',
            '-f', 'hls', '-hls_time', '2', '-hls_list_size', '0',
            '-hls_flags', 'independent_segments',
            '-hls_segment_filename', $segment,
            $playlist,
        ];

        $pid = $this->run($cmd, $logFile, $hlsDir);
        if ($pid === null) {
            $recording->update(['status' => 'failed', 'ended_at' => now()]);

            return [
                'ok' => false,
                'error' => 'FFmpeg tidak bisa dijalankan. Pastikan FFMPEG_PATH di .env benar dan FFmpeg sudah terinstall.',
            ];
        }

        $this->ensurePidsDir();
        file_put_contents($this->pidFile($streamKey), (string) $pid);

        return ['ok' => true, 'recordingId' => $recording->id, 'streamKey' => $streamKey];
    }

    /**
     * Hentikan proses rekaman tersebut saja (tidak menyentuh stream liveview),
     * lalu tutup row rekaman dengan durasi & ukuran dari folder HLS-nya.
     * Folder HLS sengaja dipertahankan agar tetap bisa diputar ulang.
     */
    public function stop(int $recordingId): void
    {
        $recording = Recording::find($recordingId);
        if ($recording === null) {
            return;
        }

        $streamKey = $recording->stream_key;
        if ($streamKey !== null && preg_match(self::STREAM_KEY_PATTERN, $streamKey)) {
            $this->killProcess($this->readPid($streamKey));
            @unlink($this->pidFile($streamKey));
        }

        $duration = $recording->started_at !== null
            ? (int) $recording->started_at->diffInSeconds(now())
            : null;

        $size = $streamKey !== null
            ? $this->directorySize($this->hlsDir($streamKey))
            : null;

        $recording->update([
            'status' => 'stopped',
            'ended_at' => now(),
            'duration_seconds' => $duration,
            'size_bytes' => $size,
        ]);
    }

    /**
     * Status rekaman mengikuti pola StreamManager: connected (proses hidup +
     * playlist segar), starting, atau failed.
     */
    public function status(int $recordingId): string
    {
        $recording = Recording::find($recordingId);
        if ($recording === null || $recording->stream_key === null) {
            return 'failed';
        }

        $streamKey = $recording->stream_key;
        $running = $this->isRunning($streamKey);
        $playlist = $this->hlsDir($streamKey).DIRECTORY_SEPARATOR.'index.m3u8';
        $playlistFresh = is_file($playlist) && (time() - (int) filemtime($playlist)) < 10;

        if ($running && $playlistFresh) {
            return 'connected';
        }

        if ($running) {
            return 'starting';
        }

        return 'failed';
    }

    public function isRecording(int $recordingId): bool
    {
        return in_array($this->status($recordingId), ['connected', 'starting'], true);
    }

    public function activeRecordingFor(Camera $camera): ?Recording
    {
        return $camera->recordings()
            ->where('status', 'recording')
            ->latest('started_at')
            ->first();
    }

    public function recordingsFor(Camera $camera): Collection
    {
        return $camera->recordings()->latest('started_at')->get();
    }

    public function error(int $recordingId): string
    {
        $recording = Recording::find($recordingId);
        if ($recording?->stream_key === null) {
            return '';
        }

        $logFile = storage_path('logs').DIRECTORY_SEPARATOR.'cctv-'.$recording->stream_key.'.log';
        if (! is_file($logFile)) {
            return '';
        }

        $lines = array_slice(file($logFile) ?: [], -6);
        $out = trim(implode("\n", $lines));

        if ($out === '') {
            return '';
        }

        return 'Detail FFmpeg (log terakhir):'."\n".$this->redactCredentials($out);
    }

    private function run(array $cmd, string $logFile, string $cwd): ?int
    {
        if ($this->runner !== null) {
            return (int) ($this->runner)($cmd);
        }

        $devNull = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $descriptors = [
            0 => ['file', $devNull, 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ];

        $proc = @proc_open($cmd, $descriptors, $pipes, $cwd);
        if (! is_resource($proc)) {
            return null;
        }

        $status = proc_get_status($proc);

        return $status['pid'] > 0 ? $status['pid'] : null;
    }

    private function redactCredentials(string $text): string
    {
        return preg_replace('#rtsp://[^@\s]+@#', 'rtsp://***@', $text) ?? $text;
    }

    private function isRunning(string $streamKey): bool
    {
        $pid = $this->readPid($streamKey);

        return $pid > 0 && $this->isProcessAlive($pid);
    }

    private function readPid(string $streamKey): int
    {
        $file = $this->pidFile($streamKey);
        if (! is_file($file)) {
            return 0;
        }

        return (int) trim((string) file_get_contents($file));
    }

    private function isProcessAlive(int $pid): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec($this->windowsTool('tasklist').' /FI "PID eq '.$pid.'"', $output);
            $combined = implode("\n", $output);

            return $combined !== ''
                && preg_match('/\b'.preg_quote((string) $pid, '/').'\b\s+\S/', $combined) === 1;
        }

        exec('kill -0 '.$pid.' 2>/dev/null', $output, $code);

        return $code === 0;
    }

    private function killProcess(int $pid): void
    {
        if ($pid <= 0) {
            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            exec($this->windowsTool('taskkill').' /PID '.$pid.' /T /F', $output);
        } else {
            exec('kill -9 '.$pid.' 2>/dev/null', $output, $code);
        }
    }

    private function windowsTool(string $name): string
    {
        $system32 = getenv('SystemRoot') ? getenv('SystemRoot').'\\System32' : 'C:\\Windows\\System32';

        return rtrim($system32, '\\').'\\'.$name.'.exe';
    }

    private function resolveFfmpegPath(): ?string
    {
        $configured = trim((string) config('cctv.ffmpeg_path', 'ffmpeg'));
        if ($configured === '') {
            return null;
        }

        $hasSeparator = strpbrk($configured, '/\\') !== false;
        if ($hasSeparator) {
            return is_file($configured) ? $configured : null;
        }

        $names = [$configured, $configured.'.exe'];
        $path = (string) getenv('PATH');
        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            if ($dir === '') {
                continue;
            }
            foreach ($names as $name) {
                $candidate = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.$name;
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function hlsDir(string $streamKey): string
    {
        return public_path('hls').DIRECTORY_SEPARATOR.$streamKey;
    }

    private function pidsDir(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'hls');
    }

    private function pidFile(string $streamKey): string
    {
        return $this->pidsDir().DIRECTORY_SEPARATOR.$streamKey.'.pid';
    }

    private function ensurePidsDir(): void
    {
        $dir = $this->pidsDir();
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function clearHlsFiles(string $streamKey): void
    {
        $dir = $this->hlsDir($streamKey);
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }

    private function directorySize(string $dir): ?int
    {
        if (! is_dir($dir)) {
            return null;
        }

        $total = 0;
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            if (is_file($file)) {
                $total += (int) filesize($file);
            }
        }

        return $total;
    }
}
