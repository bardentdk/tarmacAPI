<?php

namespace App\Http\Resources;

use App\Models\Run;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Run
 */
class RunResource extends JsonResource
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
            'mode' => $this->mode,
            'result_s' => $this->result_s,
            'trap_kmh' => $this->trap_kmh,
            'distance_m' => $this->distance_m,
            'sixty_ft_s' => $this->sixty_ft_s,
            'reaction_s' => $this->reaction_s,
            'splits' => $this->splits ?? [],
            'power_hp' => $this->power_hp,
            'power_ci_hp' => $this->power_ci_hp,
            'torque_nm' => $this->torque_nm,
            'peak_rpm' => $this->peak_rpm,
            'curve' => $this->curve,
            'conditions' => $this->conditions ?? (object) [],
            'source' => $this->source,
            'accuracy_s' => $this->accuracy_s,
            'is_record' => $this->is_record,
            'notes' => $this->notes,
            'samples' => $this->samples,
            'recorded_at' => Run::isoUtc($this->recorded_at),
            'created_at' => Run::isoUtc($this->created_at),
            'updated_at' => Run::isoUtc($this->updated_at),
            'deleted_at' => Run::isoUtc($this->deleted_at),
        ];
    }
}
