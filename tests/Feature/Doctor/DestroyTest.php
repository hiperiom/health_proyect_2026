<?php

use App\Models\Doctor;
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

    $this->item = Doctor::factory()->create();
});

it('deletes a Doctor', function () {
    $response = actingAs($this->user)
        ->delete(route('doctors.destroy', $this->item))
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('doctors.index'));

    $this->assertDatabaseMissing('doctors', [
        'id' => $this->item->id,
    ]);
});

it('redirects guests to login on destroy', function () {
    delete(route('doctors.destroy', $this->item))
        ->assertRedirect('/login');
});
