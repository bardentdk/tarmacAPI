<?php

namespace App\Support;

use App\Enums\DtcKind;
use App\Enums\DtcUrgency;
use App\Enums\RunMode;
use App\Enums\TuningStage;
use App\Models\DtcDefinition;
use App\Models\DtcRecord;
use App\Models\Run;
use App\Models\SharedRun;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Shapes models into the props consumed by the Inertia pages.
 */
class WebPresenter
{
    /**
     * @return array{uuid: string, name: string, make: string, model: string, year: int|null, engine: string|null, tuning_stage: string, tuning_label: string}
     */
    public static function vehicleSummary(Vehicle $vehicle): array
    {
        return [
            'uuid' => $vehicle->uuid,
            'name' => $vehicle->name,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'engine' => $vehicle->engine,
            'tuning_stage' => $vehicle->tuning_stage,
            'tuning_label' => TuningStage::tryFrom($vehicle->tuning_stage)?->label() ?? $vehicle->tuning_stage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function runSummary(Run $run): array
    {
        return [
            'uuid' => $run->uuid,
            'mode' => $run->mode,
            'mode_label' => self::modeLabel($run->mode),
            'result_s' => $run->result_s,
            'trap_kmh' => $run->trap_kmh,
            'power_hp' => $run->power_hp,
            'torque_nm' => $run->torque_nm,
            'is_record' => $run->is_record,
            'source' => $run->source,
            'recorded_at' => $run->recorded_at->toIso8601ZuluString(),
            'conditions_summary' => self::conditionsSummary($run->conditions),
            'vehicle' => $run->relationLoaded('vehicle') ? self::vehicleSummary($run->vehicle) : null,
        ];
    }

    /**
     * @param  Collection<string, DtcDefinition>  $definitions  generic definitions keyed by code
     * @return array<string, mixed>
     */
    public static function dtcSummary(DtcRecord $record, Collection $definitions): array
    {
        $definition = $definitions->get($record->code);
        $urgency = $definition !== null ? DtcUrgency::tryFrom($definition->urgency) : null;

        return [
            'uuid' => $record->uuid,
            'code' => $record->code,
            'kind' => $record->kind,
            'kind_label' => DtcKind::tryFrom($record->kind)?->label() ?? $record->kind,
            'title' => $definition?->title,
            'system' => $definition?->system,
            'urgency' => $urgency?->value,
            'urgency_label' => $urgency?->label(),
            'read_at' => $record->read_at->toIso8601ZuluString(),
            'cleared_at' => $record->cleared_at?->toIso8601ZuluString(),
            'vehicle' => $record->relationLoaded('vehicle') ? self::vehicleSummary($record->vehicle) : null,
        ];
    }

    /**
     * Generic knowledge base definitions for the given codes, keyed by code.
     *
     * @param  iterable<string>  $codes
     * @return Collection<string, DtcDefinition>
     */
    public static function definitionsFor(iterable $codes): Collection
    {
        $codes = collect($codes)->unique()->values();

        if ($codes->isEmpty()) {
            return collect();
        }

        return DtcDefinition::query()
            ->whereIn('code', $codes->all())
            ->where('make', '')
            ->where('engine', '')
            ->get()
            ->keyBy('code');
    }

    /**
     * @return array<string, mixed>
     */
    public static function tripSummary(Trip $trip): array
    {
        return [
            'uuid' => $trip->uuid,
            'started_at' => $trip->started_at->toIso8601ZuluString(),
            'ended_at' => $trip->ended_at?->toIso8601ZuluString(),
            'distance_km' => $trip->distance_km,
            'health_score' => $trip->health_score,
            'components' => $trip->components ?? [],
            'metrics' => $trip->metrics ?? [],
            'vehicle' => $trip->relationLoaded('vehicle') ? self::vehicleSummary($trip->vehicle) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function shareSummary(SharedRun $share): array
    {
        return [
            'id' => $share->id,
            'url' => $share->url(),
            'recipient' => $share->recipient,
            'views' => $share->views,
            'created_at' => $share->created_at?->toIso8601ZuluString(),
            'revoked_at' => $share->revoked_at?->toIso8601ZuluString(),
            'run' => $share->relationLoaded('run') ? [
                'uuid' => $share->run->uuid,
                'mode_label' => self::modeLabel($share->run->mode),
                'result_s' => $share->run->result_s,
                'recorded_at' => $share->run->recorded_at->toIso8601ZuluString(),
            ] : null,
        ];
    }

    /**
     * Short "conditions" line, e.g. "18 °C · 1013 hPa · admission 32 °C".
     *
     * @param  array<string, mixed>|null  $conditions
     */
    public static function conditionsSummary(?array $conditions): ?string
    {
        if (empty($conditions)) {
            return null;
        }

        $parts = [];

        if (is_numeric($conditions['amb_c'] ?? null)) {
            $parts[] = self::number((float) $conditions['amb_c'], 0).' °C';
        }

        if (is_numeric($conditions['baro_hpa'] ?? null)) {
            $parts[] = self::number((float) $conditions['baro_hpa'], 0).' hPa';
        }

        if (is_numeric($conditions['iat_c'] ?? null)) {
            $parts[] = 'admission '.self::number((float) $conditions['iat_c'], 0).' °C';
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public static function modeLabel(string $mode): string
    {
        return RunMode::tryFrom($mode)?->label() ?? $mode;
    }

    /**
     * Run modes as select options.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function modeOptions(): array
    {
        return array_map(fn (RunMode $mode): array => ['value' => $mode->value, 'label' => $mode->label()], RunMode::cases());
    }

    private static function number(float $value, int $decimals): string
    {
        return number_format($value, $decimals, ',', ' ');
    }
}
