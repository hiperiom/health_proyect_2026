<?php

use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('loads the doctors index page for the superusuario', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    actingAs($user)
        ->get(route('doctors.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('doctors/Index')
                ->has('items')
        );
});

it('redirects guests to the login page', function () {
    get(route('doctors.index'))->assertRedirect('/login');
});
