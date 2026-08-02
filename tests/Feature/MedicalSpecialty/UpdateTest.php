<?php

use App\Models\MedicalSpecialty;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $this->user = User::factory()->create();
    $this->user->roles()->sync([$this->role->id]);

    $this->item = MedicalSpecialty::factory()->create([
        'name' => 'Original name',
    ]);
});

it('updates a Medical Specialty through the form', function () {
    $response = actingAs($this->user)
        ->patch(route('medical-specialties.update', $this->item), [
            'name' => 'Updated name',
            'description' => 'Updated description',
            'value' => '200',
        ])
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('medical-specialties.index'));

    $this->assertDatabaseHas('medical-specialties', [
        'id' => $this->item->id,
        'name' => 'Updated name',
    ]);
});

it('requires name on update', function () {
    actingAs($this->user)
        ->patch(route('medical-specialties.update', $this->item), [
            'description' => 'Forgot the name',
        ])
        ->assertSessionHasErrors('name');
});

it('rejects a name longer than 255 chars on update', function () {
    actingAs($this->user)
        ->patch(route('medical-specialties.update', $this->item), [
            'name' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('name');
});

it('redirects guests to login on update', function () {
    patch(route('medical-specialties.update', $this->item), ['name' => 'Guest attempt'])
        ->assertRedirect('/login');
});
