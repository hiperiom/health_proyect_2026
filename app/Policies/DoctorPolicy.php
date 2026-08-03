<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Doctor $model): bool
    {
        return true;
    }

    public function delete(User $user, Doctor $model): bool
    {
        return true;
    }
}
