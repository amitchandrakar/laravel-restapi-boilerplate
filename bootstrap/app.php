<?php

use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\EnsureSanctumToken;
use App\Http\Middleware\FlushSanctumGuardState;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\LogApiCalls;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

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

        // API middleware: request ID first, then force JSON
        $middleware->api(
            append: [
                FlushSanctumGuardState::class,
                AttachRequestId::class,
                ForceJsonResponse::class,
                LogApiCalls::class,
                // EnsureSanctumToken::class,
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API exceptions are handled by App\Exceptions\Handler (expectsJson / api/*)
        // so we do not register inline renderers here for api/*.
    })
    ->create();
