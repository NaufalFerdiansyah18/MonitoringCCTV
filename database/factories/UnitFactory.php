<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->bothify('U##')),
            'nama' => 'Unit '.fake()->numberBetween(1, 99),
            'kategori' => fake()->randomElement(Unit::KATEGORI),
        ];
    }
}
