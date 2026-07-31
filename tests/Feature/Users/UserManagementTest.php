<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserCreatedTemporaryPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('user edit works without password fields', function () {
    $user = User::factory()->create();
    $paciente = Role::query()->where('slug', UserRole::Paciente->value)->firstOrFail();
    $user->roles()->attach($paciente);

    $this->actingAs($user)
        ->patch(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('admin can reset user password and send temporary password notification', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('users.reset-password', $user))
        ->assertRedirect(route('users.index'));

    Notification::assertSentTo($user, UserCreatedTemporaryPassword::class);
    $this->assertFalse($user->refresh()->password_updated);
});

test('user is created with the assigned role and stored in users_roles', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $doctorRole = Role::query()->where('slug', UserRole::Doctor->value)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Juan Perez',
            'email' => 'juan.perez@example.com',
            'role_ids' => [$doctorRole->id],
        ])
        ->assertRedirect(route('users.index'));

    $created = User::query()->where('email', 'juan.perez@example.com')->firstOrFail();

    $this->assertDatabaseHas('roles', [
        'slug' => UserRole::Doctor->value,
    ]);

    $this->assertTrue(
        $created->roles()->where('slug', UserRole::Doctor->value)->exists()
    );
});

test('user role is updated through the pivot table', function () {
    $user = User::factory()->create();
    $paciente = Role::query()->where('slug', UserRole::Paciente->value)->firstOrFail();
    $user->roles()->attach($paciente);

    $admin = User::factory()->create();
    $doctorRole = Role::query()->where('slug', UserRole::Doctor->value)->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('users.update', $user), [
            'role_ids' => [$doctorRole->id],
        ])
        ->assertRedirect(route('users.index'));

    $this->assertTrue(
        $user->fresh()->roles()->where('slug', UserRole::Doctor->value)->exists()
    );

    $this->assertFalse(
        $user->fresh()->roles()->where('slug', UserRole::Paciente->value)->exists()
    );
});

test('users can be filtered by role slug in the index', function () {
    $admin = User::factory()->create();
    $doctorRole = Role::query()->where('slug', UserRole::Doctor->value)->firstOrFail();
    $pacienteRole = Role::query()->where('slug', UserRole::Paciente->value)->firstOrFail();

    $doctor = User::factory()->create();
    $doctor->roles()->attach($doctorRole);

    $paciente = User::factory()->create();
    $paciente->roles()->attach($pacienteRole);

    $this->actingAs($admin)
        ->get(route('users.index', ['role' => UserRole::Doctor->value]))
        ->assertInertia(fn ($page) => $page
            ->where('filters.role', UserRole::Doctor->value)
            ->has('items.data', 1)
            ->where('items.data.0.id', $doctor->id)
        );
});

test('creating a user with an invalid role id fails validation', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Bad Role',
            'email' => 'bad.role@example.com',
            'role_ids' => [9999],
        ])
        ->assertSessionHasErrors('role_ids.0');
});
