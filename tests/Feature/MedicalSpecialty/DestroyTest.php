<?php

use App\Models\MedicalSpecialty;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $this->user = User::factory()->create();
    $this->user->roles()->sync([$this->role->id]);

    $this->item = MedicalSpecialty::factory()->create();
});

it('deletes a Medical Specialty', function () {
    $response = actingAs($this->user)
        ->delete(route('medical-specialties.destroy', $this->item))
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('medical-specialties.index'));

    $this->assertDatabaseMissing('medical-specialties', [
        'id' => $this->item->id,
    ]);
});

it('redirects guests to login on destroy', function () {
    delete(route('medical-specialties.destroy', $this->item))
        ->assertRedirect('/login');
});
