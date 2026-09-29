<?php

namespace Database\Factories;

use App\Models\TechnicalGroup;
use App\Models\TechnicalGroupUnitCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicalGroupUnitCategory>
 */
class TechnicalGroupUnitCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'technical_group_id' => TechnicalGroup::factory(),
            'kategori' => fake()->randomElement(Unit::KATEGORI),
        ];
    }

    public function forKategori(string $kategori): static
    {
        return $this->state(fn (array $attributes) => ['kategori' => $kategori]);
    }
}
