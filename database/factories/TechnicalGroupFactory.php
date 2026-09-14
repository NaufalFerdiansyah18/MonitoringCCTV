<?php

namespace Database\Factories;

use App\Models\TechnicalGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicalGroup>
 */
class TechnicalGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->randomElement(['tekpol', 'tanaman', 'listrik', 'mekanik']),
        ];
    }
}
