<?php

use App\Models\Role;
use App\Models\State;
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

    $this->item = State::factory()->create();
});

it('deletes a State', function () {
    $response = actingAs($this->user)
        ->delete(route('states.destroy', $this->item))
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('states.index'));

    $this->assertDatabaseMissing('states', [
        'id' => $this->item->id,
    ]);
});

it('redirects guests to login on destroy', function () {
    delete(route('states.destroy', $this->item))
        ->assertRedirect('/login');
});
