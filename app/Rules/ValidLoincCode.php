<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidLoincCode implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) && preg_match('/^\d{1,5}(-\d+)?$/', $value) === 1;
    }

    public function message(): string
    {
        return 'El código :attribute debe ser un LOINC válido.';
    }
}
