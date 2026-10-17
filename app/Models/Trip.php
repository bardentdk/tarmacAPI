<?php

namespace App\Models;

use App\Models\Concerns\SyncedRecord;
use Carbon\CarbonImmutable;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $vehicle_id
 * @property string $uuid
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $ended_at
 * @property float $distance_km
 * @property int $health_score
 * @property array{dtc?: float, misfire?: float, trims?: float, cooling?: float, electric?: float, readiness?: float, trends?: float}|null $components
 * @property array<string, mixed>|null $metrics
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $synced_at
 * @property-read Vehicle $vehicle
 */
#[Fillable(['uuid', 'started_at', 'ended_at', 'distance_km', 'health_score', 'components', 'metrics'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory, SyncedRecord;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'distance_km' => 'float',
            'health_score' => 'integer',
            'components' => 'array',
            'metrics' => 'array',
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
}
