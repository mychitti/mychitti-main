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
            $request->attributes->set('store_domain_store', $store);

            // Only the domain ROOT renders the store's storefront homepage. Every other
            // path (product, gallery, category, cart…) must fall through to normal routing —
            // otherwise each internal link on the custom domain just re-renders the homepage,
            // which looks like "links redirect to the same page".
            if (trim($request->path(), '/') === '') {
                $result = (new FrontController())->store_details($request, _selectedCity(), $store->slug);
                return Router::toResponse($request, $result);
            }

            return $next($request);
        } else {
            return redirect('https://mychitti.net');
        }
    }
} 
