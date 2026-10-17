<?php

use App\Enums\Plan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('registers a user and returns a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Djebarlen Tambon',
        'email' => 'pilote@example.com',
        'password' => 'password',
        'device_name' => 'Pixel 9',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
        ->assertJsonPath('user.email', 'pilote@example.com');

    $user = User::where('email', 'pilote@example.com')->firstOrFail();

    expect($user->tokens)->toHaveCount(1)
        ->and($user->tokens->first()->name)->toBe('Pixel 9')
        ->and($user->trial_ends_at->isFuture())->toBeTrue();
});

it('rejects an already used email on register', function () {
    User::factory()->create(['email' => 'pilote@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Autre',
        'email' => 'pilote@example.com',
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create(['email' => 'pilote@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'pilote@example.com',
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
        ->assertJsonPath('user.id', $user->id);
});

it('rejects invalid credentials with a 422', function () {
    User::factory()->create(['email' => 'pilote@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'pilote@example.com',
        'password' => 'wrong-password',
        'device_name' => 'iPhone',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('rate limits login attempts', function () {
    User::factory()->create(['email' => 'pilote@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'pilote@example.com',
            'password' => 'wrong-password',
            'device_name' => 'iPhone',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'pilote@example.com',
        'password' => 'wrong-password',
        'device_name' => 'iPhone',
    ])->assertTooManyRequests();
});

it('logs out by revoking the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('iPhone')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

it('requires a token for protected endpoints', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->postJson('/api/v1/sync', [])->assertUnauthorized();
});

it('returns the user and trial entitlements on /me', function () {
    $user = User::factory()->onTrial()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('entitlements.plan', 'pro')
        ->assertJsonPath('entitlements.is_trial', true)
        ->assertJsonPath('entitlements.features.all_modes', true)
        ->assertJsonPath('entitlements.features.ai', true)
        ->assertJsonPath('entitlements.features.csv_logs', false)
        ->assertJsonPath('entitlements.features.max_vehicles', 3)
        ->assertJsonPath('entitlements.features.runs_per_month', null)
        ->assertJsonStructure(['entitlements' => ['trial_ends_at', 'features' => [
            'all_modes', 'power', 'full_reports', 'unlimited_history', 'ai', 'csv_logs', 'compare', 'max_vehicles', 'runs_per_month',
        ]]]);
});

it('falls back to the free plan once the trial is over', function () {
    Sanctum::actingAs(User::factory()->free()->create());

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('entitlements.plan', 'free')
        ->assertJsonPath('entitlements.is_trial', false)
        ->assertJsonPath('entitlements.features.power', false)
        ->assertJsonPath('entitlements.features.max_vehicles', 1)
        ->assertJsonPath('entitlements.features.runs_per_month', 3);
});

it('gives tuner features to tuner subscribers', function () {
    Sanctum::actingAs(User::factory()->subscribed(Plan::Tuner)->create());

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('entitlements.plan', 'tuner')
        ->assertJsonPath('entitlements.is_trial', false)
        ->assertJsonPath('entitlements.features.csv_logs', true)
        ->assertJsonPath('entitlements.features.compare', true)
        ->assertJsonPath('entitlements.features.max_vehicles', 5);
});
