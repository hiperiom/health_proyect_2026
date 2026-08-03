<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class CheckDniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'max:50'],
            'ignore_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
