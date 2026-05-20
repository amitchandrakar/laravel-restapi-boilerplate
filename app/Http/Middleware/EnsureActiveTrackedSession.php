<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\UserActionLogService;
use App\Support\ApiResponseBuilder;
use App\Support\SanctumPlainTokenHasher;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveTrackedSession
{
    public function __construct(private readonly UserActionLogService $userActionLogService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        /** @var PersonalAccessToken|TransientToken|null $access */
        $access = $user->currentAccessToken();

        if ($access === null || $access instanceof TransientToken) {
            return $next($request);
        }

        $plain = (string) ($request->bearerToken() ?? '');
        $hash = SanctumPlainTokenHasher::hashPlainTextToken($plain);

        if ($hash === '' || !$this->userActionLogService->hasActiveUserSession((int) $user->id, $hash)) {
            return ApiResponseBuilder::error(
                'Session is no longer active. Please sign in again.',
                403,
                ApiResponseBuilder::ERROR_SESSION_INVALID,
                'Tracked session missing or expired.'
            );
        }

        return $next($request);
    }
}
