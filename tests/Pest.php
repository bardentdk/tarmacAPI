<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A vehicle record as pushed by the mobile app.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function vehiclePayload(array $overrides = []): array
{
    return [
        'uuid' => (string) Str::uuid(),
        'name' => 'Golf 7 GTI',
        'make' => 'Volkswagen',
        'model' => 'Golf GTI',
        'year' => 2017,
        'engine' => 'EA888 2.0 TSI',
        'fuel' => 'petrol',
        'gearbox' => 'dct',
        'drivetrain' => 'fwd',
        'mass_kg' => 1420,
        'tire_size' => '225/40R18',
        'wheel_radius_m' => 0.317,
        'cda' => 0.68,
        'tuning_stage' => 'stage1',
        'redline_rpm' => 6800,
        'shift_light_start_rpm' => 6200,
        'k_cal' => 1,
        'vin' => null,
        'created_at' => '2026-10-01T08:00:00Z',
        'updated_at' => '2026-10-01T08:00:00Z',
        'deleted_at' => null,
        ...$overrides,
    ];
}

/**
 * A run record as pushed by the mobile app.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function runPayload(string $vehicleUuid, array $overrides = []): array
{
    return [
        'uuid' => (string) Str::uuid(),
        'vehicle_uuid' => $vehicleUuid,
        'mode' => 'zero_100',
        'result_s' => 6.24,
        'trap_kmh' => 100.0,
        'distance_m' => 95.1,
        'sixty_ft_s' => 2.12,
        'reaction_s' => null,
        'splits' => [
            ['label' => '0-50', 't_s' => 2.4, 'speed_kmh' => 50],
            ['label' => '0-100', 't_s' => 6.24, 'speed_kmh' => 100],
        ],
        'power_hp' => 287.5,
        'power_ci_hp' => 9.1,
        'torque_nm' => 402.0,
        'peak_rpm' => 5800,
        'curve' => [
            ['rpm' => 3000, 'hp' => 150.0, 'nm' => 356.0],
            ['rpm' => 5800, 'hp' => 287.5, 'nm' => 353.0],
        ],
        'conditions' => ['amb_c' => 18.5, 'baro_hpa' => 1013.2, 'iat_c' => 31.0],
        'source' => 'tarmac',
        'accuracy_s' => 0.02,
        'is_record' => true,
        'notes' => null,
        'samples' => [
            ['t' => 0.0, 'v' => 0.0, 'rpm' => 3000, 'd' => 0.0],
            ['t' => 0.1, 'v' => 2.5, 'rpm' => 3200, 'd' => 0.03],
        ],
        'recorded_at' => '2026-10-02T09:15:00Z',
        'created_at' => '2026-10-02T09:15:00Z',
        'updated_at' => '2026-10-02T09:15:00Z',
        ...$overrides,
    ];
}

/**
 * A trouble code record as pushed by the mobile app.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function dtcRecordPayload(string $vehicleUuid, array $overrides = []): array
{
    return [
        'uuid' => (string) Str::uuid(),
        'vehicle_uuid' => $vehicleUuid,
        'code' => 'P0301',
        'kind' => 'confirmed',
        'freeze_frame' => ['rpm' => 2400, 'ect_c' => 91],
        'read_at' => '2026-10-03T07:00:00Z',
        'cleared_at' => null,
        ...$overrides,
    ];
}

/**
 * A trip record as pushed by the mobile app.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tripPayload(string $vehicleUuid, array $overrides = []): array
{
    return [
        'uuid' => (string) Str::uuid(),
        'vehicle_uuid' => $vehicleUuid,
        'started_at' => '2026-10-04T07:00:00Z',
        'ended_at' => '2026-10-04T07:35:00Z',
        'distance_km' => 32.4,
        'health_score' => 86,
        'components' => ['dtc' => 1, 'misfire' => 0.9, 'trims' => 0.7, 'cooling' => 1, 'electric' => 0.9, 'readiness' => 1, 'trends' => 1],
        'metrics' => ['avg_stft' => 1.2, 'avg_ltft' => 4.5, 'max_ect' => 96, 'battery_v' => 12.6, 'crank_v' => 10.2, 'misfires' => 2, 'readiness_incomplete' => 0],
        'created_at' => '2026-10-04T07:35:00Z',
        ...$overrides,
    ];
}
