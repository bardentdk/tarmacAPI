<?php

namespace Database\Factories;

use App\Enums\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is in their free trial.
     */
    public function onTrial(int $days = 14): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => Plan::Free,
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    /**
     * Indicate that the user's trial is over and they are on the free plan.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => Plan::Free,
            'trial_ends_at' => now()->subDay(),
            'plan_expires_at' => null,
        ]);
    }

    /**
     * Indicate that the user pays for the given plan.
     */
    public function subscribed(Plan $plan = Plan::Pro, string $period = 'monthly'): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => $plan,
            'plan_period' => $period,
            'trial_ends_at' => now()->subDays(30),
            'plan_expires_at' => now()->addMonth(),
        ]);
    }
}
