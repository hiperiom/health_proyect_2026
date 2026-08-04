<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncounterStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:users,id'],
            'encounter_class' => ['required', Rule::in(['AMB', 'IMP'])],
            'status' => ['required', 'string', 'max:32'],
            'start_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            'end_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after_or_equal:start_at'],
            'reason_code' => ['nullable', 'string', 'max:128'],
            'reason_system' => ['nullable', 'string', 'max:128'],
            'reason_display' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', 'integer', 'exists:users,id'],
            'location_type' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'language' => ['nullable', 'string', 'max:35'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
