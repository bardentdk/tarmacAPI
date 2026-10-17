<?php

namespace Database\Factories;

use App\Models\Run;
use App\Models\SharedRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SharedRun>
 */
class SharedRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'run_id' => Run::factory(),
            'token' => Str::random(40),
            'recipient' => null,
            'views' => 0,
            'revoked_at' => null,
        ];
    }

    /**
     * A revoked link.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => ['revoked_at' => now()]);
    }
}
