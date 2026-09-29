<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\StreamManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private function kebunTeknis(): User
    {
        $group = TechnicalGroup::factory()->create();
        TechnicalGroupUnitCategory::create(['technical_group_id' => $group->id, 'kategori' => 'kebun']);

        return User::factory()->withTechnicalGroup($group)->create();
    }

    private function cameraOn(Unit $unit): Camera
    {
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'nama' => 'DVR '.$unit->nama,
            'ip_public' => '203.0.113.10',
            'port_public' => 38003,
        ]);

        return Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => 'Gerbang',
        ]);
    }

    public function test_camera_with_nested_dvr_serialization_hides_password(): void
    {
        $dvr = Dvr::factory()->create(['password' => 'rahasia-nested']);
        $camera = Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create();

        $json = json_encode($camera->load('dvr')->toArray());

        $this->assertIsString($json);
        $this->assertStringNotContainsString('rahasia-nested', $json);
        $this->assertStringNotContainsString('"password"', $json);
    }

    public function test_stream_error_redacts_rtsp_credentials_from_log(): void
    {
        $streamKey = 'cam-123456';
        $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'cctv-'.$streamKey.'.log');
        @file_put_contents(
            $logFile,
            "rtsp://admin:rahasia123@192.168.1.10:554/cam/realmonitor?channel=1&subtype=0\n"
            ."Error mengakses stream.\n"
        );

        try {
            $error = app(StreamManager::class)->error($streamKey);

            $this->assertStringContainsString('rtsp://***@', $error);
            $this->assertStringNotContainsString('rahasia123', $error);
            $this->assertStringNotContainsString('admin:', $error);
        } finally {
            @unlink($logFile);
        }
    }

    public function test_teknis_cannot_stop_or_status_stream_of_out_of_category_camera(): void
    {
        $teknis = $this->kebunTeknis();
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $camera = $this->cameraOn($pks);

        $this->actingAs($teknis)
            ->postJson(route('liveview.stop'), ['stream_key' => 'cam-'.$camera->id])
            ->assertForbidden();

        $this->actingAs($teknis)
            ->get(route('liveview.status', ['stream_key' => 'cam-'.$camera->id]))
            ->assertForbidden();
    }

    public function test_running_count_ignores_stale_pid_files(): void
    {
        $manager = app(StreamManager::class);
        $pidsDir = storage_path('app'.DIRECTORY_SEPARATOR.'hls');
        $pidFile = $pidsDir.DIRECTORY_SEPARATOR.'cam-657898.pid';

        @mkdir($pidsDir, 0755, true);
        $before = $manager->runningCount();

        try {
            @file_put_contents($pidFile, '999999999');

            $this->assertSame($before, $manager->runningCount());
            $this->assertFileDoesNotExist($pidFile);
        } finally {
            @unlink($pidFile);
            @rmdir($pidsDir);
        }
    }

    public function test_stream_limit_config_and_env_keys_are_present(): void
    {
        $this->assertIsInt(config('cctv.max_concurrent_streams'));
        $this->assertGreaterThan(0, config('cctv.max_concurrent_streams'));

        $env = file_get_contents(base_path('.env'));
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('MAX_CONCURRENT_STREAMS', $env);
        $this->assertStringContainsString('MAX_CONCURRENT_STREAMS', $envExample);
        $this->assertStringContainsString('CCTV_MODE', $envExample);
        $this->assertStringContainsString('CCTV_SUBTYPE', $envExample);
    }
}
