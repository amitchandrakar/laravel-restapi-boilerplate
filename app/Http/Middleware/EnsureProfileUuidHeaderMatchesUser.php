<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileUuidHeaderMatchesUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('X-User-Profile-Uuid');

        if ($header === null || $header === '') {
            return $next($request);
        }

        $user = $request->user();

        if (!($user instanceof User)) {
            return $next($request);
        }

        if ((string) $header !== (string) $user->uuid) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'X-User-Profile-Uuid does not match the authenticated user',
                    'code' => 403,
                ],
                403
            );
        }

        return $next($request);
    }
}
