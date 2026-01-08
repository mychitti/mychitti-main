<?php

namespace App\Http\Middleware;

use Closure;

class FixVendorEmployeeUrls
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Only process HTML responses on vendor-employee domain
        if ($request->getHost() === 'vendor-employee.mcvendorhub.com' && 
            $response instanceof \Illuminate\Http\Response) {
            
            $content = $response->getContent();
            
            // Replace all vendor.mcvendorhub.com URLs with vendor-employee.mcvendorhub.com
            $content = str_replace(
                'vendor.mcvendorhub.com', 
                'vendor-employee.mcvendorhub.com', 
                $content
            );
            
            $response->setContent($content);
        }
        
        return $response;
    }
}