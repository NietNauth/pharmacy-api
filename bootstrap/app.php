<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
        $middleware->api(prepend: [
            HandleCors::class,
        ]);
        // NOTE: Do NOT call statefulApi() here.
        // This project uses Bearer token auth (not cookie/session SPA auth).
        // statefulApi() adds Sanctum's EnsureFrontendRequestsAreStateful which
        // attempts CSRF validation for stateful domains → causes 500 for browser requests.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return JSON for API auth errors (instead of redirect to login route)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        // Log validation errors for debugging
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                \Log::error('Validation Error:', [
                    'url' => $request->fullUrl(),
                    'errors' => $e->errors(),
                    'input' => $request->except(['file', 'password']),
                ]);
            }
            return null; // Let default handler continue
        });

        // Always return JSON for API 404 errors
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy dữ liệu.',
                ], 404);
            }
        });
    })->create();
