<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('item');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ],
            'color_class' => ['nullable', 'string', 'max:255'],
            'text_class' => ['nullable', 'string', 'max:255'],
            'icon_svg' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'slug.unique' => 'Ya existe un rol con este slug.',
        ];
    }
}
