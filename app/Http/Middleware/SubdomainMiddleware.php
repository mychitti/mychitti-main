<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\StoreConfig;
use Symfony\Component\HttpFoundation\Response;

class SubdomainMiddleware
{
    /**
     * Handle an incoming request.
     *
     * This middleware identifies the vendor store based on the subdomain
     * and makes it available throughout the request lifecycle.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Check if this is the main mychitti.net domain (no subdomain)
        if ($host === 'mychitti.net' || $host === 'www.mychitti.net') {
            // This is the main platform - show marketplace/home
            return $next($request);
        }
        
        // Extract subdomain
        $parts = explode('.', $host);
        
        // For subdomain.mychitti.net
        if (count($parts) >= 3 && $parts[count($parts) - 2] === 'mychitti') {
            $subdomain = $parts[0];
            
            // Skip 'www' subdomain
            if ($subdomain === 'www') {
                return $next($request);
            }
            
            // Find store by custom domain
            $storeConfig = StoreConfig::where('custom_domain', $subdomain)
                ->where('website_active', true)
                ->first();
            
            if (!$storeConfig) {
                // Subdomain doesn't exist or store is inactive
                return abort(404, 'Store not found');
            }
            
            // Make store available in the request
            $request->attributes->set('vendor_store', $storeConfig->store);
            $request->attributes->set('store_config', $storeConfig);
            
            // You can also set a view variable
            view()->share('currentStore', $storeConfig->store);
            view()->share('storeConfig', $storeConfig);
            
            return $next($request);
        }
        
        // Check for custom domain
        $storeConfig = StoreConfig::where('personal_domain', $host)
            ->where('website_active', true)
            ->where('domain_status', 'active')
            ->first();
        
        if ($storeConfig) {
            $request->attributes->set('vendor_store', $storeConfig->store);
            $request->attributes->set('store_config', $storeConfig);
            
            view()->share('currentStore', $storeConfig->store);
            view()->share('storeConfig', $storeConfig);
            
            return $next($request);
        }
        
        // No matching store found
        return $next($request);
    }
}