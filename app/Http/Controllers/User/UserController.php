<?php

namespace App\Http\Controllers\User;
use App\CentralLogics\Helpers;
use App\Models\ServiceRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(){
        return redirect('website/index.php');
        // return view('user.home');
    }
    
      public function expire(){
        $toBeExpired = ServiceRequest::whereNull('accepted')->where('created_at', '<', now()->subMinutes(Helpers::get_lead_exp_minutes()))->update(['expired' => '1']);
        // send notification to users when there lead expires
    }
    
 
}
