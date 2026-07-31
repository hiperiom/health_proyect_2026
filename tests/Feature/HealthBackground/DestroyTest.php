<?php

use App\Models\HealthBackground;
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

    $this->item = HealthBackground::factory()->create();
});

it('deletes a Health Background', function () {
    $response = actingAs($this->user)
        ->delete(route('health-backgrounds.destroy', $this->item))
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('health-backgrounds.index'));

    $this->assertDatabaseMissing('health-backgrounds', [
        'id' => $this->item->id,
    ]);
});

it('redirects guests to login on destroy', function () {
    delete(route('health-backgrounds.destroy', $this->item))
        ->assertRedirect('/login');
});
