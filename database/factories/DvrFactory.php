<?php

namespace Database\Factories;

use App\Models\Dvr;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dvr>
 */
class DvrFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasPublic = fake()->boolean(50);

        return [
            'unit_id' => Unit::factory(),
            'nama' => 'DVR '.fake()->numberBetween(1, 99),
            'ip_local' => fake()->ipv4(),
            'port_local' => 554,
            'ip_public' => $hasPublic ? fake()->ipv4() : null,
            'port_public' => $hasPublic ? fake()->numberBetween(554, 5999) : null,
            'username' => 'admin',
            'password' => 'password',
        ];
    }
}
