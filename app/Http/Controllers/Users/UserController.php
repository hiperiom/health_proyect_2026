<?php

namespace App\Http\Controllers\Users;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignRolesRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserCreatedTemporaryPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $users = User::query()
            ->with(['roles:id,slug,name,color_class,text_class,icon_svg', 'permissions:id,name,slug,module,description'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $like = '%'.$search.'%';
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when(in_array($role, UserRole::slugs(), true), function ($query) use ($role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('slug', $role);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) {
                $entitledRoles = $user->entitledRoles();
                $primaryRole = $entitledRoles->first();
                $allRoles = $user->roles;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $primaryRole?->slug,
                    'roleName' => $primaryRole?->name,
                    'role_ids' => $allRoles
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'passwordUpdated' => $user->password_updated,
                    'roles' => $allRoles
                        ->map(fn (Role $r): array => [
                            'slug' => $r->slug,
                            'name' => $r->name,
                            'color_class' => $r->color_class,
                            'text_class' => $r->text_class,
                            'icon_svg' => $r->icon_svg,
                        ])
                        ->values()
                        ->all(),
                    'permission_ids' => $user->permissions
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'createdAt' => $user->created_at?->toISOString(),
                    'updatedAt' => $user->updated_at?->toISOString(),
                ];
            });

        return Inertia::render('users/Index', [
            'items' => $users,
            'availableRoles' => $this->availableRoles(),
            'allPermissions' => $this->allPermissions(),
            'filters' => [
                'search' => $search,
                'role' => $role,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $temporaryPassword = Str::password(16);
        $data['password'] = $temporaryPassword;
        $data['password_updated'] = false;
        $roleIds = $data['role_ids'];
        unset($data['role_ids']);

        $user = DB::transaction(function () use ($data, $roleIds) {
            $user = User::create($data);

            $user->roles()->sync($roleIds);

            return $user;
        });

        $user->notify(new UserCreatedTemporaryPassword($temporaryPassword));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);
        Inertia::flash('temporary_password', $temporaryPassword);

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $user->load('roles:id,slug,name');

        return Inertia::render('users/Index', [
            'item' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_ids' => $user->roles->pluck('id')->values()->all(),
                'permission_ids' => $user->permissions
                    ->pluck('id')
                    ->values()
                    ->all(),
            ],
            'availableRoles' => $this->availableRoles(),
            'allPermissions' => $this->allPermissions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        DB::transaction(function () use ($user, $data, $roleIds) {
            if (! empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            if ($roleIds !== null) {
                $user->roles()->sync($roleIds);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $temporaryPassword = Str::password(16);

        $user->update([
            'password' => $temporaryPassword,
            'password_updated' => false,
        ]);

        $user->notify(new UserCreatedTemporaryPassword($temporaryPassword));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Temporary password sent to the user.')]);

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    public function assignPermissions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = array_values(array_unique($data['permission_ids'] ?? []));

        $user->permissions()->sync($permissionIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permissions updated.')]);

        return to_route('users.index');
    }

    public function assignRoles(AssignRolesRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = array_values(array_unique($data['role_ids'] ?? []));

        $user->roles()->sync($roleIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Roles updated.')]);

        return to_route('users.index');
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, module: string, description: string|null}>
     */
    private function allPermissions(): array
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'module', 'description'])
            ->map(fn (Permission $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'module' => $p->module,
                'description' => $p->description,
            ])
            ->all();
    }

    /** @return array<int, array{id: int, value: int, label: string, slug: string, color_class: string|null, text_class: string|null, icon_svg: string|null}> */
    private function availableRoles(): array
    {
        $slugs = array_map(fn (UserRole $r) => $r->value, UserRole::cases());

        $roles = Role::query()
            ->whereIn('slug', $slugs)
            ->get(['id', 'slug', 'name', 'color_class', 'text_class', 'icon_svg'])
            ->keyBy('slug');

        return collect(UserRole::cases())
            ->map(function (UserRole $role) use ($roles): array {
                $dbRole = $roles[$role->value] ?? null;

                return [
                    'id' => $dbRole?->id ?? 0,
                    'value' => $dbRole?->id ?? 0,
                    'label' => $role->label(),
                    'slug' => $role->value,
                    'color_class' => $dbRole?->color_class ?? null,
                    'text_class' => $dbRole?->text_class ?? null,
                    'icon_svg' => $dbRole?->icon_svg ?? null,
                ];
            })
            ->all();
    }
}
