<?php

namespace App\Http\Requests\Patients;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\PatientStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo_path' => ['nullable', 'string', 'max:500'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'nacionality' => ['required', 'string', Rule::in(Nacionality::values())],
            'dni' => ['required', 'string', 'max:50', 'unique:patients,dni'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'string', Rule::in(Gender::values())],
            'phone_mobile' => ['required', 'string', 'max:30'],
            'phone_landline' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'status' => ['required', 'string', Rule::enum(PatientStatus::class)],
        ];
    }
}
