<?php

namespace App\Console\Commands;

use App\Models\Camera;
use App\Services\RecordingManager;
use Illuminate\Console\Command;

class CctvRecorderCommand extends Command
{
    protected $signature = 'cctv:recorder';

    protected $description = 'Pastikan setiap kamera memiliki sesi rekaman yang berjalan (rekam kontinu).';

    public function __construct(private RecordingManager $recordings)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (Camera::count() === 0) {
            $this->comment('Tidak ada kamera untuk direkam.');

            return self::SUCCESS;
        }

        foreach (Camera::query()->with('dvr')->get() as $camera) {
            $active = $this->recordings->activeRecordingFor($camera);

            if ($active === null) {
                $this->startFor($camera);

                continue;
            }

            if ($this->recordings->isRecording($active->id)) {
                $this->line(sprintf('Rekaman kamera %d sedang berjalan (id %d).', $camera->id, $active->id));

                continue;
            }

            $fresh = $active->started_at !== null && $active->started_at->diffInSeconds(now()) < 120;
            if ($fresh) {
                $this->line(sprintf('Rekaman kamera %d baru dimulai; menunggu proses stabil.', $camera->id));

                continue;
            }

            $this->startFor($camera);
        }

        return self::SUCCESS;
    }

    private function startFor(Camera $camera): void
    {
        $result = $this->recordings->start($camera);

        if ($result['ok']) {
            $this->info(sprintf('Rekaman kamera %d dimulai (id %d).', $camera->id, $result['recordingId']));

            return;
        }

        $this->error(sprintf('Rekaman kamera %d gagal: %s', $camera->id, $result['error'] ?? 'tidak diketahui'));
    }
}
