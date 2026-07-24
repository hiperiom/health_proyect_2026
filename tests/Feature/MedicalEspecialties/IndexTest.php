<?php

use App\Models\MedicalEspecialties;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('loads the medical specialties index page for the superusuario', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    MedicalEspecialties::factory()->count(3)->create();

    actingAs($user)
        ->get(route('medicalespecialties.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('medicalespecialties/Index')
                ->has('items.data', 3)
        );
});

it('returns paginated results with the expected structure', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    actingAs($user)
        ->get(route('medicalespecialties.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('medicalespecialties/Index')
                ->has('items.current_page')
                ->has('items.last_page')
                ->has('items.per_page')
                ->has('items.total')
                ->has('items.data')
                ->has('filters')
        );
});

it('redirects guests to the login page', function () {
    get(route('medicalespecialties.index'))->assertRedirect('/login');
});
