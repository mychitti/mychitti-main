<?php

namespace App\Http\Controllers\Vendor;

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
        $search = trim($request->search ?? '');
        $key = explode(' ', request()->search);
        $tables = RestaurantTable::where('store_id', $storeId)
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('vendor-views.salespoint.tables.index', compact('tables'));
    }

    public function create()
    {
        return view('vendor-views.salespoint.tables.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required',
            'capacity'  => 'nullable|integer',
            // 'room_type' => 'required|in:ac,non_ac'
        ]);

        RestaurantTable::create([
            'store_id' => Helpers::get_store_id(),
            'name'      => $request->name,
            'capacity'  => $request->capacity,
            // 'room_type' => $request->room_type,
            'status'    => 'free'
        ]);

        Toastr::success('Table added successfully');
        return redirect()->route('vendor.pos.restaurant-tables.index');
    }

    public function edit($id)
    {
        $storeId = Helpers::get_store_id();

        $table = RestaurantTable::where('id', $id)
            ->where('store_id', $storeId)
            ->firstOrFail();

        return view('vendor-views.salespoint.tables.edit', compact('table'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'      => 'required',
            'capacity'  => 'nullable|integer',
            // 'room_type' => 'required|in:ac,non_ac',
            'status'    => 'required|in:free,occupied,reserved'
        ]);

        $storeId = Helpers::get_store_id();

        $table = RestaurantTable::where('id', $id)
            ->where('store_id', $storeId)
            ->firstOrFail();

        $table->update([
            'name'      => $request->name,
            'capacity'  => $request->capacity,
            'room_type' => $request->room_type,
            'status'    => $request->status
        ]);

        Toastr::success('Table updated successfully');
        return redirect()->route('vendor.pos.restaurant-tables.index');
    }

    public function destroy($id)
    {
        $storeId = Helpers::get_store_id();

        RestaurantTable::where('id', $id)
            ->where('store_id', $storeId)
            ->delete();

        Toastr::error('Table deleted');
        return back();
    }
}
