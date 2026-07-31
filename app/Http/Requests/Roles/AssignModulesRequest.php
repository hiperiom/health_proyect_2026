<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;

class AssignModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_ids' => ['present', 'array'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
        ];
    }
}
