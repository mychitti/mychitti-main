<?php

namespace App\Modules\POS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::where('store_id', Helpers::get_store_id())->paginate(10);
        return view('pos::vendor.branch.index', compact('branches'));
    }

    public function delete(Request $request, $id)
    {
        Branch::where('store_id', Helpers::get_store_id())->where("id", $id)->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }

    public function store(Request $request)
    {
        $branch = new Branch();
        $branch->name = $request->name;
        $branch->address = $request->address;
        $branch->gst_number = $request->gst_number;
        if ($request->hasFile('gst_doc')) {
            $extension = $request->file('gst_doc')->getClientOriginalExtension();
            $branch->gst_doc = Helpers::upload('store/branch/docs/', $extension, $request->file('gst_doc'));
        }
        $branch->type = $request->type;
        $branch->store_id = Helpers::get_store_id();
        $branch->save();

        Toastr::success('Added Successfully');
        return back();
    }

    public function update(Request $request)
    {
        $branch = Branch::findOrFail($request->edit_id);

        if ($branch->type !== $request->type) {
            [$allowed, $error] = canAddBranch(
                Helpers::get_store_id(),
                $request->type,
                $branch->id ?? null
            );

            if (!$allowed) {
                Toastr::error($error);
                return back();
            }
        }

        $branch->name = $request->name;
        $branch->address = $request->address;
        $branch->gst_number = $request->gst_number;
        $branch->type = $request->type;
        if ($request->hasFile('gst_doc')) {
            if ($branch->gst_doc) {
                if (Storage::disk('public')->exists('store/branch/docs/' . $branch->gst_doc)) {
                    Storage::disk('public')->delete('store/branch/docs/' . $branch->gst_doc);
                }
            }
            $extension = $request->file('gst_doc')->getClientOriginalExtension();
            $branch->gst_doc = Helpers::upload('store/branch/docs/', $extension, $request->file('gst_doc'));
        }
        $branch->save();

        Toastr::success('Updated Successfully');
        return back();
    }
}
