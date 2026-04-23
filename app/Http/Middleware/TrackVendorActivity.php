<?php 
use Closure;
use App\Models\VendorLoginLog;

public function handle($request, Closure $next)
{
    if (auth()->check() && session()->has('login_log_id')) {

        $lastUpdate = session('last_activity_update');
        $now = now();

        // only update every 2 minutes
        if (!$lastUpdate || $now->diffInSeconds($lastUpdate) > 120) {

            VendorLoginLog::where('id', session('login_log_id'))
                ->update([
                    'last_activity_at' => $now
                ]);

            // store last update time in session
            session(['last_activity_update' => $now]);
        }
    }

    return $next($request);
}