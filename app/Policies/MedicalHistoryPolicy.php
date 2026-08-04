<?php
namespace App\Policies;
use App\Models\User;
use App\Models\MedicalHistory;

class MedicalHistoryPolicy {
    public function viewAny(User $user): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, MedicalHistory $model): bool { return true; }
    public function delete(User $user, MedicalHistory $model): bool { return true; }
}