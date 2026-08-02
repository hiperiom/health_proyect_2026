<?php

use App\Models\Doctor;
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

it('creates a Doctor through the form', function () {
    $payload = [
        'name' => 'Test Doctor',
        'description' => 'Test description',
        'value' => '100',
    ];

    $response = actingAs($this->user)
        ->post(route('doctors.store'), $payload)
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('doctors.index'));

    $this->assertDatabaseHas('doctors', [
        'name' => 'Test Doctor',
    ]);
});

it('requires name on store', function () {
    actingAs($this->user)
        ->post(route('doctors.store'), [
            'description' => 'No name provided',
        ])
        ->assertSessionHasErrors('name');
});

it('rejects a name longer than 255 chars on store', function () {
    actingAs($this->user)
        ->post(route('doctors.store'), [
            'name' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('name');
});

it('allows null description and value on store', function () {
    actingAs($this->user)
        ->post(route('doctors.store'), [
            'name' => 'Minimal record',
        ])
        ->assertStatus(302);

    $this->assertDatabaseHas('doctors', [
        'name' => 'Minimal record',
    ]);
});

it('redirects guests to login on store', function () {
    post(route('doctors.store'), ['name' => 'Guest attempt'])
        ->assertRedirect('/login');
});
