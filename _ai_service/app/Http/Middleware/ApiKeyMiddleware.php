<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware 
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-Api-Key');

        if (empty($key) || $key !== config('services.ai_service.key')) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request); 
    }
}
