<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EncounterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'encounter_class' => $this->encounter_class,
            'status' => $this->status,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'reason' => [
                'code' => $this->reason_code,
                'system' => $this->reason_system,
                'display' => $this->reason_display,
            ],
            'location' => [
                'id' => $this->location_id,
                'type' => $this->location_type,
            ],
            'country' => $this->country,
            'language' => $this->language,
            'notes' => $this->notes,
        ];
    }
}
