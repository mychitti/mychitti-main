<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCorrectDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        if (auth('vendor')->check() && !str_contains($host, 'vendor.mychitti.net')) {
            return redirect()->to('https://vendor.mychitti.net' . $request->getRequestUri());
        }

        if (auth('vendor_employee')->check() && !str_contains($host, 'vendor-employee.mychitti.net')) {
            return redirect()->to('https://vendor-employee.mychitti.net' . $request->getRequestUri());
        }

        return $next($request);
    }
}
