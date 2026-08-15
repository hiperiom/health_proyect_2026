<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\AssignModulePermissionsRequest;
use App\Http\Requests\Roles\AssignModulesRequest;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $roles = Role::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Role $item) {
                $permissionIds = $this->permissionIdsForRole($item->id);
                $modules = $item->modules()->orderBy('name')->get();

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'color_class' => $item->color_class,
                    'text_class' => $item->text_class,
                    'icon_svg' => $item->icon_svg,
                    'permission_ids' => $permissionIds,
                    'module_ids' => $modules->pluck('id')->all(),
                    'modules' => $modules
                        ->map(fn ($m) => [
                            'id' => $m->id,
                            'name' => $m->name,
                            'display_name' => $m->display_name,
                            'color_class' => $m->color_class,
                            'text_class' => $m->text_class,
                        ])
                        ->values()
                        ->all(),
                    'createdAt' => $item->created_at?->toISOString(),
                    'updatedAt' => $item->updated_at?->toISOString(),
                ];
            });

        return Inertia::render('roles/Index', [
            'items' => $roles,
            'allPermissions' => $this->allPermissions(),
            'allModules' => $this->allModulesWithPermissions(),
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rol creado exitosamente.')]);

        return to_route('roles.index');
    }

    public function edit(Request $request, string $item): Response
    {
        $role = Role::query()->findOrFail($item);

        return Inertia::render('roles/Index', [
            'item' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'color_class' => $role->color_class,
                'text_class' => $role->text_class,
                'icon_svg' => $role->icon_svg,
                'permission_ids' => $this->permissionIdsForRole($role->id),
                'module_ids' => $role->modules()->pluck('modules.id')->all(),
            ],
            'allPermissions' => $this->allPermissions(),
            'allModules' => $this->allModulesWithPermissions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, string $item): RedirectResponse
    {
        $role = Role::query()->findOrFail($item);
        $role->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rol actualizado exitosamente.')]);

        return to_route('roles.index');
    }

    public function destroy(Request $request, string $item): RedirectResponse
    {
        $role = Role::query()->findOrFail($item);
        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rol eliminado exitosamente.')]);

        return to_route('roles.index');
    }

    public function assignPermissions(Request $request, string $item): RedirectResponse
    {
        $role = Role::query()->findOrFail($item);
        $data = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = array_values(array_unique($data['permission_ids'] ?? []));

        $userIds = DB::table('users_roles')
            ->where('role_id', $role->id)
            ->pluck('user_id');

        if ($userIds->isNotEmpty()) {
            $now = now();
            $rows = [];
            foreach ($userIds as $userId) {
                foreach ($permissionIds as $permissionId) {
                    $rows[] = [
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Replace the current set of permissions for every user in this role
            // by deleting only permissions that belong to modules relevant to the role
            // (i.e. all current permissions of those users), then re-inserting the
            // new set. Because users in the role all share the same permission set
            // for the role, this is the simplest correct sync.
            DB::table('users_permissions')
                ->whereIn('user_id', $userIds)
                ->delete();

            if (! empty($rows)) {
                DB::table('users_permissions')->insert($rows);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permisos actualizados exitosamente.')]);

        return to_route('roles.index');
    }

    /**
     * Assign the given modules to the role. Any module not in the payload
     * is detached. After the sync we also remove any stale permission
     * assignments for the detached modules so the role doesn't keep
     * permissions it no longer has access to.
     */
    public function assignModules(AssignModulesRequest $request, string $item): RedirectResponse
    {
        $role = Role::query()->findOrFail($item);
        $data = $request->validated();
        $moduleIds = array_values(array_unique($data['module_ids'] ?? []));

        DB::transaction(function () use ($role, $moduleIds): void {
            $role->modules()->sync($moduleIds);

            // Drop any role-module-permission rows for modules the role no
            // longer owns so the role can't keep access through orphaned rows.
            DB::table('roles_modules_permissions')
                ->where('role_id', $role->id)
                ->whereNotIn('module_id', $moduleIds)
                ->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Módulos actualizados exitosamente.')]);

        return to_route('roles.index');
    }

    /**
     * Assign the enabled permission set for a specific (role, module) pair.
     * Replaces the previous selection atomically.
     */
    public function assignModulePermissions(AssignModulePermissionsRequest $request, string $item): RedirectResponse
    {
        $role = Role::query()->findOrFail($item);
        $data = $request->validated();
        $moduleId = (int) $data['module_id'];
        $permissionIds = array_values(array_unique($data['permission_ids'] ?? []));

        DB::transaction(function () use ($role, $moduleId, $permissionIds): void {
            // Ensure the module is actually part of the role, otherwise the
            // assignment would be dangling.
            $role->modules()->syncWithoutDetaching([$moduleId]);

            DB::table('roles_modules_permissions')
                ->where('role_id', $role->id)
                ->where('module_id', $moduleId)
                ->delete();

            $now = now();
            $rows = [];
            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'role_id' => $role->id,
                    'module_id' => $moduleId,
                    'permission_id' => (int) $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                DB::table('roles_modules_permissions')->insert($rows);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permisos actualizados para el módulo.')]);

        return to_route('roles.index');
    }

    /**
     * Return the list of all permissions (id, name, slug, module, description)
     * in the system. Used by the role edit modal.
     *
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

    /**
     * @return array<int, array{id: int, name: string, display_name: string|null, description: string|null, permissions: list<int>}>
     */
    private function allModulesWithPermissions(): array
    {
        return Module::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Module $module): array => [
                'id' => $module->id,
                'name' => $module->name,
                'display_name' => $module->display_name,
                'description' => $module->description,
                'permissions' => $module->permissions()
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all(),
            ])
            ->all();
    }

    /**
     * Compute the permission ids that should be pre-selected when editing a role.
     * The role owns no direct permission relation (we store them on the users
     * that hold the role), so we return the union of permission ids across
     * every user currently assigned to the role.
     *
     * @return list<int>
     */
    private function permissionIdsForRole(int $roleId): array
    {
        $userIds = DB::table('users_roles')
            ->where('role_id', $roleId)
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return [];
        }

        return DB::table('users_permissions')
            ->whereIn('user_id', $userIds)
            ->pluck('permission_id')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
