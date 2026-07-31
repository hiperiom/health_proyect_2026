<?php

use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $this->user = User::factory()->create();
    $this->user->roles()->sync([$this->role->id]);
});

it('creates a Health Background through the form', function () {
    $payload = [
        'name' => 'Test Health Background',
        'description' => 'Test description',
        'value' => '100',
    ];

    $response = actingAs($this->user)
        ->post(route('health-backgrounds.store'), $payload)
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('health-backgrounds.index'));

    $this->assertDatabaseHas('health-backgrounds', [
        'name' => 'Test Health Background',
    ]);
});

it('requires name on store', function () {
    actingAs($this->user)
        ->post(route('health-backgrounds.store'), [
            'description' => 'No name provided',
        ])
        ->assertSessionHasErrors('name');
});

it('rejects a name longer than 255 chars on store', function () {
    actingAs($this->user)
        ->post(route('health-backgrounds.store'), [
            'name' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('name');
});

it('allows null description and value on store', function () {
    actingAs($this->user)
        ->post(route('health-backgrounds.store'), [
            'name' => 'Minimal record',
        ])
        ->assertStatus(302);

    $this->assertDatabaseHas('health-backgrounds', [
        'name' => 'Minimal record',
    ]);
});

it('redirects guests to login on store', function () {
    post(route('health-backgrounds.store'), ['name' => 'Guest attempt'])
        ->assertRedirect('/login');
});
