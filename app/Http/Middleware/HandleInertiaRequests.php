<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-version
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        $roles = $user?->roles()->get(['id', 'name', 'slug', 'color_class', 'text_class', 'icon_svg'])->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'color_class' => $role->color_class,
            'text_class' => $role->text_class,
            'icon_svg' => $role->icon_svg,
        ])->values()->all() ?? [];

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->primaryRole()?->slug,
                    'roleName' => $user->primaryRole()?->name,
                    'permissions' => $user->permissionSlugs(),
                ] : null,
                'roles' => $roles,
                'activeRole' => $user ? [
                    'id' => $user->active_role_id,
                    'name' => $user->activeRole?->name,
                    'slug' => $user->activeRole?->slug,
                ] : null,
                'hasMultipleRoles' => (bool) (count($roles) > 1),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
