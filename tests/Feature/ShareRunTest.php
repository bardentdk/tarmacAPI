<?php

use App\Models\Run;
use App\Models\SharedRun;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

it('creates a public link through the API and shows it publicly', function () {
    $user = User::factory()->create(['name' => 'Djebarlen Tambon']);
    $run = Run::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $url = $this->postJson("/api/v1/runs/{$run->uuid}/share")
        ->assertOk()
        ->json('url');

    expect($url)->toContain('/r/');

    $this->postJson("/api/v1/runs/{$run->uuid}/share")->assertJsonPath('url', $url);
    expect(SharedRun::count())->toBe(1);

    $this->get(parse_url($url, PHP_URL_PATH))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/SharedRun')
            ->where('run.uuid', $run->uuid)
            ->where('driver', 'Djebarlen')
            ->missing('run.samples'));

    expect(SharedRun::firstOrFail()->views)->toBe(1);
});

it('revokes a public link through the API', function () {
    $user = User::factory()->create();
    $run = Run::factory()->for($user)->create();
    $share = SharedRun::factory()->for($run)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/runs/{$run->uuid}/share")->assertNoContent();

    expect($share->fresh()->revoked_at)->not->toBeNull();
    $this->get("/r/{$share->token}")->assertNotFound();
});

it('cannot share the run of another user', function () {
    $run = Run::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/runs/{$run->uuid}/share")->assertNotFound();
    $this->deleteJson("/api/v1/runs/{$run->uuid}/share")->assertNotFound();
});

it('returns 404 for unknown share tokens', function () {
    $this->get('/r/unknown-token')->assertNotFound();
});

it('shares and revokes from the web client', function () {
    $user = User::factory()->create();
    $run = Run::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('runs.share', $run), ['recipient' => 'Mon mappeur'])
        ->assertRedirect();

    $share = SharedRun::firstOrFail();
    expect($share->recipient)->toBe('Mon mappeur');

    $this->actingAs($user)->delete(route('shares.destroy', $share))->assertRedirect();
    expect($share->fresh()->revoked_at)->not->toBeNull();
});

it('forbids revoking the share of another user from the web', function () {
    $share = SharedRun::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('shares.destroy', $share))
        ->assertForbidden();
});
