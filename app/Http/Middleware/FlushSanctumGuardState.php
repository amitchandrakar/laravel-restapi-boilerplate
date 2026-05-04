<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clear cached user on the Sanctum request guard after each API response so the next HTTP request
 * re-resolves auth from the current session / Bearer token.
 *
 * {@see \Illuminate\Auth\RequestGuard} caches the resolved user on the guard singleton; the same PHP
 * process (tests, Octane, etc.) would otherwise keep a stale user after logout or token revocation.
 * Flushing in {@see self::terminate()} preserves {@see \Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication::actingAs()}
 * for the duration of the request.
 */
class FlushSanctumGuardState
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $guard = Auth::guard('sanctum');
        if ($guard instanceof RequestGuard) {
            $guard->forgetUser();
        }
    }
}
