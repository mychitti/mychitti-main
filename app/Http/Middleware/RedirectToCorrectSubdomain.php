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
            if (!str_contains($host, 'vendor.mychitti.shop')) {
                return redirect()->away('https://vendor.mychitti.shop' . $request->getRequestUri());
            }
        }

        if (Auth::guard('vendor_employee')->check()) {
            if (!str_contains($host, 'vendor-employee.mychitti.shop')) {
                return redirect()->away('https://vendor-employee.mychitti.shop' . $request->getRequestUri());
            }
        }

        return $next($request);
    }
}
