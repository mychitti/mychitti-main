<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VendorRequirement;
use Brian2694\Toastr\Facades\Toastr;

class RequirementController extends Controller
{
    public function delete(Request $request, $id)
    {
        VendorRequirement::find($id)->delete();
        Toastr::success('Requirement Deleted Successfully');
        return back();
    }
}
