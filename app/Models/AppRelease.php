<?php

namespace App\Models;

use App\Enums\ReleaseChannel;
use App\Enums\ReleaseKind;
use Carbon\CarbonImmutable;
use Database\Factories\AppReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A published build of the mobile app: an OTA bundle or a native APK.
 *
 * @property int $id
 * @property string $uuid
 * @property string $project
 * @property string $platform
 * @property ReleaseChannel $channel
 * @property ReleaseKind $kind
 * @property string|null $fingerprint
 * @property string $version
 * @property int|null $version_code
 * @property string|null $notes
 * @property string $sha256
 * @property int $size
 * @property string $path
 * @property string|null $native_signature
 * @property bool $mandatory
 * @property bool $is_active
 * @property CarbonImmutable $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'uuid', 'project', 'platform', 'channel', 'kind', 'fingerprint', 'version', 'version_code', 'notes',
    'sha256', 'size', 'path', 'native_signature', 'mandatory', 'is_active', 'published_at',
])]
class AppRelease extends Model
{
    /** @use HasFactory<AppReleaseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ReleaseChannel::class,
            'kind' => ReleaseKind::class,
            'version_code' => 'integer',
            'size' => 'integer',
            'mandatory' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Releases currently offered to devices.
     *
     * @param  Builder<AppRelease>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Releases of the given kind.
     *
     * @param  Builder<AppRelease>  $query
     */
    public function scopeOfKind(Builder $query, ReleaseKind $kind): void
    {
        $query->where('kind', $kind->value);
    }

    /**
     * Download file name presented to the client.
     */
    public function downloadName(): string
    {
        return sprintf('%s-%s.%s', $this->project, $this->version, $this->kind->extension());
    }

    /**
     * Publication date as an ISO-8601 UTC ("Z") string.
     */
    public function publishedAtIso(): string
    {
        return $this->published_at->utc()->toIso8601ZuluString();
    }
}
