<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Zone;
use App\Models\Store;
use App\CentralLogics\StoreLogic;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Translation;
use App\Models\VendorEmployee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class VendorLoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $vendor_type= $request->vendor_type;

        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if($vendor_type == 'owner'){
            if (auth('vendor')->attempt($data)) {
                $token = $this->genarate_token($request['email']);
                $vendor = Vendor::where(['email' => $request['email']])->first();
                if($vendor->stores[0]->status == 0 && $vendor->status == 0)
                {
                    return response()->json([
                        'errors' => [
                            ['code' => 'auth-002', 'message' => translate('messages.Your_registration_is_not_approved_yet._You_can_login_once_admin_approved_the_request')]
                        ]
                    ], 403);
                } elseif($vendor->stores[0]->status == 0 && $vendor->status == 1){
                    return response()->json([
                        'errors' => [
                            ['code' => 'auth-002', 'message' => translate('messages.Your_account_is_suspended')]
                        ]
                    ], 403);
                }

              
                $vendor->firebase_token = $request->firebase_token;
                $vendor->auth_token = $token;
                $vendor->save();
                return response()->json(['token' => $token, 'zone_wise_topic'=> $vendor->stores[0]->zone->store_wise_topic], 200);
            }  else {
                $errors = [];
                array_push($errors, ['code' => 'auth-001', 'message' => translate('Credential_do_not_match,_please_try_again')]);
                return response()->json([
                    'errors' => $errors
                ], 401);
            }

        }elseif($vendor_type == 'employee'){

            if (auth('vendor_employee')->attempt($data)) {
                $token = $this->genarate_token($request['email']);
                $vendor = VendorEmployee::where(['email' => $request['email']])->first();
                if($vendor->store->status == 0)
                {
                    return response()->json([
                        'errors' => [
                            ['code' => 'auth-002', 'message' => translate('messages.Your_account_is_suspended')]
                        ]
                    ], 403);
                }
                $vendor->firebase_token = $request->firebase_token;
                $vendor->auth_token = $token;
                $vendor->save();
                $role = $vendor->role ? json_decode($vendor->role->modules):[];
                return response()->json(['token' => $token, 'zone_wise_topic'=> $vendor->store->zone->store_wise_topic, 'role'=>$role], 200);
            } else {
                $errors = [];
                array_push($errors, ['code' => 'auth-001', 'message' => translate('Credential_do_not_match,_please_try_again')]);
                return response()->json([
                    'errors' => $errors
                ], 401);
            }
        } else {
            $errors = [];
            array_push($errors, ['code' => 'auth-001', 'message' => translate('Credential_do_not_match,_please_try_again')]);
            return response()->json([
                'errors' => $errors
            ], 401);
        }

    }

    private function genarate_token($email)
    {
        $token = Str::random(120);
        $is_available = Vendor::where('auth_token', $token)->where('email', '!=', $email)->count();
        if($is_available)
        {
            $this->genarate_token($email);
        }
        return $token;
    }
 

    public function login_new(Request $request){
   
    }
    public function verify_otp(Request $request)
    {
        $request->validate([
            'otp'   => 'required',
            'phone' => 'required'
        ]);

        $vendor = Vendor::where('phone', $request->phone)->first();

        if (!$vendor) {
            return response()->json(['errors' => [['code' => 'auth-001', 'message' => 'Phone number does not exist']]], 401);
        }

        if (!_verify_otp($request->phone, $request->otp)) {
            return response()->json(['errors' => [['code' => 'auth-001', 'message' => 'Invalid or expired OTP.']]], 401);
        }

        if (($vendor->stores[0]->status ?? 1) == 0) {
            return response()->json(['errors' => [['code' => 'auth-001', 'message' => 'Vendor is inactive.']]], 401);
        }

        Auth::guard('vendor')->login($vendor);

        if (auth('vendor')->check()) {
            return response()->json(['status' => true, 'message' => 'Login successful.']);
        }

        return response()->json(['errors' => [['code' => 'auth-001', 'message' => 'OTP login failed.']]], 401);
    }

    public function send_vendor_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $phone = $request->phone;
        $exists = Store::where('phone', $phone)->exists();

        if (!$exists) {
            return response()->json(['status' => false, 'message' => 'This phone is not registered']);
        }

        $check = _check_otp_send_allowed($phone);
        if (!$check['allowed']) {
            return response()->json(['status' => false, 'message' => $check['message']], 429);
        }

        $otp = rand(1000, 9999);
        _send_confirmation_sms('mobile_verification', $phone, $otp);
        _store_otp($phone, $otp);

        return response()->json(['status' => true, 'message' => 'OTP sent successfully.', 'action' => 'otp_sent', 'phone' => $phone]);
    }
    

    public function register(Request $request)
    {
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            return response()->json(['errors' => Helpers::error_processor('self-registration', translate('messages.store_self_registration_disabled'))]);
        }

        $rules = [
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'latitude' => 'required',
            'longitude' => 'required',
            'email' => 'required|unique:vendors',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:vendors',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'zone_id' => 'required',
            'module_id' => 'required',
            'logo' => 'required',
            'category_1' => 'required',
        ];
        
        // Custom error messages
        $messages = [
            'password.required' => translate('The password is required'),
            'password.min_length' => translate('The password must be at least :min characters long'),
            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
            'password.letters' => translate('The password must contain letters'),
            'password.numbers' => translate('The password must contain numbers'),
            'password.symbols' => translate('The password must contain symbols'),
            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
        ];
        
        // Add conditional validation rules
        if (!isset($request->confirmation) || $request->confirmation == '' || $request->module_id == '5') {
            $rules['gst_doc'] = 'required|mimes:png,jpg,jpeg,pdf';
            $rules['gst_number'] = 'required';
        }
        
        if ($request->business_type == 'business') {
            $rules['google_verification'] = 'required';
        }
        
        if ($request->module_id != '6') {
            $rules['minimum_delivery_time'] = 'required';
            $rules['maximum_delivery_time'] = 'required';
            $rules['delivery_time_type'] = 'required';
        }
        
        // Perform validation
        $validator = Validator::make($request->all(), $rules, $messages);
        
        // Check for validation failure
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        if($request->zone_id)
        {
            $zone = Zone::query()
            ->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))
            ->where('id',$request->zone_id)
            ->first();
            if(!$zone){
                $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
                return response()->json(['errors' => Helpers::error_processor($validator)], 403);
            }
        }

        $data = json_decode($request->translations, true);

        if (count($data) < 1) {
            $validator->getMessageBag()->add('translations', translate('messages.Name and description in english is required'));
        }

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

         // cross verification 
         $verify_mobile  = DB::table('phone_otp')->where([
            'phone' =>  $request->phone,
            'otp' => $request->otp
        ])->exists();

        if (!$verify_mobile) {
            return response()->json(['message'=>translate('messages.application_placed_successfully')],403);
        }

        $vendor = new Vendor();
        $vendor->f_name = $request->f_name;
        $vendor->l_name = $request->l_name;
        $vendor->email = $request->email;
        $vendor->phone = $request->phone;
        $vendorImage = Helpers::upload('vendor/', 'png', $request->file('logo'));
        $vendor->image = $vendorImage;
        $vendor->secondary_phone = $request->secondary_phone;
        $vendor->password = bcrypt($request->password);
        $vendor->status = null;
        $vendor->save();
 
        $store = new Store;
        $store->name = $data[0]['value'];
        $store->phone = $request->phone;
        $store->business_type = $request->business_type;
        $store->secondary_phone = $request->secondary_phone;
        $store->gst_number = $request->gst_num;
        $store->email = $request->email; 
        $store->vendor_type = $request->vendor_type ?? 'regular';
        $storeLogo = Helpers::upload('store/', 'png', $request->file('logo'));
        $store->logo = $storeLogo;
        $storeCover = $request->hasFile('cover_photo') ? Helpers::upload('store/cover/', 'png', $request->file('cover_photo')) : null;
        $store->cover_photo = $storeCover;
        $store->address = $data[1]['value'];
        $store->latitude = $request->latitude;
        $store->longitude = $request->longitude;
        $store->vendor_id = $vendor->id;
        $store->zone_id = $request->zone_id;
        // $store->tax = $request->tax;
        // $store->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $store->module_id = strtolower($request->business_type ?? '') === 'ecommerce' ? 5 : $request->module_id;
        if(!isset($request->minimum_delivery_time)){

            $store->delivery_time = 0 . '-' . 0 . ' ' . 'minutes';
        }else{
            $store->delivery_time = $request->minimum_delivery_time . '-' . $request->maximum_delivery_time . ' ' . $request->delivery_time_type;

        }
        $store->status = 0;
        $store->category_1 = $request->category_1;
        $store->category_2 = $request->category_2;
        $store->services_1 = isset($request->services_1) ? implode(',',$request->services_1) : null;
        $store->services_2 = isset($request->services_2) ? implode(',',$request->services_2) : null;
        if ($request->business_type == 'business') {
            $store->other_verification = $request->other_verification ?? '';
            $store->google_verification = $request->google_verification;
        }
        $gstDoc = null;
        if (!isset($request->confirmation) || $request->confirmation == ''|| $request->module_id == '5') {
            $extension = $request->file('gst_doc')->getClientOriginalExtension();
            $gstDoc = Helpers::upload('store/docs/', $extension, $request->file('gst_doc'));
            $store->gst_doc = $gstDoc;
            $store->gst_number = $request->gst_number;
        }
        $store->save();

        _logVendorFile('vendor', $vendor->id, $store->id, 'profile_image', 'vendor/' . $vendorImage);
        _logVendorFile('vendor', $vendor->id, $store->id, 'store_logo', 'store/' . $storeLogo);
        if (!empty($storeCover)) {
            _logVendorFile('vendor', $vendor->id, $store->id, 'store_cover', 'store/cover/' . $storeCover);
        }
        if (!empty($gstDoc)) {
            _logVendorFile('vendor', $vendor->id, $store->id, 'store_gst_doc', 'store/docs/' . $gstDoc);
        }

        Helpers::_addWelcomeCouponsIfExist($store);
        $store->module->increment('stores_count');

         // services  offered 1
         if(isset($request->services_1) && count($request->services_1)){
            foreach ($request->services_1 as $key => $value) {
                $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                $storeArr = explode(',', $fetchServicesRow->store_ids);
                array_push($storeArr, $store->id);
                $newStoreString = implode(',', $storeArr);

                $oldIds = _getIdsFrist($value);
                DB::table('items')->where('id', $value)->update(['store_ids' => $newStoreString]);
                _trackStoreIds('test 18', $newStoreString, $value, '-', 'api_ven_reg',$oldIds);

            }
        }
        // services  offered 1
        if( isset($request->services_2) && count($request->services_2)){
            foreach ($request->services_2 as $key => $value) {
                $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                $storeArr = explode(',', $fetchServicesRow->store_ids);
                array_push($storeArr, $store->id);
                $newStoreString = implode(',', $storeArr);

                $oldIds = _getIdsFrist($value);
                DB::table('items')->where('id', $value)->update(['store_ids' => $newStoreString]);
                _trackStoreIds('test 17', $newStoreString, $value, '-', 'api_ven_reg',$oldIds);

            }
        }

         StoreLogic::insert_schedule($store->id);
       

        foreach ($data as $key=>$i) {
            $data[$key]['translationable_type'] = 'App\Models\Store';
            $data[$key]['translationable_id'] = $store->id;
        }
        Translation::insert($data);

        try{
             $admin = Admin::where('role_id', 1)->first();
            Mail::to($admin['email'])->send(new \App\Mail\VendorSelfRegistration('pending', $vendor->f_name . ' ' . $vendor->l_name));
            Mail::to($admin['email'])->send(new \App\Mail\StoreRegistration('pending', $vendor->f_name . ' ' . $vendor->l_name));
        
        }catch(\Exception $ex){
            info($ex->getMessage());
        }

        return response()->json(['message'=>translate('messages.application_placed_successfully')],200);
    }
}
