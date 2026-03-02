<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActivationCheckMiddleware
{
    /**
     * Always passes — activation check removed.
     */ 
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
