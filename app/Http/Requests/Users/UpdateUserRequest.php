<?php

namespace App\Http\Requests\Users;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id ?? $this->route('user');
        $currentYear = (int) date('Y');

        return [
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'status' => ['sometimes', 'required', 'string', Rule::enum(UserStatus::class)],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'nacionality' => ['sometimes', 'required', 'string', Rule::in(Nacionality::values())],
            'dni' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users_profiles', 'dni')->ignore($this->usersProfileId())],
            'birth_date' => ['sometimes', 'required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:'.$currentYear.'-12-31'],
            'gender' => ['sometimes', 'required', 'string', Rule::in(Gender::values())],
            'phone_mobile' => ['sometimes', 'required', 'string', 'max:30'],
            'phone_landline' => ['sometimes', 'nullable', 'string', 'max:30'],
            'state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'municipality_id' => ['sometimes', 'nullable', 'integer', 'exists:municipalities,id'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    /**
     * Get the user profile id linked to the user being updated, used to
     * ignore the current record when validating the unique dni.
     */
    private function usersProfileId(): ?int
    {
        $user = $this->route('user');

        if ($user === null) {
            return null;
        }

        return $user->usersProfile()->value('id');
    }
}
