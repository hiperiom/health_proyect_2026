<?php

namespace App\Services\MedicalHistory;

use App\Models\MedicalHistory;
use App\Models\Role;
use App\Models\User;
use App\Models\UsersProfile;
use App\Notifications\UserCreatedTemporaryPassword;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicalHistoryService
{
    public function getList(array $filters): LengthAwarePaginator
    {
        $query = MedicalHistory::query();
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create user, profile and medical history in a single transaction.
     */
    public function store(array $data): MedicalHistory
    {
        return DB::transaction(function () use ($data) {
            // If patient_id provided, attach to existing user/profile and skip user creation
            if (! empty($data['patient_id'])) {
                // ensure the user's profile MRN is updated atomically
                $patientProfile = UsersProfile::where('user_id', $data['patient_id'])
                    ->orWhere('dni', $data['patient_identifier'] ?? null)
                    ->first();

                if ($patientProfile !== null) {
                    if ($patientProfile->mrn && $patientProfile->mrn !== ($data['mrn'] ?? null)) {
                        throw new \RuntimeException('El MRN ingresado no coincide con el MRN del paciente.');
                    }

                    if (empty($patientProfile->mrn) && ! empty($data['mrn'])) {
                        // Use query builder update to avoid any model-level side effects
                        DB::table('users_profiles')
                            ->where('id', $patientProfile->id)
                            ->update(['mrn' => $data['mrn']]);
                        // refresh model instance
                        $patientProfile->refresh();
                    }
                }

                $mhData = $data;
                foreach (['email', 'status', 'first_name', 'last_name', 'nacionality', 'dni', 'birth_date', 'gender', 'phone_mobile', 'phone_landline', 'state_id', 'municipality_id', 'address'] as $k) {
                    if (array_key_exists($k, $mhData)) {
                        unset($mhData[$k]);
                    }
                }

                return MedicalHistory::create($mhData + ['patient_id' => $data['patient_id']]);
            }
            // Create user with temporary password
            $temporaryPassword = Str::password(16);

            $user = User::create([
                'name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
                'email' => $data['email'],
                'status' => $data['status'] ?? null,
                'password' => $temporaryPassword,
                'password_updated' => false,
            ]);

            // Assign default role 'paciente'
            $role = Role::query()->firstOrCreate(
                ['slug' => 'paciente'],
                ['name' => 'Paciente']
            );
            $user->roles()->sync([$role->id]);

            // Create user profile
            UsersProfile::create([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'nacionality' => $data['nacionality'] ?? null,
                'dni' => $data['dni'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone_mobile' => $data['phone_mobile'] ?? null,
                'phone_landline' => $data['phone_landline'] ?? null,
                'state_id' => $data['state_id'] ?? null,
                'municipality_id' => $data['municipality_id'] ?? null,
                'address' => $data['address'] ?? null,
                'user_id' => $user->id,
                'created_by_user_id' => auth()->id(),
                'mrn' => $data['mrn'] ?? null,
            ]);

            // Create medical history record (remove user-specific fields)
            $mhData = $data;
            foreach (['email', 'status', 'first_name', 'last_name', 'nacionality', 'dni', 'birth_date', 'gender', 'phone_mobile', 'phone_landline', 'state_id', 'municipality_id', 'address'] as $k) {
                if (array_key_exists($k, $mhData)) {
                    unset($mhData[$k]);
                }
            }
            $mhData['patient_id'] = $user->id;

            $medicalHistory = MedicalHistory::create($mhData);

            // Notify user
            $user->notify(new UserCreatedTemporaryPassword($temporaryPassword));

            return $medicalHistory;
        });
    }

    public function update(MedicalHistory $item, array $data): MedicalHistory
    {
        $item->update($data);

        return $item;
    }

    public function destroy(MedicalHistory $item): bool
    {
        return $item->delete();
    }
}
