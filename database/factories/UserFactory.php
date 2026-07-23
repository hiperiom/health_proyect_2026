<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Create the user with the "Superusuario" role assigned.
     */
    public function superusuario(): static
    {
        return $this->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate(
                ['slug' => 'superusuario'],
                ['name' => 'Superusuario']
            );
            $user->roles()->sync([$role->id]);
        })->state(fn (): array => [
            'name' => 'Superusuario',
            'email' => 'superusuario@test.com',
            'password' => Hash::make('12345678'),
        ]);
    }

    /**
     * Create the user with the "Paciente" role assigned.
     */
    public function paciente(): static
    {
        return $this->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate(
                ['slug' => 'paciente'],
                ['name' => 'Paciente']
            );
            $user->roles()->sync([$role->id]);
        })->state(fn (): array => [
            'name' => 'Paciente',
            'email' => 'paciente@test.com',
            'password' => Hash::make('12345678'),
        ]);
    }

    /**
     * Create the user with the "Doctor" role assigned.
     */
    public function doctor(): static
    {
        return $this->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate(
                ['slug' => 'doctor'],
                ['name' => 'Doctor']
            );
            $user->roles()->sync([$role->id]);
        })->state(fn (): array => [
            'name' => 'Doctor',
            'email' => 'doctor@test.com',
            'password' => Hash::make('12345678'),
        ]);
    }

    /**
     * Create the user with the "Administrador" role assigned.
     */
    public function administrador(): static
    {
        return $this->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate(
                ['slug' => 'administrador'],
                ['name' => 'Administrador']
            );
            $user->roles()->sync([$role->id]);
        })->state(fn (): array => [
            'name' => 'Administrador',
            'email' => 'admin@test.com',
            'password' => Hash::make('12345678'),
        ]);
    }

    /**
     * Create the user with the "Enfermería" role assigned.
     */
    public function enfermeria(): static
    {
        return $this->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate(
                ['slug' => 'enfermeria'],
                ['name' => 'Enfermería']
            );
            $user->roles()->sync([$role->id]);
        })->state(fn (): array => [
            'name' => 'Enfermería',
            'email' => 'enfermeria@test.com',
            'password' => Hash::make('12345678'),
        ]);
    }

    /**
     * Create the user with the "Asistencial" role assigned.
     */
    public function asistencial(): static
    {
        return $this->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate(
                ['slug' => 'asistencial'],
                ['name' => 'Asistencial']
            );
            $user->roles()->sync([$role->id]);
        })->state(fn (): array => [
            'name' => 'Asistencial',
            'email' => 'asistencial@test.com',
            'password' => Hash::make('12345678'),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
