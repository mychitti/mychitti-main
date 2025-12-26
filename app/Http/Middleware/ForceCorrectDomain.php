<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCorrectDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        if (auth('vendor')->check() && !str_contains($host, 'vendor.mychitti.shop')) {
            return redirect()->to('https://vendor.mychitti.shop' . $request->getRequestUri());
        }

        if (auth('vendor_employee')->check() && !str_contains($host, 'vendor-employee.mychitti.shop')) {
            return redirect()->to('https://vendor-employee.mychitti.shop' . $request->getRequestUri());
        }

        return $next($request);
    }
}
