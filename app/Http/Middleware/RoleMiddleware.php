<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        if (!$request->user()->hasAnyRole($roles)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Forbidden. Required role: ' . implode(', ', $roles),
                ],
                403
            );
        }

        return $next($request);
    }
}
