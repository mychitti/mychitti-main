<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Store;
use App\Models\Module;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\CentralLogics\StoreLogic;
use App\Models\Admin; 
use App\Models\Category;
use App\Models\StoreDocument;
use App\Models\StoreType;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Gregwar\Captcha\CaptchaBuilder;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Validation\Rules\Password;

class VendorController extends Controller
{
    // create() / check_business() / store_ajax() moved to MC Vendor Hub with the Business
    // Listing flow — see mcvendorhub's VendorRegistrationController.


    public function send_confirmation_sms(Request $request)
    {
        // for testing purpose only - not used
        $sms_type = $request->sms_type;
        $phone = $request->phone;
        $otp =  rand(1000, 9999);
        // 2407145545136643741
        $apikey = "PH73e7LuzUGqwSWbO8ta5A";
        $apisender = "MCHITI";
        if ($sms_type == 'mobile_verification') {
            $msg =  "Dear User , Your OTP for Mobile verification is " . $otp . " - Regards MY CHITTI APP.";
        } else {
            $msg =  "Dear User , Your OTP for Mobile verification is " . $otp . " - Regards MY CHITTI APP.";
        }
        $num = '91' . $phone;
        $ms = rawurlencode($msg); //This for encode your message content
        $url = 'https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=' . $apikey . '&senderid=' . $apisender .
            '&channel=2&DCS=0&flashsms=0&number=' . $num . '&text=' . $ms . '&route=1';
        //echo $url; 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        $data = json_decode(curl_exec($ch));
        // echo 1;
        return response($data->ErrorMessage);/* result of API call*/
    }


    public function get_all_modules(Request $request)
    {
        $module_data = Module::whereHas('zones', function ($query) use ($request) {
            $query->where('zone_id', $request->zone_id);
        })->notParcel()
            ->where('modules.module_name', 'like', '%' . $request->q . '%')
            ->limit(8)->get([DB::raw('modules.id as id, modules.module_name as text')]);
        return response()->json($module_data);
    }
}
