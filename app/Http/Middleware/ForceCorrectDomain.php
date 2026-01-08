<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCorrectDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        if (auth('vendor')->check() && !str_contains($host, 'vendor.mcvendorhub.com')) {
            return redirect()->to('https://vendor.mcvendorhub.com' . $request->getRequestUri());
        }

        if (auth('vendor_employee')->check() && !str_contains($host, 'vendor-staff.mcvendorhub.com')) {
            return redirect()->to('https://vendor-staff.mcvendorhub.com' . $request->getRequestUri());
        }

        return $next($request);
    }
}
