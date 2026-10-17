<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subDays(fake()->numberBetween(0, 60))->subMinutes(fake()->numberBetween(0, 600));
        $endedAt = $startedAt->addMinutes(fake()->numberBetween(10, 90));

        return [
            'user_id' => User::factory(),
            'vehicle_id' => fn (array $attributes): int => Vehicle::factory()->create(['user_id' => $attributes['user_id']])->id,
            'uuid' => (string) Str::uuid(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'distance_km' => fake()->randomFloat(1, 5, 120),
            'health_score' => fake()->numberBetween(72, 98),
            'components' => [
                'dtc' => 1.0,
                'misfire' => fake()->randomFloat(2, 0.8, 1),
                'trims' => fake()->randomElement([1.0, 0.7]),
                'cooling' => 1.0,
                'electric' => fake()->randomFloat(2, 0.7, 1),
                'readiness' => 1.0,
                'trends' => fake()->randomFloat(2, 0.7, 1),
            ],
            'metrics' => [
                'avg_stft' => fake()->randomFloat(1, -4, 4),
                'avg_ltft' => fake()->randomFloat(1, -6, 6),
                'max_ect' => fake()->randomFloat(1, 88, 102),
                'battery_v' => fake()->randomFloat(2, 12.2, 12.8),
                'crank_v' => fake()->randomFloat(2, 9.6, 10.8),
                'misfires' => fake()->numberBetween(0, 3),
                'readiness_incomplete' => 0,
            ],
            'created_at' => $endedAt,
            'updated_at' => $endedAt,
            'synced_at' => $endedAt,
        ];
    }
}
