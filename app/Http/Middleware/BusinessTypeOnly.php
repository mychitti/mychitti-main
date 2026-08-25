<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Http\Request;

class BusinessTypeOnly
{
    public function handle(Request $request, Closure $next, ...$types)
    {
        if (auth('admin')->check()) {
            return $next($request);
        }

        if (! auth('vendor')->check() && ! auth('vendor_employee')->check()) {
            return $next($request);
        }

        $current = strtolower(Helpers::get_store_data()->business_type ?? '');
        $allowed = array_map('strtolower', $types);

        if (in_array($current, $allowed, true)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => false, 'message' => translate('messages.access_denied')], 403);
        }

        Toastr::error(translate('messages.access_denied'));
        return redirect()->route('vendor.dashboard');
    }
}
