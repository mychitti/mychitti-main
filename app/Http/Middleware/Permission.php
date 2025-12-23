<?php

namespace App\Http\Middleware;

use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $feature
     * @param  string  $action
     * @return mixed
     */
    public function handle($request, Closure $next, $feature, $action)
    {
        $user = Auth::guard('vendor_employee')->user();
        // vendor has full access
        if (auth('vendor')->check()) {
            return $next($request);
        }

        if (! $user) {
            Toastr::error(translate('messages.unauthorized'));
            return back();
        }
        // Example: check from your DB
        $hasPermission = DB::table('role_feature_permissions as rfp')
            ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
            ->join('features as f', 'fp.feature_id', '=', 'f.id')
            ->where('rfp.role_id', $user->employee_role_id)
            ->where('f.name', $feature)
            ->where('fp.action', $action)
            ->exists();

        if (! $hasPermission) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }

        return $next($request);
    }
}
