<?php

namespace App\Modules\POS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class RestaurantTableController extends Controller
{
    public function index()
    {
        $storeId = Helpers::get_store_id();
        $key = explode(' ', request()->search ?? '');
        $tables = RestaurantTable::where('store_id', $storeId)
            ->when(request()->search, function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('pos::vendor.salespoint.tables.index', compact('tables'));
    }

    public function create()
    {
        return view('pos::vendor.salespoint.tables.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'capacity' => 'nullable|integer',
        ]);

        RestaurantTable::create([
            'store_id' => Helpers::get_store_id(),
            'name'     => $request->name,
            'capacity' => $request->capacity,
            'status'   => 'free'
        ]);

        Toastr::success('Table added successfully');
        return hasPermission('restaurant_tables', 'list')
            ? redirect()->route('vendor.pos.restaurant-tables.index')
            : redirect()->route('vendor.pos.restaurant-tables.create');
    }

    public function edit($id)
    {
        $storeId = Helpers::get_store_id();

        $table = RestaurantTable::where('id', $id)
            ->where('store_id', $storeId)
            ->firstOrFail();

        return view('pos::vendor.salespoint.tables.edit', compact('table'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required',
            'capacity' => 'nullable|integer',
            'status'   => 'required|in:free,occupied,reserved'
        ]);

        $storeId = Helpers::get_store_id();

        $table = RestaurantTable::where('id', $id)
            ->where('store_id', $storeId)
            ->firstOrFail();

        $table->update([
            'name'     => $request->name,
            'capacity' => $request->capacity,
            'room_type' => $request->room_type,
            'status'   => $request->status
        ]);

        Toastr::success('Table updated successfully');
        return hasPermission('restaurant_tables', 'list')
            ? redirect()->route('vendor.pos.restaurant-tables.index')
            : redirect()->route('vendor.pos.restaurant-tables.edit', $id);
    }

    public function destroy($id)
    {
        $storeId = Helpers::get_store_id();

        RestaurantTable::where('id', $id)
            ->where('store_id', $storeId)
            ->delete();

        Toastr::error('Table deleted');
        return hasPermission('restaurant_tables', 'list')
            ? redirect()->route('vendor.pos.restaurant-tables.index')
            : back();
    }
}
