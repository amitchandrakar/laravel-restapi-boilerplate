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
 * {@see RequestGuard} caches the resolved user on the guard singleton; the same PHP
 * process (tests, Octane, etc.) would otherwise keep a stale user after logout or token revocation.
 * Flushing in {@see self::terminate()} preserves {@see InteractsWithAuthentication::actingAs()}
 * for the duration of the request.
 *
 * Also restores `auth.defaults.guard` to `web`. `auth:sanctum` mutates that config via
 * {@see Auth::shouldUse()}; leaving it as `sanctum` breaks Spatie role lookups that use
 * `config('auth.defaults.guard')` (e.g. candidate registration) on the next request in-process.
 */
class FlushSanctumGuardState
{
    private const APPLICATION_GUARD = 'web';

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

        // Only restore the config value — do not call Auth::shouldUse() here, which would
        // replace the auth user resolver and break Bearer auth on the next in-process request.
        config(['auth.defaults.guard' => self::APPLICATION_GUARD]);
    }
}
