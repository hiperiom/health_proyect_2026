<?php

use App\Models\MedicalHistory;
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

it('loads the medical-histories index page for the superusuario', function () {
    MedicalHistory::factory()->count(3)->create();

    actingAs($this->user)
        ->get(route('medical-histories.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('medical-histories/Index')
                ->has('items.data', 3)
        );
});

it('returns paginated results with the expected structure', function () {
    actingAs($this->user)
        ->get(route('medical-histories.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('medical-histories/Index')
                ->has('items.current_page')
                ->has('items.last_page')
                ->has('items.per_page')
                ->has('items.total')
                ->has('items.data')
                ->has('filters')
        );
});

it('redirects guests to the login page', function () {
    get(route('medical-histories.index'))->assertRedirect('/login');
});
