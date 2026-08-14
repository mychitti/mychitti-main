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
    public function create()
    {
        // The listing form is an MC Vendor Hub page now — its header and footer are that site's,
        // and those links are relative, so on mychitti.net half the nav would 404. Anyone arriving
        // on the old host is sent to the same page where it belongs.
        //
        // Host-checked, never unconditional: mychitti.net and mcvendorhub.com are served by this
        // one application off one route table, so a blanket redirect to mcvendorhub.com arrives
        // back at this very method and loops until the browser gives up.
        if (!$this->onVendorHub()) {
            return redirect()->away('https://mcvendorhub.com/list-your-business', 301);
        }

        $module_categories = Category::where('module_id', 6)->where('position', 0)->where('status', 1)->get();
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();

        if (!isset($status) || $status->value == '0') {
            Toastr::error(translate('messages.not_found'));
            return back();
        }
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        StoreType::firstOrCreate(['name' => 'School', 'module_id' => 6]);
        _ensureRetailPosStoreType();

        $shop_stores_type = StoreType::where('module_id', 6)->get();
        $service_stores_type =  StoreType::where('module_id', 6)->get();

        return view('front-views.vendor_registration', compact('custome_recaptcha', 'module_categories', 'shop_stores_type', 'service_stores_type'));
    }

    /** Whether this request is already on MC Vendor Hub, where the signup pages live. */
    private function onVendorHub(): bool
    {
        return str_contains(request()->getHost(), 'mcvendorhub.com');
    }
    public function check_business(Request $request)
    {
        $store =  Store::withoutGlobalScopes()->where('phone', $request->phone)->first();
        if ($store) {
            $verified = $store->gst_doc || $store->id_doc ? 1 : 0;
            $in_verification = StoreDocument::where('store_id', $store->id)->where('verified', '0')->exists() ? 1 : 0;
            return response()->json(['status' => true, 'name' => $store->name, 'phone' => $store->phone, 'verified'=>  $verified, 'in_verification' => $in_verification]);
        } else {
            return response()->json(['status' => false]);
        }
    }

    public function store_ajax(Request $request)
    {
        // prx($request->all());
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if (!isset($status) || $status->value == '0') {
            return response()->json(['status' => false, 'message' => "Not found"]);
        }
        $rules = [
            'f_name' => 'required',
            'name' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'email' => 'required',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:vendors',
            'password' => ['required', Password::min(8)->letters()->numbers()->symbols()],
            'zone_id' => 'required',
            'module_id' => 'required',
            'terms' => 'required',
            'logo' => 'required',
        ];
        $messages = [
            'latitude.required' => 'Please select location on map.',
            'longitude.required' => 'Please select location on map.',
            'terms.required' => 'Please read and accept the terms and conditions.',
        ];

        if (!isset($request->confirmation) || $request->confirmation == '') {
            $rules['gst_doc']  = 'required|mimes:png,jpg,jpeg,pdf';
            $rules['gst_number']  = 'required';
        }
        if (!$request->has('gst_number') || $request->gst_number == '') {
            $rules['id_doc']  = 'required|mimes:png,jpg,jpeg,pdf';
            $rules['id_number']  = 'required';
        }

        // if ($request->business_type == 'business') {
        $rules['google_verification']  = 'required';
        // }

        if ($request->module_id == 6) {
            $rules['category_1'] = 'required';
        }
        if ($request->module_id != 6) {
            $rules['minimum_delivery_time'] = 'required';
            $rules['maximum_delivery_time'] = 'required';
            $rules['delivery_time_type'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        // cross verification 
        $verify_mobile  = DB::table('phone_otp')->where([
            'phone' =>  $request->phone,
            'otp' => $request->otp
        ])->exists();
 
        if (!$verify_mobile) {
            Toastr::error('Mobile not verified');
            return response()->json(['status' => false, 'message' => 'Mobile not verified']);
        }

        DB::table('phone_otp')->where('phone', $request->phone)->delete();

        if ($request->zone_id) {
            $zone = Zone::query()
                ->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))
                ->where('id', $request->zone_id)
                ->first();
            if (!$zone) {
                $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }
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
        $store->name =  $request->name[array_search('default', $request->lang)];
        $store->phone = $request->phone;
        $store->business_type = $request->business_type;
        $store->other_type = $request->business_type == 'other' ? $request->other_type_fld : null;
        $store->secondary_phone = $request->secondary_phone;
        $store->gst_number = $request->gst_num;
        $store->gst = $request->gst_num ? json_encode(['status' => $request->gst_status, 'code' => $request->gst_num]) : json_encode(['status' => 0, 'code' => '']); // gst number and on/off gst
        $store->email = $request->email;
        $storeLogo = Helpers::upload('store/', 'png', $request->file('logo'));
        $store->logo = $storeLogo;
        $storeCover = $request->hasFile('cover_photo') ? Helpers::upload('store/cover/', 'png', $request->file('cover_photo')) : null;
        $store->cover_photo = $storeCover;
        $store->address = $request->address[array_search('default', $request->lang)];
        $store->latitude = $request->latitude;
        $store->longitude = $request->longitude;
        $store->pin_code = getPincodeFromCoordinates($request->latitude,  $request->longitude);
        $store->vendor_id = $vendor->id;
        $store->zone_id = $request->zone_id;
        $store->vendor_type = $request->vendor_type ?? 'regular';
        $store->module_id = strtolower($request->business_type ?? '') === 'ecommerce' ? 5 : $request->module_id;
        if (!$request->has('minimum_delivery_time')) {
            $store->delivery_time = 0 . '-' . 0 . ' ' . 'min';
        } else {
            $store->delivery_time = $request->minimum_delivery_time . '-' . $request->maximum_delivery_time . ' ' . $request->delivery_time_type;
        }
        $store->status = 0;
        if ($request->module_id == 5) {
            $store->shop_categories = isset($request->shop_categories) ? implode(',', $request->shop_categories) : null;
        } else {
            if ($request->category_1 == 'other') {
                $store->other_cat_fld = $request->other_cat_fld;
                $store->category_1 = 0;
            } else {
                $store->category_1 = $request->category_1;
            }

            if ($request->has('second_cat_on') && $request->second_cat_on) {
                if ($request->category_1 == 'other') {
                    $store->other_cat2_fld = $request->other_cat2_fld;
                    $store->category_2 = 0;
                } else {
                    $store->category_2 = $request->category_2;
                }
            }
        }

        if ($request->business_type == 'business') {
            $store->other_verification = $request->other_verification ?? '';
            $store->google_verification = $request->google_verification;
        }
        $gstDoc = null;
        $idDoc = null;
        if (!isset($request->confirmation) || $request->confirmation == '' || $request->module_id == '5') {
            $extension = $request->file('gst_doc')->getClientOriginalExtension();
            $gstDoc = Helpers::upload('store/docs/', $extension, $request->file('gst_doc'));
            $store->gst_doc = $gstDoc;
            $store->gst_number = $request->gst_number;
        }

        if ($request->has('id_number') && $request->id_number != '' && $request->hasFile('id_doc')) {
            $extension = $request->file('id_doc')->getClientOriginalExtension();
            $idDoc = Helpers::upload('store/docs/', $extension, $request->file('id_doc'));
            $store->id_doc = $idDoc;
            $store->id_number = $request->id_number;
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
        if (!empty($idDoc)) {
            _logVendorFile('vendor', $vendor->id, $store->id, 'store_id_doc', 'store/docs/' . $idDoc);
        }
        Helpers::_addWelcomeCouponsIfExist($store);
        Helpers::_assignFreeTrial($store);
        Helpers::_createDefaultLedgerAccounts();
        //    die;

        $store->module->increment('stores_count');
        if ($request->module_id == 6) {
            // services  offered 1
            if (isset($request->services_1) && count($request->services_1)) {
                foreach ($request->services_1 as $key => $value) {
                    $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                    $storeArr = \App\CentralLogics\Helpers::item_store_ids($value);
                    array_push($storeArr, $store->id);
                    $newStoreString = implode(',', $storeArr);

                    $oldIds = _getIdsFrist($value);
                    // store_ids column write removed — item_store pivot is the source of truth
                    \App\CentralLogics\Helpers::sync_item_store_pivot($value, $newStoreString);
                    _trackStoreIds('store_ajax_service1', $newStoreString, $value, '-', 0 . '_vendor', $oldIds);
                }
            }
            // services  offered 1
            if (isset($request->services_2) && count($request->services_2)) {
                foreach ($request->services_2 as $key => $value) {
                    $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                    $storeArr = \App\CentralLogics\Helpers::item_store_ids($value);
                    array_push($storeArr, $store->id);
                    $newStoreString = implode(',', $storeArr);

                    $oldIds = _getIdsFrist($value);
                    // store_ids column write removed — item_store pivot is the source of truth
                    \App\CentralLogics\Helpers::sync_item_store_pivot($value, $newStoreString);
                    _trackStoreIds('store_ajax_service2', $newStoreString, $value, '-', 0 . '_vendor', $oldIds);
                }
            }
            $url = route('admin.store.pending-requests');
            _inAppNotification("New Vendor Registration", 'New Vendor Registered on My Chitti! Please check for details.', null, 0, $url, 'admin');

            $default_lang = str_replace('_', '-', app()->getLocale());
            $data = [];
            foreach ($request->lang as $index => $key) {
                if ($default_lang == $key && !($request->name[$index])) {
                    if ($key != 'default') {
                        array_push($data, array(
                            'translationable_type' => 'App\Models\Store',
                            'translationable_id' => $store->id,
                            'locale' => $key,
                            'key' => 'name',
                            'value' => $store->name,
                        ));
                    }
                } else {
                    if ($request->name[$index] && $key != 'default') {
                        array_push($data, array(
                            'translationable_type' => 'App\Models\Store',
                            'translationable_id' => $store->id,
                            'locale' => $key,
                            'key' => 'name',
                            'value' => $request->name[$index],
                        ));
                    }
                }
                if ($default_lang == $key && !($request->address[$index])) {
                    if ($key != 'default') {
                        array_push($data, array(
                            'translationable_type' => 'App\Models\Store',
                            'translationable_id' => $store->id,
                            'locale' => $key,
                            'key' => 'address',
                            'value' => $store->address,
                        ));
                    }
                } else {
                    if ($request->address[$index] && $key != 'default') {
                        array_push($data, array(
                            'translationable_type' => 'App\Models\Store',
                            'translationable_id' => $store->id,
                            'locale' => $key,
                            'key' => 'address',
                            'value' => $request->address[$index],
                        ));
                    }
                }
            }
            Translation::insert($data);
            try {
                if ($vendor->email) { 
                    Mail::to($vendor->email)->send(new \App\Mail\VendorSelfRegistration('pending', $store->name));
                }
            } catch (\Exception $ex) {
                info('Vendor registration email error: ' . $ex->getMessage()); 
            }

            StoreLogic::insert_schedule($store->id);

            return response()->json(['status' => true, 'message' => translate('messages.application_placed_successfully')]);
        }
    }

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
