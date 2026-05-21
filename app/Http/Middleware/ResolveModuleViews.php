<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\FileViewFinder;

class ResolveModuleViews
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('vendor')->check() || auth('vendor_employee')->check()) {
            $type = strtolower(Helpers::get_store_data()->business_type ?? '');

            if ($type === 'laundry') {
                $this->prependViewPath(config('business_modules.laundry.views'));
            } elseif ($type === 'pos') {
                $this->prependViewPath(config('business_modules.pos.views'));
            } elseif ($type === 'hospital') {
                $this->prependViewPath(config('business_modules.hmis.views'));
            }
        }

        return $next($request);
    }

    private function prependViewPath(?string $path): void
    {
        if (! $path || ! is_dir($path)) {
            return;
        }

        $finder = view()->getFinder();

        if ($finder instanceof FileViewFinder) {
            $finder->prependLocation($path);
        }
    }
}
