<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_masterdata_pages(): void
    {
        $this->get(route('admin.units.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dvrs.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dvrs.cameras.index', Dvr::factory()->create()))->assertRedirect(route('login'));
    }

    public function test_teknis_cannot_access_masterdata_pages(): void
    {
        $teknis = User::factory()->create();
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create();

        $this->actingAs($teknis)
            ->get(route('admin.units.index'))
            ->assertForbidden();
        $this->actingAs($teknis)
            ->get(route('admin.dvrs.index'))
            ->assertForbidden();
        $this->actingAs($teknis)
            ->get(route('admin.dvrs.cameras.index', $dvr))
            ->assertForbidden();
    }

    public function test_superadmin_can_access_masterdata_pages(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create();

        $this->actingAs($admin)
            ->get(route('admin.units.index'))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.dvrs.index'))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.dvrs.cameras.index', $dvr))
            ->assertOk();
    }

    public function test_superadmin_can_create_and_update_unit(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.units.store'), [
                'kode' => 'U1',
                'nama' => 'Unit Satu',
                'kategori' => 'kebun',
            ])
            ->assertRedirect(route('admin.units.index'));

        $unit = Unit::where('kode', 'U1')->firstOrFail();
        $this->assertSame('Unit Satu', $unit->nama);

        $this->actingAs($admin)
            ->put(route('admin.units.update', $unit), [
                'kode' => 'U1',
                'nama' => 'Unit Satu Baru',
                'kategori' => 'pks',
            ])
            ->assertRedirect(route('admin.units.index'));

        $unit->refresh();
        $this->assertSame('Unit Satu Baru', $unit->nama);
        $this->assertSame('pks', $unit->kategori);
    }

    public function test_unit_validation_rejects_duplicate_kode_and_bad_kategori(): void
    {
        $admin = User::factory()->superadmin()->create();
        Unit::factory()->create(['kode' => 'U1']);

        $this->actingAs($admin)
            ->post(route('admin.units.store'), [
                'kode' => 'U1',
                'nama' => 'Unit Dua',
                'kategori' => 'kebun',
            ])
            ->assertSessionHasErrors('kode');

        $this->actingAs($admin)
            ->post(route('admin.units.store'), [
                'kode' => 'U2',
                'nama' => 'Unit Dua',
                'kategori' => 'lainnya',
            ])
            ->assertSessionHasErrors('kategori');
    }

    public function test_deleting_unit_cascades_dvrs_and_cameras(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create();
        $camera = Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create();

        $this->actingAs($admin)
            ->delete(route('admin.units.destroy', $unit))
            ->assertRedirect(route('admin.units.index'));

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
        $this->assertDatabaseMissing('dvrs', ['id' => $dvr->id]);
        $this->assertDatabaseMissing('cameras', ['id' => $camera->id]);
    }

    public function test_superadmin_can_create_dvr_with_encrypted_password(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.dvrs.store'), [
                'unit_id' => $unit->id,
                'nama' => 'DVR Gerbang',
                'ip_local' => '192.168.1.10',
                'port_local' => 554,
                'ip_public' => '203.0.113.5',
                'port_public' => 8054,
                'username' => 'admin',
                'password' => 'rahasia123',
            ])
            ->assertRedirect(route('admin.dvrs.index'));

        $dvr = Dvr::where('nama', 'DVR Gerbang')->firstOrFail();
        $this->assertNotSame('rahasia123', $dvr->getAttributes()['password']);
        $this->assertSame('rahasia123', $dvr->getPlainPassword());
    }

    public function test_dvr_update_without_password_keeps_existing_password(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create(['password' => 'semula123']);

        $this->actingAs($admin)
            ->put(route('admin.dvrs.update', $dvr), [
                'unit_id' => $unit->id,
                'nama' => 'DVR Gerbang Edit',
                'ip_local' => '192.168.1.11',
                'port_local' => 554,
                'ip_public' => '',
                'port_public' => '',
                'username' => 'admin',
                'password' => '',
            ])
            ->assertRedirect(route('admin.dvrs.index'));

        $dvr->refresh();
        $this->assertSame('DVR Gerbang Edit', $dvr->nama);
        $this->assertNull($dvr->ip_public);
        $this->assertNull($dvr->port_public);
        $this->assertSame('semula123', $dvr->getPlainPassword());
    }

    public function test_dvr_requires_password_on_create_and_validates_ip(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.dvrs.store'), [
                'unit_id' => $unit->id,
                'nama' => 'DVR X',
                'ip_local' => 'bukan-ip',
                'port_local' => 554,
                'username' => 'admin',
                'password' => '',
            ])
            ->assertSessionHasErrors(['ip_local', 'password']);
    }

    public function test_dvr_listing_never_exposes_plain_password(): void
    {
        $admin = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        Dvr::factory()->for($unit, 'unit')->create(['password' => 'rahasia-super', 'nama' => 'DVR Rahasia']);

        $this->actingAs($admin)
            ->get(route('admin.dvrs.index'))
            ->assertOk()
            ->assertSee('DVR Rahasia')
            ->assertDontSee('rahasia-super');
    }

    public function test_superadmin_can_create_and_update_camera(): void
    {
        $admin = User::factory()->superadmin()->create();
        $dvr = Dvr::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.dvrs.cameras.store', $dvr), [
                'channel' => 3,
                'nama_lokasi' => 'Crh Timbangan',
                'kategori' => 'Timbangan',
            ])
            ->assertRedirect(route('admin.dvrs.cameras.index', $dvr));

        $camera = $dvr->cameras()->where('channel', 3)->firstOrFail();
        $this->assertSame('Crh Timbangan', $camera->nama_lokasi);

        $this->actingAs($admin)
            ->put(route('admin.dvrs.cameras.update', [$dvr, $camera]), [
                'channel' => 4,
                'nama_lokasi' => 'Gudang',
                'kategori' => '',
            ])
            ->assertRedirect(route('admin.dvrs.cameras.index', $dvr));

        $camera->refresh();
        $this->assertSame(4, $camera->channel);
        $this->assertSame('Gudang', $camera->nama_lokasi);
        $this->assertNull($camera->kategori);
    }

    public function test_duplicate_channel_for_same_dvr_is_rejected_by_validation(): void
    {
        $admin = User::factory()->superadmin()->create();
        $dvr = Dvr::factory()->create();
        Camera::factory()->for($dvr, 'dvr')->forChannel(2)->create();

        $this->actingAs($admin)
            ->post(route('admin.dvrs.cameras.store', $dvr), [
                'channel' => 2,
                'nama_lokasi' => 'Kamera Baru',
                'kategori' => '',
            ])
            ->assertSessionHasErrors('channel');

        $this->actingAs($admin)
            ->post(route('admin.dvrs.cameras.store', Dvr::factory()->create()), [
                'channel' => 2,
                'nama_lokasi' => 'Kamera DVR Lain',
                'kategori' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('cameras', 2);
    }

    public function test_camera_channel_out_of_range_is_rejected(): void
    {
        $admin = User::factory()->superadmin()->create();
        $dvr = Dvr::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.dvrs.cameras.store', $dvr), [
                'channel' => 17,
                'nama_lokasi' => 'Kamera 17',
                'kategori' => '',
            ])
            ->assertSessionHasErrors('channel');
    }

    public function test_camera_of_other_dvr_cannot_be_edited_or_deleted(): void
    {
        $admin = User::factory()->superadmin()->create();
        $dvrA = Dvr::factory()->create();
        $dvrB = Dvr::factory()->create();
        $camera = Camera::factory()->for($dvrA, 'dvr')->forChannel(1)->create();

        $this->actingAs($admin)
            ->get(route('admin.dvrs.cameras.edit', [$dvrB, $camera]))
            ->assertNotFound();
        $this->actingAs($admin)
            ->put(route('admin.dvrs.cameras.update', [$dvrB, $camera]), [
                'channel' => 1,
                'nama_lokasi' => 'Hack',
                'kategori' => '',
            ])
            ->assertNotFound();
        $this->actingAs($admin)
            ->delete(route('admin.dvrs.cameras.destroy', [$dvrB, $camera]))
            ->assertNotFound();

        $this->assertDatabaseHas('cameras', ['id' => $camera->id]);
    }
}
