<?php

namespace App\Http\Middleware;

use App\Models\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access to module-prefixed routes when the authenticated user
 * does not have the corresponding module assigned to any of their
 * roles. The superusuario always has access to every module.
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

        $holdsSuper = $user->roles()->where('slug', 'superusuario')->exists()
            || ($user->activeRole?->slug === 'superusuario');

        if ($holdsSuper) {
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

        $userModuleNames = $user
            ->roles()
            ->with('modules:id,name')
            ->get()
            ->pluck('modules')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        if (! in_array($moduleName, $userModuleNames, true)) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        return $next($request);
    }
}
