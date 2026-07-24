<?php

namespace App\Http\Requests\MedicalEspecialties;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalEspecialtiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('medical_especialties', 'name')],
            'description' => ['nullable', 'string'],
        ];
    }
}
