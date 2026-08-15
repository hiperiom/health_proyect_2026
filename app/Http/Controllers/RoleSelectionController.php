<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleSelectionController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return to_route('login');
        }

        $roles = $user->roles()->get(['id', 'name', 'slug', 'color_class', 'text_class', 'icon_svg']);

        if ($roles->count() <= 1) {
            if ($roles->count() === 1) {
                $user->update(['active_role_id' => $roles->first()->id]);
            }

            return to_route('dashboard');
        }

        return Inertia::render('auth/RoleSelect', [
            'roles' => $roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'color_class' => $role->color_class,
                'text_class' => $role->text_class,
                'icon_svg' => $role->icon_svg,
            ])->values()->all(),
            'active_role_id' => $user->active_role_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user = $request->user();

        if (! $user) {
            return to_route('login');
        }

        $role = Role::query()->findOrFail($request->input('role_id'));

        if (! $user->roles()->where('role_id', $role->id)->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('No estás autorizado para usar este rol.'),
            ]);

            return to_route('role.selection');
        }

        $user->update(['active_role_id' => $role->id]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Rol activo cambiado a :role.', ['role' => $role->name]),
        ]);

        return to_route('dashboard');
    }
}
