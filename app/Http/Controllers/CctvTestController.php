<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CctvTestController extends Controller
{
    /**
     * Tampilkan halaman testing CCTV.
     */
    public function index()
    {
        return view('cctv-test');
    }

    /**
     * Mulai stream: jalankan FFmpeg (RTSP -> HLS) dan simpan PID-nya.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rtsp_url' => ['required', 'string', 'max:500'],
        ]);

        $rtspUrl = trim($validated['rtsp_url']);

        if (! $this->isValidRtspUrl($rtspUrl)) {
            return response()->json([
                'ok' => false,
                'error' => 'Format URL tidak valid. Harus diawali rtsp:// (contoh: rtsp://user:password@192.168.1.10:554/stream).',
            ], 422);
        }

        // Hentikan stream lama kalau masih jalan.
        $this->stopStreamProcess(false);

        // Bersihkan sisa file HLS dari run sebelumnya.
        $this->clearHlsFiles();

        $hlsDir = public_path('hls');
        if (! is_dir($hlsDir)) {
            mkdir($hlsDir, 0755, true);
        }

        $playlist = $hlsDir.DIRECTORY_SEPARATOR.'stream.m3u8';
        $segment = $hlsDir.DIRECTORY_SEPARATOR.'stream_%03d.ts';
        $logFile = storage_path('logs').DIRECTORY_SEPARATOR.'cctv-ffmpeg.log';

        $ffmpeg = $this->resolveFfmpegPath();
        if ($ffmpeg === null) {
            return response()->json([
                'ok' => false,
                'error' => 'FFmpeg tidak ditemukan. Install FFmpeg lalu isi FFMPEG_PATH di .env '
                    .'dengan path lengkap (contoh Windows: "C:\\ffmpeg\\bin\\ffmpeg.exe").',
            ], 500);
        }

        // RTSP tidak bisa langsung diputar browser.
        // FFmpeg menyalurkannya jadi HLS (file .m3u8 + segmen .ts) yang bisa diputar via hls.js.
        // Gunakan bentuk array: di Windows ini mem-bypass cmd.exe (tidak butuh System32 di PATH)
        // dan aman untuk karakter seperti '&' maupun '%' di URL/format file.
        // Kamera Dahua umumnya mengirim HEVC/H.265 yang tidak bisa diputar hls.js, jadi
        // transcode ke H.264. -use_wallclock_as_timestamps memperbaiki timestamp non-monotonik.
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
            return response()->json([
                'ok' => false,
                'error' => 'FFmpeg tidak bisa dijalankan. Pastikan FFMPEG_PATH di .env benar dan FFmpeg sudah terinstall.',
            ], 500);
        }

        $status = proc_get_status($proc);
        file_put_contents($this->pidFile(), $status['pid']);

        return response()->json(['ok' => true]);
    }

    /**
     * Cek status: connected / starting / failed.
     */
    public function status(): JsonResponse
    {
        $running = $this->isStreamProcessRunning();
        $playlist = public_path('hls').DIRECTORY_SEPARATOR.'stream.m3u8';

        $playlistExists = is_file($playlist);
        $playlistFresh = $playlistExists && (time() - (int) filemtime($playlist)) < 10;

        if ($running && $playlistFresh) {
            return response()->json(['status' => 'connected']);
        }

        if ($running) {
            return response()->json(['status' => 'starting']);
        }

        // Proses sudah berhenti -> gagal koneksi atau terputus.
        $error = $this->readFfmpegError();
        $message = $playlistExists
            ? 'Koneksi terputus. CCTV tidak lagi mengirim stream.'
            : 'Gagal terhubung ke CCTV. Periksa URL, username, password, dan pastikan kamera online.';

        if ($error !== '') {
            $message .= "\n\n".$error;
        }

        return response()->json(['status' => 'failed', 'error' => $message]);
    }

    /**
     * Hentikan stream dan bersihkan file sementara.
     */
    public function stop(): JsonResponse
    {
        $this->stopStreamProcess(false);
        $this->clearHlsFiles();

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Temukan path lengkap binary FFmpeg, atau null jika tidak ada.
     * Mencari di FFMPEG_PATH (.env) atau menyusuri PATH.
     */
    private function resolveFfmpegPath(): ?string
    {
        $configured = trim((string) env('FFMPEG_PATH', 'ffmpeg'));
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

    private function isValidRtspUrl(string $url): bool
    {
        if (! preg_match('#^rtsps?://#i', $url)) {
            return false;
        }

        $parts = parse_url($url);
        if (! isset($parts['host']) || $parts['host'] === '') {
            return false;
        }

        // Pisahkan query (parameter kamera seperti ?channel=1&subtype=0),
        // karena '&' adalah pemisah parameter yang wajar di URL RTSP.
        $pathPart = $url;
        if (strpos($url, '?') !== false) {
            [$pathPart] = explode('?', $url, 2);
        }

        // Tolak karakter shell berbahaya + kontrol karakter di bagian path.
        if (preg_match('/[;&|`\'"\x00-\x1F\x7F]/', $pathPart)) {
            return false;
        }

        // Di query hanya boleh ada karakter aman URL.
        if (isset($parts['query']) && preg_match('/[^A-Za-z0-9_\-&=:%+,.~\/]/', $parts['query'])) {
            return false;
        }

        return true;
    }

    private function hlsDir(): string
    {
        return public_path('hls');
    }

    private function pidFile(): string
    {
        return storage_path('app').DIRECTORY_SEPARATOR.'cctv.pid';
    }

    private function getPid(): int
    {
        $file = $this->pidFile();
        if (! is_file($file)) {
            return 0;
        }

        return (int) trim((string) file_get_contents($file));
    }

    private function isStreamProcessRunning(): bool
    {
        $pid = $this->getPid();
        if ($pid <= 0) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            exec($this->windowsTool('tasklist').' /FI "PID eq '.$pid.'"', $output);
            $combined = implode("\n", $output);

            // Cari line "ffmpeg.exe  <pid> <session> ..." tanpa bergantung bahasa
            // (jangan pakai "No tasks" karena bisa lokal Indonesia).
            return $combined !== ''
                && preg_match('/\b'.preg_quote((string) $pid, '/').'\b\s+\S/', $combined) === 1;
        }

        exec('kill -0 '.$pid.' 2>/dev/null', $output, $code);

        return $code === 0;
    }

    private function stopStreamProcess(bool $wait): void
    {
        $pid = $this->getPid();
        if ($pid > 0) {
            if (PHP_OS_FAMILY === 'Windows') {
                exec($this->windowsTool('taskkill').' /PID '.$pid.' /T /F', $output);
            } else {
                exec('kill -9 '.$pid.' 2>/dev/null', $output, $code);
            }
        }

        // Bunuh sisa process ffmpeg yang terlanjur jalan dari PID yang tidak tercatat,
        // supaya tidak ada dua instance menulis ke folder HLS yang sama.
        if (PHP_OS_FAMILY === 'Windows') {
            exec($this->windowsTool('taskkill').' /IM ffmpeg.exe /T /F', $output);
        } else {
            exec('pkill -9 -f ffmpeg 2>/dev/null', $output, $code);
        }

        @unlink($this->pidFile());

        if ($wait) {
            usleep(300000);
        }
    }

    private function windowsTool(string $name): string
    {
        $system32 = getenv('SystemRoot') ? getenv('SystemRoot').'\\System32' : 'C:\\Windows\\System32';

        return rtrim($system32, '\\').'\\'.$name.'.exe';
    }

    private function clearHlsFiles(): void
    {
        foreach (glob($this->hlsDir().DIRECTORY_SEPARATOR.'stream*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function readFfmpegError(): string
    {
        $logFile = storage_path('logs').DIRECTORY_SEPARATOR.'cctv-ffmpeg.log';
        if (! is_file($logFile)) {
            return '';
        }

        $lines = array_slice(file($logFile) ?: [], -6);
        $out = trim(implode("\n", $lines));

        return $out === '' ? '' : 'Detail FFmpeg (log terakhir):'."\n".$out;
    }
}
