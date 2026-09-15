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

class LiveviewTest extends TestCase
{
    use RefreshDatabase;

    private function kebunTeknis(): User
    {
        $group = TechnicalGroup::factory()->create();
        TechnicalGroupUnitCategory::create(['technical_group_id' => $group->id, 'kategori' => 'kebun']);

        return User::factory()->withTechnicalGroup($group)->create();
    }

    private function cameraOn(Unit $unit, bool $withPublic = true): Camera
    {
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'nama' => 'DVR '.$unit->nama,
            'ip_public' => $withPublic ? '203.0.113.10' : null,
            'port_public' => $withPublic ? 38003 : null,
        ]);

        return Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => 'Gerbang',
        ]);
    }

    public function test_guest_is_redirected_to_login_for_liveview_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post(route('liveview.start'), ['camera_id' => 1, 'mode' => 'local'])
            ->assertRedirect(route('login'));
        $this->get(route('liveview.status', ['stream_key' => 'cam-1']))
            ->assertRedirect(route('login'));
    }

    public function test_teknis_dashboard_filters_units_and_denies_out_of_category_unit(): void
    {
        $teknis = $this->kebunTeknis();
        $kebun = Unit::factory()->create(['kategori' => 'kebun', 'nama' => 'Kebun Satu']);
        $pks = Unit::factory()->create(['kategori' => 'pks', 'nama' => 'Pabrik Satu']);

        $this->actingAs($teknis)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kebun Satu')
            ->assertDontSee('Pabrik Satu');

        $this->actingAs($teknis)
            ->get(route('dashboard', ['unit' => $pks->id]))
            ->assertForbidden();

        $this->actingAs($teknis)
            ->get(route('dashboard', ['unit' => $kebun->id]))
            ->assertOk();
    }

    public function test_superadmin_dashboard_shows_all_units_and_cameras_of_selected_unit(): void
    {
        $admin = User::factory()->superadmin()->create();
        $kebun = Unit::factory()->create(['kategori' => 'kebun', 'nama' => 'Kebun Satu']);
        $this->cameraOn($kebun);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kebun Satu');

        $this->actingAs($admin)
            ->get(route('dashboard', ['unit' => $kebun->id]))
            ->assertOk()
            ->assertSee('Gerbang');
    }

    public function test_dashboard_page_never_leaks_rtsp_url_or_dvr_password(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create(['kategori' => 'kebun', 'nama' => 'Unit Satu']);
        $camera = $this->cameraOn($unit);
        $camera->dvr->update(['password' => 'rahasia-super']);

        $this->actingAs($admin)
            ->get(route('dashboard', ['unit' => $unit->id]))
            ->assertOk()
            ->assertDontSee('rahasia-super')
            ->assertDontSee('rtsp://');
    }

    public function test_teknis_cannot_start_stream_of_unit_outside_its_category(): void
    {
        $teknis = $this->kebunTeknis();
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $camera = $this->cameraOn($pks);

        $this->actingAs($teknis)
            ->postJson(route('liveview.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertForbidden();
    }

    public function test_invalid_mode_is_rejected(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)
            ->postJson(route('liveview.start'), ['camera_id' => 1, 'mode' => 'bogus'])
            ->assertUnprocessable();
    }

    public function test_start_public_stream_rejected_when_dvr_has_no_ip_public(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit, withPublic: false);

        $response = $this->actingAs($admin)
            ->postJson(route('liveview.start'), ['camera_id' => $camera->id, 'mode' => 'public'])
            ->assertJson(['ok' => false]);

        $this->assertStringContainsString('Mode Public tidak tersedia', (string) $response->json('error'));
    }

    public function test_start_payload_is_built_before_ffmpeg_and_reports_missing_binary(): void
    {
        config(['cctv.ffmpeg_path' => null]);

        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $response = $this->actingAs($admin)
            ->postJson(route('liveview.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertOk()
            ->assertJson(['ok' => false]);

        $this->assertStringContainsString('FFmpeg tidak ditemukan', (string) $response->json('error'));
    }

    public function test_start_rejected_when_max_concurrent_streams_reached(): void
    {
        config(['cctv.max_concurrent_streams' => 0]);

        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $response = $this->actingAs($admin)
            ->postJson(route('liveview.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertOk()
            ->assertJson(['ok' => false]);

        $this->assertStringContainsString('Batas stream bersamaan', (string) $response->json('error'));
    }

    public function test_stream_key_is_built_from_camera_id(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);
        $manager = app(StreamManager::class);

        $streamKey = $manager->streamKeyFor($camera);

        $this->assertSame('cam-'.$camera->id, $streamKey);
        $this->assertMatchesRegularExpression('/^[a-z0-9\-_]+$/', $streamKey);
    }

    public function test_status_returns_failed_for_unknown_stream_and_stop_is_idempotent(): void
    {
        $manager = app(StreamManager::class);

        $this->assertSame('failed', $manager->status('cam-999999'));
        $manager->stop('cam-999999');
        $this->assertSame(0, $manager->runningCount());
    }

    public function test_stop_cleans_pid_file_and_hls_folder(): void
    {
        $manager = app(StreamManager::class);
        $streamKey = 'cam-999991';
        $hlsDir = public_path('hls').DIRECTORY_SEPARATOR.$streamKey;
        $pidsDir = storage_path('app'.DIRECTORY_SEPARATOR.'hls');

        @mkdir($hlsDir, 0755, true);
        @mkdir($pidsDir, 0755, true);
        @file_put_contents($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8', '#EXTM3U');
        @file_put_contents($hlsDir.DIRECTORY_SEPARATOR.'segment_000.ts', 'x');
        @file_put_contents($pidsDir.DIRECTORY_SEPARATOR.$streamKey.'.pid', '0');

        try {
            $manager->stop($streamKey);
        } finally {
            @unlink($pidsDir.DIRECTORY_SEPARATOR.$streamKey.'.pid');
            foreach (glob($hlsDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($hlsDir);
            @rmdir($pidsDir);
        }

        $this->assertDirectoryDoesNotExist($hlsDir);
        $this->assertFileDoesNotExist($pidsDir.DIRECTORY_SEPARATOR.$streamKey.'.pid');
    }
}
