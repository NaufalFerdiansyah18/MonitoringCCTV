<?php

namespace Tests\Feature;

use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visits_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_superadmin_can_login_and_logout(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password-salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_on_protected_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.technical-groups.index'))->assertRedirect(route('login'));
    }

    public function test_teknis_cannot_access_admin_pages(): void
    {
        $teknis = User::factory()->create();

        $this->actingAs($teknis)
            ->get(route('admin.users.index'))
            ->assertForbidden();
        $this->actingAs($teknis)
            ->get(route('admin.technical-groups.create'))
            ->assertForbidden();
        $this->actingAs($teknis)
            ->post(route('admin.users.store'), [
                'name' => 'Siapapun',
                'email' => 'x@example.com',
                'password' => 'rahasia123',
                'role' => 'teknis',
            ])
            ->assertForbidden();
    }

    public function test_superadmin_can_access_admin_pages(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($admin->name);
        $this->actingAs($admin)
            ->get(route('admin.technical-groups.index'))
            ->assertOk();
    }

    public function test_superadmin_can_create_teknis_user(): void
    {
        $admin = User::factory()->superadmin()->create();
        $group = TechnicalGroup::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Teknisi Kebun',
                'email' => 'teknisi@example.com',
                'password' => 'rahasia123',
                'role' => 'teknis',
                'technical_group_id' => $group->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'teknisi@example.com',
            'role' => 'teknis',
            'technical_group_id' => $group->id,
        ]);
    }

    public function test_user_creation_validates_fields(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => 'bukan-email',
                'password' => '123',
                'role' => 'teknis',
            ])
            ->assertSessionHasErrors(['name', 'email', 'password', 'technical_group_id']);
    }

    public function test_superadmin_can_edit_user_and_self_role_never_downgraded(): void
    {
        $admin = User::factory()->superadmin()->create();
        $group = TechnicalGroup::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Superadmin Baru',
                'email' => $admin->email,
                'password' => '',
                'role' => 'teknis',
                'technical_group_id' => $group->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'superadmin',
            'technical_group_id' => null,
        ]);
    }

    public function test_superadmin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_superadmin_can_delete_other_user(): void
    {
        $admin = User::factory()->superadmin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_group_crud_and_delete_nulls_user_group(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.technical-groups.store'), [
                'nama' => 'tanaman',
                'kategoris' => ['kebun', 'ro'],
            ])
            ->assertRedirect(route('admin.technical-groups.index'));

        $group = TechnicalGroup::where('nama', 'tanaman')->firstOrFail();
        $this->assertSame(['kebun', 'ro'], $group->allowedUnitCategories()->all());

        $this->actingAs($admin)
            ->put(route('admin.technical-groups.update', $group), [
                'nama' => 'tanaman',
                'kategoris' => ['pks'],
            ])
            ->assertRedirect(route('admin.technical-groups.index'));

        $group->refresh();
        $this->assertSame(['pks'], $group->allowedUnitCategories()->all());

        $user = User::factory()->withTechnicalGroup($group)->create();

        $this->actingAs($admin)
            ->delete(route('admin.technical-groups.destroy', $group))
            ->assertRedirect(route('admin.technical-groups.index'));

        $this->assertDatabaseMissing('technical_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'technical_group_id' => null,
        ]);
    }

    public function test_teknis_dashboard_shows_only_allowed_unit_categories(): void
    {
        $kebun = Unit::factory()->create(['kategori' => 'kebun', 'nama' => 'Kebun Satu']);
        Unit::factory()->create(['kategori' => 'pks', 'nama' => 'Pabrik Satu']);
        Unit::factory()->create(['kategori' => 'ro', 'nama' => 'RO Satu']);

        $group = TechnicalGroup::factory()->create();
        TechnicalGroupUnitCategory::create(['technical_group_id' => $group->id, 'kategori' => 'kebun']);
        $teknis = User::factory()->withTechnicalGroup($group)->create();

        $this->actingAs($teknis)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kebun Satu')
            ->assertDontSee('Pabrik Satu')
            ->assertDontSee('RO Satu');
    }

    public function test_superadmin_dashboard_shows_all_units(): void
    {
        Unit::factory()->state(['kategori' => 'kebun', 'nama' => 'Kebun Satu'])->create();
        Unit::factory()->state(['kategori' => 'pks', 'nama' => 'Pabrik Satu'])->create();
        Unit::factory()->state(['kategori' => 'ro', 'nama' => 'RO Satu'])->create();

        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kebun Satu')
            ->assertSee('Pabrik Satu')
            ->assertSee('RO Satu');
    }
}