<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access to module-prefixed routes when the authenticated user
 * does not have the corresponding module assigned to the role they
 * are currently active in.
 *
 * The superusuario always has access to every module, but only when
 * the user is *active as* superusuario. Holding the role without
 * being active in it is not enough — the user must have explicitly
 * switched to it. This prevents a "superusuario + paciente" user
 * from accessing admin routes while acting as paciente.
 *
 * Usage in routes/web.php:
 *   Route::middleware(['auth', EnsureModuleAccess::class])
 *       ->prefix('users')->name('users.')->group(...)
 *
 * The middleware extracts the module from the first URL segment,
 * normalises it (replacing `_` with `-` and lower-casing), and looks
 * it up in the `modules` table. If the route prefix does not match
 * any registered module (e.g. `/settings`, `/dashboard`), the request
 * is allowed through.
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $activeRole = $user->activeRole ?? $user->primaryRole();

        if ($activeRole?->slug === Role::SUPERUSER_SLUG) {
            return $next($request);
        }

        $urlSegment = strtolower((string) $request->segment(1));

        if ($urlSegment === '') {
            return $next($request);
        }

        // Build a normalised lookup: `medicalespecialties` →
        // `medical-Especialties` and `medical_especialties` →
        // `medical_especialties`. The first match wins; the request is
        // allowed through if no module matches (e.g. `/settings`,
        // `/dashboard`, `/login`).
        $candidates = array_unique(array_filter([
            $urlSegment,
            str_replace('_', '-', $urlSegment),
        ]));

        $matchedModuleName = null;
        foreach ($candidates as $candidate) {
            $exists = Module::query()->where('name', $candidate)->exists();
            if ($exists) {
                $matchedModuleName = $candidate;
                break;
            }
        }

        if ($matchedModuleName === null) {
            return $next($request);
        }

        // Use the active role's modules, not the union of every role
        // the user has. This is what makes the "active role" semantic
        // actually take effect.
        $userModuleNames = $activeRole?->modules()->pluck('name')->all() ?? [];

        if (! in_array($matchedModuleName, $userModuleNames, true)) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        return $next($request);
    }
}
