<?php

namespace App\Http\Resources;

use App\Models\AppRelease;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Native APK offered to the app's in-app updater.
 *
 * @mixin AppRelease
 */
class AppBuildResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{uuid: string, version: string, version_code: int|null, notes: string|null, sha256: string, size: int, mandatory: bool, published_at: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'version' => $this->version,
            'version_code' => $this->version_code,
            'notes' => $this->notes,
            'sha256' => $this->sha256,
            'size' => $this->size,
            'mandatory' => $this->mandatory,
            'published_at' => $this->publishedAtIso(),
        ];
    }
}
