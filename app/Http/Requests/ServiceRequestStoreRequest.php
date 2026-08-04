<?php

namespace App\Http\Requests;

use App\Rules\ValidLoincCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'encounter_id' => ['required', 'exists:encounters,id'],
            'requester_id' => ['nullable', 'exists:users,id'],
            'code' => ['required', 'string', 'max:64', new ValidLoincCode()],
            'code_system' => ['required', 'string', 'max:16', Rule::in(['LOINC'])],
            'code_display' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:32'],
            'priority' => ['nullable', 'string', 'max:32'],
            'ordered_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            'scheduled_for' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
            'body_site' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
