<?php

namespace App\Http\Requests\MedicalSpecialty;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalSpecialtyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
