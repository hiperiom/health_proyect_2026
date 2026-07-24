<?php

namespace App\Http\Requests\Patients;

use Illuminate\Foundation\Http\FormRequest;

class UploadPatientPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Debe seleccionar una imagen.',
            'photo.image' => 'El archivo debe ser una imagen válida.',
            'photo.mimes' => 'Solo se permiten imágenes en formato PNG o JPG.',
            'photo.max' => 'La imagen no puede pesar más de 5 MB.',
        ];
    }
}
