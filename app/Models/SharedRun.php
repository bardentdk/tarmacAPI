<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SharedRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A public, revocable read-only link to a run.
 *
 * @property int $id
 * @property int $run_id
 * @property string $token
 * @property string|null $recipient
 * @property int $views
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Run $run
 */
#[Fillable(['token', 'recipient', 'revoked_at'])]
class SharedRun extends Model
{
    /** @use HasFactory<SharedRunFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'views' => 'integer',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Run, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    /**
     * Links that have not been revoked.
     *
     * @param  Builder<SharedRun>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('revoked_at');
    }

    /**
     * The public URL of the link.
     */
    public function url(): string
    {
        return route('shared-runs.show', $this->token);
    }
}
