<?php

namespace App\Http\Requests\Modules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // PascalCase: starts with uppercase, only letters/digits.
                'regex:/^[A-Z][A-Za-z0-9]*$/',
                Rule::unique('modules', 'name'),
            ],
            'display_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name must be PascalCase (e.g. "Patient", "OrderItem").',
        ];
    }
}
