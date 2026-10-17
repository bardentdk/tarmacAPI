<?php

namespace App\Models;

use App\Models\Concerns\SyncedRecord;
use Carbon\CarbonImmutable;
use Database\Factories\DtcRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $vehicle_id
 * @property string $uuid
 * @property string $code
 * @property string $kind
 * @property array<string, mixed>|null $freeze_frame
 * @property CarbonImmutable $read_at
 * @property CarbonImmutable|null $cleared_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $synced_at
 * @property-read Vehicle $vehicle
 */
#[Fillable(['uuid', 'code', 'kind', 'freeze_frame', 'read_at', 'cleared_at'])]
class DtcRecord extends Model
{
    /** @use HasFactory<DtcRecordFactory> */
    use HasFactory, SyncedRecord;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'freeze_frame' => 'array',
            'read_at' => 'datetime',
            'cleared_at' => 'datetime',
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
