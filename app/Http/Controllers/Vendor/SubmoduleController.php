<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\EmployeeRole;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Store;
use App\Models\StoreEnabledModule;

class SubmoduleController extends Controller
{
    public function show_offer(Request $request, $module)
    {
        return view('vendor-views.sub-module.offer.' . $module . '_offer');
    }
    public function enable(Request $request, $module)
    {
        $store = Helpers::get_store_data();

        $new_module = new StoreEnabledModule();
        $new_module->store_id = $store->id;
        $new_module->submodule_id = $module;
        $new_module->start_date	 = date('Y-m-d');
        $new_module->type = 'postpaid';
        $new_module->save();
        
        Toastr::success('Enabled Successfully');

        return redirect()->route('vendor.sub-module.list');
    }
    public function list(Request $request, $module = null)
    {
        return view('vendor-views.sub-module.list');
    }
}
