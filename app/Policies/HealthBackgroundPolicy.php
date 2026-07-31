<?php

namespace App\Policies;

use App\Models\HealthBackground;
use App\Models\User;

class HealthBackgroundPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, HealthBackground $model): bool
    {
        return true;
    }

    public function delete(User $user, HealthBackground $model): bool
    {
        return true;
    }
}
