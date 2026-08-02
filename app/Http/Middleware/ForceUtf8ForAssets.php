<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Make sure the response includes an explicit `charset=utf-8` for any
 * static asset that the PHP built-in server (`php artisan serve`)
 * would otherwise serve without one.
 *
 * Without this, browsers that fall back to ISO-8859-1 for unknown
 * text/* MIME types would render the accented Spanish characters
 * in our Vite-compiled `.js` bundles as mojibake (e.g. `Ã³` instead
 * of `ó`).
 */
class ForceUtf8ForAssets
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $path = $request->path();
        $contentType = (string) $response->headers->get('Content-Type', '');

        // Vite built assets live under /build/assets/*.js and the Vite
        // dev server serves /@vite/* and /resources/* as application/javascript.
        $isJsAsset = str_ends_with($path, '.js')
            || str_ends_with($path, '.mjs')
            || str_contains($path, '/build/assets/')
            || str_contains($path, '/@vite/')
            || str_contains($path, '/resources/')
            || str_contains($path, '/node_modules/.vite/');

        if ($isJsAsset && $contentType !== '' && ! str_contains($contentType, 'charset=')) {
            $response->headers->set('Content-Type', $contentType.'; charset=utf-8');
        }

        // Also force the main HTML document to UTF-8 in case any
        // upstream tool stripped the meta charset.
        if ($contentType !== '' && str_contains($contentType, 'text/html') && ! str_contains($contentType, 'charset=')) {
            $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        }

        return $response;
    }
}
