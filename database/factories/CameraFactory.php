<?php

namespace Database\Factories;

use App\Models\Camera;
use App\Models\Dvr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Camera>
 */
class CameraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dvr_id' => Dvr::factory(),
            'channel' => fake()->randomElement(range(Camera::CHANNEL_MIN, Camera::CHANNEL_MAX)),
            'nama_lokasi' => fake()->randomElement(['Crh Timbangan', 'Rebusan', 'Gudang', 'Akses Utama']),
            'kategori' => fake()->optional(0.6)->randomElement(['PKS', 'Bioglas', 'Timbangan', 'Rebusan']),
        ];
    }

    public function forChannel(int $channel): static
    {
        return $this->state(fn (array $attributes) => ['channel' => $channel]);
    }
}
