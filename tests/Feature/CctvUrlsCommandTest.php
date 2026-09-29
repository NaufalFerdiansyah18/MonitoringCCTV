<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CctvUrlsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    private Dvr $dvrLocal;

    private Dvr $dvrPublic;

    private Camera $cam2;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cctv.mode' => 'local']);
        config(['cctv.subtype' => 0]);

        $this->unit = Unit::factory()->create(['kode' => 'KEB1', 'nama' => 'Kebun 1', 'kategori' => 'kebun']);

        $this->dvrLocal = Dvr::factory()->for($this->unit, 'unit')->create([
            'nama' => 'DVR Lokal',
            'ip_local' => '192.168.1.10',
            'port_local' => 554,
            'username' => 'admin',
            'password' => 'Secret123',
            'ip_public' => null,
            'port_public' => null,
        ]);

        $this->dvrPublic = Dvr::factory()->for($this->unit, 'unit')->create([
            'nama' => 'DVR Publik',
            'ip_local' => '10.0.0.5',
            'port_local' => 554,
            'username' => 'admin',
            'password' => 'Secret123',
            'ip_public' => '203.0.113.7',
            'port_public' => 8554,
        ]);

        Camera::factory()->for($this->dvrLocal, 'dvr')->forChannel(1)->create(['nama_lokasi' => 'Gerbang']);
        $this->cam2 = Camera::factory()->for($this->dvrLocal, 'dvr')->forChannel(2)->create(['nama_lokasi' => 'Parkir']);
        Camera::factory()->for($this->dvrPublic, 'dvr')->forChannel(1)->create(['nama_lokasi' => 'Ruang Server']);
    }

    public function test_default_output_lists_local_urls_in_dahua_format(): void
    {
        $this->artisan('cctv:urls')->assertExitCode(0);

        Artisan::call('cctv:urls', ['--mode' => 'local']);
        $output = Artisan::output();
        $this->assertStringContainsString('rtsp://admin:Secret123@192.168.1.10:554/cam/realmonitor?channel=1&subtype=0', $output);
        $this->assertStringContainsString('rtsp://admin:Secret123@192.168.1.10:554/cam/realmonitor?channel=2&subtype=0', $output);
        $this->assertStringContainsString('[KEB1] DVR DVR Lokal → CH 1 (Gerbang) [local]:', $output);
    }

    public function test_public_mode_uses_public_host_or_marks_unavailable(): void
    {
        $this->artisan('cctv:urls', ['--mode' => 'public'])->assertExitCode(0);

        Artisan::call('cctv:urls', ['--mode' => 'public']);
        $output = Artisan::output();
        $this->assertStringContainsString('rtsp://admin:Secret123@203.0.113.7:8554/cam/realmonitor?channel=1&subtype=0', $output);
        $this->assertStringContainsString('(public: tidak tersedia — ip_public kosong)', $output);
        $this->assertStringNotContainsString('192.168.1.10', $output);
    }

    public function test_raw_output_contains_only_urls(): void
    {
        Artisan::call('cctv:urls', ['--mode' => 'local', '--raw' => true]);
        $lines = preg_split('/\R/', trim(Artisan::output()));

        $this->assertCount(3, $lines);
        foreach ($lines as $line) {
            $this->assertStringStartsWith('rtsp://', $line);
        }
    }

    public function test_raw_output_skips_cameras_without_public_ip(): void
    {
        Artisan::call('cctv:urls', ['--mode' => 'public', '--raw' => true]);
        $lines = preg_split('/\R/', trim(Artisan::output()));

        $this->assertCount(1, $lines);
        $this->assertSame('rtsp://admin:Secret123@203.0.113.7:8554/cam/realmonitor?channel=1&subtype=0', $lines[0]);
    }

    public function test_redact_hides_password(): void
    {
        Artisan::call('cctv:urls', ['--mode' => 'local', '--redact' => true]);
        $this->assertStringContainsString('rtsp://admin:***@192.168.1.10:554', Artisan::output());
        $this->assertStringNotContainsString('Secret123', Artisan::output());

        Artisan::call('cctv:urls', ['--mode' => 'local']);
        $this->assertStringContainsString('Secret123', Artisan::output());
    }

    public function test_unit_dvr_and_camera_filters(): void
    {
        Artisan::call('cctv:urls', ['--mode' => 'local', '--dvr' => $this->dvrLocal->id]);
        $this->assertStringContainsString('192.168.1.10', Artisan::output());
        $this->assertStringNotContainsString('203.0.113.7', Artisan::output());

        Artisan::call('cctv:urls', ['--mode' => 'local', '--camera' => $this->cam2->id]);
        $this->assertStringContainsString('channel=2', Artisan::output());
        $this->assertStringNotContainsString('channel=1', Artisan::output());

        Artisan::call('cctv:urls', ['--mode' => 'local', '--unit' => $this->unit->id]);
        $this->assertStringContainsString('rtsp://', Artisan::output());
    }

    public function test_invalid_mode_returns_failure(): void
    {
        $exitCode = Artisan::call('cctv:urls', ['--mode' => 'garbage']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('tidak valid', Artisan::output());
    }

    public function test_warns_when_no_cameras_match(): void
    {
        $exitCode = Artisan::call('cctv:urls', ['--camera' => 99999]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Tidak ada kamera', Artisan::output());
    }

    public function test_command_does_not_write_urls_to_storage(): void
    {
        $logFile = storage_path('logs/laravel.log');
        $before = is_file($logFile) ? (string) file_get_contents($logFile) : '';

        Artisan::call('cctv:urls', ['--mode' => 'local']);

        $after = is_file($logFile) ? (string) file_get_contents($logFile) : '';
        $this->assertSame($before, $after);
        $this->assertStringNotContainsString('Secret123', $after);
    }
}
