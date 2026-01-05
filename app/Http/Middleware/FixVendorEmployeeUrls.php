<?php

namespace App\Http\Middleware;

use Closure;

class FixVendorEmployeeUrls
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Only process HTML responses on vendor-employee domain
        if ($request->getHost() === 'vendor-employee.mychitti.net' && 
            $response instanceof \Illuminate\Http\Response) {
            
            $content = $response->getContent();
            
            // Replace all vendor.mychitti.net URLs with vendor-employee.mychitti.net
            $content = str_replace(
                'vendor.mychitti.net', 
                'vendor-employee.mychitti.net', 
                $content
            );
            
            $response->setContent($content);
        }
        
        return $response;
    }
}