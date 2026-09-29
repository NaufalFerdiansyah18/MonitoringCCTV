<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\Recording;
use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\RecordingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RecordingTest extends TestCase
{
    use RefreshDatabase;

    private function kebunTeknis(): User
    {
        $group = TechnicalGroup::factory()->create();
        TechnicalGroupUnitCategory::create(['technical_group_id' => $group->id, 'kategori' => 'kebun']);

        return User::factory()->withTechnicalGroup($group)->create();
    }

    private function cameraOn(Unit $unit, bool $withPublic = true, string $lokasi = 'Gerbang'): Camera
    {
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'nama' => 'DVR '.$unit->nama,
            'ip_public' => $withPublic ? '203.0.113.10' : null,
            'port_public' => $withPublic ? 38003 : null,
        ]);

        return Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => $lokasi,
        ]);
    }

    private function bindRecordingManager(callable $runner): void
    {
        $this->app->instance(RecordingManager::class, new RecordingManager(null, $runner));
    }

    private function cleanRecordingFiles(string $streamKey): void
    {
        $pidsDir = storage_path('app'.DIRECTORY_SEPARATOR.'hls');
        $hlsDir = public_path('hls').DIRECTORY_SEPARATOR.$streamKey;

        @unlink($pidsDir.DIRECTORY_SEPARATOR.$streamKey.'.pid');
        foreach (glob($hlsDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($hlsDir);
        @rmdir($pidsDir);
    }

    public function test_guest_is_redirected_to_login_for_recordings_routes(): void
    {
        $this->get(route('recordings.index'))->assertRedirect(route('login'));
        $this->get(route('recordings.playlist', ['recording' => 1]))->assertRedirect(route('login'));
        $this->get(route('recordings.export', ['recording' => 1]))->assertRedirect(route('login'));
        $this->post(route('recordings.start'), ['camera_id' => 1, 'mode' => 'local'])
            ->assertRedirect(route('login'));
    }

    public function test_start_builds_recording_row_and_hls_command_without_delete_segments(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);
        $captured = [];

        $this->bindRecordingManager(function (array $cmd) use (&$captured) {
            $captured[] = $cmd;

            return 900001;
        });

        $response = $this->actingAs($admin)
            ->postJson(route('recordings.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $recordingId = $response->json('recordingId');
        $streamKey = 'rec-'.$recordingId;

        $this->assertDatabaseHas('recordings', [
            'id' => $recordingId,
            'camera_id' => $camera->id,
            'status' => 'recording',
            'stream_key' => $streamKey,
        ]);

        $this->assertSame($streamKey, $response->json('streamKey'));
        $this->assertStringNotContainsString('rtsp://', (string) $response->getContent());

        $flat = implode(' ', $captured[0]);
        $this->assertStringContainsString('-hls_list_size', $flat);
        $this->assertStringNotContainsString('delete_segments', $flat);

        $this->cleanRecordingFiles($streamKey);
    }

    public function test_start_public_mode_rejected_when_dvr_has_no_ip_public(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit, withPublic: false);

        $this->bindRecordingManager(fn (array $cmd) => 900002);

        $response = $this->actingAs($admin)
            ->postJson(route('recordings.start'), ['camera_id' => $camera->id, 'mode' => 'public'])
            ->assertOk()
            ->assertJson(['ok' => false]);

        $this->assertStringContainsString('Mode Public tidak tersedia', (string) $response->json('error'));
        $this->assertDatabaseMissing('recordings', ['camera_id' => $camera->id]);
    }

    public function test_start_reports_clear_error_when_ffmpeg_missing(): void
    {
        config(['cctv.ffmpeg_path' => null]);

        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $response = $this->actingAs($admin)
            ->postJson(route('recordings.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertOk()
            ->assertJson(['ok' => false]);

        $this->assertStringContainsString('FFmpeg tidak ditemukan', (string) $response->json('error'));
        $this->assertDatabaseHas('recordings', ['camera_id' => $camera->id, 'status' => 'failed']);
    }

    public function test_teknis_cannot_start_recording_of_out_of_category_camera(): void
    {
        $teknis = $this->kebunTeknis();
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $camera = $this->cameraOn($pks);

        $this->actingAs($teknis)
            ->postJson(route('recordings.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertForbidden();
    }

    public function test_teknis_can_start_recording_of_own_category_camera(): void
    {
        $teknis = $this->kebunTeknis();
        $kebun = Unit::factory()->create(['kategori' => 'kebun']);
        $camera = $this->cameraOn($kebun);

        $this->bindRecordingManager(fn (array $cmd) => 900003);

        $response = $this->actingAs($teknis)
            ->postJson(route('recordings.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->cleanRecordingFiles('rec-'.$response->json('recordingId'));
    }

    public function test_teknis_only_sees_recordings_of_own_category(): void
    {
        $teknis = $this->kebunTeknis();
        $kebun = Unit::factory()->create(['kategori' => 'kebun']);
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $kebunCam = $this->cameraOn($kebun, lokasi: 'Kebun Cam');
        $pksCam = $this->cameraOn($pks, lokasi: 'PKS Cam');

        Recording::factory()->create(['camera_id' => $kebunCam->id]);
        Recording::factory()->create(['camera_id' => $pksCam->id]);

        $this->actingAs($teknis)
            ->get(route('recordings.index'))
            ->assertOk()
            ->assertSee('Kebun Cam')
            ->assertDontSee('PKS Cam');
    }

    public function test_recordings_page_never_leaks_credentials_for_superadmin(): void
    {
        $admin = User::factory()->superadmin()->create();
        $kebun = Unit::factory()->create(['kategori' => 'kebun']);
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $kebunCam = $this->cameraOn($kebun, lokasi: 'Kebun Cam');
        $pksCam = $this->cameraOn($pks, lokasi: 'PKS Cam');

        $kebunCam->dvr->update(['password' => 'rahasia-rek']);
        $pksCam->dvr->update(['password' => 'rahasia-rek']);

        Recording::factory()->create(['camera_id' => $kebunCam->id]);
        Recording::factory()->create(['camera_id' => $pksCam->id]);

        $this->actingAs($admin)
            ->get(route('recordings.index'))
            ->assertOk()
            ->assertSee('Kebun Cam')
            ->assertSee('PKS Cam')
            ->assertDontSee('rahasia-rek')
            ->assertDontSee('rtsp://');
    }

    public function test_playlist_and_export_of_out_of_category_recording_are_forbidden(): void
    {
        $teknis = $this->kebunTeknis();
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $camera = $this->cameraOn($pks);
        $recording = Recording::factory()->create(['camera_id' => $camera->id]);

        $this->actingAs($teknis)
            ->get(route('recordings.playlist', $recording))
            ->assertForbidden();

        $this->actingAs($teknis)
            ->get(route('recordings.export', $recording))
            ->assertForbidden();
    }

    public function test_playlist_serves_rewritten_segment_urls(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $recording = Recording::factory()->create([
            'camera_id' => $camera->id,
            'stream_key' => 'rec-42',
        ]);

        $hlsDir = public_path('hls').DIRECTORY_SEPARATOR.'rec-42';
        @mkdir($hlsDir, 0755, true);
        @file_put_contents(
            $hlsDir.DIRECTORY_SEPARATOR.'index.m3u8',
            "#EXTM3U\n#EXTINF:2,\nsegment_000.ts\n#EXTINF:2,\nsegment_001.ts\n"
        );

        try {
            $this->actingAs($admin)
                ->get(route('recordings.playlist', $recording))
                ->assertOk()
                ->assertSee(url('/hls/rec-42/segment_000.ts'))
                ->assertSee(url('/hls/rec-42/segment_001.ts'))
                ->assertDontSee('rtsp://');
        } finally {
            @unlink($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8');
            @rmdir($hlsDir);
        }
    }

    public function test_stop_closes_recording_with_duration_and_size_and_is_idempotent(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $recording = Recording::factory()->recording()->create([
            'camera_id' => $camera->id,
            'started_at' => now()->subMinutes(5),
            'stream_key' => 'rec-7711',
        ]);

        $pidsDir = storage_path('app'.DIRECTORY_SEPARATOR.'hls');
        $hlsDir = public_path('hls').DIRECTORY_SEPARATOR.'rec-7711';
        @mkdir($pidsDir, 0755, true);
        @mkdir($hlsDir, 0755, true);
        @file_put_contents($pidsDir.DIRECTORY_SEPARATOR.'rec-7711.pid', '0');
        @file_put_contents($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8', "#EXTM3U\n");
        @file_put_contents($hlsDir.DIRECTORY_SEPARATOR.'segment_000.ts', str_repeat('x', 2048));

        try {
            $manager = app(RecordingManager::class);
            $manager->stop($recording->id);

            $recording->refresh();
            $this->assertSame('stopped', $recording->status);
            $this->assertNotNull($recording->ended_at);
            $this->assertGreaterThanOrEqual(290, $recording->duration_seconds);
            $this->assertLessThanOrEqual(310, $recording->duration_seconds);
            $this->assertGreaterThan(2048, $recording->size_bytes);

            $manager->stop($recording->id);
            $this->assertSame('stopped', $recording->refresh()->status);
        } finally {
            @unlink($pidsDir.DIRECTORY_SEPARATOR.'rec-7711.pid');
            @unlink($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8');
            @unlink($hlsDir.DIRECTORY_SEPARATOR.'segment_000.ts');
            @rmdir($hlsDir);
            @rmdir($pidsDir);
        }
    }

    public function test_status_returns_failed_for_unknown_and_recording_without_stream_key(): void
    {
        $manager = app(RecordingManager::class);

        $this->assertSame('failed', $manager->status(999999));

        $recording = Recording::factory()->recording()->create([
            'camera_id' => Camera::factory(),
            'stream_key' => null,
        ]);

        $this->assertSame('failed', $manager->status($recording->id));
    }

    public function test_recorder_command_starts_recording_for_camera_without_active_session(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $this->bindRecordingManager(fn (array $cmd) => 900004);

        Artisan::call('cctv:recorder');

        $this->assertDatabaseHas('recordings', ['camera_id' => $camera->id, 'status' => 'recording']);

        $recording = Recording::where('camera_id', $camera->id)->first();
        $this->cleanRecordingFiles('rec-'.$recording->id);
    }

    public function test_recorder_command_does_not_restart_fresh_recording(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $started = Recording::factory()->recording()->create([
            'camera_id' => $camera->id,
            'started_at' => now(),
        ]);

        $this->bindRecordingManager(fn (array $cmd) => 900005);

        Artisan::call('cctv:recorder');

        $this->assertDatabaseCount('recordings', 1);
        $this->assertDatabaseHas('recordings', ['id' => $started->id, 'status' => 'recording']);
    }
}
