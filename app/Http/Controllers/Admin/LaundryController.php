<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Models\LaundryChallan;
use App\Models\Store;
use Illuminate\Http\Request;

class LaundryController extends Controller
{
    public function orders(Request $request)
    {
        $search  = $request->get('search');
        $storeId = $request->get('store_id');
        $status  = $request->get('status');
        $from    = $request->get('from');
        $to      = $request->get('to');

        $query = LaundryOrder::with('customer')
            ->join('stores', 'stores.id', '=', 'laundry_orders.store_id')
            ->select('laundry_orders.*', 'stores.name as store_name');

        if ($storeId) $query->where('laundry_orders.store_id', $storeId);
        if ($status)  $query->where('laundry_orders.status', $status);
        if ($from)    $query->whereDate('laundry_orders.drop_date', '>=', $from);
        if ($to)      $query->whereDate('laundry_orders.drop_date', '<=', $to);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('laundry_orders.order_no', 'like', "%{$search}%")
                  ->orWhere('laundry_orders.customer_name', 'like', "%{$search}%")
                  ->orWhere('laundry_orders.customer_phone', 'like', "%{$search}%")
                  ->orWhere('stores.name', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderByDesc('laundry_orders.created_at')->paginate(25)->appends($request->query());
        $stores = Store::select('id', 'name')->orderBy('name')->get();

        return view('admin-views.laundry.orders', compact('orders', 'stores', 'search', 'storeId', 'status', 'from', 'to'));
    }

    public function challans(Request $request)
    {
        $search  = $request->get('search');
        $storeId = $request->get('store_id');
        $status  = $request->get('status');
        $from    = $request->get('from');
        $to      = $request->get('to');

        $query = LaundryChallan::with('hotel', 'items')
            ->join('stores', 'stores.id', '=', 'laundry_challans.store_id')
            ->select('laundry_challans.*', 'stores.name as store_name');

        if ($storeId) $query->where('laundry_challans.store_id', $storeId);
        if ($status)  $query->where('laundry_challans.status', $status);
        if ($from)    $query->whereDate('laundry_challans.outward_date', '>=', $from);
        if ($to)      $query->whereDate('laundry_challans.outward_date', '<=', $to);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('laundry_challans.challan_no', 'like', "%{$search}%")
                  ->orWhere('stores.name', 'like', "%{$search}%");
            });
        }

        $challans = $query->orderByDesc('laundry_challans.created_at')->paginate(25)->appends($request->query());
        $stores   = Store::select('id', 'name')->orderBy('name')->get();

        return view('admin-views.laundry.challans', compact('challans', 'stores', 'search', 'storeId', 'status', 'from', 'to'));
    }
}
