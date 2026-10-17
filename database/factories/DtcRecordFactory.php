<?php

namespace Database\Factories;

use App\Enums\DtcKind;
use App\Models\DtcRecord;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DtcRecord>
 */
class DtcRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $readAt = now()->subDays(fake()->numberBetween(0, 60));

        return [
            'user_id' => User::factory(),
            'vehicle_id' => fn (array $attributes): int => Vehicle::factory()->create(['user_id' => $attributes['user_id']])->id,
            'uuid' => (string) Str::uuid(),
            'code' => fake()->randomElement(['P0171', 'P0300', 'P0420', 'P0299', 'P0128', 'P0302']),
            'kind' => fake()->randomElement(DtcKind::values()),
            'freeze_frame' => ['rpm' => 2400, 'load_pct' => 62, 'ect_c' => 91, 'speed_kmh' => 74],
            'read_at' => $readAt,
            'cleared_at' => null,
            'created_at' => $readAt,
            'updated_at' => $readAt,
            'synced_at' => $readAt,
        ];
    }

    /**
     * A code that was cleared after being read.
     */
    public function cleared(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cleared_at' => $attributes['read_at']->addHour(),
            'updated_at' => $attributes['read_at']->addHour(),
        ]);
    }
}
