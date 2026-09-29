<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\Camera;
use App\Models\Dvr;
use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlarmTest extends TestCase
{
    use RefreshDatabase;

    private function kebunTeknis(): User
    {
        $group = TechnicalGroup::factory()->create();
        TechnicalGroupUnitCategory::create(['technical_group_id' => $group->id, 'kategori' => 'kebun']);

        return User::factory()->withTechnicalGroup($group)->create();
    }

    private function cameraOn(Unit $unit, string $lokasi = 'Gerbang'): Camera
    {
        $dvr = Dvr::factory()->for($unit, 'unit')->create([
            'nama' => 'DVR '.$unit->nama,
        ]);

        return Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create([
            'nama_lokasi' => $lokasi,
        ]);
    }

    public function test_guest_is_redirected_to_login_for_alarm_routes(): void
    {
        $this->get(route('admin.alarms.index'))->assertRedirect(route('login'));
        $this->post(route('alarms.store'), ['camera_id' => 1])->assertRedirect(route('login'));
    }

    public function test_alarm_belongs_to_camera(): void
    {
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);
        $alarm = Alarm::factory()->create(['camera_id' => $camera->id]);

        $this->assertInstanceOf(Camera::class, $alarm->camera);
        $this->assertSame($camera->id, $alarm->camera->id);
    }

    public function test_superadmin_sees_all_alarms_without_credential_leaks(): void
    {
        $admin = User::factory()->superadmin()->create();
        $kebun = Unit::factory()->create(['kategori' => 'kebun']);
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $kebunCam = $this->cameraOn($kebun, 'Alarm Kebun');
        $pksCam = $this->cameraOn($pks, 'Alarm PKS');

        $kebunCam->dvr->update(['password' => 'rahasia-alarm']);
        $pksCam->dvr->update(['password' => 'rahasia-alarm']);

        Alarm::factory()->create(['camera_id' => $kebunCam->id, 'type' => 'manual']);
        Alarm::factory()->offline()->create(['camera_id' => $pksCam->id]);

        $this->actingAs($admin)
            ->get(route('admin.alarms.index'))
            ->assertOk()
            ->assertSee('Alarm Kebun')
            ->assertSee('Alarm PKS')
            ->assertDontSee('rahasia-alarm')
            ->assertDontSee('rtsp://');
    }

    public function test_teknis_cannot_open_admin_alarm_page(): void
    {
        $teknis = $this->kebunTeknis();

        $this->actingAs($teknis)
            ->get(route('admin.alarms.index'))
            ->assertForbidden();
    }

    public function test_teknis_cannot_create_manual_alarm_for_out_of_category_camera(): void
    {
        $teknis = $this->kebunTeknis();
        $pks = Unit::factory()->create(['kategori' => 'pks']);
        $camera = $this->cameraOn($pks);

        $this->actingAs($teknis)
            ->post(route('alarms.store'), ['camera_id' => $camera->id, 'message' => 'Coba'])
            ->assertForbidden();
    }

    public function test_teknis_can_create_manual_alarm_for_own_category_camera(): void
    {
        $teknis = $this->kebunTeknis();
        $kebun = Unit::factory()->create(['kategori' => 'kebun']);
        $camera = $this->cameraOn($kebun);

        $this->from(route('dashboard'))
            ->actingAs($teknis)
            ->post(route('alarms.store'), ['camera_id' => $camera->id, 'message' => 'Kebakaran'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('alarms', [
            'camera_id' => $camera->id,
            'type' => 'manual',
            'message' => 'Kebakaran',
        ]);
    }

    public function test_seen_marks_alarm_as_read(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $camera = $this->cameraOn($unit);
        $alarm = Alarm::factory()->create(['camera_id' => $camera->id]);

        $this->from(route('admin.alarms.index'))
            ->actingAs($admin)
            ->post(route('admin.alarms.seen', $alarm))
            ->assertRedirect(route('admin.alarms.index'));

        $this->assertNotNull($alarm->fresh()->seen_at);
    }
}
