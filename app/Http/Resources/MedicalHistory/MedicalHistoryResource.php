<?php
namespace App\Http\Resources\MedicalHistory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'patient_id' => $this->patient_id,
            'patient_identifier' => $this->patient_identifier,
            'mrn' => $this->mrn,
            'encounter_id' => $this->encounter_id,
            'name' => $this->name,
            'description' => $this->description,
            'value' => $this->value,
            'condition' => [
                'code' => $this->condition_code,
                'system' => $this->condition_system,
                'display' => $this->condition_display,
            ],
            'observation' => [
                'code' => $this->observation_code,
                'system' => $this->observation_system,
                'display' => $this->observation_display,
            ],
            'onset_at' => $this->onset_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'country' => $this->country,
            'language' => $this->language,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}