<?php

namespace App\Modules\Laundry\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\StoreShift;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = StoreShift::where('store_id', Helpers::get_store_id())->latest()->paginate(10);
        return view('laundry::vendor.shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        StoreShift::create([
            'store_id'    => Helpers::get_store_id(),
            'name'        => $request->name,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
        ]);


        Toastr::success('Shift created successfully.');
        return back();
    }

    public function edit($id)
    {
        $shift = StoreShift::findOrFail($id);
        return view('store_shifts.edit', compact('shift'));
    }

    public function update(Request $request)
    {
        $id = $request->shift_id;
        $request->validate([
            'name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shift = StoreShift::findOrFail($id);
        $shift->update($request->all());
        Toastr::success('Shift updated successfully.');
        return redirect()->back();
    }

    public function delete($id)
    {
        StoreShift::findOrFail($id)->delete();
        Toastr::success('Shift deleted successfully.');

        return redirect()->route('vendor.shifts.index');
    }
}
