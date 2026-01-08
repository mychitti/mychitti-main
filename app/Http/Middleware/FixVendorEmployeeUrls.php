<?php

namespace App\Http\Middleware;

use Closure;

class FixVendorEmployeeUrls
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Only process HTML responses on vendor-staff domain
        if ($request->getHost() === 'vendor-staff.mcvendorhub.com' && 
            $response instanceof \Illuminate\Http\Response) {
            
            $content = $response->getContent();
            
            // Replace all vendor.mcvendorhub.com URLs with vendor-staff.mcvendorhub.com
            $content = str_replace(
                'vendor.mcvendorhub.com', 
                'vendor-staff.mcvendorhub.com', 
                $content
            );
            
            $response->setContent($content);
        }
        
        return $response;
    }
}