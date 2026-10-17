<?php

namespace App\Services\Sync;

use App\Enums\Drivetrain;
use App\Enums\DtcKind;
use App\Enums\Fuel;
use App\Enums\Gearbox;
use App\Enums\RunMode;
use App\Enums\RunSource;
use App\Enums\TuningStage;
use App\Models\DtcRecord;
use App\Models\Run;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Upserts records pushed by the mobile app.
 *
 * Records are keyed by (user_id, uuid). The incoming `updated_at` is compared
 * with the stored one: only strictly newer versions are written
 * (last-write-wins), so replaying the same payload is a no-op.
 */
class SyncService
{
    /**
     * The record types handled by the sync, in processing order.
     *
     * @var list<string>
     */
    public const TYPES = ['vehicles', 'runs', 'dtc_records', 'trips'];

    /**
     * Payload columns holding ISO-8601 dates, converted to UTC on write.
     *
     * @var list<string>
     */
    private const DATE_COLUMNS = ['recorded_at', 'read_at', 'cleared_at', 'started_at', 'ended_at'];

    /**
     * @param  array<string, mixed>  $payload
     * @return array{accepted: array<string, list<string>>, rejected: array<string, list<array{uuid: string|null, errors: array<string, array<int, string>>}>>, server_time: string}
     */
    public function push(User $user, array $payload): array
    {
        $accepted = array_fill_keys(self::TYPES, []);
        $rejected = array_fill_keys(self::TYPES, []);

        foreach ($this->records($payload, 'vehicles') as $record) {
            $errors = $this->validate($record, $this->vehicleRules());

            if ($errors !== null) {
                $rejected['vehicles'][] = ['uuid' => $this->uuidOf($record), 'errors' => $errors];

                continue;
            }

            $this->upsert($user, Vehicle::class, $record, [
                'name', 'make', 'model', 'year', 'engine', 'fuel', 'gearbox', 'drivetrain', 'mass_kg',
                'tire_size', 'wheel_radius_m', 'cda', 'tuning_stage', 'redline_rpm', 'shift_light_start_rpm',
                'k_cal', 'vin',
            ], defaults: ['k_cal' => 1.0]);

            $accepted['vehicles'][] = $record['uuid'];
        }

        $vehicleIds = $this->vehicleIds($user, $payload);

        $dependents = [
            'runs' => [Run::class, $this->runRules(), [
                'mode', 'result_s', 'trap_kmh', 'distance_m', 'sixty_ft_s', 'reaction_s', 'splits', 'power_hp',
                'power_ci_hp', 'torque_nm', 'peak_rpm', 'curve', 'conditions', 'source', 'accuracy_s', 'is_record',
                'notes', 'samples', 'recorded_at',
            ]],
            'dtc_records' => [DtcRecord::class, $this->dtcRecordRules(), [
                'code', 'kind', 'freeze_frame', 'read_at', 'cleared_at',
            ]],
            'trips' => [Trip::class, $this->tripRules(), [
                'started_at', 'ended_at', 'distance_km', 'health_score', 'components', 'metrics',
            ]],
        ];

        foreach ($dependents as $type => [$modelClass, $rules, $columns]) {
            foreach ($this->records($payload, $type) as $record) {
                $errors = $this->validate($record, $rules);

                if ($errors === null && ! isset($vehicleIds[strtolower((string) $record['vehicle_uuid'])])) {
                    $errors = ['vehicle_uuid' => ['Véhicule inconnu : synchronise d\'abord le véhicule.']];
                }

                if ($errors !== null) {
                    $rejected[$type][] = ['uuid' => $this->uuidOf($record), 'errors' => $errors];

                    continue;
                }

                $extra = ['vehicle_id' => $vehicleIds[strtolower((string) $record['vehicle_uuid'])]];

                if ($modelClass === Run::class) {
                    $extra['samples_bytes'] = isset($record['samples']) ? strlen((string) json_encode($record['samples'])) : 0;
                }

                if ($modelClass === DtcRecord::class) {
                    $record['code'] = strtoupper((string) $record['code']);
                }

                $this->upsert($user, $modelClass, $record, $columns, $extra, defaults: [
                    'is_record' => false,
                    'accuracy_s' => 0.0,
                ]);

                $accepted[$type][] = $record['uuid'];
            }
        }

        $user->forceFill(['last_synced_at' => now()])->save();

        return [
            'accepted' => $accepted,
            'rejected' => $rejected,
            'server_time' => now()->toIso8601ZuluString(),
        ];
    }

