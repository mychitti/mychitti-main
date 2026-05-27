<?php

namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function notificationTest(Request $request)
    {
       return view('front-views.notification_test'); 
    }
}