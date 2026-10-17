<?php

use App\Models\DtcRecord;
use App\Models\Run;
use App\Models\SharedRun;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

it('exports every piece of user data', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $run = Run::factory()->for($user)->for($vehicle)->create();
    DtcRecord::factory()->for($user)->for($vehicle)->create();
    Trip::factory()->for($user)->for($vehicle)->create();
    SharedRun::factory()->for($run)->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me/export')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonCount(1, 'vehicles')
        ->assertJsonCount(1, 'runs')
        ->assertJsonCount(1, 'dtc_records')
        ->assertJsonCount(1, 'trips')
        ->assertJsonCount(1, 'shared_runs')
        ->assertJsonPath('runs.0.uuid', $run->uuid)
        ->assertJsonPath('runs.0.vehicle_uuid', $vehicle->uuid)
        ->assertJsonStructure(['exported_at', 'entitlements', 'runs' => [['samples']]]);
});

it('deletes the account and all of its data', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $run = Run::factory()->for($user)->for($vehicle)->create();
    DtcRecord::factory()->for($user)->for($vehicle)->create();
    Trip::factory()->for($user)->for($vehicle)->create();
    SharedRun::factory()->for($run)->create();
    $token = $user->createToken('iPhone')->plainTextToken;

    $other = User::factory()->create();
    Vehicle::factory()->for($other)->create();

    $this->withToken($token)->deleteJson('/api/v1/me')->assertNoContent();

    expect(User::find($user->id))->toBeNull()
        ->and(Vehicle::withTrashed()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Run::withTrashed()->where('user_id', $user->id)->count())->toBe(0)
        ->and(DtcRecord::withTrashed()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Trip::withTrashed()->where('user_id', $user->id)->count())->toBe(0)
        ->and(SharedRun::count())->toBe(0)
        ->and(PersonalAccessToken::count())->toBe(0)
        ->and(Vehicle::where('user_id', $other->id)->count())->toBe(1);
});
