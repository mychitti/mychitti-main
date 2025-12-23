<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\VendorModuleInstruction;
use Illuminate\Http\Request;

class ModuleInfoController extends Controller
{
    public function module_info(Request $request, $module)
    {
        $module = VendorModuleInstruction::where('slug', $module)->first();
        return view('front-views.vendor_module', compact('module'));
    }
}
