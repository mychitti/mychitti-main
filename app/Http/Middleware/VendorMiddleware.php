<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;

class VendorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('vendor')->check()) {
            if (!auth('vendor')->user()->status) {
                return redirect('login');
            }
            return $next($request);
        } else if (Auth::guard('vendor_employee')->check()) {
            if (Auth::guard('vendor_employee')->user()->is_logged_in == 0) {
                return redirect('login');
            }
            if (!auth('vendor_employee')->user()->store->status) {
                return redirect('login');
            }
return $next($request);
        }
        $userAgent = request()->header('User-Agent');

        $platform = 'web'; // default

        if (preg_match('/(iPhone|iPad|iPod).*AppleWebKit(?!.*Safari)/i', $userAgent)) {
            $platform = 'app';
        } elseif (preg_match('/Android.*(wv|Version\/\d+\.\d+)/i', $userAgent)) {
            $platform = 'app';
        }

            return redirect('login');
    
    }
}
