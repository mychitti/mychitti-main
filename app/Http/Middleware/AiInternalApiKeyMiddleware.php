<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AiInternalApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = config('services.ai_service.key');

        if (empty($key) || $request->header('X-Api-Key') !== $key) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        return $next($request); 
    }
}
