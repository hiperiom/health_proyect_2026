<?php

namespace App\Policies;

use App\Models\MedicalSpecialty;
use App\Models\User;

class MedicalSpecialtyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MedicalSpecialty $model): bool
    {
        return true;
    }

    public function delete(User $user, MedicalSpecialty $model): bool
    {
        return true;
    }
}
