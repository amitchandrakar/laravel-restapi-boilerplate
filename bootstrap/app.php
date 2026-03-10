<?php

use App\Http\Middleware\EnsureSanctumToken;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api'
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add middleware aliases
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        // Force JSON response for all API routes
        $middleware->api(
            append: [
                ForceJsonResponse::class,
                // EnsureSanctumToken::class,
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Resource not found',
                    ],
                    404
                );
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'code' => 401,
                    ],
                    401
                );
            }
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Method not allowed',
                        'code' => 405,
                    ],
                    405
                );
            }
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // If specific exception handlers didn't catch it, and it's API
                // We let Laravel handle debug mode rendering if app.debug is true
                // But if we want to FORCE strict JSON:
                if (!config('app.debug')) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Server error',
                            'code' => 500,
                        ],
                        500
                    );
                }
                // If debug is true, we might want to let Laravel render the detailed error,
                // but FORCE JSON via middleware.
                // However, the user asked for "proper json".
                // Laravel's default debug JSON is "message", "exception", "trace".
                // If we want our schema, we must wrap it.
                if (config('app.debug')) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => $e->getMessage(),
                            'code' => 500,
                            'trace' => collect($e->getTrace())->take(5)->toArray(),
                        ],
                        500
                    );
                }
            }
        });
    })
    ->create();
