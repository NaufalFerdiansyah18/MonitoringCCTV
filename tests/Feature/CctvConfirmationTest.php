<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\TechnicalGroup;
use App\Models\Unit;
use App\Models\User;
use App\Services\RtspGenerator;
use App\Services\StreamManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CctvConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superadmin()->create();
    }

    private function cameraOn(Unit $unit, int $channel = 1, ?string $kategori = null): Camera
    {
        $dvr = Dvr::factory()->for($unit, 'unit')->create(['nama' => 'DVR '.$unit->nama]);

        return Camera::factory()->for($dvr, 'dvr')->forChannel($channel)->create([
            'nama_lokasi' => 'Kamera '.$channel,
            'kategori' => $kategori,
        ]);
    }

    public function test_teknis_group_cannot_use_ro_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.technical-groups.create'))
            ->post(route('admin.technical-groups.store'), ['nama' => 'grup-ro', 'kategoris' => ['ro']])
            ->assertSessionHasErrors('kategoris.0');

        $this->assertDatabaseMissing('technical_groups', ['nama' => 'grup-ro']);

        $this->actingAs($admin)
            ->post(route('admin.technical-groups.store'), ['nama' => 'grup-bagus', 'kategoris' => ['kebun', 'pks']])
            ->assertRedirect(route('admin.technical-groups.index'));

        $group = TechnicalGroup::where('nama', 'grup-bagus')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.technical-groups.update', $group), ['nama' => 'grup-bagus', 'kategoris' => ['ro']])
            ->assertSessionHasErrors('kategoris.0');

        $group->refresh();
        $this->assertSame(['kebun', 'pks'], $group->allowedUnitCategories()->all());
    }

    public function test_group_form_hides_ro_category(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.technical-groups.create'))
            ->assertOk()
            ->assertSee('Kebun')
            ->assertSee('Pks')
            ->assertDontSee('value="ro"');
    }

    public function test_superadmin_still_has_ro_in_allowed_categories(): void
    {
        $admin = $this->admin();

        $this->assertSame(['kebun', 'pks', 'ro'], $admin->allowedUnitCategories()->all());
    }

    public function test_liveview_start_uses_grid_subtype_by_default(): void
    {
        config(['cctv.grid_subtype' => 1]);
        $unit = Unit::factory()->create(['kategori' => 'kebun']);
        $camera = $this->cameraOn($unit);

        $spy = new class extends StreamManager
        {
            public array $calls = [];

            public function start(Camera $camera, string $mode, ?int $subtype = null): array
            {
                $this->calls[] = ['mode' => $mode, 'subtype' => $subtype];

                return ['ok' => true, 'streamKey' => 'cam-'.$camera->id];
            }
        };
        $this->app->instance(StreamManager::class, $spy);

        $this->actingAs($this->admin())
            ->postJson(route('liveview.start'), ['camera_id' => $camera->id, 'mode' => 'local'])
            ->assertOk();

        $this->assertSame([['mode' => 'local', 'subtype' => 1]], $spy->calls);

        $this->actingAs($this->admin())
            ->postJson(route('liveview.start'), ['camera_id' => $camera->id, 'mode' => 'local', 'subtype' => 0])
            ->assertOk();

        $this->assertSame(0, $spy->calls[1]['subtype']);
    }

    public function test_liveview_start_rejects_invalid_subtype(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $this->actingAs($this->admin())
            ->postJson(route('liveview.start'), ['camera_id' => $camera->id, 'mode' => 'local', 'subtype' => 2])
            ->assertUnprocessable();
    }

    public function test_stream_manager_defaults_subtype_to_config_when_null(): void
    {
        config(['cctv.ffmpeg_path' => null]);
        config(['cctv.subtype' => 0]);
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);

        $probe = new class extends StreamManager
        {
            public array $subtypes = [];

            protected function rtspUrl(Camera $camera, int $subtype, string $mode): ?string
            {
                $this->subtypes[] = $subtype;

                return parent::rtspUrl($camera, $subtype, $mode);
            }
        };

        $probe->start($camera, 'local');
        $probe->start($camera, 'local', 1);

        $this->assertSame([0, 1], $probe->subtypes);
    }

    public function test_generator_default_subtype_follows_config(): void
    {
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create(['ip_local' => '192.168.1.10', 'port_local' => 554]);
        $generator = app(RtspGenerator::class);

        config(['cctv.subtype' => 0]);
        $this->assertStringContainsString('&subtype=0', $generator->generate($dvr, 1));

        config(['cctv.subtype' => 1]);
        $this->assertStringContainsString('&subtype=1', $generator->generate($dvr, 1));
    }

    public function test_dashboard_filters_cameras_by_kategori_and_supports_mixed_dvr(): void
    {
        $unit = Unit::factory()->create(['kategori' => 'pks', 'nama' => 'Pabrik Satu']);
        $dvr = Dvr::factory()->for($unit, 'unit')->create(['nama' => 'DVR Campur']);

        Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => 'Kamera Satu',
            'kategori' => 'pks',
        ]);
        Camera::factory()->for($dvr, 'dvr')->forChannel(2)->create([
            'nama_lokasi' => 'Kamera Dua',
            'kategori' => 'bioglas',
        ]);

        $this->actingAs($this->admin())
            ->get(route('dashboard', ['unit' => $unit->id]))
            ->assertOk()
            ->assertSee('Kamera Satu')
            ->assertSee('Kamera Dua');

        $this->actingAs($this->admin())
            ->get(route('dashboard', ['unit' => $unit->id, 'kategori' => 'pks']))
            ->assertOk()
            ->assertSee('Kamera Satu')
            ->assertDontSee('Kamera Dua');

        $this->actingAs($this->admin())
            ->get(route('dashboard', ['unit' => $unit->id, 'kategori' => 'bioglas']))
            ->assertOk()
            ->assertSee('Kamera Dua')
            ->assertDontSee('Kamera Satu');

        $this->actingAs($this->admin())
            ->get(route('dashboard', ['unit' => $unit->id, 'kategori' => 'tidak-ada']))
            ->assertOk()
            ->assertDontSee('Kamera Satu')
            ->assertDontSee('Kamera Dua');
    }

    public function test_camera_crud_accepts_different_kategori_on_same_dvr(): void
    {
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.dvrs.cameras.store', $dvr), [
                'channel' => 2,
                'nama_lokasi' => 'Kamera Bioglas',
                'kategori' => 'bioglas',
            ])
            ->assertRedirect(route('admin.dvrs.cameras.index', $dvr));

        $this->assertDatabaseHas('cameras', [
            'dvr_id' => $dvr->id,
            'channel' => 2,
            'kategori' => 'bioglas',
        ]);
    }
}
