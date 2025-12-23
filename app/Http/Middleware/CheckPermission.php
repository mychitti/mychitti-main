<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, $feature, $action)
    {
        $user = Auth::guard('vendor_employee')->user();

        // Vendor bypasses all checks
        if (Auth::guard('vendor')->check()) {
            return $next($request);
        }

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if (!$user->hasPermission($feature, $action)) {
            abort(403, 'Permission denied');
        }

        return $next($request);
    }
}
