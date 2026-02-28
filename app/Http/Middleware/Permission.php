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
    public function handle($request, Closure $next, ...$params)
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
        // check if free
        if (auth('vendor')->check()) {

            $featureAction = DB::table('feature_permissions as fp')
                ->join('features as f', 'fp.feature_id', '=', 'f.id')
                ->where('f.name', $params[0])
                ->where('fp.action', $params[1]) 
                ->select('f.master_module', 'fp.free')
                ->first();

            if ($featureAction && $featureAction->free == 1) {
                return $next($request);
            }
        }

        // Support multiple feature,action pairs (OR logic): permission:task,add,project_task,add
        $pairs = array_chunk($params, 2);
        foreach ($pairs as [$feature, $action]) {
            $hasPermission = DB::table('role_feature_permissions as rfp')
                ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
                ->join('features as f', 'fp.feature_id', '=', 'f.id')
                ->where('f.name', $feature)
                ->where('fp.action', $action)
                ->where('rfp.role_id', $user->employee_role_id)
                ->exists();
            if ($hasPermission) {
                return $next($request);
            }
        }

        Toastr::error(translate('messages.access_denied'));
        return back();
    }
}
