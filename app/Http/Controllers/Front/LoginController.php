<?php

namespace App\Http\Controllers\Front;
use App\CentralLogics\Helpers;
use App\Models\ServiceRequest;
use App\CentralLogics\CategoryLogic;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\CentralLogics\StoreLogic;
use App\CentralLogics\ProductLogic;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\AdminFeature;
use App\Models\AdminPromotionalBanner;
use App\Models\AdminSpecialCriteria;
use App\Models\AdminTestimonial;
use App\Models\BusinessSetting;
use App\Models\DataSetting;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\File;
use GuzzleHttp\Client;
use App\Models\Module;

use App\Http\Controllers\Api\V1\StoreController;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        /*$this->middleware('auth');*/
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function login()
    {
        return view('front-views.home', compact('stores', 'data', 'items'));
    }
  
}