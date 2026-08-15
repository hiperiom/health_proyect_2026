<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function createModule(string $name): Module
{
    return Module::query()->create([
        'name' => $name,
        'display_name' => ucfirst($name),
        'description' => 'Gestión de '.$name,
    ]);
}

it('renders the error page when the active role lacks access to a module', function () {
    createModule('users');

    $role = Role::factory()->create(['slug' => 'recepcionista']);
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);
    $user->update(['active_role_id' => $role->id]);

    actingAs($user)
        ->get('/users')
        ->assertForbidden()
        ->assertInertia(
            fn ($page) => $page
                ->component('errors/ErrorPage')
                ->where('status', 403)
                ->where('message', 'No tienes acceso a este módulo.')
        );
});

it('renders the error page with the unauthenticated design for missing routes', function () {
    get('/ruta-inexistente')
        ->assertNotFound()
        ->assertInertia(
            fn ($page) => $page
                ->component('errors/ErrorPage')
                ->where('status', 404)
        );
});

it('keeps module access working for the superusuario', function () {
    createModule('users');

    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    actingAs($user)
        ->get('/users')
        ->assertOk();
});
