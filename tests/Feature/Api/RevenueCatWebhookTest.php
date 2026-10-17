<?php

use App\Enums\Plan;
use App\Models\User;

beforeEach(function () {
    config(['tarmac.revenuecat.webhook_secret' => 'rc-secret']);
});

function revenueCatEvent(User $user, string $type, array $overrides = []): array
{
    return ['event' => [
        'type' => $type,
        'app_user_id' => (string) $user->id,
        'original_app_user_id' => (string) $user->id,
        'aliases' => [(string) $user->id],
        'product_id' => 'tarmac_tuner_monthly',
        'expiration_at_ms' => now()->addMonth()->getTimestampMs(),
        ...$overrides,
    ]];
}

it('rejects requests without the shared secret', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'INITIAL_PURCHASE'))->assertUnauthorized();

    $this->withToken('wrong')
        ->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'INITIAL_PURCHASE'))
        ->assertUnauthorized();

    expect($user->fresh()->plan)->toBe(Plan::Free);
});

it('rejects every request when no secret is configured', function () {
    config(['tarmac.revenuecat.webhook_secret' => null]);
    $user = User::factory()->create();

    $this->withToken('')->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'INITIAL_PURCHASE'))->assertUnauthorized();
});

it('grants the purchased plan', function () {
    $user = User::factory()->free()->create();

    $this->withToken('rc-secret')
        ->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'INITIAL_PURCHASE'))
        ->assertOk()
        ->assertJsonPath('handled', true);

    $user->refresh();

    expect($user->plan)->toBe(Plan::Tuner)
        ->and($user->plan_period)->toBe('monthly')
        ->and($user->plan_expires_at->isFuture())->toBeTrue()
        ->and($user->revenuecat_app_user_id)->toBe((string) $user->id);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me')
        ->assertJsonPath('entitlements.plan', 'tuner')
        ->assertJsonPath('entitlements.is_trial', false);
});

it('extends the plan on renewal', function () {
    $user = User::factory()->subscribed(Plan::Pro)->create();
    $expiresAt = now()->addYear();

    $this->withToken('rc-secret')
        ->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'RENEWAL', [
            'product_id' => 'tarmac_pro_yearly',
            'expiration_at_ms' => $expiresAt->getTimestampMs(),
        ]))
        ->assertOk();

    expect($user->fresh()->plan)->toBe(Plan::Pro)
        ->and($user->fresh()->plan_period)->toBe('yearly')
        ->and($user->fresh()->plan_expires_at->timestamp)->toBe($expiresAt->timestamp);
});

it('keeps access until the end of the period on cancellation', function () {
    $user = User::factory()->subscribed(Plan::Pro)->create();
    $periodEnd = now()->addDays(10);

    $this->withToken('rc-secret')
        ->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'CANCELLATION', [
            'expiration_at_ms' => $periodEnd->getTimestampMs(),
        ]))
        ->assertOk();

    expect($user->fresh()->plan)->toBe(Plan::Pro);

    $this->travelTo($periodEnd->addDay());

    $this->actingAs($user->fresh(), 'sanctum')
        ->getJson('/api/v1/me')
        ->assertJsonPath('entitlements.plan', 'free');
});

it('downgrades to free on expiration', function () {
    $user = User::factory()->subscribed(Plan::Tuner)->create();

    $this->withToken('rc-secret')
        ->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'EXPIRATION'))
        ->assertOk();

    expect($user->fresh()->plan)->toBe(Plan::Free)
        ->and($user->fresh()->plan_expires_at)->toBeNull();
});

it('ignores unknown products and users', function () {
    $user = User::factory()->free()->create();

    $this->withToken('rc-secret')
        ->postJson('/api/v1/webhooks/revenuecat', revenueCatEvent($user, 'INITIAL_PURCHASE', ['product_id' => 'unknown']))
        ->assertOk()
        ->assertJsonPath('handled', false);

    $this->withToken('rc-secret')
        ->postJson('/api/v1/webhooks/revenuecat', ['event' => ['type' => 'INITIAL_PURCHASE', 'app_user_id' => 'ghost', 'product_id' => 'tarmac_pro_monthly']])
        ->assertOk()
        ->assertJsonPath('handled', false);

    expect($user->fresh()->plan)->toBe(Plan::Free);
});
