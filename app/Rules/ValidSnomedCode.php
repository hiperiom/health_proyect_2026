<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidSnomedCode implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) && preg_match('/^\d{6,18}$/', $value) === 1;
    }

    public function message(): string
    {
        return 'El código :attribute debe ser un SNOMED CT válido.';
    }
}
