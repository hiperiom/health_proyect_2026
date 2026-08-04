<?php

namespace App\Http\Requests\Users;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['state_id', 'municipality_id'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
        if ($this->has('address') && $this->input('address') === '') {
            $this->merge(['address' => null]);
        }
        if ($this->has('phone_landline') && $this->input('phone_landline') === '') {
            $this->merge(['phone_landline' => null]);
        }
    }

    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
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
        ];
    }
}
