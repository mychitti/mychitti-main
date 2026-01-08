<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class SetSessionCookieName
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        if (Str::contains($host, 'vendor-staff')) {
            config(['session.cookie' => 'vendor_employee_session']);
        } elseif (Str::contains($host, 'vendor')) {
            config(['session.cookie' => 'vendor_session']);
        }

        return $next($request);
    }
}
