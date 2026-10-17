<?php

namespace App\Http\Resources;

use App\Models\AppRelease;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Release offered to the nativephp/mobile-ota plugin.
 *
 * @mixin AppRelease
 */
class OtaReleaseResource extends JsonResource
{
    /**
     * Transform the resource into an array (OTA plugin contract).
     *
     * @return array{uuid: string, version: string, notes: string|null, sha256: string, size: int, commit: null, published_at: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'version' => $this->version,
            'notes' => $this->notes,
            'sha256' => $this->sha256,
            'size' => $this->size,
            'commit' => null,
            'published_at' => $this->publishedAtIso(),
        ];
    }
}
