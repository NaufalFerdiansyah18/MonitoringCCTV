<?php

namespace App\Services;

use App\Models\Camera;

class RtspProber
{
    private RtspGenerator $rtspGenerator;

    private mixed $runner = null;

    public function __construct(?RtspGenerator $rtspGenerator = null, ?callable $runner = null)
    {
        $this->rtspGenerator = $rtspGenerator ?? new RtspGenerator;
        $this->runner = $runner;
    }

    /**
     * Probe kamera via RTSP DESCRIBE (terbuka oleh FFmpeg dengan timeout).
     * Runner dapat di-stub di pengujian agar tidak bergantung jaringan.
     *
     * @return array{online: bool, latency_ms: ?int}
     */
    public function probe(Camera $camera): array
    {
        $mode = (string) config('cctv.mode', 'local');
        if ($mode === 'public' && blank($camera->dvr->ip_public)) {
            $mode = 'local';
        }

        $url = $this->rtspGenerator->generateForMode(
            $camera->dvr,
            $camera->channel,
            (int) config('cctv.subtype'),
            $mode,
        );

        $ffmpeg = $this->resolveFfmpegPath();
        if ($url === null) {
            return ['online' => false, 'latency_ms' => null];
        }

        if ($ffmpeg === null && $this->runner === null) {
            return ['online' => false, 'latency_ms' => null];
        }
        $ffmpeg ??= (string) config('cctv.ffmpeg_path', 'ffmpeg');

        $cmd = [
            $ffmpeg,
            '-hide_banner', '-loglevel', 'error',
            '-rtsp_transport', 'tcp',
            '-timeout', '5000000',
            '-i', $url,
            '-t', '1',
            '-f', 'null', '-',
        ];

        $runner = $this->runner ?? $this->defaultRunner();
        $started = hrtime(true);
        $online = $runner($cmd);
        $latency = (int) round((hrtime(true) - $started) / 1_000_000);

        return ['online' => $online, 'latency_ms' => $latency];
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

            $deadline = microtime(true) + 6.0;
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
}
