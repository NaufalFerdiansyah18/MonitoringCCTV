<?php

namespace Database\Factories;

use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recording>
 */
class RecordingFactory extends Factory
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
            'stream_key' => null,
            'started_at' => now()->subMinutes(10),
            'ended_at' => now()->subMinutes(5),
            'duration_seconds' => 300,
            'size_bytes' => 1048576,
            'format' => 'hls',
            'status' => 'stopped',
        ];
    }

    public function recording(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'recording',
            'ended_at' => null,
            'duration_seconds' => null,
            'size_bytes' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'ended_at' => now(),
        ]);
    }
}
