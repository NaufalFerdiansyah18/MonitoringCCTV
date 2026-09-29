<?php

namespace App\Console\Commands;

use App\Models\Alarm;
use App\Models\Camera;
use App\Services\RtspProber;
use Illuminate\Console\Command;

class CctvMonitorCommand extends Command
{
    protected $signature = 'cctv:monitor';

    protected $description = 'Perbarui status online/offline kamera via probing RTSP.';

    public function handle(): int
    {
        if (Camera::count() === 0) {
            $this->comment('Tidak ada kamera untuk dimonitor.');

            return self::SUCCESS;
        }

        $prober = app(RtspProber::class);

        foreach (Camera::query()->with('dvr')->get() as $camera) {
            $result = $prober->probe($camera);
            $online = $result['online'];

            $camera->update([
                'is_online' => $online,
                'last_checked_at' => now(),
                'latency_ms' => $result['latency_ms'],
            ]);

            if ($online) {
                $this->closeOpenOfflineAlarm($camera);
            } else {
                $this->openOfflineAlarm($camera);
            }

            $this->info(sprintf('Kamera %d: %s', $camera->id, $online ? 'online' : 'offline'));
        }

        return self::SUCCESS;
    }

    private function openOfflineAlarm(Camera $camera): void
    {
        $open = Alarm::query()
            ->where('camera_id', $camera->id)
            ->where('type', 'offline')
            ->whereNull('ended_at')
            ->latest('id')
            ->first();

        if ($open === null) {
            Alarm::create([
                'camera_id' => $camera->id,
                'type' => 'offline',
                'message' => 'Kamera tidak terjangkau saat monitoring.',
                'started_at' => now(),
            ]);
        }
    }

    private function closeOpenOfflineAlarm(Camera $camera): void
    {
        $open = Alarm::query()
            ->where('camera_id', $camera->id)
            ->where('type', 'offline')
            ->whereNull('ended_at')
            ->latest('id')
            ->first();

        if ($open !== null) {
            $open->update(['ended_at' => now()]);
        }
    }
}
