<?php

namespace App\Policies;

use App\Models\State;
use App\Models\User;

class StatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, State $model): bool
    {
        return true;
    }

    public function delete(User $user, State $model): bool
    {
        return true;
    }
}
