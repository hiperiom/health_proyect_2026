<?php

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\UserStatus;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Models\UsersProfile;
use App\Notifications\UserCreatedTemporaryPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

/**
 * Payload válido base para el formulario fusionado de usuarios.
 *
 * @return array<string, mixed>
 */
function validUserPayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'nuevo.usuario@example.com',
        'status' => UserStatus::Active->value,
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'nacionality' => Nacionality::Venezuelan->value,
        'dni' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => Gender::Male->value,
        'phone_mobile' => '04141234567',
        'phone_landline' => '',
    ], $overrides);
}

test('store creates a user with the user profile and default paciente role', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), validUserPayload())
        ->assertRedirect(route('users.index'));

    $created = User::query()->where('email', 'nuevo.usuario@example.com')->firstOrFail();

    $this->assertDatabaseHas('users', [
        'id' => $created->id,
        'name' => 'Juan Pérez',
        'email' => 'nuevo.usuario@example.com',
        'status' => UserStatus::Active->value,
    ]);

    $this->assertDatabaseHas('users_profiles', [
        'user_id' => $created->id,
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'dni' => '12345678',
    ]);

    $this->assertTrue(
        $created->roles()->where('slug', UserRole::Paciente->value)->exists()
    );

    Notification::assertSentTo($created, UserCreatedTemporaryPassword::class);
});

test('store fails validation when required fields are missing', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [])
        ->assertSessionHasErrors([
            'email',
            'status',
            'first_name',
            'last_name',
            'nacionality',
            'dni',
            'birth_date',
            'gender',
            'phone_mobile',
        ]);
});

test('store fails when the email is already taken', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), validUserPayload(['email' => $admin->email]))
        ->assertSessionHasErrors('email');
});

test('store fails when the dni is already taken', function () {
    $admin = User::factory()->create();

    $existing = User::factory()->create();
    UsersProfile::factory()->create(['dni' => '99887766', 'user_id' => $existing->id, 'created_by_user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('users.store'), validUserPayload(['dni' => '99887766']))
        ->assertSessionHasErrors('dni');
});

test('store fails when birth_date is before the year 1900', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), validUserPayload(['birth_date' => '1899-12-31']))
        ->assertSessionHasErrors('birth_date');
});

test('store fails when birth_date is after the current year', function () {
    $admin = User::factory()->create();
    $nextYear = ((int) date('Y')) + 1;

    $this->actingAs($admin)
        ->post(route('users.store'), validUserPayload(['birth_date' => $nextYear.'-01-01']))
        ->assertSessionHasErrors('birth_date');
});

test('update edits the user account and the user profile', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
    $profile = UsersProfile::factory()->create([
        'user_id' => $user->id,
        'created_by_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('users.update', $user), [
            'email' => 'actualizado@example.com',
            'status' => UserStatus::Inactive->value,
            'first_name' => 'Carlos',
            'last_name' => 'Gómez',
            'nacionality' => Nacionality::Foreigner->value,
            'dni' => '87654321',
            'birth_date' => '1985-03-20',
            'gender' => Gender::Female->value,
            'phone_mobile' => '04241234567',
            'phone_landline' => '02121234567',
        ])
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Carlos Gómez',
        'email' => 'actualizado@example.com',
        'status' => UserStatus::Inactive->value,
    ]);

    $this->assertDatabaseHas('users_profiles', [
        'id' => $profile->id,
        'first_name' => 'Carlos',
        'last_name' => 'Gómez',
        'dni' => '87654321',
        'phone_landline' => '02121234567',
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

test('index returns user fields and select options', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertInertia(fn ($page) => $page
            ->has('items')
            ->has('availableStatuses')
            ->has('availableNacionalities')
            ->has('availableGenders')
        );
});

test('check-email returns exists false for a new email', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.check-email', ['email' => 'nuevo@example.com']))
        ->assertOk()
        ->assertJson(['exists' => false]);
});

test('check-email returns exists true for a taken email', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.check-email', ['email' => $admin->email]))
        ->assertOk()
        ->assertJson(['exists' => true]);
});

test('check-email ignores the provided user id', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.check-email', ['email' => $admin->email, 'ignore_id' => $admin->id]))
        ->assertOk()
        ->assertJson(['exists' => false]);
});

test('check-email requires a valid email parameter', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.check-email'))
        ->assertSessionHasErrors('email');
});

test('check-email redirects guests to the login page', function () {
    $this->get(route('users.check-email', ['email' => 'test@example.com']))
        ->assertRedirect('/login');
});
