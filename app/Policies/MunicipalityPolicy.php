<?php

namespace App\Policies;

use App\Models\Municipality;
use App\Models\User;

class MunicipalityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Municipality $model): bool
    {
        return true;
    }

    public function delete(User $user, Municipality $model): bool
    {
        return true;
    }
}
