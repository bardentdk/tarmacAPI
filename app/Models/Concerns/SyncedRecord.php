<?php

namespace App\Models\Concerns;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Shared behaviour for records synced from the mobile app.
 *
 * Records are keyed by (user_id, uuid). `created_at` / `updated_at` hold the
 * client timestamps (used for last-write-wins) and `synced_at` holds the
 * server time of the last write (used by /sync/pull).
 *
 * @mixin Model
 */
trait SyncedRecord
{
    use SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Records written on the server at or after the given time.
     *
     * @param  Builder<static>  $query
     */
    public function scopeSyncedSince(Builder $query, ?DateTimeInterface $since): void
    {
        if ($since !== null) {
            $query->where($this->qualifyColumn('synced_at'), '>=', $since);
        }
    }

    /**
     * Format a date as an ISO-8601 UTC string (e.g. 2026-10-17T08:30:00Z).
     */
    public static function isoUtc(?DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return Carbon::instance($date)->utc()->toIso8601ZuluString();
    }
}
