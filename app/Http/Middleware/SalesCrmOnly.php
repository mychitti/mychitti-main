<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SalesCrmOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth('admin')->check()) {
            return $next($request);
        }

        $admin = auth('admin')->user();

        if (!$admin->isSalesCrmOnly()) {
            return $next($request);
        }

        // Admin has zone + only sales_crm permission — restrict to CRM routes only
        $allowed = [
            'admin',
            'admin/sales-crm',
            'admin/sales-crm/*',
            'admin/settings',
            'admin/profile',
            'admin/profile/*',
            'admin/quotation/add',
            'admin/quotation/save-info-crm',
            'admin/quotation/manage/*',
        ];

        foreach ($allowed as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        return redirect()->route('admin.sales-crm.query.index');
    }
}
