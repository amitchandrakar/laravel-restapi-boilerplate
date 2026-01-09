<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureSanctumToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the token from the Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthenticated',
                ],
                401
            );
        }

        // Find the token
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthenticated',
                ],
                401
            );
        }

        // Check if token is expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Token expired',
                ],
                401
            );
        }

        // Set the authenticated user
        $request->setUserResolver(fn() => $accessToken->tokenable);

        return $next($request);
    }
}
