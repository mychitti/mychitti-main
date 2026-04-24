<?php

namespace App\Http\Middleware;

use Closure;

class RegisteredUserMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!auth('web')->check()) {
            return redirect()->route('home')->with('error', 'Please log in to access this page.');
        }
        return $next($request);
    }
}
