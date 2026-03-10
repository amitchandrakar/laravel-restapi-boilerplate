<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UniqueUrl;
use Closure;

class VerifyUniqueUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $segments = $request->segments();
        $name = reset($segments);
        $url = UniqueUrl::getUniqueUrlByUrl($name);

        if (!$url) {
            abort(404);
        }

        return $next($request);
    }
}
