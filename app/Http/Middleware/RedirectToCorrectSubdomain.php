<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectToCorrectSubdomain
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        if (Auth::guard('vendor')->check()) {
            if (!str_contains($host, 'vendor.mychitti.net')) {
                return redirect()->away('https://vendor.mychitti.net' . $request->getRequestUri());
            }
        }

        if (Auth::guard('vendor_employee')->check()) {
            if (!str_contains($host, 'vendor-employee.mychitti.net')) {
                return redirect()->away('https://vendor-employee.mychitti.net' . $request->getRequestUri());
            }
        }

        return $next($request);
    }
}
