<?php

namespace App\Http\Requests\MedicalEspecialties;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalEspecialtiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('item');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('medical_especialties', 'name')->ignore($itemId)],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
