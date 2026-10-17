<?php

use App\Enums\Plan;
use App\Models\DtcRecord;
use App\Models\Run;
use App\Models\SharedRun;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->onTrial()->create(['name' => 'Djebarlen Tambon']);
    $this->vehicle = Vehicle::factory()->for($this->user)->create(['name' => 'Golf 7 GTI']);
    $this->run = Run::factory()->for($this->user)->for($this->vehicle)->create(['result_s' => 6.24]);
    Trip::factory()->count(3)->for($this->user)->for($this->vehicle)->create();
    DtcRecord::factory()->for($this->user)->for($this->vehicle)->create(['code' => 'P0301']);
});

it('redirects guests to the login page', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['dashboard', 'vehicles.index', 'runs.index', 'diagnostics.index', 'health.index', 'shares.index', 'billing.index']);

it('renders the dashboard', function () {
    SharedRun::factory()->for($this->run)->create(['recipient' => 'Mon mappeur']);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('firstName', 'Djebarlen')
            ->where('stats.best_zero_100.result_s', 6.24)
            ->has('stats.health.score')
            ->has('recentRuns', 1)
            ->has('recentDtcs', 1)
            ->has('vehicles', 1)
            ->has('activeShares', 1)
            ->has('bestByMode', 1)
            ->has('healthTrend', 3)
            ->where('subscription.plan', 'pro')
            ->where('subscription.is_trial', true));
});

it('renders the vehicle pages', function () {
    $this->actingAs($this->user)
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/Index')
            ->has('vehicles', 1)
            ->where('vehicles.0.name', 'Golf 7 GTI')
            ->where('maxVehicles', 3));

    $this->actingAs($this->user)
        ->get(route('vehicles.show', $this->vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/Show')
            ->where('vehicle.uuid', $this->vehicle->uuid)
            ->has('runs', 1)
            ->has('dtcRecords', 1)
            ->has('trips', 3));

    $this->actingAs($this->user)
        ->get(route('vehicles.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('vehicles/Create')->has('options.fuel'));
});

it('creates a vehicle from the web within the plan limit', function () {
    $payload = [
        'name' => 'Mégane RS',
        'make' => 'Renault',
        'model' => 'Mégane RS',
        'fuel' => 'petrol',
        'gearbox' => 'manual',
        'drivetrain' => 'fwd',
        'mass_kg' => 1390,
        'tuning_stage' => 'stock',
    ];

    $this->actingAs($this->user)
        ->post(route('vehicles.store'), $payload)
        ->assertRedirect();

    $vehicle = Vehicle::where('name', 'Mégane RS')->firstOrFail();

    expect($vehicle->user_id)->toBe($this->user->id)
        ->and($vehicle->uuid)->not->toBeEmpty()
        ->and($vehicle->synced_at)->not->toBeNull();

    $freeUser = User::factory()->free()->create();
    Vehicle::factory()->for($freeUser)->create();

    $this->actingAs($freeUser)
        ->post(route('vehicles.store'), $payload)
        ->assertSessionHasErrors('name');
});

it('forbids viewing the vehicle or run of another user', function () {
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get(route('vehicles.show', $this->vehicle))->assertForbidden();
    $this->actingAs($intruder)->get(route('runs.show', $this->run))->assertForbidden();
    $this->actingAs($intruder)->post(route('runs.share', $this->run))->assertForbidden();
});

it('renders and filters the runs list', function () {
    Run::factory()->for($this->user)->for($this->vehicle)->create(['mode' => 'quarter_mile']);

    $this->actingAs($this->user)
        ->get(route('runs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('runs/Index')
            ->has('runs.data', 2)
            ->has('modes'));

    $this->actingAs($this->user)
        ->get(route('runs.index', ['mode' => 'quarter_mile', 'vehicle' => $this->vehicle->uuid]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('runs.data', 1)
            ->where('runs.data.0.mode', 'quarter_mile')
            ->where('filters.mode', 'quarter_mile'));
});

it('renders the run report', function () {
    $this->actingAs($this->user)
        ->get(route('runs.show', $this->run))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('runs/Show')
            ->where('run.uuid', $this->run->uuid)
            ->has('run.splits', 3)
            ->has('run.curve')
            ->where('can.see_power', true)
            ->where('can.download_csv', false)
            ->where('share', null));
});

it('hides the power curve on the free plan', function () {
    $user = User::factory()->free()->create();
    $run = Run::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('runs.show', $run))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.see_power', false)
            ->where('run.curve', []));
});

it('only lets tuner plans download the CSV logs', function () {
    $this->actingAs($this->user)->get(route('runs.csv', $this->run))->assertForbidden();

    $tuner = User::factory()->subscribed(Plan::Tuner)->create();
    $run = Run::factory()->for($tuner)->create();

    $response = $this->actingAs($tuner)->get(route('runs.csv', $run));

    $response->assertOk();
    expect($response->streamedContent())->toStartWith('t_s;speed_kmh;rpm;distance_m');
});

it('renders the diagnostics page with the selected explanation', function () {
    $this->actingAs($this->user)
        ->get(route('diagnostics.index', ['code' => 'P0301']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('diagnostics/Index')
            ->has('records.data', 1)
            ->where('records.data.0.code', 'P0301')
            ->where('records.data.0.urgency', 'stop')
            ->where('selected.code', 'P0301')
            ->where('selected.source', 'kb'));
});

it('renders the health page', function () {
    $this->actingAs($this->user)
        ->get(route('health.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('health/Index')
            ->has('trips', 3)
            ->has('latest.health_score')
            ->where('vehicle.uuid', $this->vehicle->uuid));
});

it('renders the shares page', function () {
    SharedRun::factory()->for($this->run)->create();
    SharedRun::factory()->for($this->run)->revoked()->create();

    $this->actingAs($this->user)
        ->get(route('shares.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shares/Index')
            ->has('active', 1)
            ->has('revoked', 1));
});

it('renders the billing page with checkout disabled without Stripe keys', function () {
    $this->actingAs($this->user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('billing/Index')
            ->has('plans', 4)
            ->where('plans.1.monthly', 4.99)
            ->where('plans.1.yearly', 39)
            ->where('plans.2.yearly', 79)
            ->where('subscription.is_trial', true)
            ->where('checkoutEnabled', false));
});

it('downloads the account export from the web', function () {
    $response = $this->actingAs($this->user)->get(route('account.export'));

    $response->assertOk()
        ->assertHeader('content-disposition')
        ->assertJsonPath('user.email', $this->user->email)
        ->assertJsonCount(1, 'vehicles');
});

it('deletes the account from the web with password confirmation', function () {
    $this->actingAs($this->user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    expect(User::find($this->user->id))->toBeNull()
        ->and(Run::withTrashed()->count())->toBe(0);
});

it('renders the public landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});
