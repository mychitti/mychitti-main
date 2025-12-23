<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;

use Closure;
use Illuminate\Http\Request;

class PermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $module)
    {
        if ($module === 'quotation_manage') {
            $module = 'quotaiton_manage';
        }
        // prx($module); 

        if (auth('admin')->check()) {
            return $next($request);
        }
        // elseif($module == 'account_manage' &&  ((auth('vendor_employee')->check() && auth('vendor_employee')->user()->store->module_id == 5) || (auth('vendor')->check() && auth('vendor')->user()->stores[0]->module_id == 5))){

        //     return $next($request);
        // }
        else if (auth('vendor_employee')->check() || auth('vendor')->check()) {

            if (Helpers::permission_check($module)) {
                return $next($request);
            }
        }

        Toastr::error(translate('messages.access_denied'));
        return back();
    }
}
