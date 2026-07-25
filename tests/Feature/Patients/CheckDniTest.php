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

it('returns exists false when no patient matches the dni', function () {
    actingAs($this->user)
        ->get(route('patients.check-dni', ['dni' => '99999999']))
        ->assertOk()
        ->assertJson([
            'exists' => false,
            'patient' => null,
        ]);
});

it('returns exists true with patient data when the dni already exists', function () {
    $patient = Patients::factory()->create([
        'dni' => '22014778',
        'first_name' => 'María',
        'last_name' => 'González',
    ]);

    actingAs($this->user)
        ->get(route('patients.check-dni', ['dni' => '22014778']))
        ->assertOk()
        ->assertJson([
            'exists' => true,
            'patient' => [
                'id' => $patient->id,
                'dni' => '22014778',
                'firstName' => 'María',
                'lastName' => 'González',
            ],
        ]);
});

it('ignores the patient with the provided ignore_id when checking dni', function () {
    $patient = Patients::factory()->create(['dni' => '12345678']);

    actingAs($this->user)
        ->get(route('patients.check-dni', [
            'dni' => '12345678',
            'ignore_id' => $patient->id,
        ]))
        ->assertOk()
        ->assertJson([
            'exists' => false,
            'patient' => null,
        ]);
});

it('requires dni parameter', function () {
    actingAs($this->user)
        ->get(route('patients.check-dni'))
        ->assertSessionHasErrors('dni');
});

it('requires dni to be a non empty string', function () {
    actingAs($this->user)
        ->get(route('patients.check-dni', ['dni' => '']))
        ->assertSessionHasErrors('dni');
});

it('redirects guests to login', function () {
    get(route('patients.check-dni', ['dni' => '22014778']))
        ->assertRedirect('/login');
});
