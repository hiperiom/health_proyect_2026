<?php

namespace App\Enums;

enum Nacionality: string
{
    case Venezuelan = 'V';
    case Foreigner = 'E';

    public function label(): string
    {
        return match ($this) {
            self::Venezuelan => 'Venezolano',
            self::Foreigner => 'Extranjero',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
