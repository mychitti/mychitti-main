<?php

namespace App\Http\Middleware;

use Closure;

class FixVendorEmployeeUrls
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Only process HTML responses on vendor-employee domain
        if ($request->getHost() === 'vendor-employee.mychitti.shop' && 
            $response instanceof \Illuminate\Http\Response) {
            
            $content = $response->getContent();
            
            // Replace all vendor.mychitti.shop URLs with vendor-employee.mychitti.shop
            $content = str_replace(
                'vendor.mychitti.shop', 
                'vendor-employee.mychitti.shop', 
                $content
            );
            
            $response->setContent($content);
        }
        
        return $response;
    }
}