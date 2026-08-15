<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
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
     * Locales the application supports. The first one is the
     * default; subsequent ones are fallbacks. Keep in sync with
     * the `LOCALES` array in `resources/js/composables/useI18n.ts`.
     *
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['es', 'en'];

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
     * Return the canonical `display_name` for every module the user
     * can access, keyed by the module's kebab/snake `name`.
     *
     * The sidebar reads this map as the **source of truth** for the
     * human-readable title shown next to each module icon.
     *
     * @return array<string, string>
     */
    private function moduleDisplayNamesForUser(User $user): array
    {
        $accessible = $this->accessibleModuleNames($user);

        if ($accessible === []) {
            return [];
        }

        return Module::query()
            ->whereIn('name', $accessible)
            ->pluck('display_name', 'name')
            ->all();
    }

    /**
     * Return true if the user is currently acting as `superusuario`.
     */
    private function userIsSuperuser(User $user): bool
    {
        $activeRole = $user->activeRole ?? $user->primaryRole();

        return $activeRole?->slug === Role::SUPERUSER_SLUG;
    }

    /**
     * Resolve the active locale for this request, honouring the
     * precedence:
     *   1. `locale` query / body parameter (set by the LocaleController
     *      just before redirecting).
     *   2. `locale` stored in the session (the user's last choice).
     *   3. `APP_LOCALE` from `.env`.
     *
     * Persists the resolved value back to the session so subsequent
     * requests keep it, and pushes it onto the Laravel `App` facade
     * so any `trans()` / `__()` call inside the request uses the same
     * language.
     */
    private function resolveLocale(Request $request): string
    {
        $default = config('app.locale', self::SUPPORTED_LOCALES[0]);

        $candidate = $request->input('locale')
            ?? ($request->hasSession() ? $request->session()->get('locale') : null)
            ?? $default;

        if (! in_array($candidate, self::SUPPORTED_LOCALES, true)) {
            $candidate = $default;
        }

        if (! in_array($candidate, self::SUPPORTED_LOCALES, true)) {
            $candidate = self::SUPPORTED_LOCALES[0];
        }

        if ($request->hasSession()) {
            $request->session()->put('locale', $candidate);
        }
        App::setLocale($candidate);

        return $candidate;
    }

    /**
     * Load every `lang/<locale>/*.php` file into a single
     * `{ [locale]: { ... } }` map, ready to ship to the frontend.
     *
     * Only loads files that exist on disk so a missing translation
     * directory does not 500 the whole request; the frontend just
     * shows English fallbacks.
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadTranslations(): array
    {
        $result = [];

        foreach (self::SUPPORTED_LOCALES as $locale) {
            $base = lang_path($locale);

            if (! File::isDirectory($base)) {
                $result[$locale] = [];

                continue;
            }

            $merged = [];
            foreach (File::files($base) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $values = require $file->getPathname();
                if (is_array($values)) {
                    $merged = array_replace_recursive($merged, $values);
                }
            }

            $result[$locale] = $merged;
        }

        return $result;
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

        $locale = $this->resolveLocale($request);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => $locale,
            'translations' => $this->loadTranslations(),
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
                'moduleDisplayNames' => $user ? $this->moduleDisplayNamesForUser($user) : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
