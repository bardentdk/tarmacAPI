<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
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
            'name' => $this->name,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'engine' => $this->engine,
            'fuel' => $this->fuel,
            'gearbox' => $this->gearbox,
            'drivetrain' => $this->drivetrain,
            'mass_kg' => $this->mass_kg,
            'tire_size' => $this->tire_size,
            'wheel_radius_m' => $this->wheel_radius_m,
            'cda' => $this->cda,
            'tuning_stage' => $this->tuning_stage,
            'redline_rpm' => $this->redline_rpm,
            'shift_light_start_rpm' => $this->shift_light_start_rpm,
            'k_cal' => $this->k_cal,
            'vin' => $this->vin,
            'created_at' => Vehicle::isoUtc($this->created_at),
            'updated_at' => Vehicle::isoUtc($this->updated_at),
            'deleted_at' => Vehicle::isoUtc($this->deleted_at),
        ];
    }
}
