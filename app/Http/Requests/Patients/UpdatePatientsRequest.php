<?php

namespace App\Http\Requests\Patients;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\PatientStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patientId = $this->route('item');

        return [
            'photo_path' => ['sometimes', 'nullable', 'string', 'max:500'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'nacionality' => ['sometimes', 'required', 'string', Rule::in(Nacionality::values())],
            'dni' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('patients', 'dni')->ignore($patientId)],
            'birth_date' => ['sometimes', 'required', 'date'],
            'gender' => ['sometimes', 'required', 'string', Rule::in(Gender::values())],
            'phone_mobile' => ['sometimes', 'required', 'string', 'max:30'],
            'phone_landline' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email'],
            'status' => ['sometimes', 'required', 'string', Rule::enum(PatientStatus::class)],
        ];
    }
}
