<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\ForceUtf8ForAssets;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AuditLogMiddleware::class,
        ]);

        // Ensure every static asset (and the main HTML document) is
        // served with an explicit `charset=utf-8` so browsers do not
        // fall back to ISO-8859-1 and render accented characters
        // (á, é, í, ó, ñ) as mojibake.
        $middleware->append(ForceUtf8ForAssets::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Render HTTP error pages (403 / 404 / 500 / 503) with the same UI
        // language used for unauthenticated users (centered card, logo and
        // teal accents) while keeping the real HTTP status code.
        //
        // @see https://inertiajs.com/error-handling
        Inertia::handleExceptionsUsing(function (ExceptionResponse $error): ?ExceptionResponse {
            $status = $error->statusCode();

            if (! in_array($status, [403, 404, 500, 503], true)) {
                return null;
            }

            // Only surface explicit messages set by our own abort() calls
            // (e.g. "No tienes acceso a este módulo."). Never leak internal
            // exception details from 5xx responses.
            $message = null;
            if ($status === 403 && $error->exception instanceof HttpExceptionInterface) {
                $abortMessage = $error->exception->getMessage();
                $message = $abortMessage !== '' && $abortMessage !== 'Forbidden' ? $abortMessage : null;
            }

            return $error
                ->render('errors/ErrorPage', [
                    'status' => $status,
                    'message' => $message,
                ])
                ->withSharedData();
        });
    })->create();
