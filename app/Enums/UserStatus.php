<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
            self::Archived => 'Archivado',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::Active => 'bg-green-500/15 text-green-700 dark:text-green-300',
            self::Inactive => 'bg-yellow-500/15 text-yellow-700 dark:text-yellow-300',
            self::Archived => 'bg-gray-500/15 text-gray-700 dark:text-gray-300',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
