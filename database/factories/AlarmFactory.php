<?php

namespace Database\Factories;

use App\Models\Alarm;
use App\Models\Camera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alarm>
 */
class AlarmFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'camera_id' => Camera::factory(),
            'type' => 'manual',
            'message' => 'Alarm uji',
            'started_at' => now(),
            'ended_at' => null,
            'seen_at' => null,
        ];
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'offline',
        ]);
    }
}
