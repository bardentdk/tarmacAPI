<?php

namespace App\Http\Resources;

use App\Models\AppRelease;
use App\Services\Ota\ReleaseCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full release record for the publishing (admin) API.
 *
 * @mixin AppRelease
 */
class AppReleaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'project' => $this->project,
            'platform' => $this->platform,
            'channel' => $this->channel->value,
            'kind' => $this->kind->value,
            'fingerprint' => $this->fingerprint,
            'version' => $this->version,
            'version_code' => $this->version_code,
            'notes' => $this->notes,
            'sha256' => $this->sha256,
            'size' => $this->size,
            'path' => $this->path,
            'native_signature' => $this->native_signature,
            'mandatory' => $this->mandatory,
            'is_active' => $this->is_active,
            'published_at' => $this->publishedAtIso(),
            'download_url' => $this->is_active ? app(ReleaseCatalog::class)->downloadUrl($this->resource) : null,
            'created_at' => $this->created_at?->utc()->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->utc()->toIso8601ZuluString(),
        ];
    }
}
