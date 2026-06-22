<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Http\Request;

class RetailInventoryBasic
{
    // Retail POS (business_type = pos_retail) stores get BASIC inventory free — items, storage
    // spaces, item images and stock. These advanced areas remain part of the full (paid) Inventory
    // module and are blocked for pos_retail stores.
    private const BLOCKED = [
        'inventory/dashboard',
        'inventory/gatepass', 'inventory/gatepass/*',
        'inventory/purchase', 'inventory/purchase/*',
        'inventory/stock', 'inventory/stock/*',
        'inventory/sale', 'inventory/sale/*',
        'inventory/report', 'inventory/report/*',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (auth('admin')->check()) {
            return $next($request);
        }

        $businessType = strtolower(Helpers::get_store_data()->business_type ?? '');
        if ($businessType === 'pos_retail' && $request->is(...self::BLOCKED)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'This feature needs the full Inventory module.'], 403);
            }
            Toastr::error('Retail POS includes basic inventory only (items & stock). This feature needs the full Inventory module.');
            return redirect()->route('vendor.inventory.index');
        }

        return $next($request);
    }
}
