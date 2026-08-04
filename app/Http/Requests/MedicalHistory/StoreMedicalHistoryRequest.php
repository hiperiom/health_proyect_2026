<?php
namespace App\Http\Requests\MedicalHistory;

use App\Rules\MedicalRecordNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['nullable', 'exists:users,id'],
            'encounter_id' => ['nullable', 'exists:encounters,id'],
            'patient_identifier' => ['required', 'string', 'max:50'],
            'mrn' => ['required', 'string', 'max:50', new MedicalRecordNumber(), 'unique:medical_histories,mrn'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'value' => ['nullable', 'string', 'max:255'],
            'condition_code' => ['nullable', 'string', 'max:128'],
            'condition_system' => ['nullable', 'string', 'max:128'],
            'condition_display' => ['nullable', 'string', 'max:255'],
            'observation_code' => ['nullable', 'string', 'max:128'],
            'observation_system' => ['nullable', 'string', 'max:128'],
            'observation_display' => ['nullable', 'string', 'max:255'],
            'onset_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
            'resolved_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after_or_equal:onset_at'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'language' => ['nullable', 'string', 'max:35'],
        ];
    }
}