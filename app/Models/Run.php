<?php

namespace App\Models;

use App\Models\Concerns\SyncedRecord;
use Carbon\CarbonImmutable;
use Database\Factories\RunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $vehicle_id
 * @property string $uuid
 * @property string $mode
 * @property float|null $result_s
 * @property float|null $trap_kmh
 * @property float|null $distance_m
 * @property float|null $sixty_ft_s
 * @property float|null $reaction_s
 * @property list<array{label: string, t_s: float, speed_kmh: float|null}>|null $splits
 * @property float|null $power_hp
 * @property float|null $power_ci_hp
 * @property float|null $torque_nm
 * @property int|null $peak_rpm
 * @property list<array{rpm: int|float, hp: float, nm: float}>|null $curve
 * @property array<string, mixed>|null $conditions
 * @property string $source
 * @property float $accuracy_s
 * @property bool $is_record
 * @property string|null $notes
 * @property list<array{t?: float, v?: float, rpm?: int|float|null, d?: float|null}>|null $samples
 * @property int $samples_bytes
 * @property CarbonImmutable $recorded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $synced_at
 * @property-read Vehicle $vehicle
 */
#[Fillable([
    'uuid', 'mode', 'result_s', 'trap_kmh', 'distance_m', 'sixty_ft_s', 'reaction_s', 'splits', 'power_hp',
    'power_ci_hp', 'torque_nm', 'peak_rpm', 'curve', 'conditions', 'source', 'accuracy_s', 'is_record',
    'notes', 'samples', 'recorded_at',
])]
#[Hidden(['samples'])]
class Run extends Model
{
    /** @use HasFactory<RunFactory> */
    use HasFactory, SyncedRecord;

    /**
     * Columns used by list views (everything except the heavy samples).
     *
     * @var list<string>
     */
    public const LIST_COLUMNS = [
        'id', 'user_id', 'vehicle_id', 'uuid', 'mode', 'result_s', 'trap_kmh', 'distance_m', 'sixty_ft_s',
        'reaction_s', 'power_hp', 'torque_nm', 'peak_rpm', 'conditions', 'source', 'accuracy_s', 'is_record',
        'samples_bytes', 'recorded_at', 'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_s' => 'float',
            'trap_kmh' => 'float',
            'distance_m' => 'float',
            'sixty_ft_s' => 'float',
            'reaction_s' => 'float',
            'splits' => 'array',
            'power_hp' => 'float',
            'power_ci_hp' => 'float',
            'torque_nm' => 'float',
            'peak_rpm' => 'integer',
            'curve' => 'array',
            'conditions' => 'array',
            'accuracy_s' => 'float',
            'is_record' => 'boolean',
            'samples' => 'array',
            'samples_bytes' => 'integer',
            'recorded_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    /**
     * @return HasMany<SharedRun, $this>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(SharedRun::class);
    }

    /**
     * The currently active (not revoked) share link.
     *
     * @return HasOne<SharedRun, $this>
     */
    public function activeShare(): HasOne
    {
        return $this->hasOne(SharedRun::class)->whereNull('revoked_at')->latestOfMany();
    }

    /**
     * Select every column except the heavy samples payload.
     *
     * @param  Builder<Run>  $query
     */
    public function scopeWithoutSamples(Builder $query): void
    {
        $query->select(array_map(fn (string $column): string => $this->qualifyColumn($column), self::LIST_COLUMNS));
    }
}
