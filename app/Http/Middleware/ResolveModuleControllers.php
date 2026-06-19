<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Closure;
use Illuminate\Http\Request;

class ResolveModuleControllers
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('vendor')->check() || auth('vendor_employee')->check()) {
            $type = strtolower(Helpers::get_store_data()->business_type ?? '');

            if ($type === 'laundry') {
                $this->swapControllers($request, config('business_modules.laundry.controllers', []));
            } elseif ($type === 'pos') {
                $this->swapControllers($request, config('business_modules.pos.controllers', []));
            } elseif ($type === 'pos_retail') {
                $this->swapControllers($request, config('business_modules.pos_retail.controllers', []));
            } elseif ($type === 'hospital') {
                $this->swapControllers($request, config('business_modules.hmis.controllers', []));
            }
        }

        return $next($request);
    }

    private function swapControllers(Request $request, array $map): void
    {
        $route = $request->route();
        if (! $route) {
            return;
        }

        $action = $route->getAction();
        $uses   = $action['uses'] ?? null;

        if (! is_string($uses)) {
            return;
        }

        foreach ($map as $abstract => $concrete) {
            if (str_starts_with($uses, $abstract . '@')) {
                $method = substr($uses, strlen($abstract) + 1);
                $newUses = $concrete . '@' . $method;
                $action['uses']       = $newUses;
                $action['controller'] = $newUses;
                $route->setAction($action);
                $route->controller = null; // clear cached instance from pre-middleware gathering
                break;
            }
        }
    }
}
