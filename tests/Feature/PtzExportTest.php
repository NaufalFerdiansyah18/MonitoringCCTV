<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\Recording;
use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\PtzService;
use App\Services\RecordingExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class PtzExportTest extends TestCase
{
    use RefreshDatabase;

    private function kebunTeknis(): User
    {
        $group = TechnicalGroup::factory()->create();
        TechnicalGroupUnitCategory::create(['technical_group_id' => $group->id, 'kategori' => 'kebun']);

        return User::factory()->withTechnicalGroup($group)->create();
    }

    private function cameraOn(Unit $unit, string $lokasi = 'Gerbang', bool $canPtz = false): Camera
    {
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'nama' => 'DVR '.$unit->nama,
        ]);

        return Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => $lokasi,
            'can_ptz' => $canPtz,
        ]);
    }

    private function bindPtzRequester(callable $requester): void
    {
        $this->app->instance(PtzService::class, new PtzService($requester));
    }

    public function test_guest_is_redirected_for_ptz_and_export(): void
    {
        $this->post(route('ptz.send', ['camera' => 1]), ['action' => 'start', 'code' => 'Up'])
            ->assertRedirect(route('login'));

        $this->get(route('recordings.export', ['recording' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_teknis_cannot_send_ptz_to_out_of_category_camera(): void
    {
        $teknis = $this->kebunTeknis();
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $camera = $this->cameraOn($pks, canPtz: true);

        $this->actingAs($teknis)
            ->postJson(route('ptz.send', $camera), ['action' => 'start', 'code' => 'Up'])
            ->assertForbidden();
    }

    public function test_ptz_rejected_when_camera_not_ptz_capable(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit, canPtz: false);

        $this->actingAs($admin)
            ->postJson(route('ptz.send', $camera), ['action' => 'start', 'code' => 'Up'])
            ->assertStatus(422);
    }

    public function test_ptz_send_builds_correct_cgi_url_and_never_leaks_credentials(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'ip_local' => '192.168.1.50',
            'port_local' => 554,
            'username' => 'admin',
            'password' => 'rahasia-p',
        ]);
        $camera = Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => 'PTZ Gate',
            'can_ptz' => true,
        ]);

        $captured = [];
        $this->bindPtzRequester(function (string $url, string $username, string $password) use (&$captured): bool {
            $captured = ['url' => $url, 'user' => $username, 'pass' => $password];

            return true;
        });

        $response = $this->actingAs($admin)
            ->postJson(route('ptz.send', $camera), ['action' => 'start', 'code' => 'Up'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(
            'http://192.168.1.50:554/cgi-bin/ptz_control.cgi?action=start&code=Up&channel=1',
            $captured['url']
        );
        $this->assertSame('admin', $captured['user']);
        $this->assertSame('rahasia-p', $captured['pass']);
        $this->assertStringNotContainsString('rahasia-p', (string) $response->getContent());
        $this->assertStringNotContainsString('rtsp://', (string) $response->getContent());
        $this->assertStringNotContainsString('Basic ', (string) $response->getContent());
    }

    public function test_ptz_stop_normalizes_code_to_stop(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'ip_local' => '192.168.1.50',
            'port_local' => 554,
        ]);
        $camera = Camera::factory()->for($dvr, 'dvr')->forChannel(2)->create([
            'can_ptz' => true,
        ]);

        $captured = [];
        $this->bindPtzRequester(function (string $url) use (&$captured): bool {
            $captured['url'] = $url;

            return true;
        });

        $this->actingAs($admin)
            ->postJson(route('ptz.send', $camera), ['action' => 'stop', 'code' => 'Up'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertStringEndsWith('action=stop&code=Stop&channel=2', $captured['url']);
    }

    public function test_export_ffmpeg_missing_returns_clear_error(): void
    {
        config(['cctv.ffmpeg_path' => null]);

        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);
        $recording = Recording::factory()->create([
            'camera_id' => $camera->id,
            'stream_key' => 'rec-99',
        ]);

        $hlsDir = public_path('hls').DIRECTORY_SEPARATOR.'rec-99';
        @mkdir($hlsDir, 0755, true);
        @file_put_contents($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8', "#EXTM3U\n");

        try {
            $this->from(route('recordings.index'))
                ->actingAs($admin)
                ->get(route('recordings.export', $recording))
                ->assertRedirect(route('recordings.index'))
                ->assertSessionHasErrors('export');

            $this->assertStringContainsString('FFmpeg', (string) session('errors')->first('export'));
        } finally {
            @unlink($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8');
            @rmdir($hlsDir);
        }
    }

    public function test_export_runs_remux_with_stub_and_returns_mp4(): void
    {
        $captured = [];
        $runner = function (array $cmd) use (&$captured): bool {
            $captured[] = $cmd;
            file_put_contents(end($cmd), "\x00\x00\x00\x18ftypmp42");

            return true;
        };
        $exporter = new RecordingExporter($runner);

        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);
        $recording = Recording::factory()->create([
            'camera_id' => $camera->id,
            'stream_key' => 'rec-88',
        ]);

        $hlsDir = public_path('hls').DIRECTORY_SEPARATOR.'rec-88';
        @mkdir($hlsDir, 0755, true);
        @file_put_contents($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8', "#EXTM3U\n");

        $response = null;

        try {
            $response = $exporter->export($recording);

            $this->assertInstanceOf(BinaryFileResponse::class, $response);
            $this->assertCount(1, $captured);

            $flat = implode(' ', $captured[0]);
            $this->assertStringContainsString('-c copy', $flat);
            $this->assertStringContainsString('aac_adtstoasc', $flat);
            $this->assertSame('export.mp4', basename((string) $captured[0][count($captured[0]) - 1]));
            $this->assertStringContainsString(
                storage_path('app'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'export'),
                $response->getFile()->getPath()
            );
            $this->assertStringContainsString('ftypmp42', file_get_contents($response->getFile()->getPathname()));
        } finally {
            $file = $response?->getFile();
            if ($file !== null) {
                @unlink($file->getPathname());
                @rmdir($file->getPath());
            }
            @unlink($hlsDir.DIRECTORY_SEPARATOR.'index.m3u8');
            @rmdir($hlsDir);
        }
    }
}