    /**
     * Insert or update one record unless the stored version is newer or equal.
     *
     * @param  class-string<Vehicle|Run|DtcRecord|Trip>  $modelClass
     * @param  array<string, mixed>  $record
     * @param  list<string>  $columns
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $defaults
     */
    private function upsert(User $user, string $modelClass, array $record, array $columns, array $extra = [], array $defaults = [], bool $isRetry = false): void
    {
        $uuid = strtolower((string) $record['uuid']);
        $version = $this->versionOf($record);

        /** @var Vehicle|Run|DtcRecord|Trip|null $model */
        $model = $modelClass::withTrashed()
            ->where('user_id', $user->id)
            ->where('uuid', $uuid)
            ->first();

        if ($model?->updated_at !== null && $version->lessThanOrEqualTo($model->updated_at)) {
            return;
        }

        $model ??= new $modelClass;

        $attributes = [];

        foreach ($columns as $column) {
            if (array_key_exists($column, $record)) {
                $attributes[$column] = in_array($column, self::DATE_COLUMNS, true)
                    ? $this->date($record[$column])
                    : $record[$column];
            } elseif (array_key_exists($column, $defaults)) {
                $attributes[$column] = $defaults[$column];
            }
        }

        foreach ($attributes as $column => $value) {
            if ($value === null && array_key_exists($column, $defaults)) {
                $attributes[$column] = $defaults[$column];
            }
        }

        $model->forceFill([
            ...$attributes,
            ...$extra,
            'user_id' => $user->id,
            'uuid' => $uuid,
            'deleted_at' => $this->date($record['deleted_at'] ?? null),
            'synced_at' => now(),
        ]);

        $model->created_at = $model->exists && $model->created_at !== null
            ? $model->created_at
            : ($this->date($record['created_at'] ?? null) ?? $version);
        $model->updated_at = $version;
        $model->timestamps = false;

        try {
            $model->save();
        } catch (UniqueConstraintViolationException $exception) {
            if ($isRetry) {
                throw $exception;
            }

            $this->upsert($user, $modelClass, $record, $columns, $extra, $defaults, isRetry: true);
        }
    }

