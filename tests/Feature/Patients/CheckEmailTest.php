<?php

use App\Models\Patients;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $this->user = User::factory()->create();
    $this->user->roles()->sync([$this->role->id]);
});

it('returns exists false when no patient matches the email', function () {
    actingAs($this->user)
        ->get(route('patients.check-email', ['email' => 'novisible@example.com']))
        ->assertOk()
        ->assertJson([
            'exists' => false,
            'patient' => null,
        ]);
});

it('returns exists true with patient data when the email already exists', function () {
    $patient = Patients::factory()->create([
        'email' => 'juan@example.com',
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
    ]);

    actingAs($this->user)
        ->get(route('patients.check-email', ['email' => 'juan@example.com']))
        ->assertOk()
        ->assertJson([
            'exists' => true,
            'patient' => [
                'id' => $patient->id,
                'email' => 'juan@example.com',
                'firstName' => 'Juan',
                'lastName' => 'Pérez',
            ],
        ]);
});

it('matches emails case insensitively', function () {
    $patient = Patients::factory()->create([
        'email' => 'maria@example.com',
    ]);

    actingAs($this->user)
        ->get(route('patients.check-email', ['email' => 'MARIA@EXAMPLE.COM']))
        ->assertOk()
        ->assertJson([
            'exists' => true,
            'patient' => [
                'id' => $patient->id,
            ],
        ]);
});

it('ignores the patient with the provided ignore_id when checking email', function () {
    $patient = Patients::factory()->create(['email' => 'juan@example.com']);

    actingAs($this->user)
        ->get(route('patients.check-email', [
            'email' => 'juan@example.com',
            'ignore_id' => $patient->id,
        ]))
        ->assertOk()
        ->assertJson([
            'exists' => false,
            'patient' => null,
        ]);
});

it('requires email parameter', function () {
    actingAs($this->user)
        ->get(route('patients.check-email'))
        ->assertSessionHasErrors('email');
});

it('requires email to be a valid email', function () {
    actingAs($this->user)
        ->get(route('patients.check-email', ['email' => 'no-es-un-email']))
        ->assertSessionHasErrors('email');
});

it('redirects guests to login', function () {
    get(route('patients.check-email', ['email' => 'test@example.com']))
        ->assertRedirect('/login');
});
