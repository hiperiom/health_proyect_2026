<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permissions\StorePermissionRequest;
use App\Http\Requests\Permissions\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Permission::query()
            ->latest()
            ->get()
            ->map(fn (Permission $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'module' => $item->module,
                'description' => $item->description,
                'createdAt' => $item->created_at?->toISOString(),
                'updatedAt' => $item->updated_at?->toISOString(),
            ])
            ->all();

        return Inertia::render('permissions/Index', [
            'items' => $items,
        ]);
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $item = Permission::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permission created.')]);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permission updated.')]);

        return to_route('permissions.index');
    }

    public function destroy(Request $request, Permission $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permission deleted.')]);

        return to_route('permissions.index');
    }
}
