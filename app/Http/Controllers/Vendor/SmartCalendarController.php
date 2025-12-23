<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmartCalendarController extends Controller
{
    public function index(Request $request) {
       return view('vendor-views.coming-soon');
    }
}