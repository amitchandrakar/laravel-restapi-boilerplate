<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * Force API requests to always expect JSON responses
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force Accept header to be application/json for API routes
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
