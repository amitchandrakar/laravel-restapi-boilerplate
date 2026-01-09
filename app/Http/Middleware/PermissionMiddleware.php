<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!$request->user()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthenticated',
                ],
                401
            );
        }

        if (!$request->user()->hasAnyPermission($permissions)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Forbidden. Required permission: ' . implode(', ', $permissions),
                ],
                403
            );
        }

        return $next($request);
    }
}
