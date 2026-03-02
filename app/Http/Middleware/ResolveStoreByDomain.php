<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Http\Controllers\Front\FrontController;
use Illuminate\Routing\Router;

class ResolveStoreByDomain
{ 
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
      
        // your system domains (important for your setup)
        $systemDomains = [
            'mychitti.net',
            'www.mychitti.net', 
            'admin.mychitti.net',
            'www.admin.mychitti.net',
            'mcvendorhub.com',
            'www.mcvendorhub.com',
            'binoculars.mcvendorhub.com',
            'www.binoculars.mcvendorhub.com',
            'vendor.mcvendorhub.com',
            'www.vendor.mcvendorhub.com',
            'vendor-staff.mcvendorhub.com',
            'www.vendor-staff.mcvendorhub.com',
            'staging.mychitti.net',
            'www.staging.mychitti.net'
        ];

        // If system domain → skip resolving
        if (in_array($host, $systemDomains)) { 
            return $next($request);
        } 
  
        // Try to find store by custom domain 
        $store = Store::where('domain', $host)->first();

        if ($store) {
            $request->attributes->set('is_store_domain', true);
            $result = (new FrontController())->store_details($request, _selectedCity(), $store->slug);
            return Router::toResponse($request, $result);
        } else {
            return redirect('https://mychitti.net');
        }
    }
} 
