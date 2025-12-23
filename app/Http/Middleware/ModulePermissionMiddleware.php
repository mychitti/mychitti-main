<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Closure;

class ModulePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$modules)
    {
        if (auth('admin')->check()) {
            foreach ($modules as $module) {
                if (Helpers::module_permission_check($module)) {
                    return $next($request);
                }
            }
        }

        if (auth('vendor_employee')->check() || auth('vendor')->check()) {
            foreach ($modules as $module) {
                if (Helpers::employee_module_permission_check($module)) {
                    return $next($request);
                }
            }
        }

        Toastr::error(translate('messages.access_denied'));
        return back();
    }
}
