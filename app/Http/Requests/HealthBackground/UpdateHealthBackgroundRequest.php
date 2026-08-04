<?php
namespace App\Http\Requests\HealthBackground;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthBackgroundRequest extends FormRequest {
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'value' => ['nullable', 'string', 'max:255'],
        ];
    }
}