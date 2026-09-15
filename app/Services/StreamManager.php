<?php

namespace App\Services;

use App\Models\Camera;

class StreamManager
{
    public const STREAM_KEY_PATTERN = '/^[a-z0-9\-_]+$/';

    private RtspGenerator $rtspGenerator;

    public function __construct(?RtspGenerator $rtspGenerator = null)
    {
        $this->rtspGenerator = $rtspGenerator ?? new RtspGenerator;
    }

    public function streamKeyFor(Camera $camera): string
    {
        return 'cam-'.$camera->id;
    }

    /**
     * Mulai satu stream per kamera. Menolak (tanpa overwrite stream berjalan)
     * bila batas MAX_CONCURRENT_STREAMS sudah tercapai.
     *
     * @return array{ok: bool, streamKey?: string, error?: string}
     */
    public function start(Camera $camera, string $mode): array
    {
        $dvr = $camera->dvr;

        if ($mode === 'public' && blank($dvr->ip_public)) {
            return [
                'ok' => false,
                'error' => 'Mode Public tidak tersedia untuk DVR ini (IP public kosong).',
            ];
        }

        if ($this->runningCount() >= $this->maxConcurrentStreams()) {
            $limit = $this->maxConcurrentStreams();

            return [
                'ok' => false,
                'error' => 'Batas stream bersamaan tercapai ('.$limit.'). Hentikan sebagian stream dulu.',
            ];
        }

        $rtspUrl = $this->rtspGenerator->generateForMode(
            $dvr,
            $camera->channel,
            (int) config('cctv.subtype'),
            $mode,
        );

        if ($rtspUrl === null) {
            return ['ok' => false, 'error' => 'Tidak dapat membangkitkan URL stream untuk mode '.$mode.'.'];
        }

        $streamKey = $this->streamKeyFor($camera);
        if (! preg_match(self::STREAM_KEY_PATTERN, $streamKey)) {
            return ['ok' => false, 'error' => 'Stream key tidak valid.'];
        }

        if ($this->isRunning($streamKey)) {
            $this->stop($streamKey);
        }

        $ffmpeg = $this->resolveFfmpegPath();
        if ($ffmpeg === null) {
            return [
                'ok' => false,
                'error' => 'FFmpeg tidak ditemukan. Install FFmpeg lalu isi FFMPEG_PATH di .env '
                    .'dengan path lengkap (contoh Windows: "C:\ffmpeg\bin\ffmpeg.exe").',
            ];
        }

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
            '-use_wallclock_as_timestamps', '1',
            '-i', $rtspUrl,
            '-map', '0:v:0',
            '-map', '0:a?',
            '-c:v', 'libx264', '-preset', 'veryfast', '-tune', 'zerolatency',
            '-pix_fmt', 'yuv420p',
            '-g', '30', '-sc_threshold', '0',
            '-b:v', '2500k', '-maxrate', '3000k', '-bufsize', '6000k',
            '-c:a', 'aac', '-b:a', '128k',
            '-f', 'hls', '-hls_time', '2', '-hls_list_size', '6',
            '-hls_flags', 'temp_file+delete_segments+independent_segments',
            '-hls_segment_filename', $segment,
            $playlist,
        ];

        $devNull = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $descriptors = [
            0 => ['file', $devNull, 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ];

        $proc = @proc_open($cmd, $descriptors, $pipes, $hlsDir);

        if (! is_resource($proc)) {
            return [
                'ok' => false,
                'error' => 'FFmpeg tidak bisa dijalankan. Pastikan FFMPEG_PATH di .env benar dan FFmpeg sudah terinstall.',
            ];
        }

        $status = proc_get_status($proc);
        $this->ensurePidsDir();
        file_put_contents($this->pidFile($streamKey), (string) $status['pid']);

        return ['ok' => true, 'streamKey' => $streamKey];
    }

    /**
     * Status stream: connected (PID hidup + playlist segar), starting (PID
     * hidup tapi playlist belum terbentuk/segar), atau failed.
     */
    public function status(string $streamKey): string
    {
        if (! preg_match(self::STREAM_KEY_PATTERN, $streamKey)) {
            return 'failed';
        }

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

    /**
     * Bunuh proses stream yang dimaksud saja, lalu bersihkan PID dan folder HLS-nya.
     */
    public function stop(string $streamKey): void
    {
        if (! preg_match(self::STREAM_KEY_PATTERN, $streamKey)) {
            return;
        }

        $pid = $this->readPid($streamKey);
        if ($pid > 0) {
            $this->killProcess($pid);
        }

        @unlink($this->pidFile($streamKey));
        $this->clearHlsFiles($streamKey);
    }

    /**
     * Jumlah proses stream yang masih hidup (dari file PID). File PID basi dibersihkan.
     */
    public function runningCount(): int
    {
        $count = 0;

        foreach (glob($this->pidsDir().DIRECTORY_SEPARATOR.'*.pid') ?: [] as $file) {
            $pid = (int) trim((string) @file_get_contents($file));
            if ($pid > 0 && $this->isProcessAlive($pid)) {
                $count++;
            } else {
                @unlink($file);
            }
        }

        return $count;
    }

    public function error(string $streamKey): string
    {
        $logFile = storage_path('logs').DIRECTORY_SEPARATOR.'cctv-'.$streamKey.'.log';
        if (! is_file($logFile)) {
            return '';
        }

        $lines = array_slice(file($logFile) ?: [], -6);
        $out = trim(implode("\n", $lines));

        return $out === '' ? '' : 'Detail FFmpeg (log terakhir):'."\n".$out;
    }

    private function maxConcurrentStreams(): int
    {
        return (int) config('cctv.max_concurrent_streams', 8);
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
}
