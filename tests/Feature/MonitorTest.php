<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\Camera;
use App\Models\Dvr;
use App\Models\Unit;
use App\Services\RtspProber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorTest extends TestCase
{
    use RefreshDatabase;

    private function bindProber(bool $online): void
    {
        $this->app->instance(RtspProber::class, new RtspProber(null, fn (array $cmd) => $online));
    }

    private function cameraOn(Unit $unit): Camera
    {
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'nama' => 'DVR '.$unit->nama,
        ]);

        return Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => 'Gerbang',
        ]);
    }

    public function test_offline_camera_is_marked_and_alarm_created_once(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $this->bindProber(false);

        $this->artisan('cctv:monitor')->assertExitCode(0);

        $camera->refresh();
        $this->assertFalse($camera->is_online);
        $this->assertNotNull($camera->last_checked_at);
        $this->assertDatabaseHas('alarms', ['camera_id' => $camera->id, 'type' => 'offline']);

        $this->artisan('cctv:monitor')->assertExitCode(0);

        $this->assertSame(1, Alarm::where('camera_id', $camera->id)->where('type', 'offline')->count());
    }

    public function test_online_camera_closes_open_offline_alarm(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $this->bindProber(false);
        $this->artisan('cctv:monitor');

        $this->bindProber(true);
        $this->artisan('cctv:monitor');

        $camera->refresh();
        $this->assertTrue($camera->is_online);

        $alarm = Alarm::where('camera_id', $camera->id)->where('type', 'offline')->latest('id')->first();
        $this->assertNotNull($alarm->ended_at);
    }

    public function test_monitor_records_online_state_and_latency(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $this->bindProber(true);
        $this->artisan('cctv:monitor');

        $camera->refresh();
        $this->assertTrue($camera->is_online);
        $this->assertIsInt($camera->latency_ms);
        $this->assertCount(0, Alarm::where('camera_id', $camera->id)->where('type', 'offline')->get());
    }

    public function test_command_succeeds_when_no_cameras(): void
    {
        $this->artisan('cctv:monitor')->assertExitCode(0);
    }
}
