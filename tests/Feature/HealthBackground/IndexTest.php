<?php

use App\Models\HealthBackground;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $this->user = User::factory()->create();
    $this->user->roles()->sync([$this->role->id]);
});

it('loads the health-backgrounds index page for the superusuario', function () {
    HealthBackground::factory()->count(3)->create();

    actingAs($this->user)
        ->get(route('health-backgrounds.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('health-backgrounds/Index')
                ->has('items.data', 3)
        );
});

it('returns paginated results with the expected structure', function () {
    actingAs($this->user)
        ->get(route('health-backgrounds.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('health-backgrounds/Index')
                ->has('items.current_page')
                ->has('items.last_page')
                ->has('items.per_page')
                ->has('items.total')
                ->has('items.data')
                ->has('filters')
        );
});

it('redirects guests to the login page', function () {
    get(route('health-backgrounds.index'))->assertRedirect('/login');
});
