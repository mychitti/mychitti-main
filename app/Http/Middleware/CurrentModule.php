<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use App\Models\Module;

class CurrentModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // print_r(auth('admin')->user());die;
        if (request()->get('module_id')) {
            session()->put('current_module', request()->get('module_id'));
            Config::set('module.current_module_id', request()->get('module_id'));
        } else {
          
                Config::set('module.current_module_id', session()->get('current_module'));
        }

        $module_id = Config::get('module.current_module_id');
        $module_id = is_array($module_id) ? null : $module_id;
        $module = isset($module_id) ? Module::with('translations')->find($module_id) : Module::with('translations')->active()->get()->first();

        if ($module) {
            Config::set('module.current_module_id', $module->id);
            Config::set('module.current_module_type', $module->module_type);
        } else {
            Config::set('module.current_module_id', null);
            Config::set('module.current_module_type', 'settings');
        }
        if (Request::is('users*')) {
            Config::set('module.current_module_id', null);
            Config::set('module.current_module_type', 'users');
        }
        if (Request::is('transactions*')) {
            Config::set('module.current_module_id', null);
            Config::set('module.current_module_type', 'transactions');
        }
        if (Request::is('dispatch*')) {
            Config::set('module.current_module_id', null);
            Config::set('module.current_module_type', 'dispatch');
        }
        if (Request::is('business-settings/*')) {
            Config::set('module.current_module_id', null);
            Config::set('module.current_module_type', 'settings');
        }
        if (Request::is('admin/sales-crm*')) {
            Config::set('module.current_module_id', null);
            Config::set('module.current_module_type', 'sales_marketing');
        }
        if (Request::is('admin/mcvendorhub*')) {
            Config::set('module.current_module_id', null);
            Config::set('module.current_module_type', 'mcvendorhub');
        }


        // add more conditions for more modules here
        return $next($request);
    }
}
