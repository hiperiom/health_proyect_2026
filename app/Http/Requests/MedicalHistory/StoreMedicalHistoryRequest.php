<?php

namespace App\Http\Requests\MedicalHistory;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\UserStatus;
use App\Models\UsersProfile;
use App\Rules\MedicalRecordNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pid = $this->input('patient_identifier') ?? $this->input('dni') ?? null;
        $exists = false;

        if ($pid !== null) {
            $exists = UsersProfile::where('dni', $pid)->exists();
        }

        $this->merge(['patient_exists' => $exists]);

        if (! $this->has('name') || $this->input('name') === '') {
            $firstName = $this->input('first_name', '');
            $lastName = $this->input('last_name', '');
            $this->merge(['name' => trim($firstName.' '.$lastName)]);
        }
    }

    public function rules(): array
    {
        $currentYear = (int) date('Y');

        $patientExists = (bool) $this->input('patient_exists', false);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // Medical history core
            'patient_id' => ['nullable', 'exists:users,id'],
            'encounter_id' => ['nullable', 'exists:encounters,id'],
            'patient_identifier' => ['required', 'string', 'max:50'],
            'mrn' => ['required', 'string', 'max:50', new MedicalRecordNumber, 'unique:medical_histories,mrn'],

            // Clinical fields kept optional
            'condition_code' => ['nullable', 'string', 'max:128'],
            'condition_system' => ['nullable', 'string', 'max:128'],
            'condition_display' => ['nullable', 'string', 'max:255'],
            'observation_code' => ['nullable', 'string', 'max:128'],
            'observation_system' => ['nullable', 'string', 'max:128'],
            'observation_display' => ['nullable', 'string', 'max:255'],
            'onset_at' => ['nullable', 'date_format:Y-m-d\\TH:i:sP'],
            'resolved_at' => ['nullable', 'date_format:Y-m-d\\TH:i:sP', 'after_or_equal:onset_at'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'language' => ['nullable', 'string', 'max:35'],
        ];

        if (! $patientExists) {
            // require user creation fields when patient does not exist
            $rules = array_merge($rules, [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'status' => ['required', 'string', Rule::enum(UserStatus::class)],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'nacionality' => ['required', 'string', Rule::in(Nacionality::values())],
                'dni' => ['required', 'string', 'max:50', 'unique:users_profiles,dni'],
                'birth_date' => ['required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:'.$currentYear.'-12-31'],
                'gender' => ['required', 'string', Rule::in(Gender::values())],
                'phone_mobile' => ['required', 'string', 'max:30'],
                'phone_landline' => ['nullable', 'string', 'max:30'],
                'state_id' => ['nullable', 'integer', 'exists:states,id'],
                'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
                'address' => ['nullable', 'string', 'max:255'],
            ]);
        } else {
            // patient exists: ensure dni is present but may match existing
            $rules['dni'] = ['required', 'string', 'max:50'];
        }

        return $rules;
    }
}
