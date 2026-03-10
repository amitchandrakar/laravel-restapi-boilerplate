<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthOrGuestCheckout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user() || session()->get('via-guest-checkout')) {
            return $next($request);
        }

        session()->put('url.intended', url()->current());

        return redirect('/login');
    }
}
