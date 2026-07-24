<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $now = now();
    $defaults = [
        ['users.create', 'users', 'Create user'],
        ['users.read', 'users', 'View user'],
        ['users.update', 'users', 'Update user'],
        ['users.delete', 'users', 'Delete user'],
        ['roles.create', 'roles', 'Create role'],
        ['roles.read', 'roles', 'View role'],
        ['roles.update', 'roles', 'Update role'],
        ['roles.delete', 'roles', 'Delete role'],
        ['roles.assignPermissions', 'roles', 'Assign perms'],
        ['permissions.read', 'permissions', 'View permission'],
        ['modules.read', 'modules', 'View module'],
    ];

    foreach ($defaults as [$slug, $module, $name]) {
        Permission::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'module' => $module,
                'description' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }

    Role::query()->updateOrCreate(
        ['slug' => 'doctor'],
        ['name' => 'Doctor', 'color_class' => null, 'text_class' => null, 'icon_svg' => null],
    );
});

/**
 * Attach the doctor role to a freshly created user via the `users_roles` pivot.
 *
 * @return array{0: User, 1: Role}
 */
function attachDoctorToFreshUser(): array
{
    $user = User::factory()->create();
    $doctor = Role::query()->where('slug', 'doctor')->first();

    DB::table('users_roles')->insert([
        'user_id' => $user->id,
        'role_id' => $doctor->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$user, $doctor];
}

test('a role can be assigned exactly one permission to a user', function () {
    [$user, $doctor] = attachDoctorToFreshUser();
    $permission = Permission::query()->where('slug', 'users.read')->first();

    $response = $this->actingAs($user)
        ->post(route('roles.assignPermissions', $doctor), [
            'permission_ids' => [$permission->id],
        ]);

    $response->assertRedirect(route('roles.index'));

    $assigned = DB::table('users_permissions')
        ->where('user_id', $user->id)
        ->pluck('permission_id')
        ->all();

    expect($assigned)->toBe([$permission->id]);
});

test('a role can be assigned multiple permissions to a user in a single request', function () {
    [$user, $doctor] = attachDoctorToFreshUser();
    $permissionIds = Permission::query()
        ->whereIn('slug', ['users.read', 'users.create', 'users.update', 'roles.read'])
        ->pluck('id')
        ->all();

    $response = $this->actingAs($user)
        ->post(route('roles.assignPermissions', $doctor), [
            'permission_ids' => $permissionIds,
        ]);

    $response->assertRedirect(route('roles.index'));

    $assigned = DB::table('users_permissions')
        ->where('user_id', $user->id)
        ->pluck('permission_id')
        ->sort()
        ->values()
        ->all();

    expect($assigned)->toBe(collect($permissionIds)->sort()->values()->all());
});

test('saving an empty array removes all permissions from a user', function () {
    [$user, $doctor] = attachDoctorToFreshUser();
    DB::table('users_permissions')->insert([
        ['user_id' => $user->id, 'permission_id' => Permission::query()->where('slug', 'users.read')->first()->id, 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $user->id, 'permission_id' => Permission::query()->where('slug', 'roles.read')->first()->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(DB::table('users_permissions')->where('user_id', $user->id)->count())->toBe(2);

    $response = $this->actingAs($user)
        ->post(route('roles.assignPermissions', $doctor), [
            'permission_ids' => [],
        ]);

    $response->assertRedirect(route('roles.index'));

    expect(DB::table('users_permissions')->where('user_id', $user->id)->count())->toBe(0);
});

test('a later assignment replaces the previous set of permissions', function () {
    [$user, $doctor] = attachDoctorToFreshUser();
    $firstSet = Permission::query()->whereIn('slug', ['users.read', 'users.create'])->pluck('id')->all();
    $secondSet = Permission::query()->whereIn('slug', ['roles.read', 'roles.create'])->pluck('id')->all();

    $this->actingAs($user)
        ->post(route('roles.assignPermissions', $doctor), ['permission_ids' => $firstSet]);

    $firstAssigned = DB::table('users_permissions')->where('user_id', $user->id)->pluck('permission_id')->sort()->values()->all();
    expect($firstAssigned)->toBe(collect($firstSet)->sort()->values()->all());

    $this->actingAs($user)
        ->post(route('roles.assignPermissions', $doctor), ['permission_ids' => $secondSet]);

    $secondAssigned = DB::table('users_permissions')->where('user_id', $user->id)->pluck('permission_id')->sort()->values()->all();
    expect($secondAssigned)->toBe(collect($secondSet)->sort()->values()->all())
        ->and($secondAssigned)->not->toContain(Permission::query()->where('slug', 'users.read')->first()->id)
        ->and($secondAssigned)->not->toContain(Permission::query()->where('slug', 'users.create')->first()->id);
});

test('a permission id that does not exist is rejected with a validation error', function () {
    [$user, $doctor] = attachDoctorToFreshUser();

    $response = $this->actingAs($user)
        ->from(route('roles.edit', $doctor))
        ->post(route('roles.assignPermissions', $doctor), [
            'permission_ids' => [999999],
        ]);

    $response->assertSessionHasErrors('permission_ids.0');
});

test('the doctor role can be reopened and its user pre-existing permissions are pre-selected', function () {
    [$user, $doctor] = attachDoctorToFreshUser();
    $permission = Permission::query()->where('slug', 'roles.assignPermissions')->first();
    DB::table('users_permissions')->insert([
        'user_id' => $user->id,
        'permission_id' => $permission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('roles.edit', $doctor));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page->component('roles/Index')
            ->where('item.id', $doctor->id)
            ->where('item.permission_ids', [$permission->id]),
    );
});
