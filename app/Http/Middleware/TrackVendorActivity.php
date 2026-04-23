<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\VendorLoginLog;

class TrackVendorActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('vendor')->check() && session()->has('login_log_id')) {

            $lastUpdate = session('last_activity_update');
            $now = now();

            // throttle: update only every 2 minutes
            if (!$lastUpdate || $now->diffInSeconds($lastUpdate) > 120) {

                VendorLoginLog::where('id', session('login_log_id'))
                    ->update([
                        'last_activity_at' => $now
                    ]);

                session(['last_activity_update' => $now]);
            }
        }

        return $next($request);
    }
}