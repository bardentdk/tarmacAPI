<?php

namespace Database\Factories;

use App\Enums\Drivetrain;
use App\Enums\Fuel;
use App\Enums\Gearbox;
use App\Enums\TuningStage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $car = fake()->randomElement([
            ['Volkswagen', 'Golf GTI', 'EA888 2.0 TSI'],
            ['Renault', 'Mégane RS', 'M5P 1.8 TCe'],
            ['Peugeot', '208 GTi', 'EP6FDTR 1.6 THP'],
            ['BMW', 'M140i', 'B58 3.0'],
            ['Audi', 'S3', 'EA888 2.0 TFSI'],
        ]);
        $updatedAt = now()->subDays(fake()->numberBetween(0, 30));

        return [
            'user_id' => User::factory(),
            'uuid' => (string) Str::uuid(),
            'name' => $car[1],
            'make' => $car[0],
            'model' => $car[1],
            'year' => fake()->numberBetween(2012, 2024),
            'engine' => $car[2],
            'fuel' => Fuel::Petrol->value,
            'gearbox' => fake()->randomElement(Gearbox::values()),
            'drivetrain' => fake()->randomElement([Drivetrain::Fwd->value, Drivetrain::Awd->value]),
            'mass_kg' => fake()->numberBetween(1150, 1650),
            'tire_size' => '225/40R18',
            'wheel_radius_m' => 0.317,
            'cda' => 0.68,
            'tuning_stage' => fake()->randomElement([TuningStage::Stock->value, TuningStage::Stage1->value, TuningStage::Stage2->value]),
            'redline_rpm' => 6800,
            'shift_light_start_rpm' => 6200,
            'k_cal' => 1.0,
            'vin' => null,
            'created_at' => $updatedAt->subDays(30),
            'updated_at' => $updatedAt,
            'synced_at' => $updatedAt,
        ];
    }
}
