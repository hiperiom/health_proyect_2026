<?php

namespace App\Enums;

enum Gender: string
{
    case Female = 'F';
    case Male = 'M';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Femenino',
            self::Male => 'Masculino',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
