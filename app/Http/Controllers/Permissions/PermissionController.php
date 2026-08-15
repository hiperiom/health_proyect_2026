<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permissions\StorePermissionRequest;
use App\Http\Requests\Permissions\UpdatePermissionRequest;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $module = trim((string) $request->query('module', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $permissions = Permission::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Permission $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'module' => $item->module,
                'description' => $item->description,
                'createdAt' => $item->created_at?->toISOString(),
                'updatedAt' => $item->updated_at?->toISOString(),
            ]);

        return Inertia::render('permissions/Index', [
            'items' => $permissions,
            'availableModules' => Module::query()->orderBy('name')->pluck('name')->all(),
            'filters' => [
                'search' => $search,
                'module' => $module,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $item = Permission::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permiso creado exitosamente.')]);

        return to_route('permissions.index');
    }

    public function edit(Request $request, Permission $item): Response
    {
        return Inertia::render('permissions/Index', [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'module' => $item->module,
                'description' => $item->description,
            ],
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $item): RedirectResponse
    {
        $item->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permiso actualizado exitosamente.')]);

        return to_route('permissions.index');
    }

    public function destroy(Request $request, Permission $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permiso eliminado exitosamente.')]);

        return to_route('permissions.index');
    }
}