    /**
     * Map of vehicle uuid => id for every vehicle referenced by the payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, int>
     */
    private function vehicleIds(User $user, array $payload): array
    {
        $uuids = collect(['runs', 'dtc_records', 'trips'])
            ->flatMap(fn (string $type): array => $this->records($payload, $type))
            ->pluck('vehicle_uuid')
            ->filter(fn (mixed $uuid): bool => is_string($uuid))
            ->map(fn (string $uuid): string => strtolower($uuid))
            ->unique()
            ->values();

        if ($uuids->isEmpty()) {
            return [];
        }

        return Vehicle::withTrashed()
            ->where('user_id', $user->id)
            ->whereIn('uuid', $uuids->all())
            ->pluck('id', 'uuid')
            ->mapWithKeys(fn (int $id, string $uuid): array => [strtolower($uuid) => $id])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function records(array $payload, string $type): array
    {
        $records = $payload[$type] ?? [];

        return is_array($records) ? array_values(array_filter($records, 'is_array')) : [];
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $rules
     * @return array<string, array<int, string>>|null
     */
    private function validate(array $record, array $rules): ?array
    {
        $validator = Validator::make($record, $rules);

        return $validator->fails() ? $validator->errors()->toArray() : null;
    }

    /**
     * The timestamp used for last-write-wins comparisons (second precision).
     *
     * @param  array<string, mixed>  $record
     */
    private function versionOf(array $record): CarbonImmutable
    {
        $candidates = Arr::only($record, ['updated_at', 'cleared_at', 'read_at', 'ended_at', 'created_at', 'recorded_at', 'started_at']);

        foreach (['updated_at', 'cleared_at', 'created_at', 'ended_at', 'read_at', 'recorded_at', 'started_at'] as $key) {
            $date = $this->date($candidates[$key] ?? null);

            if ($date !== null) {
                return $date->startOfSecond();
            }
        }

        return CarbonImmutable::now('UTC')->startOfSecond();
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->utc();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function uuidOf(array $record): ?string
    {
        return isset($record['uuid']) && is_string($record['uuid']) ? strtolower($record['uuid']) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function commonRules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'created_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
            'deleted_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleRules(): array
    {
        return [
            ...$this->commonRules(),
            'name' => ['required', 'string', 'max:255'],
            'make' => ['required', 'string', 'max:64'],
            'model' => ['required', 'string', 'max:64'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'engine' => ['nullable', 'string', 'max:64'],
            'fuel' => ['required', Rule::enum(Fuel::class)],
            'gearbox' => ['required', Rule::enum(Gearbox::class)],
            'drivetrain' => ['required', Rule::enum(Drivetrain::class)],
            'mass_kg' => ['required', 'integer', 'between:100,20000'],
            'tire_size' => ['nullable', 'string', 'max:32'],
            'wheel_radius_m' => ['required', 'numeric', 'gt:0', 'max:2'],
            'cda' => ['required', 'numeric', 'gt:0', 'max:10'],
            'tuning_stage' => ['required', Rule::enum(TuningStage::class)],
            'redline_rpm' => ['required', 'integer', 'between:1000,25000'],
            'shift_light_start_rpm' => ['required', 'integer', 'between:500,25000'],
            'k_cal' => ['nullable', 'numeric', 'gt:0', 'max:5'],
            'vin' => ['nullable', 'string', 'max:17'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runRules(): array
    {
        return [
            ...$this->commonRules(),
            'vehicle_uuid' => ['required', 'uuid'],
            'mode' => ['required', Rule::enum(RunMode::class)],
            'result_s' => ['nullable', 'numeric'],
            'trap_kmh' => ['nullable', 'numeric'],
            'distance_m' => ['nullable', 'numeric'],
            'sixty_ft_s' => ['nullable', 'numeric'],
            'reaction_s' => ['nullable', 'numeric'],
            'splits' => ['nullable', 'array', 'max:100'],
            'splits.*.label' => ['required', 'string', 'max:64'],
            'splits.*.t_s' => ['required', 'numeric'],
            'splits.*.speed_kmh' => ['nullable', 'numeric'],
            'power_hp' => ['nullable', 'numeric'],
            'power_ci_hp' => ['nullable', 'numeric'],
            'torque_nm' => ['nullable', 'numeric'],
            'peak_rpm' => ['nullable', 'integer'],
            'curve' => ['nullable', 'array', 'max:2000'],
            'conditions' => ['nullable', 'array'],
            'source' => ['required', Rule::enum(RunSource::class)],
            'accuracy_s' => ['nullable', 'numeric'],
            'is_record' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'samples' => ['nullable', 'array', 'max:2000'],
            'recorded_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dtcRecordRules(): array
    {
        return [
            ...$this->commonRules(),
            'vehicle_uuid' => ['required', 'uuid'],
            'code' => ['required', 'string', 'regex:/^[PCBUpcbu][0-9A-Fa-f]{4}$/'],
            'kind' => ['required', Rule::enum(DtcKind::class)],
            'freeze_frame' => ['nullable', 'array'],
            'read_at' => ['required', 'date'],
            'cleared_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tripRules(): array
    {
        return [
            ...$this->commonRules(),
            'vehicle_uuid' => ['required', 'uuid'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date'],
            'distance_km' => ['required', 'numeric', 'min:0'],
            'health_score' => ['required', 'integer', 'between:0,100'],
            'components' => ['nullable', 'array'],
            'components.*' => ['nullable', 'numeric', 'between:0,1'],
            'metrics' => ['nullable', 'array'],
        ];
    }

    /**
     * Records written on the server since the given time, for restore.
     *
     * @return array{vehicles: array<int, Vehicle>, runs: array<int, Run>, dtc_records: array<int, DtcRecord>, trips: array<int, Trip>}
     */
    public function pull(User $user, ?CarbonInterface $since): array
    {
        return [
            'vehicles' => $user->vehicles()->withTrashed()->syncedSince($since)->orderBy('id')->get()->all(),
            'runs' => $user->runs()->withTrashed()->with('vehicle')->syncedSince($since)->orderBy('id')->get()->all(),
            'dtc_records' => $user->dtcRecords()->withTrashed()->with('vehicle')->syncedSince($since)->orderBy('id')->get()->all(),
            'trips' => $user->trips()->withTrashed()->with('vehicle')->syncedSince($since)->orderBy('id')->get()->all(),
        ];
    }
}
