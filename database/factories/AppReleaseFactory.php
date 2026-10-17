<?php

namespace Database\Factories;

use App\Enums\ReleaseChannel;
use App\Enums\ReleaseKind;
use App\Models\AppRelease;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AppRelease>
 */
class AppReleaseFactory extends Factory
{
    /**
     * Define the model's default state (an active production OTA bundle).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'project' => 'tarmac',
            'platform' => 'android',
            'channel' => ReleaseChannel::Production,
            'kind' => ReleaseKind::Ota,
            'fingerprint' => 'android-1.0.0b1',
            'version' => '1.0.'.fake()->numberBetween(1, 99),
            'version_code' => null,
            'notes' => fake()->optional()->sentence(),
            'sha256' => hash('sha256', $uuid),
            'size' => fake()->numberBetween(10_000, 5_000_000),
            'path' => "releases/{$uuid}.zip",
            'native_signature' => null,
            'mandatory' => false,
            'is_active' => true,
            'published_at' => now(),
        ];
    }

    /**
     * An over-the-air bundle.
     */
    public function ota(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => ReleaseKind::Ota,
            'version_code' => null,
            'path' => "releases/{$attributes['uuid']}.zip",
        ]);
    }

    /**
     * A native Android package.
     */
    public function apk(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => ReleaseKind::Apk,
            'version_code' => fake()->numberBetween(1, 1000),
            'native_signature' => hash('sha256', 'native-'.$attributes['uuid']),
            'path' => "releases/{$attributes['uuid']}.apk",
        ]);
    }

    /**
     * A release that is no longer offered.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    /**
     * A release on the staging channel.
     */
    public function staging(): static
    {
        return $this->state(fn (array $attributes): array => ['channel' => ReleaseChannel::Staging]);
    }
}
