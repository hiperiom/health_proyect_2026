<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Role;
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
     * Compute the list of module `name`s (e.g. `users`, `roles`, `modules`)
     * that the given user can see in the sidebar right now.
     *
     * The check is **strictly based on the user's active role**, not on
     * the full set of roles they happen to hold. This avoids the
     * "I have superusuario + paciente, I'm active as paciente, but
     * I still see every superusuario module" bug: the visible modules
     * are always the ones granted to the role the user is currently
     * acting as. Users with `superusuario` always get the full set,
     * because the active role short-circuits to `superusuario`.
     *
     * @return list<string>
     */
    private function accessibleModuleNames(User $user): array
    {
        if ($this->userIsSuperuser($user)) {
            return Module::query()->pluck('name')->all();
        }

        // The user is "acting as" their active role. Compute the
        // accessible modules for that single role, ignoring any other
        // role they might happen to hold on top of it.
        $activeRole = $user->activeRole ?? $user->primaryRole();

        if ($activeRole === null) {
            return [];
        }

        return $activeRole->modules()
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Return true if the user is currently acting as `superusuario`.
     *
     * The user is considered a superuser only when the role they are
     * currently active in is `superusuario`. Holding the role without
     * being active in it is NOT enough — the user must have explicitly
     * switched to it.
     */
    private function userIsSuperuser(User $user): bool
    {
        $activeRole = $user->activeRole ?? $user->primaryRole();

        return $activeRole?->slug === Role::SUPERUSER_SLUG;
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
                'isSuperuser' => $user ? $this->userIsSuperuser($user) : false,
                'accessibleModules' => $user ? $this->accessibleModuleNames($user) : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
