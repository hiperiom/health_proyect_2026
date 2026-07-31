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
 * The middleware resolves the module by matching the first URL
 * segment against `modules.name`. If no match is found, the request
 * is allowed through (the module simply has not been registered yet).
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

        $moduleName = ltrim((string) $request->segment(1), '/');

        if ($moduleName === '') {
            return $next($request);
        }

        $moduleExists = Module::query()->where('name', $moduleName)->exists();

        if (! $moduleExists) {
            return $next($request);
        }

        // Use the active role's modules, not the union of every role
        // the user has. This is what makes the "active role" semantic
        // actually take effect.
        $userModuleNames = $activeRole?->modules()->pluck('name')->all() ?? [];

        if (! in_array($moduleName, $userModuleNames, true)) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        return $next($request);
    }
}
