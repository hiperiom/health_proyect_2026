<?php

namespace App\Http\Middleware;

use App\Jobs\WriteAuditLogJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        WriteAuditLogJob::dispatch([
            'user_id' => Auth::id(),
            'action' => $this->detectAction($request->method()),
            'target_resource' => $this->resolveTargetResource($request),
            'target_id' => $this->resolveTargetId($request),
            'payload' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'query' => $request->query(),
                'body' => $request->except(['password', 'password_confirmation']),
            ],
            'ip_address' => $request->ip(),
            'performed_at' => now()->utc()->toIso8601String(),
        ]);

        return $response;
    }

    protected function detectAction(string $method): string
    {
        return match ($method) {
            'POST' => 'INSERT',
            'PUT', 'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            'GET' => 'SELECT',
            default => 'SELECT',
        };
    }

    protected function resolveTargetResource(Request $request): string
    {
        return $request->route()?->getName() ?? 'unknown';
    }

    protected function resolveTargetId(Request $request): ?int
    {
        $route = $request->route();

        if (!$route) {
            return null;
        }

        foreach ($route->parameters() as $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                return (int) $value->getKey();
            }

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }
}
