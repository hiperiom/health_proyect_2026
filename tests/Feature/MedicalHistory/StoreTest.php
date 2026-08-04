<?php

use App\Models\MedicalHistory;
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

it('creates a Medical History through the form', function () {
    $payload = [
        'name' => 'Test Medical History',
        'description' => 'Test description',
        'value' => '100',
    ];

    $response = actingAs($this->user)
        ->post(route('medical-histories.store'), $payload)
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('medical-histories.index'));

    $this->assertDatabaseHas('medical-histories', [
        'name' => 'Test Medical History',
    ]);
});

it('requires name on store', function () {
    actingAs($this->user)
        ->post(route('medical-histories.store'), [
            'description' => 'No name provided',
        ])
        ->assertSessionHasErrors('name');
});

it('rejects a name longer than 255 chars on store', function () {
    actingAs($this->user)
        ->post(route('medical-histories.store'), [
            'name' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('name');
});

it('allows null description and value on store', function () {
    actingAs($this->user)
        ->post(route('medical-histories.store'), [
            'name' => 'Minimal record',
        ])
        ->assertStatus(302);

    $this->assertDatabaseHas('medical-histories', [
        'name' => 'Minimal record',
    ]);
});

it('redirects guests to login on store', function () {
    post(route('medical-histories.store'), ['name' => 'Guest attempt'])
        ->assertRedirect('/login');
});
