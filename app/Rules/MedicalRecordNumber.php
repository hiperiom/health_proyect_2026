<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MedicalRecordNumber implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        if (! preg_match('/^[A-Z0-9]{2,10}-[0-9]{4}-[0-9]{3,10}-[0-9]$/', $value)) {
            return false;
        }

        [$prefix, $year, $sequence, $checkDigit] = explode('-', $value);

        $base = sprintf('%s-%s-%s', $prefix, $year, $sequence);
        $expected = $this->calculateLuhnDigit($base);

        return $expected === $checkDigit;
    }

    public function message(): string
    {
        return 'El número de historia clínica (MRN) no tiene un formato válido o el dígito de control es incorrecto.';
    }

    protected function calculateLuhnDigit(string $value): string
    {
        $digits = array_reverse(str_split(preg_replace('/[^0-9]/', '', $value)));
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $digit = (int) $digit;

            if ($index % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
