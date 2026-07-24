<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\StoreModuleRequest;
use App\Http\Requests\Modules\UpdateModuleRequest;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $modules = Module::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('display_name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Module $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'display_name' => $item->display_name,
                'description' => $item->description,
                'createdAt' => $item->created_at?->toISOString(),
                'updatedAt' => $item->updated_at?->toISOString(),
            ]);

        return Inertia::render('modules/Index', [
            'items' => $modules,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreModuleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $moduleName = $data['name'];
        $tableName = Str::of($moduleName)->plural()->lower()->toString();

        // Default display_name: convert the technical PascalCase name into a
        // human readable form (User -> User, medical_especiality -> Medical
        // Especiality). If the user already provided a value, keep it.
        if (empty($data['display_name'] ?? null)) {
            $data['display_name'] = Str::headline($moduleName);
        }

        // Default description: fall back to the display name so every module
        // has a meaningful description even if the user leaves the field empty.
        if (empty($data['description'] ?? null)) {
            $data['description'] = $data['display_name'];
        }

        $module = Module::create($data);

        $generated = $this->runMakeCrud($moduleName);

        $permissionMessage = '';
        if ($generated['success']) {
            $permissionMessage = $this->grantSuperuserPermissionsFor($tableName);
        }

        $toast = [
            'type' => $generated['success'] ? 'success' : 'error',
            'message' => $generated['success']
                ? __('Module saved, CRUD generated for ":name" and superusuario permissions updated.', [
                    'name' => $moduleName,
                ])
                : __('Module saved, but the CRUD could not be generated: :error', [
                    'error' => $generated['error'],
                ]),
        ];

        Inertia::flash('toast', $toast);

        return to_route('modules.index');
    }

    public function edit(Request $request, Module $item): Response
    {
        return Inertia::render('modules/Index', [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'display_name' => $item->display_name,
                'description' => $item->description,
            ],
        ]);
    }

    public function update(UpdateModuleRequest $request, Module $item): RedirectResponse
    {
        $item->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Module updated.')]);

        return to_route('modules.index');
    }

    public function destroy(Request $request, Module $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Module deleted.')]);

        return to_route('modules.index');
    }

    /**
     * Run `php artisan make:crud <name>` and capture the output / exit code.
     *
     * @return array{success: bool, error: string|null}
     */
    private function runMakeCrud(string $name): array
    {
        try {
            $exit = Artisan::call('make:crud', ['name' => $name]);
            if ($exit !== 0) {
                $output = Artisan::output();

                return [
                    'success' => false,
                    'error' => trim($output) !== '' ? $output : "make:crud exited with code {$exit}",
                ];
            }

            try {
                Artisan::call('wayfinder:generate', ['--with-form' => true]);
            } catch (\Throwable $wayfinderError) {
                // Swallow: the CRUD was generated, only the wayfinder regeneration is optional.
            }

            return ['success' => true, 'error' => null];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Attach every permission that belongs to the given module to every
     * user that holds the `superusuario` role so the role keeps full
     * access to the newly generated module.
     *
     * Returns a short human readable message (or '' if nothing was done).
     */
    private function grantSuperuserPermissionsFor(string $module): string
    {
        $permissionIds = Permission::query()
            ->where('module', $module)
            ->pluck('id')
            ->all();

        if (empty($permissionIds)) {
            return '';
        }

        $now = now();

        $superuserUserIds = DB::table('users_roles')
            ->join('roles', 'roles.id', '=', 'users_roles.role_id')
            ->where('roles.slug', Role::SUPERUSER_SLUG)
            ->pluck('users_roles.user_id');

        if ($superuserUserIds->isEmpty()) {
            return '';
        }

        $attached = 0;
        foreach ($superuserUserIds as $userId) {
            foreach ($permissionIds as $permissionId) {
                $inserted = DB::table('users_permissions')->insertOrIgnore([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $attached += $inserted;
            }
        }

        if ($attached === 0) {
            return '';
        }

        return " ({$attached} new permission assignments attached to superusuario)";
    }
}
