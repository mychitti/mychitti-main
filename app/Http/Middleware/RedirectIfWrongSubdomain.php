<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RedirectIfWrongSubdomain
{
    public function handle(Request $request, Closure $next)
    {

        $host = $request->getHost();
        $path = $request->path();

        if ($host !== 'staging.mychitti.net') {
            //  Only do redirects for GET requests (not POST logins)
            if ($request->isMethod('GET') && Str::startsWith($path, 'login')) {

                $allowedPaths = [
                    'admin.mychitti.net' => ['login/admin', 'login/admin-employee'],
                    'vendor.mychitti.net' => ['login/store'],
                    'vendor-employee.mychitti.net' => ['login/store-employee'],
                ];

                $defaultLoginPath = [
                    'admin.mychitti.net' => 'login/admin',
                    'vendor.mychitti.net' => 'login/store',
                    'vendor-employee.mychitti.net' => 'login/store-employee',
                ];

                if (isset($allowedPaths[$host])) {
                    $isAllowed = false;

                    foreach ($allowedPaths[$host] as $allowedPrefix) {
                        if (Str::startsWith($path, $allowedPrefix)) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    if (!$isAllowed) {
                        return redirect($defaultLoginPath[$host]);
                    }
                }
            }
            if (auth('vendor')->check()) {
                if (!str_contains($host, 'vendor.mychitti.net')) {
                    return redirect()->away('https://vendor.mychitti.net' . $request->getRequestUri());
                }
            }

            if (auth('vendor_employee')->check()) {
                if (!str_contains($host, 'vendor-employee.mychitti.net')) {
                    return redirect()->away('https://vendor-employee.mychitti.net' . $request->getRequestUri());
                }
            }
        }




        return $next($request);
    }
}
