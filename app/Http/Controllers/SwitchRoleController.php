<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SwitchRoleController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user = $request->user();

        $role = Role::query()->findOrFail($request->input('role_id'));

        if (! $user->roles()->where('role_id', $role->id)->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('No estás autorizado para usar este rol.'),
            ]);

            return to_route('dashboard');
        }

        $user->update(['active_role_id' => $role->id]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Rol activo cambiado a :role.', ['role' => $role->name]),
        ]);

        return to_route('dashboard');
    }
}
