<?php

namespace App\Http\Resources;

use App\Models\DtcRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DtcRecord
 */
class DtcRecordResource extends JsonResource
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
            'code' => $this->code,
            'kind' => $this->kind,
            'freeze_frame' => $this->freeze_frame,
            'read_at' => DtcRecord::isoUtc($this->read_at),
            'cleared_at' => DtcRecord::isoUtc($this->cleared_at),
            'updated_at' => DtcRecord::isoUtc($this->updated_at),
            'deleted_at' => DtcRecord::isoUtc($this->deleted_at),
        ];
    }
}
