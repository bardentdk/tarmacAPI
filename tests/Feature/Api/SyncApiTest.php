<?php

use App\Models\DtcRecord;
use App\Models\Run;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('upserts vehicles, runs, trouble codes and trips in one payload', function () {
    $vehicle = vehiclePayload();
    $run = runPayload($vehicle['uuid']);
    $dtc = dtcRecordPayload($vehicle['uuid']);
    $trip = tripPayload($vehicle['uuid']);

    $this->postJson('/api/v1/sync', [
        'vehicles' => [$vehicle],
        'runs' => [$run],
        'dtc_records' => [$dtc],
        'trips' => [$trip],
    ])
        ->assertOk()
        ->assertJsonPath('accepted.vehicles', [$vehicle['uuid']])
        ->assertJsonPath('accepted.runs', [$run['uuid']])
        ->assertJsonPath('accepted.dtc_records', [$dtc['uuid']])
        ->assertJsonPath('accepted.trips', [$trip['uuid']])
        ->assertJsonPath('rejected.runs', [])
        ->assertJsonStructure(['server_time']);

    $storedRun = Run::where('uuid', $run['uuid'])->firstOrFail();

    expect($storedRun->user_id)->toBe($this->user->id)
        ->and($storedRun->vehicle->uuid)->toBe($vehicle['uuid'])
        ->and($storedRun->result_s)->toBe(6.24)
        ->and($storedRun->splits)->toHaveCount(2)
        ->and($storedRun->samples)->toHaveCount(2)
        ->and($storedRun->samples_bytes)->toBeGreaterThan(0)
        ->and($storedRun->updated_at->toIso8601ZuluString())->toBe('2026-10-02T09:15:00Z')
        ->and(DtcRecord::where('uuid', $dtc['uuid'])->value('code'))->toBe('P0301')
        ->and(Trip::where('uuid', $trip['uuid'])->value('health_score'))->toBe(86)
        ->and($this->user->fresh()->last_synced_at)->not->toBeNull();
});

it('is idempotent when the same payload is replayed', function () {
    $vehicle = vehiclePayload();
    $payload = ['vehicles' => [$vehicle], 'runs' => [runPayload($vehicle['uuid'])]];

    Date::setTestNow('2026-10-10 10:00:00');
    $this->postJson('/api/v1/sync', $payload)->assertOk();
    $syncedAt = Run::firstOrFail()->synced_at;

    Date::setTestNow('2026-10-10 11:00:00');
    $this->postJson('/api/v1/sync', $payload)
        ->assertOk()
        ->assertJsonPath('accepted.runs', [$payload['runs'][0]['uuid']]);

    expect(Vehicle::count())->toBe(1)
        ->and(Run::count())->toBe(1)
        ->and(Run::firstOrFail()->synced_at->equalTo($syncedAt))->toBeTrue();
});

it('applies last-write-wins on updated_at', function () {
    $vehicle = vehiclePayload(['name' => 'Version 1', 'updated_at' => '2026-10-05T10:00:00Z']);
    $this->postJson('/api/v1/sync', ['vehicles' => [$vehicle]])->assertOk();

    $older = [...$vehicle, 'name' => 'Ancienne', 'updated_at' => '2026-10-04T10:00:00Z'];
    $this->postJson('/api/v1/sync', ['vehicles' => [$older]])
        ->assertOk()
        ->assertJsonPath('accepted.vehicles', [$vehicle['uuid']]);

    expect(Vehicle::firstOrFail()->name)->toBe('Version 1');

    $newer = [...$vehicle, 'name' => 'Version 2', 'updated_at' => '2026-10-06T10:00:00Z'];
    $this->postJson('/api/v1/sync', ['vehicles' => [$newer]])->assertOk();

    expect(Vehicle::firstOrFail()->name)->toBe('Version 2')
        ->and(Vehicle::firstOrFail()->updated_at->toIso8601ZuluString())->toBe('2026-10-06T10:00:00Z');
});

it('soft deletes and restores records through deleted_at', function () {
    $vehicle = vehiclePayload();
    $this->postJson('/api/v1/sync', ['vehicles' => [$vehicle]])->assertOk();

    $this->postJson('/api/v1/sync', ['vehicles' => [[...$vehicle, 'updated_at' => '2026-10-02T08:00:00Z', 'deleted_at' => '2026-10-02T08:00:00Z']]])->assertOk();
    expect(Vehicle::count())->toBe(0)->and(Vehicle::withTrashed()->count())->toBe(1);

    $this->postJson('/api/v1/sync', ['vehicles' => [[...$vehicle, 'updated_at' => '2026-10-03T08:00:00Z', 'deleted_at' => null]]])->assertOk();
    expect(Vehicle::count())->toBe(1);
});

