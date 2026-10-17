<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array (sync contract shape).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'vehicle_uuid' => $this->vehicle->uuid,
            'started_at' => Trip::isoUtc($this->started_at),
            'ended_at' => Trip::isoUtc($this->ended_at),
            'distance_km' => $this->distance_km,
            'health_score' => $this->health_score,
            'components' => $this->components ?? (object) [],
            'metrics' => $this->metrics ?? (object) [],
            'created_at' => Trip::isoUtc($this->created_at),
            'updated_at' => Trip::isoUtc($this->updated_at),
            'deleted_at' => Trip::isoUtc($this->deleted_at),
        ];
    }
}
