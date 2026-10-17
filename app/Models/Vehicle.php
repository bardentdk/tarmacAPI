<?php

namespace App\Models;

use App\Models\Concerns\SyncedRecord;
use Carbon\CarbonImmutable;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $uuid
 * @property string $name
 * @property string $make
 * @property string $model
 * @property int|null $year
 * @property string|null $engine
 * @property string $fuel
 * @property string $gearbox
 * @property string $drivetrain
 * @property int $mass_kg
 * @property string|null $tire_size
 * @property float $wheel_radius_m
 * @property float $cda
 * @property string $tuning_stage
 * @property int $redline_rpm
 * @property int $shift_light_start_rpm
 * @property float $k_cal
 * @property string|null $vin
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $synced_at
 * @property-read int|null $runs_count
 * @property-read int|null $trips_count
 * @property-read int|null $active_dtc_count
 */
#[Fillable([
    'uuid', 'name', 'make', 'model', 'year', 'engine', 'fuel', 'gearbox', 'drivetrain', 'mass_kg',
    'tire_size', 'wheel_radius_m', 'cda', 'tuning_stage', 'redline_rpm', 'shift_light_start_rpm',
    'k_cal', 'vin',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SyncedRecord;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mass_kg' => 'integer',
            'wheel_radius_m' => 'float',
            'cda' => 'float',
            'redline_rpm' => 'integer',
            'shift_light_start_rpm' => 'integer',
            'k_cal' => 'float',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Run, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }

    /**
     * @return HasMany<DtcRecord, $this>
     */
    public function dtcRecords(): HasMany
    {
        return $this->hasMany(DtcRecord::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
