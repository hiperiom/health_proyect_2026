<?php

use App\Models\Municipality;
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

    $this->item = Municipality::factory()->create();
});

it('deletes a Municipality', function () {
    $response = actingAs($this->user)
        ->delete(route('municipalities.destroy', $this->item))
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('municipalities.index'));

    $this->assertDatabaseMissing('municipalities', [
        'id' => $this->item->id,
    ]);
});

it('redirects guests to login on destroy', function () {
    delete(route('municipalities.destroy', $this->item))
        ->assertRedirect('/login');
});
