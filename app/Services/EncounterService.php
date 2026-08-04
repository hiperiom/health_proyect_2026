<?php

namespace App\Services;

use App\Models\Encounter;

class EncounterService
{
    public function create(array $data): Encounter
    {
        return Encounter::create($data);
    }
}
