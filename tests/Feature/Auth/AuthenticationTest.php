<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
});

test('users with a single role are redirected to the dashboard', function () {
    $user = User::factory()->create();
    $role = Role::where('slug', 'doctor')->firstOrCreate([
        'slug' => 'doctor',
    ], [
        'name' => 'Doctor',
    ]);
    $user->roles()->attach($role->id);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
});

test('users with multiple roles are redirected to role selection', function () {
    $user = User::factory()->create();
    $doctorRole = Role::where('slug', 'doctor')->firstOrCreate([
        'slug' => 'doctor',
    ], [
        'name' => 'Doctor',
    ]);
    $pacienteRole = Role::where('slug', 'paciente')->firstOrCreate([
        'slug' => 'paciente',
    ], [
        'name' => 'Paciente',
    ]);
    $user->roles()->attach([$doctorRole->id, $pacienteRole->id]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('role.selection'));
});

test('role selection page receives the active role when previously selected', function () {
    $user = User::factory()->create();
    $doctorRole = Role::where('slug', 'doctor')->firstOrCreate([
        'slug' => 'doctor',
    ], [
        'name' => 'Doctor',
    ]);
    $pacienteRole = Role::where('slug', 'paciente')->firstOrCreate([
        'slug' => 'paciente',
    ], [
        'name' => 'Paciente',
    ]);
    $user->roles()->attach([$doctorRole->id, $pacienteRole->id]);
    $user->update(['active_role_id' => $doctorRole->id]);

    $this->actingAs($user);

    $response = $this->get(route('role.selection'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('active_role_id', $doctorRole->id)
    );
});

test('users with multiple roles and no active role are redirected to role selection when accessing dashboard', function () {
    $user = User::factory()->create();
    $doctorRole = Role::where('slug', 'doctor')->firstOrCreate([
        'slug' => 'doctor',
    ], [
        'name' => 'Doctor',
    ]);
    $pacienteRole = Role::where('slug', 'paciente')->firstOrCreate([
        'slug' => 'paciente',
    ], [
        'name' => 'Paciente',
    ]);
    $user->roles()->attach([$doctorRole->id, $pacienteRole->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('role.selection'));
});

test('users with multiple roles and an active role can access dashboard', function () {
    $user = User::factory()->create();
    $doctorRole = Role::where('slug', 'doctor')->firstOrCreate([
        'slug' => 'doctor',
    ], [
        'name' => 'Doctor',
    ]);
    $pacienteRole = Role::where('slug', 'paciente')->firstOrCreate([
        'slug' => 'paciente',
    ], [
        'name' => 'Paciente',
    ]);
    $user->roles()->attach([$doctorRole->id, $pacienteRole->id]);
    $user->update(['active_role_id' => $doctorRole->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('users with multiple roles can select a role and continue to dashboard', function () {
    $user = User::factory()->create();
    $doctorRole = Role::where('slug', 'doctor')->firstOrCreate([
        'slug' => 'doctor',
    ], [
        'name' => 'Doctor',
    ]);
    $pacienteRole = Role::where('slug', 'paciente')->firstOrCreate([
        'slug' => 'paciente',
    ], [
        'name' => 'Paciente',
    ]);
    $user->roles()->attach([$doctorRole->id, $pacienteRole->id]);

    $this->actingAs($user);

    $response = $this->post(route('role.selection.store'), [
        'role_id' => $doctorRole->id,
    ]);

    $response->assertRedirect(route('dashboard'));
    expect($user->fresh()->active_role_id)->toBe($doctorRole->id);
});

test('users with multiple roles cannot select an unauthorized role', function () {
    $user = User::factory()->create();
    $doctorRole = Role::where('slug', 'doctor')->firstOrCreate([
        'slug' => 'doctor',
    ], [
        'name' => 'Doctor',
    ]);
    $pacienteRole = Role::where('slug', 'paciente')->firstOrCreate([
        'slug' => 'paciente',
    ], [
        'name' => 'Paciente',
    ]);
    $user->roles()->attach($pacienteRole->id);

    $this->actingAs($user);

    $response = $this->post(route('role.selection.store'), [
        'role_id' => $doctorRole->id,
    ]);

    $response->assertRedirect(route('role.selection'));
    expect($user->fresh()->active_role_id)->toBeNull();
});

test('passkey login response redirects to the dashboard', function () {
    $user = User::factory()->create();

    $request = Request::create(route('login', absolute: false), 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $request->setLaravelSession($this->app['session.store']);
    $request->setUserResolver(fn () => $user);

    $jsonResponse = app(PasskeyLoginResponse::class)->toResponse($request);

    expect($jsonResponse->getData()->redirect)->toBe(route('dashboard'));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
