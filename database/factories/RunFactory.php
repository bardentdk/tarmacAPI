<?php

namespace Database\Factories;

use App\Enums\RunMode;
use App\Enums\RunSource;
use App\Models\Run;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Run>
 */
class RunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $result = fake()->randomFloat(2, 5.6, 7.8);
        $recordedAt = now()->subDays(fake()->numberBetween(0, 60))->subMinutes(fake()->numberBetween(0, 600));
        $powerHp = fake()->randomFloat(1, 230, 330);

        return [
            'user_id' => User::factory(),
            'vehicle_id' => fn (array $attributes): int => Vehicle::factory()->create(['user_id' => $attributes['user_id']])->id,
            'uuid' => (string) Str::uuid(),
            'mode' => RunMode::ZeroHundred->value,
            'result_s' => $result,
            'trap_kmh' => 100.0,
            'distance_m' => round($result * 15.2, 1),
            'sixty_ft_s' => fake()->randomFloat(2, 2.0, 2.4),
            'reaction_s' => null,
            'splits' => [
                ['label' => '0-50', 't_s' => round($result * 0.38, 2), 'speed_kmh' => 50],
                ['label' => '0-80', 't_s' => round($result * 0.7, 2), 'speed_kmh' => 80],
                ['label' => '0-100', 't_s' => $result, 'speed_kmh' => 100],
            ],
            'power_hp' => $powerHp,
            'power_ci_hp' => round($powerHp * 0.04, 1),
            'torque_nm' => fake()->randomFloat(1, 350, 480),
            'peak_rpm' => fake()->numberBetween(5200, 6200),
            'curve' => $this->curve($powerHp),
            'conditions' => [
                'amb_c' => fake()->randomFloat(1, 8, 28),
                'baro_hpa' => fake()->randomFloat(1, 990, 1025),
                'dry_hpa' => fake()->randomFloat(1, 980, 1015),
                'humidity' => fake()->numberBetween(30, 80),
                'iat_c' => fake()->randomFloat(1, 15, 45),
                'ect_c' => fake()->randomFloat(1, 85, 98),
                'mass_kg' => 1420,
                'slope_pct' => fake()->randomFloat(1, -0.5, 0.5),
                'sats' => fake()->numberBetween(12, 24),
                'gps_hz' => 25,
                'fuel_pct' => fake()->numberBetween(20, 90),
            ],
            'source' => RunSource::Tarmac->value,
            'accuracy_s' => 0.02,
            'is_record' => false,
            'notes' => null,
            'samples' => $this->samples($result),
            'samples_bytes' => 0,
            'recorded_at' => $recordedAt,
            'created_at' => $recordedAt,
            'updated_at' => $recordedAt,
            'synced_at' => $recordedAt,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Run $run): void {
            $run->samples_bytes = $run->samples === null ? 0 : strlen((string) json_encode($run->samples));
        });
    }

    /**
     * A run in the given mode.
     */
    public function mode(RunMode $mode): static
    {
        return $this->state(fn (array $attributes): array => ['mode' => $mode->value]);
    }

    /**
     * A personal best.
     */
    public function record(): static
    {
        return $this->state(fn (array $attributes): array => ['is_record' => true]);
    }

    /**
     * @return list<array{rpm: int, hp: float, nm: float}>
     */
    private function curve(float $peakHp): array
    {
        $points = [];

        foreach (range(2500, 6500, 250) as $rpm) {
            $shape = sin(M_PI * ($rpm - 2000) / 5200);
            $hp = round(max(0.35, $shape) * $peakHp, 1);
            $points[] = ['rpm' => $rpm, 'hp' => $hp, 'nm' => round($hp * 7120 / $rpm, 1)];
        }

        return $points;
    }

    /**
     * @return list<array{t: float, v: float, rpm: int, d: float}>
     */
    private function samples(float $duration): array
    {
        $samples = [];
        $distance = 0.0;
        $previousSpeed = 0.0;

        for ($t = 0.0; $t <= $duration; $t += 0.1) {
            $speed = min(100, 100 * sqrt($t / $duration));
            $distance += (($speed + $previousSpeed) / 2) / 3.6 * 0.1;
            $previousSpeed = $speed;
            $samples[] = [
                't' => round($t, 2),
                'v' => round($speed, 2),
                'rpm' => (int) (2500 + fmod($speed * 75, 4000)),
                'd' => round($distance, 2),
            ];
        }

        return $samples;
    }
}
