<?php

use App\Models\Patients;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('loads the patients index page for the superusuario', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    actingAs($user)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('patients/Index')
                ->has('items')
                ->has('availableStatuses')
                ->has('availableNacionalities')
                ->has('availableGenders')
        );
});

it('redirects guests to the login page', function () {
    get(route('patients.index'))->assertRedirect('/login');
});

it('paginates patient results', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    Patients::factory()->count(20)->create();

    actingAs($user)
        ->get(route('patients.index'))
        ->assertInertia(fn ($page) => $page->where('items.total', 20));
});

it('filters patients by search term', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    $match = Patients::factory()->create(['first_name' => 'Juan', 'last_name' => 'Pérez']);
    Patients::factory()->count(5)->create();

    actingAs($user)
        ->get(route('patients.index', ['search' => 'Juan']))
        ->assertInertia(
            fn ($page) => $page
                ->where('items.total', 1)
                ->where('items.data.0.id', $match->id)
        );
});

it('filters patients by status', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    $inactive = Patients::factory()->inactive()->create();
    Patients::factory()->count(3)->create();

    actingAs($user)
        ->get(route('patients.index', ['status' => 'inactive']))
        ->assertInertia(
            fn ($page) => $page
                ->where('items.total', 1)
                ->where('items.data.0.id', $inactive->id)
        );
});

it('creates a patient through the form', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Pérez García',
        'nacionality' => 'V',
        'dni' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => 'M',
        'phone_mobile' => '04141234567',
        'phone_landline' => '',
        'email' => 'juan.perez@example.com',
        'status' => 'active',
    ];

    $response = actingAs($user)
        ->post(route('patients.store'), $payload)
        ->assertStatus(302);

    expect($response->headers->get('Location'))->toBe(route('patients.index'));

    $this->assertDatabaseHas('patients', [
        'dni' => '12345678',
        'first_name' => 'Juan',
        'created_by_user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'juan.perez@example.com',
    ]);
});

it('checks email availability', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    actingAs($user)
        ->get(route('patients.check-email', ['email' => 'new@example.com']))
        ->assertOk()
        ->assertJson(['exists' => false]);

    actingAs($user)
        ->get(route('patients.check-email', ['email' => $user->email]))
        ->assertOk()
        ->assertJson(['exists' => true]);
});

it('uploads a patient photo', function () {
    Storage::fake('public');

    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);
    $patient = Patients::factory()->create();

    $file = UploadedFile::fake()->image('photo.jpg', 800, 800)->size(500);

    actingAs($user)
        ->post(route('patients.photo.store', $patient), ['photo' => $file])
        ->assertRedirect();

    expect($patient->fresh()->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($patient->fresh()->photo_path);
});

it('rejects non-image uploads', function () {
    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);
    $patient = Patients::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    actingAs($user)
        ->post(route('patients.photo.store', $patient), ['photo' => $file])
        ->assertSessionHasErrors('photo');
});

it('removes a patient photo', function () {
    Storage::fake('public');

    $role = Role::query()->firstOrCreate(
        ['slug' => 'superusuario'],
        ['name' => 'Superusuario']
    );
    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);
    $patient = Patients::factory()->create(['photo_path' => 'patients/photos/old.jpg']);

    Storage::disk('public')->put('patients/photos/old.jpg', 'fake-content');

    actingAs($user)
        ->delete(route('patients.photo.destroy', $patient))
        ->assertRedirect();

    expect($patient->fresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing('patients/photos/old.jpg');
});
