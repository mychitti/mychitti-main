<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;

class ResolveStoreByDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
      
        // your system domains (important for your setup)
        $systemDomains = [
            'mychitti.net',
            'www.mychitti.net',
            'vendor.mcvendorhub.com',
            'vendor-staff.mcvendorhub.com',
            'staging.mychitti.net'
        ];

        // If system domain → skip resolving
        if (in_array($host, $systemDomains)) {
            return $next($request);
        }

        // Try to find store by custom domain
        $store = Store::where('domain', $host)->first();

        if ($store) {
            prx($store);
            die;
        } else {
            // echo 'no stoer';
            // die;
        }

        return $next($request);
    }
}
