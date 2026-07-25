<?php

namespace App\Http\Requests\Patients;

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
            'dni' => ['required', 'string', 'min:1', 'max:50'],
            'ignore_id' => ['nullable', 'integer', 'exists:patients,id'],
        ];
    }
}
