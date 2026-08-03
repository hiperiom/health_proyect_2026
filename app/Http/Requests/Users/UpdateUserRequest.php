<?php

namespace App\Http\Requests\Users;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\PatientStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');
        $currentYear = (int) date('Y');

        return [
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'status' => ['sometimes', 'required', 'string', Rule::enum(PatientStatus::class)],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'nacionality' => ['sometimes', 'required', 'string', Rule::in(Nacionality::values())],
            'dni' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('patients', 'dni')->ignore($this->patientId())],
            'birth_date' => ['sometimes', 'required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:'.$currentYear.'-12-31'],
            'gender' => ['sometimes', 'required', 'string', Rule::in(Gender::values())],
            'phone_mobile' => ['sometimes', 'required', 'string', 'max:30'],
            'phone_landline' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    /**
     * Get the patient id linked to the user being updated, used to
     * ignore the current record when validating the unique dni.
     */
    private function patientId(): ?int
    {
        $user = $this->route('user');

        if ($user === null) {
            return null;
        }

        return $user->patient()->value('id');
    }
}
