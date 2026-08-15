<?php

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\UsersProfile;
use App\Notifications\UserCreatedTemporaryPassword;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

test('admin can reset another user password and send temporary password notification', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $originalHash = $user->password;

    $this->actingAs($admin)
        ->patch(route('users.reset-password', $user))
        ->assertRedirect(route('users.index'));

    Notification::assertSentTo($user, UserCreatedTemporaryPassword::class);
    $this->assertNotSame($originalHash, $user->refresh()->password);
    $this->assertFalse($user->password_updated);
});

test('user cannot reset their own password', function () {
    $user = User::factory()->create();
    $originalHash = $user->password;

    $this->actingAs($user)
        ->patch(route('users.reset-password', $user))
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    Notification::assertNothingSent();
    $this->assertSame($originalHash, $user->refresh()->password);
});

test('non-superuser cannot reset a superuser password', function () {
    $admin = User::factory()->create();
    $superuser = User::factory()->superusuario()->create(['email' => 'super.uno@test.com']);
    $originalHash = $superuser->password;

    $this->actingAs($admin)
        ->patch(route('users.reset-password', $superuser))
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    Notification::assertNothingSent();
    $this->assertSame($originalHash, $superuser->refresh()->password);
});

test('user cannot delete their own account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('users.destroy', $user))
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('non-superuser cannot delete a superuser', function () {
    $admin = User::factory()->create();
    $superuser = User::factory()->superusuario()->create(['email' => 'super.dos@test.com']);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $superuser))
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertDatabaseHas('users', ['id' => $superuser->id]);
});

test('superuser can delete another superuser when more than one exists', function () {
    $actingSuperuser = User::factory()->superusuario()->create(['email' => 'super.actor@test.com']);
    $targetSuperuser = User::factory()->superusuario()->create(['email' => 'super.target@test.com']);

    $this->actingAs($actingSuperuser)
        ->delete(route('users.destroy', $targetSuperuser))
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', ['id' => $targetSuperuser->id]);
});

test('superuser cannot remove their own superuser role', function () {
    $superuser = User::factory()->superusuario()->create(['email' => 'super.tres@test.com']);
    $paciente = Role::query()->where('slug', UserRole::Paciente->value)->firstOrFail();

    $this->actingAs($superuser)
        ->patch(route('users.assignRoles', $superuser), [
            'role_ids' => [$paciente->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertTrue(
        $superuser->refresh()->roles()->where('slug', UserRole::Superusuario->value)->exists()
    );
});

test('superuser can remove the superuser role from another superuser when more than one exists', function () {
    $actingSuperuser = User::factory()->superusuario()->create(['email' => 'super.actor2@test.com']);
    $targetSuperuser = User::factory()->superusuario()->create(['email' => 'super.target2@test.com']);
    $paciente = Role::query()->where('slug', UserRole::Paciente->value)->firstOrFail();

    $this->actingAs($actingSuperuser)
        ->patch(route('users.assignRoles', $targetSuperuser), [
            'role_ids' => [$paciente->id],
        ])
        ->assertRedirect(route('users.index'));

    $this->assertFalse(
        $targetSuperuser->refresh()->roles()->where('slug', UserRole::Superusuario->value)->exists()
    );
});

test('non-superuser cannot assign the superuser role', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $superuserRole = Role::query()->where('slug', UserRole::Superusuario->value)->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('users.assignRoles', $user), [
            'role_ids' => [$superuserRole->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertFalse(
        $user->refresh()->roles()->where('slug', UserRole::Superusuario->value)->exists()
    );
});

test('user cannot deactivate their own account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('users.update', $user), [
            'status' => UserStatus::Inactive->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertSame(UserStatus::Active, $user->refresh()->status);
});

test('non-superuser cannot deactivate the last active superuser', function () {
    $admin = User::factory()->create();
    $superuser = User::factory()->superusuario()->create(['email' => 'super.cuatro@test.com']);

    $this->actingAs($admin)
        ->patch(route('users.update', $superuser), [
            'status' => UserStatus::Inactive->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertSame(UserStatus::Active, $superuser->refresh()->status);
});

test('non-superuser cannot change the roles of a superuser through update', function () {
    $admin = User::factory()->create();
    $superuser = User::factory()->superusuario()->create(['email' => 'super.cinco@test.com']);
    $paciente = Role::query()->where('slug', UserRole::Paciente->value)->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('users.update', $superuser), [
            'role_ids' => [$paciente->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertTrue(
        $superuser->refresh()->roles()->where('slug', UserRole::Superusuario->value)->exists()
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

test('index exposes role filter options with slug values', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertInertia(fn ($page) => $page
            ->where('availableRoles.0.value', UserRole::Paciente->value)
            ->where('availableRoles.1.value', UserRole::Doctor->value)
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

test('admin can upload a user profile photo', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $user = User::factory()->create();
    $profile = UsersProfile::factory()->create([
        'user_id' => $user->id,
        'created_by_user_id' => $admin->id,
    ]);

    $file = UploadedFile::fake()->image('foto.png', 512, 512);

    $this->actingAs($admin)
        ->post(route('users.photo.store', $user), ['photo' => $file])
        ->assertRedirect();

    $this->assertNotSame(null, $profile->refresh()->photo_path);
    Storage::disk('public')->assertExists($profile->photo_path);

    $this->assertDatabaseHas('audit_logs', [
        'target_resource' => 'users.photo.store',
        'action' => 'INSERT',
    ]);
});

test('admin can remove a user profile photo', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $user = User::factory()->create();
    $profile = UsersProfile::factory()->create([
        'user_id' => $user->id,
        'created_by_user_id' => $admin->id,
        'photo_path' => 'users-profiles/photos/old.png',
    ]);
    Storage::disk('public')->put($profile->photo_path, 'fake-content');

    $this->actingAs($admin)
        ->delete(route('users.photo.destroy', $user))
        ->assertRedirect();

    $this->assertSame(null, $profile->refresh()->photo_path);
    Storage::disk('public')->assertMissing($profile->photo_path);
});

test('photo upload is rejected for unsupported formats', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $user = User::factory()->create();
    UsersProfile::factory()->create([
        'user_id' => $user->id,
        'created_by_user_id' => $admin->id,
    ]);

    $file = UploadedFile::fake()->create('documento.txt', 100, 'text/plain');

    $this->actingAs($admin)
        ->post(route('users.photo.store', $user), ['photo' => $file])
        ->assertSessionHasErrors('photo');
});
