<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DtcDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A plain-French explanation of an OBD-II trouble code.
 *
 * `make` / `engine` are lowercase, '' meaning "generic".
 *
 * @property int $id
 * @property string $code
 * @property string $make
 * @property string $engine
 * @property string $title
 * @property string $system
 * @property string $explanation
 * @property string $urgency
 * @property string|null $can_drive_note
 * @property string $source kb|ai
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['code', 'make', 'engine', 'title', 'system', 'explanation', 'urgency', 'can_drive_note', 'source'])]
class DtcDefinition extends Model
{
    /** @use HasFactory<DtcDefinitionFactory> */
    use HasFactory;

    /**
     * @return HasMany<DtcCause, $this>
     */
    public function causes(): HasMany
    {
        return $this->hasMany(DtcCause::class)->orderBy('position')->orderByDesc('probability');
    }
}