it('rejects records referencing an unknown vehicle', function () {
    $run = runPayload((string) Str::uuid());
    $trip = tripPayload((string) Str::uuid());

    $this->postJson('/api/v1/sync', ['runs' => [$run], 'trips' => [$trip]])
        ->assertOk()
        ->assertJsonPath('accepted.runs', [])
        ->assertJsonPath('rejected.runs.0.uuid', $run['uuid'])
        ->assertJsonStructure(['rejected' => ['runs' => [['uuid', 'errors' => ['vehicle_uuid']]]]])
        ->assertJsonPath('rejected.trips.0.uuid', $trip['uuid']);

    expect(Run::count())->toBe(0)->and(Trip::count())->toBe(0);
});

it('accepts records referencing a vehicle synced earlier', function () {
    $vehicle = vehiclePayload();
    $this->postJson('/api/v1/sync', ['vehicles' => [$vehicle]])->assertOk();

    $this->postJson('/api/v1/sync', ['runs' => [runPayload($vehicle['uuid'])]])
        ->assertOk()
        ->assertJsonCount(1, 'accepted.runs');
});

it('rejects invalid records individually', function () {
    $vehicle = vehiclePayload();
    $valid = runPayload($vehicle['uuid']);
    $invalid = runPayload($vehicle['uuid'], ['mode' => 'warp_speed']);

    $this->postJson('/api/v1/sync', ['vehicles' => [$vehicle], 'runs' => [$valid, $invalid]])
        ->assertOk()
        ->assertJsonPath('accepted.runs', [$valid['uuid']])
        ->assertJsonPath('rejected.runs.0.uuid', $invalid['uuid'])
        ->assertJsonStructure(['rejected' => ['runs' => [['errors' => ['mode']]]]]);
});

it('isolates records between users', function () {
    $other = User::factory()->create();
    $otherVehicle = Vehicle::factory()->for($other)->create(['name' => 'Pas à toi']);

    $sameUuid = vehiclePayload(['uuid' => $otherVehicle->uuid, 'name' => 'La mienne', 'updated_at' => '2027-01-01T00:00:00Z']);

    $this->postJson('/api/v1/sync', ['vehicles' => [$sameUuid]])->assertOk();

    expect($otherVehicle->fresh()->name)->toBe('Pas à toi')
        ->and(Vehicle::where('user_id', $this->user->id)->value('name'))->toBe('La mienne');

    $intruder = runPayload((string) Str::uuid());
    $otherOnly = Vehicle::factory()->for($other)->create();
    $intruder['vehicle_uuid'] = $otherOnly->uuid;

    $this->postJson('/api/v1/sync', ['runs' => [$intruder]])
        ->assertOk()
        ->assertJsonPath('rejected.runs.0.uuid', $intruder['uuid']);

    expect(Run::count())->toBe(0);
});

it('pulls records written since a given time', function () {
    Date::setTestNow('2026-10-10 10:00:00');
    $old = Vehicle::factory()->for($this->user)->create(['synced_at' => now()->subDay()]);
    $recent = Vehicle::factory()->for($this->user)->create(['synced_at' => now()]);
    Run::factory()->for($this->user)->for($recent)->create(['synced_at' => now()]);
    Trip::factory()->for($this->user)->for($old)->create(['synced_at' => now()->subDay()]);
    Vehicle::factory()->create(['synced_at' => now()]);

    $this->getJson('/api/v1/sync/pull?since='.urlencode('2026-10-10T09:00:00Z'))
        ->assertOk()
        ->assertJsonCount(1, 'vehicles')
        ->assertJsonPath('vehicles.0.uuid', $recent->uuid)
        ->assertJsonCount(1, 'runs')
        ->assertJsonPath('runs.0.vehicle_uuid', $recent->uuid)
        ->assertJsonCount(0, 'trips')
        ->assertJsonPath('server_time', '2026-10-10T10:00:00Z');

    $this->getJson('/api/v1/sync/pull')
        ->assertOk()
        ->assertJsonCount(2, 'vehicles')
        ->assertJsonCount(1, 'trips');
});

it('includes soft deleted records in the pull', function () {
    $vehicle = Vehicle::factory()->for($this->user)->create(['deleted_at' => now(), 'synced_at' => now()]);

    $this->getJson('/api/v1/sync/pull')
        ->assertOk()
        ->assertJsonPath('vehicles.0.uuid', $vehicle->uuid)
        ->assertJsonPath('vehicles.0.deleted_at', fn (?string $value) => $value !== null);
});
