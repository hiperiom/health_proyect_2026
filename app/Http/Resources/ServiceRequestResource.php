<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'encounter_id' => $this->encounter_id,
            'requester_id' => $this->requester_id,
            'code' => $this->code,
            'code_system' => $this->code_system,
            'code_display' => $this->code_display,
            'status' => $this->status,
            'priority' => $this->priority,
            'ordered_at' => $this->ordered_at?->toIso8601String(),
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'body_site' => $this->body_site,
            'notes' => $this->notes,
        ];
    }
}
