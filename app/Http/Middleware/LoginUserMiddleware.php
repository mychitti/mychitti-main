<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use App\Models\Guest;
use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Support\Facades\Log;

class LoginUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!auth('web')->user()){
            return redirect()->back()->with('error', 'Your account is not active.');
        }
        return $next($request);
    }
}
