<?php

namespace Database\Factories;

use App\Models\DtcCause;
use App\Models\DtcDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DtcCause>
 */
class DtcCauseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costMin = fake()->numberBetween(20, 300);

        return [
            'dtc_definition_id' => DtcDefinition::factory(),
            'label' => fake()->sentence(3),
            'probability' => fake()->randomFloat(2, 0.1, 0.7),
            'part' => fake()->word(),
            'difficulty' => fake()->randomElement(['facile', 'moyenne', 'difficile']),
            'cost_min' => $costMin,
            'cost_max' => $costMin + fake()->numberBetween(50, 500),
            'position' => 0,
        ];
    }
}
