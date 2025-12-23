<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AccountOption;
use App\Models\MonthlyMaintanance;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{ 
    public function index()
    {
        return view('vendor-views.coming-soon');
    }
} 