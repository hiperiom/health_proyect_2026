<?php

namespace App\Enums;

enum UserRole: string
{
    case Paciente = 'paciente';
    case Doctor = 'doctor';
    case Administrador = 'administrador';
    case Superusuario = 'superusuario';
    case Enfermeria = 'enfermeria';
    case Asistencial = 'asistencial';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paciente => 'Paciente',
            self::Doctor => 'Doctor',
            self::Administrador => 'Administrador',
            self::Superusuario => 'Superusuario',
            self::Enfermeria => 'Enfermería',
            self::Asistencial => 'Asistencial',
        };
    }

    /** @return array<int, string> */
    public static function slugs(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
