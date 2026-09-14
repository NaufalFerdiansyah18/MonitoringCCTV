<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Dvr;
use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CctvModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_has_many_dvrs_and_dvr_has_many_cameras(): void
    {
        $unit = Unit::factory()->create();
        $dvr = Dvr::factory()->for($unit, 'unit')->create();

        Camera::factory()->for($dvr, 'dvr')->forChannel(1)->create();
        Camera::factory()->for($dvr, 'dvr')->forChannel(2)->create();

        $this->assertSame($unit->id, $dvr->unit->id);
        $this->assertTrue($unit->dvrs->contains($dvr));
        $this->assertCount(2, $dvr->cameras);
        $this->assertSame($dvr->id, $dvr->cameras->first()->dvr->id);
    }

    public function test_duplicate_channel_for_same_dvr_is_rejected(): void
    {
        $dvr = Dvr::factory()->create();

        Camera::factory()->for($dvr, 'dvr')->forChannel(5)->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Camera::factory()->for($dvr, 'dvr')->forChannel(5)->create();
    }

    public function test_same_channel_can_be_used_by_different_dvrs(): void
    {
        $dvrA = Dvr::factory()->create();
        $dvrB = Dvr::factory()->create();

        Camera::factory()->for($dvrA, 'dvr')->forChannel(3)->create();
        Camera::factory()->for($dvrB, 'dvr')->forChannel(3)->create();

        $this->assertSame(3, $dvrA->cameras()->findOrFail(1)->channel);
        $this->assertSame(3, $dvrB->cameras()->findOrFail(2)->channel);
    }

    public function test_channel_outside_one_to_sixteen_is_rejected(): void
    {
        $dvr = Dvr::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        Camera::factory()->for($dvr, 'dvr')->forChannel(0)->create();
    }

    public function test_dvr_password_is_encrypted_and_never_serialized(): void
    {
        Dvr::factory()->create(['password' => 'rahasia123']);

        $raw = Dvr::query()->firstOrFail();

        $this->assertNotSame('rahasia123', $raw->getAttributes()['password']);
        $this->assertSame('rahasia123', $raw->getPlainPassword());

        $serialized = json_encode($raw->toArray());
        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('rahasia123', $serialized);
    }

    public function test_superadmin_allows_all_unit_categories(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->assertTrue($superadmin->isSuperadmin());
        $this->assertEqualsCanonicalizing(Unit::KATEGORI, $superadmin->allowedUnitCategories()->all());
    }

    public function test_teknis_allowed_categories_follow_assigned_group(): void
    {
        $group = TechnicalGroup::factory()->create();
        TechnicalGroupUnitCategory::create(['technical_group_id' => $group->id, 'kategori' => 'pks']);

        $teknis = User::factory()->withTechnicalGroup($group)->create();

        $this->assertFalse($teknis->isSuperadmin());
        $this->assertSame(['pks'], $teknis->allowedUnitCategories()->all());
    }

    public function test_teknis_without_group_has_no_access(): void
    {
        $teknis = User::factory()->create();

        $this->assertSame([], $teknis->allowedUnitCategories()->all());
    }
}
