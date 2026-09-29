<?php

namespace App\Console\Commands;

use App\Models\Dvr;
use App\Services\RtspGenerator;
use Illuminate\Console\Command;

class CctvUrlsCommand extends Command
{
    protected $signature = 'cctv:urls
        {--mode=local : Mode stream: "local" atau "public"}
        {--unit= : Batasi ke id unit tertentu}
        {--dvr= : Batasi ke id DVR tertentu}
        {--camera= : Batasi ke id kamera tertentu}
        {--raw : Cetak hanya URL mentah, satu per baris}
        {--redact : Sembunyikan password di URL}';

    protected $description = 'Tampilkan URL RTSP hasil generate untuk pengujian VLC Open Network Stream.';

    public function handle(RtspGenerator $generator): int
    {
        $mode = trim((string) $this->option('mode'));
        if (! in_array($mode, ['local', 'public'], true)) {
            $this->error(sprintf('Mode "%s" tidak valid. Gunakan "local" atau "public".', $mode));

            return self::FAILURE;
        }

        $query = Dvr::query()
            ->with(['unit', 'cameras'])
            ->orderBy('unit_id')
            ->orderBy('id');

        if ($this->option('unit') !== null && $this->option('unit') !== '') {
            $query->where('unit_id', (int) $this->option('unit'));
        }

        if ($this->option('dvr') !== null && $this->option('dvr') !== '') {
            $query->where('id', (int) $this->option('dvr'));
        }

        $selectedCameraId = $this->option('camera') !== null && $this->option('camera') !== ''
            ? (int) $this->option('camera')
            : null;

        $rows = [];
        foreach ($query->get() as $dvr) {
            foreach ($dvr->cameras->sortBy('channel') as $camera) {
                if ($selectedCameraId !== null && $selectedCameraId !== $camera->id) {
                    continue;
                }

                $rows[] = [$dvr, $camera];
            }
        }

        if ($rows === []) {
            $this->warn('Tidak ada kamera yang cocok dengan filter.');

            return self::SUCCESS;
        }

        $subtype = (int) config('cctv.subtype');
        $raw = (bool) $this->option('raw');
        $redact = (bool) $this->option('redact');

        foreach ($rows as [$dvr, $camera]) {
            $unitCode = $dvr->unit?->kode ?? '-';
            $header = sprintf('[%s] DVR %s → CH %d (%s) [%s]: ', $unitCode, $dvr->nama, $camera->channel, $camera->nama_lokasi, $mode);

            $url = $generator->generateForMode($dvr, $camera->channel, $subtype, $mode);

            if ($url === null) {
                if (! $raw) {
                    $this->line($header.'(public: tidak tersedia — ip_public kosong)');
                }

                continue;
            }

            if ($redact) {
                $url = $this->redact($url);
            }

            $this->line($raw ? $url : $header.$url);
        }

        return self::SUCCESS;
    }

    private function redact(string $url): string
    {
        return (string) preg_replace('/^(rtsps?:\/\/[^:]*:)[^@]*@/', '$1***@', $url);
    }
}
