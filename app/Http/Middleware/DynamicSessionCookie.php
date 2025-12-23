<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DynamicSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        if (str_contains($host, 'vendor-employee')) {
            config(['session.cookie' => 'vendor_employee_session']);
            config(['auth.defaults.guard' => 'vendor_employee']);
            $request->attributes->set('guard', 'vendor_employee');
        } elseif (str_contains($host, 'vendor')) {
            config(['session.cookie' => 'vendor_session']);
            config(['auth.defaults.guard' => 'vendor']);
            $request->attributes->set('guard', 'vendor');
        } else {
            config(['session.cookie' => 'laravel_session']);
            config(['auth.defaults.guard' => 'web']);
            $request->attributes->set('guard', 'web');
        }

        return $next($request);
    }
}
