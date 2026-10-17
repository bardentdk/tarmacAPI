<?php

namespace App\Models;

use Database\Factories\DtcCauseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dtc_definition_id
 * @property string $label
 * @property float $probability 0..1
 * @property string|null $part
 * @property string|null $difficulty
 * @property int|null $cost_min
 * @property int|null $cost_max
 * @property int $position
 */
#[Fillable(['label', 'probability', 'part', 'difficulty', 'cost_min', 'cost_max', 'position'])]
class DtcCause extends Model
{
    /** @use HasFactory<DtcCauseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'probability' => 'float',
            'cost_min' => 'integer',
            'cost_max' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DtcDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(DtcDefinition::class, 'dtc_definition_id');
    }
}
