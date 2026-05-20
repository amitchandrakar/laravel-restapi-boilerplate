<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * If Authorization Bearer is present, resolve the user like {@see EnsureSanctumToken}
 * or return 401. If no Bearer, continue as guest (no user on the request).
 */
class OptionalSanctumAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return $next($request);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken === null) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthenticated',
                ],
                401
            );
        }

        $expiresAt = $accessToken->expires_at ?? null;

        if ($expiresAt !== null && $expiresAt->isPast()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Token expired',
                ],
                401
            );
        }

        $user = $accessToken->tokenable;

        if ($user instanceof User) {
            $user->withAccessToken($accessToken);
        }

        $request->setUserResolver(static fn() => $user);

        return $next($request);
    }
}
