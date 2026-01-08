<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class SetSessionCookieByGuard
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        if (str_contains($host, 'vendor-staff.')) {
            config(['session.cookie' => 'vendor_employee_session']);
        } elseif (str_contains($host, 'vendor.')) {
            config(['session.cookie' => 'vendor_session']);
        }

        return $next($request);
    }
}
