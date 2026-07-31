<?php

namespace App\Policies;

use App\Models\Allergy;
use App\Models\User;

class AllergyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Allergy $model): bool
    {
        return true;
    }

    public function delete(User $user, Allergy $model): bool
    {
        return true;
    }
}
