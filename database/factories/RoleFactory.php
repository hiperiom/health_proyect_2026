<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    public function paciente(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Paciente',
            'slug' => 'paciente',
        ]);
    }

    public function doctor(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Doctor',
            'slug' => 'doctor',
        ]);
    }

    public function administrador(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Administrador',
            'slug' => 'administrador',
        ]);
    }

    public function superusuario(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Superusuario',
            'slug' => 'superusuario',
        ]);
    }

    public function enfermeria(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Enfermería',
            'slug' => 'enfermeria',
        ]);
    }

    public function asistencial(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Asistencial',
            'slug' => 'asistencial',
        ]);
    }
}
