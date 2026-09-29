<?php

namespace App\Services;

use App\Models\Recording;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecordingExporter
{
    private mixed $runner = null;

    public function __construct(?callable $runner = null)
    {
        $this->runner = $runner;
    }

    /**
     * Remux arsip HLS rekaman menjadi satu file MP4 (-c copy, bukan re-encode).
     * File hasil ada di storage temp dan dihapus setelah dikirim.
     */
    public function export(Recording $recording): BinaryFileResponse
    {
        $this->pruneEmptyTempDirs();

        $streamKey = $recording->stream_key;
        if ($streamKey === null) {
            throw new RuntimeException('Rekaman ini tidak memiliki berkas HLS.');
        }

        $hlsDir = public_path('hls').DIRECTORY_SEPARATOR.$streamKey;
        $playlist = $hlsDir.DIRECTORY_SEPARATOR.'index.m3u8';
        if (! is_dir($hlsDir) || ! is_file($playlist)) {
            throw new RuntimeException('Berkas HLS rekaman tidak ditemukan.');
        }

        $ffmpeg = $this->resolveFfmpegPath();
        if ($ffmpeg === null && $this->runner === null) {
            throw new RuntimeException(
                'FFmpeg tidak ditemukan. Install FFmpeg lalu isi FFMPEG_PATH di .env dengan path lengkap.'
            );
        }
        $ffmpeg ??= (string) config('cctv.ffmpeg_path', 'ffmpeg');

        $tempDir = storage_path('app'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'export'
            .DIRECTORY_SEPARATOR.'rec-'.$recording->id.'-'.uniqid());
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $output = $tempDir.DIRECTORY_SEPARATOR.'export.mp4';
        $cmd = [
            $ffmpeg,
            '-hide_banner', '-loglevel', 'error',
            '-i', $playlist,
            '-c', 'copy',
            '-bsf:a', 'aac_adtstoasc',
            $output,
        ];

        $runner = $this->runner ?? $this->defaultRunner();
        try {
            $ok = $runner($cmd);
        } catch (\Throwable) {
            $ok = false;
        }

        if (! $ok || ! is_file($output)) {
            @unlink($output);

            throw new RuntimeException('Ekspor rekaman gagal dijalankan oleh FFmpeg.');
        }

        return response()->file($output)->deleteFileAfterSend(true);
    }

    private function defaultRunner(): callable
    {
        return function (array $cmd): bool {
            $devNull = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
            $descriptors = [
                0 => ['file', $devNull, 'r'],
                1 => ['file', $devNull, 'w'],
                2 => ['file', $devNull, 'w'],
            ];

            $proc = @proc_open($cmd, $descriptors, $pipes);
            if (! is_resource($proc)) {
                return false;
            }

            $deadline = microtime(true) + 120.0;
            while (true) {
                $status = proc_get_status($proc);
                if (! $status['running']) {
                    break;
                }

                if (microtime(true) > $deadline) {
                    proc_terminate($proc);
                    break;
                }

                usleep(200000);
            }

            $status = proc_get_status($proc);
            $exitCode = $status['exitcode'];
            proc_close($proc);

            return $exitCode === 0;
        };
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

    private function pruneEmptyTempDirs(): void
    {
        $exportDir = storage_path('app'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'export');
        if (! is_dir($exportDir)) {
            return;
        }

        foreach (glob($exportDir.DIRECTORY_SEPARATOR.'*') ?: [] as $dir) {
            if (is_dir($dir) && glob($dir.DIRECTORY_SEPARATOR.'*') === []) {
                @rmdir($dir);
            }
        }
    }
}
