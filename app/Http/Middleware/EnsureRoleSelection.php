<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleSelection
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->roles()->count() > 1 && $user->active_role_id === null) {
            if ($request->routeIs('role.selection') || $request->routeIs('role.selection.store')) {
                return $next($request);
            }

            return redirect()->route('role.selection');
        }

        return $next($request);
    }
}
