<?php

namespace App\Http\Controllers\Front;


use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Category;
use App\Mail\LoginVerification;
use App\CentralLogics\SMS_module;
use App\CentralLogics\StoreLogic;
use App\Http\Controllers\Api\V1\ServiceRequestController;
use App\Models\AcceptedServiceRequest;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Order;
use MatanYadaev\EloquentSpatial\Objects\Point;
use App\Models\Zone;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerAddress;
use App\Models\GatePass;
use App\Models\GatePassItem;
use App\Models\InServiceQuotation;
use App\Models\Item;
use App\Models\OrderDetail;
use App\Models\Review;
use App\Models\ServiceQuoteItem;
use App\Models\ServiceRequest;
use Modules\Gateways\Traits\SmsGateway;
use Illuminate\Support\Facades\DB;

use App\Models\Store;
use App\Models\StoreReview;
use App\Models\UserRecentSearch;
use App\Models\Wishlist;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    private $latitude;
    private $longitude;
    private $module;
    private $module_id;
    private $zone_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $location = Helpers::_setLocation();
            $this->latitude = $location['latitude'];
            $this->longitude = $location['longitude'];
            $this->module = $location['module'];
            $this->module_id = $location['module']->id;
            $this->zone_id = $location['zone_id'];
            return $next($request);
        });
    }
    public function verify_otp(Request $request)
    {
        // verify otp
        $phone = $request->phone;
        $otp = $request->otp;
        $verify = _verify_otp($phone, $otp);

        if ($verify) {
            $user_id = auth('web')->user()->id;
            $user = User::find($user_id);
            $user->phone = $phone;
            $user->save();

            return response()->json(['status' => true, 'message' => 'verified']);
        } else {
            return response()->json(['status' => false, 'message' => 'invalid_otp']);
        }
    }
    public function send_login_otp(Request $request)
    {
        $phone = $request->phone;

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'No account found with this phone number.']);
        }

        $lastOtp = DB::table('phone_otp')->where('phone', $phone)->first();

        // Rate limiting: 3 attempts per 10 minutes
        if ($lastOtp && $lastOtp->attempts >= 3) {
            $timePassed = now()->diffInMinutes($lastOtp->created_at);
            $timeLeft = max(10 - $timePassed, 0);
            if ($timeLeft > 0) {
                return response()->json(['status' => false, 'message' => "Too many attempts. Try again after {$timeLeft} minutes."]);
            }
            DB::table('phone_otp')->where('phone', $phone)->update(['attempts' => 0]);
        }

        // Rate limiting: 1 OTP per 60 seconds
        if ($lastOtp && \Carbon\Carbon::parse($lastOtp->created_at)->diffInSeconds(now()) < 60) {
            $timeLeft = 60 - \Carbon\Carbon::parse($lastOtp->created_at)->diffInSeconds(now());
            return response()->json(['status' => false, 'message' => "Please wait {$timeLeft} seconds before requesting another OTP."]);
        }

        $otp = rand(1000, 9999);
        _send_confirmation_sms('mobile_verification', $phone, $otp);

        DB::table('phone_otp')->updateOrInsert(
            ['phone' => $phone],
            ['otp' => $otp, 'attempts' => ($lastOtp->attempts ?? 0) + 1, 'created_at' => now()]
        );

        return response()->json(['status' => true, 'message' => 'OTP sent successfully.']);
    }

    public function verify_login_otp(Request $request)
    {
        $phone = $request->phone;
        $otp = implode('', $request->otp);

        $valid = DB::table('phone_otp')->where(['phone' => $phone, 'otp' => $otp])->exists();

        if (!$valid) {
            return response()->json(['status' => false, 'message' => 'Incorrect OTP.']);
        }

        DB::table('phone_otp')->where('phone', $phone)->delete();

        $user = User::where('phone', $phone)->first();
        auth('web')->login($user);

        return response()->json(['status' => true, 'message' => 'Login successful.']);
    }

    public function gatepass_details(Request $request)
    {
        $gatepss = GatePass::where('service_id',  $request->id)->first();
        $gpItems = GatePassItem::where('gatepass_id', $gatepss->id)->get();
        // prx($gpItems);

        $html = ' <table class="table">
                            <thead>
                            <tr>
                            <th scope="col">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Description</th>
                            <th scope="col">Images</th>
                            </tr>
                            </thead>
                            <tbody>';

        foreach ($gpItems as $key => $item) {
            $html .= '<tr>
                                    <td>' . $key + 1 . '</td>
                                    <td>' .  $item->title . '</td>
                                    <td>' . $item->description . '</td>
                                    <td>';
            if (is_array(json_decode($item->image))) {
                $html .= '<div class="d-flex ">';
                foreach (json_decode($item->image) as $key => $value) {
                    $html .= '<a target="_blank"
                                                href="' . asset('storage/app/public/gatepass') . '/' . $value . '"
                                                style="cursor:default;"
                                                class="table-rest-info"
                                                alt="Gatepass image">
                                                <img style="width: 30px; height: 30px; cursor:zoom-in;"
                                                    src="' . asset('storage/app/public/gatepass') . '/' . $value . '">
                                            </a>';
                }
                $html .= '</div>';
            } else {
                $html .= '<a target="_blank"
                                            href="' . asset('storage/app/public/gatepass') . '/' . $item->image . '"
                                            style="cursor:default;"
                                            class="table-rest-info" alt="Gatepass image">
                                            <img style="width: 80px; height: 80px; cursor:zoom-in;"
                                                src="' . asset('storage/app/public/gatepass') . '/' . $item->image . '">
                                        </a>';
            }
            $html .= '<a target="_blank" href="' . asset("storage/app/public/gatepass") . '/' . $item->image . '" style="cursor:default;" class="table-rest-info" alt="Gatepass image">
                                    <img style=" cursor:zoom-in;" 
                                            src="' . asset('storage/app/public/gatepass') . '/' . $item->image . '">
                                    </a></td>
                                </tr>';
        }

        $html .= '</tbody>
                             </table>';
        echo $html;
    }
    public function quotation_details(Request $request)
    {
        $quote = InServiceQuotation::where('service_id',  $request->id)->first();
        $quoteItems = ServiceQuoteItem::where('quote_id', $quote->id)->get();
        // prx($gpItems);

        $html = ' <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Price</th>
                            <th scope="col">Tax</th>
                            <th scope="col">Qty</th>
                        </tr>
                    </thead>
                <tbody>';

        foreach ($quoteItems as $key => $item) {
            $html .= '<tr>
                        <td>' . $key + 1 . '</td>
                        <td>' .  $item->name . '</td>
                        <td>' . $item->price . '</td>
                        <td>' . $item->tax . '</td>
                        <td>' . $item->qty . '</td>
                    </tr>';
        }

        $html .= '</tbody> </table>';
        echo $html;
    }
    public function login()
    {
        return view('front-views.login');
    }

    public function delete_account(Request $request)
    {
        $user = User::find($request->id);
        if ($user) {
            if (Order::where('user_id', $user->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count()) {
                return response()->json(['errors' => [['code' => 'on-going', 'message' => translate('messages.Please_complete_your_ongoing_and_accepted_orders')]]], 203);
            }
            // $user->token()->revoke();
            if ($user->userinfo) {
                $user->userinfo->delete();
            }
            $user->delete();
        } else {
            return response()->json(['errors' => [['code' => 'not-found', 'message' => 'account not found']]], 203);
        }

        return response()->json([]);
    }


    public function signup()
    {
        return view('front-views.signup');
    }

    public function forgot_password(Request $request)
    {
        return view('front-views.forgot-password');
    }
    public function reset_password_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 200);
        }

        $customer = User::Where(['phone' => $request['phone']])->first();

        if (isset($customer)) {
            // if(env('APP_MODE')=='demo')
            // {
            //     return response()->json(['message' => translate('messages.otp_sent_successfull')], 200);
            // }

            // $interval_time = BusinessSetting::where('key', 'otp_interval_time')->first();
            // $otp_interval_time= isset($interval_time) ? $interval_time->value : 20;
            $otp_interval_time = 60; //seconds
            $password_verification_data = DB::table('password_resets')->where('email', $customer['email'])->first();
            if (isset($password_verification_data) &&  Carbon::parse($password_verification_data->created_at)->DiffInSeconds() < $otp_interval_time) {
                $time = $otp_interval_time - Carbon::parse($password_verification_data->created_at)->DiffInSeconds();
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' =>  translate('messages.please_try_again_after_') . $time . ' ' . translate('messages.seconds')]);
                return response()->json([
                    'errors' => $errors
                ], 200);
            }

            $token = rand(1000, 9999);
            DB::table('password_resets')->updateOrInsert(
                ['email' => $customer->email],
                [
                    'token' => $token,
                    'created_at' => now(),
                ]
            );
            try {
                Mail::to($customer['email'])->send(new \App\Mail\UserPasswordResetMail($token, $customer['f_name']));
            } catch (\Throwable $th) {
            }


            //for payment and sms gateway addon
            $published_status = 0;
            $payment_published_status = config('get_payment_publish_status');
            if (isset($payment_published_status[0]['is_published'])) {
                $published_status = $payment_published_status[0]['is_published'];
            }


            _send_confirmation_sms('mobile_verification', $request['phone'], $token);
            // if($response == 'success')
            // {
            return response()->json(['message' => translate('messages.otp_sent_successfull')], 200);
            // }
            // else
            // {
            //     return response()->json([
            //         'errors' => [
            //             ['code' => 'otp', 'message' => translate('messages.failed_to_send_sms')]
            //     ]], 200);

            //     // Need To Update the logic for Sms and Email

            // }
        }
        return response()->json(['errors' => [
            ['code' => 'not-found', 'message' => 'Phone number not found!']
        ]], 200);
    }
    public function update_password(Request $request)
    {
        return view('front-views.update-password');
    }
    public function reset_password_submit(Request $request)
    {
        $request->validate([
            'otp' => 'required',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
            'confirm_password' => 'required|same:password',
        ]);
        $otp = implode('', $request->otp);
        $data = DB::table('password_resets')->where(['token' => $otp])->first();
        if (isset($data)) {
            if ($request['password'] == $request['confirm_password']) {

                DB::table('users')->where(['email' => $data->email])->update([
                    'password' => bcrypt($request['confirm_password'])
                ]);

                DB::table('password_resets')->where(['token' => $otp])->delete();

                return response()->json(['message' => translate('messages.password_changed_successfully')], 200);
            }
        }
        return response()->json(['message' => translate('messages.something_went_wrong')], 200);
    }

    public function phone_save(Request $request)
    {
        $phone = $request->phone;
        $exists = User::where('phone', $phone)->exists();
        if ($exists) {
            return response()->json(['status' => false, 'message' => 'exists', 'phone' => '']);
            exit;
        }
        $otp  = rand(1000, 9999);
        $insert  = DB::table('phone_otp')->updateOrInsert([
            'phone' =>  $phone,
        ], [
            'otp' => $otp,
            'created_at' => now()
        ]);
        _send_confirmation_sms('mobile_verification', $request->phone, $otp);
        return response()->json(['status' => true, 'message' => 'otp_sent', 'phone' => $phone]);
    }
    public function book_service(Request $request)
    {

        $user = auth('web')->user();
        $user_id = $user->id;
        $phone = $user->phone;

        // phone number check 
        if (!$user->phone) {
            return response()->json(['status' => false, 'message' => 'phone_missing']);
        }

        if (!$request->has('address')) {
            $address_exists = CustomerAddress::where('user_id', $user_id)->first();
            if (!$address_exists) {
                $address = '';
                $city = '';
            } else {
                $address = $address_exists->address;
                $city = '';
            }
        } else {
            $address = $request->address;
        }
        if (!$request->has('city')) {
            $city = '';
        } else {
            $city = $request->city;
        }
        if ($address_exists && !$address_exists->pin_code) {
            $pin_code = getPincodeFromCoordinates((float)$this->latitude, (float)$this->longitude);
        } else if (!$address_exists) {

            $pin_code = getPincodeFromCoordinates((float)$this->latitude, (float)$this->longitude);
        } else {
            $pin_code = $address_exists->pin_code;
        }
        $user = User::find($user_id);
        if (!$user->pin_code) {
            $user->pin_code = $pin_code;
            $user->save();
        }
        $storeId = $request->storeId ?? false;
        $storesChunk = Helpers::get_store_range($request->serviceId, $this->zone_id, $user_id, $storeId);

        $serviceReq = new ServiceRequest();
        $serviceReq->user_id = $user_id;
        $serviceReq->item_id = $request->serviceId;
        $serviceReq->sent_to = implode(',', $storesChunk);
        $serviceReq->module_id = $this->module_id;
        $serviceReq->zone_id = $this->zone_id;
        $serviceReq->latitude = (float)$this->latitude;
        $serviceReq->longitude = (float)$this->longitude;
        $serviceReq->status = 'new';
        $serviceReq->pin_code = $pin_code;
        $serviceReq->address = $address;
        $serviceReq->city =  $city;
        $serviceReq->created_at = date('Y-m-d H:i:s');

        try {
            if ($serviceReq->save()) {
                DB::table('lead_statuses')->insert([
                    'service_request_id' => $serviceReq->id,
                    'status' => 'User Requested Service',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $url = route('admin.service.lead-list');
                $title = 'New Service Enquiry';
                _sendSMSToAdmin("A new service enquiry has been submitted. Please review the details", $title, $url);

                $itemDet = DB::table('items')->where('id', $request->serviceId)->first();
                $userDet = User::find($user_id);

                if (count($storesChunk)) {
                    $msg = "Hello! , You have received a new ENQUIRY from " . (!empty($userDet->f_name) ? $userDet->f_name : "a customer") . " for " . (!empty($itemDet->name) ? $itemDet->name : "a service") . ". Please visit the My Chitti Vendor App. Thank you, My Chitti Team.";
                    foreach ($storesChunk as $store) {
                        $store2 = DB::table('stores')->where('id', $store)->first();
                        $url =  route('vendor.service.leads_list');
                        if ($store2) {
                            _sendSMS($store2->phone, $msg);
                            _inAppNotification($title, $msg, null, $store2->id, $url, 'vendor');
                        }
                    }
                }
                return response()->json(['status' => true, 'message' => 'Requested Successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Some Error Occured']);
            }
        } catch (\Exception $e) {

            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function save_recent_search(Request $request)
    {
        // prx(auth('web')->user()->id );
        // Validate input
        $validated = $request->validate([
            'text' => 'required|string',
            'url' => 'required|url',
        ]);
        $user_id  =  auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');

        // Save the search item in the database
        $exists = UserRecentSearch::where('user_id', $user_id)
            ->where('text', $validated['text'])
            ->where('url', $validated['url'])
            ->exists();

        if (!$exists) {
            UserRecentSearch::create([
                'user_id' => $user_id,
                'text' => $validated['text'],
                'url' => $validated['url']
            ]);
        }

        // Return a success message
        return response()->json(['message' => 'Search item saved successfully']);
    }
    public function getRecent()
    {
        $userId  =  auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $searches = UserRecentSearch::where('user_id', $userId)->where('trash', 0)
            ->latest()
            ->limit(10)
            ->get(['text', 'url', 'type', 'type_id']);

        return response()->json($searches);
    }


    public function clear()
    {
        $userId  =  auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        UserRecentSearch::where('user_id', $userId)->update(['trash' => 1]);
        return response()->json(['status' => 'cleared']);
    }


    public function add_new_address(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required|max:10',
            'address' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $zone = Zone::whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))->get(['id']);
        if (count($zone) == 0) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.service_not_available_in_this_area')]);
            return response()->json([
                'errors' => $errors
            ]);
        }

        $address = [
            'user_id' => $request->user()->id,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'floor' => $request->floor,
            'road' => $request->road,
            'house' => $request->house,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'zone_id' => $zone[0]->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
        DB::table('customer_addresses')->insert($address);
        return response()->json(['message' => translate('messages.successfully_added'), 'zone_ids' => array_column($zone->toArray(), 'id')]);
    }

    public function logout()
    {

        auth()->guard('web')->logout();

        return redirect()->route('home');
    }


    public function signup_post(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required',
            'phone' => 'required|unique:users|max:10',
            'password' => ['required', Password::min(8)],
        ], [
            'f_name.required' => 'The first name field is required.',
            'l_name.required' => 'The last name field is required.',
            'phone.unique' => 'Already Registered'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $ref_by = null;
        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;

        if ($request->ref_code) {
            $ref_status = BusinessSetting::where('key', 'ref_earning_status')->first()->value;
            if ($ref_status != '1') {
                return response()->json(['errors' => Helpers::error_formater('ref_code', translate('messages.referer_disable'))], 403);
            }

            $referar_user = User::where('ref_code', '=', $request->ref_code)->first();
            if (!$referar_user || !$referar_user->status) {
                return response()->json(['errors' => Helpers::error_formater('ref_code', translate('messages.referer_code_not_found'))], 405);
            }

            if (WalletTransaction::where('reference', $request->phone)->first()) {
                return response()->json(['errors' => Helpers::error_formater('phone', translate('Referrer code already used'))], 203);
            }

            $notification_data = [
                'title' => translate('messages.Your_referral_code_is_used_by') . ' ' . $request->f_name . ' ' . $request->l_name,
                'description' => translate('Be prepare to receive when they complete there first purchase'),
                'order_id' => 1,
                'image' => '',
                'type' => 'referral_code',
            ];

            if ($referar_user?->cm_firebase_token) {
                Helpers::send_push_notif_to_device($referar_user?->cm_firebase_token, $notification_data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($notification_data),
                    'user_id' => $referar_user?->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
         
            $ref_by = $referar_user->id;
        }

        $user = User::create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gst' =>  $request->gst_number,
            'ref_by' =>   $ref_by,
            'password' => bcrypt($request->password),
        ]);
        $user->ref_code = Helpers::generate_referer_code($user);
        $user->save();



        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

        if ($customer_verification && env('APP_MODE') != 'demo') {
            $otp_interval_time = 60; //seconds
            $verification_data = DB::table('phone_verifications')->where('phone', $request['phone'])->first();

            if (isset($verification_data) &&  Carbon::parse($verification_data->updated_at)->DiffInSeconds() < $otp_interval_time) {
                $time = $otp_interval_time - Carbon::parse($verification_data->updated_at)->DiffInSeconds();
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' =>  translate('messages.please_try_again_after_') . $time . ' ' . translate('messages.seconds')]);
                return response()->json([
                    'errors' => $errors
                ], 405);
            }

            $otp = rand(1000, 9999);
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $request['phone']],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $mail_status = Helpers::get_mail_status('registration_otp_mail_status_user');
            if (config('mail.status') && $mail_status == '1') {
                Mail::to($request['email'])->send(new EmailVerification($otp, $request->f_name));
            }
            //for payment and sms gateway addon
            $published_status = 0;
            $payment_published_status = config('get_payment_publish_status');
            if (isset($payment_published_status[0]['is_published'])) {
                $published_status = $payment_published_status[0]['is_published'];
            }

            if ($published_status == 1) {
                $response = SmsGateway::send($request['phone'], $otp);
            } else {
                $response = SMS_module::send($request['phone'], $otp);
            }
            if ($response != 'success') {
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]);
                return response()->json([
                    'errors' => $errors
                ], 405);
            }
        }
        try {
            $mail_status = Helpers::get_mail_status('registration_mail_status_user');
            if (config('mail.status') && $request->email && $mail_status == '1') {
                Mail::to($request->email)->send(new \App\Mail\CustomerRegistration($request->f_name . ' ' . $request->l_name));
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }
        if ($request->guest_id  && isset($user->id)) {

            $userStoreIds = Cart::where('user_id', $request->guest_id)
                ->join('items', 'carts.item_id', '=', 'items.id')
                ->pluck('items.store_id')
                ->toArray();

            Cart::where('user_id', $user->id)
                ->whereHas('item', function ($query) use ($userStoreIds) {
                    $query->whereNotIn('store_id', $userStoreIds);
                })
                ->delete();

            Cart::where('user_id', $request->guest_id)->update(['user_id' => $user->id, 'is_guest' => 0]);
        }
        $url = route('admin.users.customer.list');
        _sendSMSToAdmin("A new user registered on MYCHITTI", 'New User Registration', $url);

        return response()->json(['token' => $token, 'is_phone_verified' => 0, 'message' => 'Account Created Successfully', 'phone_verify_end_url' => "api/v1/auth/verify-phone"], 200);
    }
    public function login_post(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 200);
        }

        $data = [
            'phone' => $request->phone,
            'password' => $request->password
        ];

        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;
        if (auth()->attempt($data)) {

            $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
            if (!auth()->user()->status) {
                $errors = [];
                array_push($errors, ['code' => 'auth-003', 'message' => translate('messages.your_account_is_blocked')]);
                return response()->json([
                    'errors' => $errors
                ], 200);
            }
            $user = auth()->user();
            if ($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo') {
                $otp_interval_time = 60; //seconds

                $verification_data = DB::table('phone_verifications')->where('phone', $request['phone'])->first();

                if (isset($verification_data) &&  Carbon::parse($verification_data->updated_at)->DiffInSeconds() < $otp_interval_time) {

                    $time = $otp_interval_time - Carbon::parse($verification_data->updated_at)->DiffInSeconds();
                    $errors = [];
                    array_push($errors, ['code' => 'otp', 'message' =>  translate('messages.please_try_again_after_') . $time . ' ' . translate('messages.seconds')]);
                    return response()->json([
                        'errors' => $errors
                    ], 200);
                }

                $otp = rand(1000, 9999);
                DB::table('phone_verifications')->updateOrInsert(
                    ['phone' => $request['phone']],
                    [
                        'token' => $otp,
                        'otp_hit_count' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $mail_status = Helpers::get_mail_status('login_otp_mail_status_user');
                if (config('mail.status') && $mail_status == '1') {
                    Mail::to($user['email'])->send(new LoginVerification($otp, $user->f_name));
                }
                //for payment and sms gateway addon
                $published_status = 0;
                $payment_published_status = config('get_payment_publish_status');
                if (isset($payment_published_status[0]['is_published'])) {
                    $published_status = $payment_published_status[0]['is_published'];
                }

                if ($published_status == 1) {
                    $response = SmsGateway::send($request['phone'], $otp);
                } else {
                    $response = SMS_module::send($request['phone'], $otp);
                }

                if ($response != 'success') {
                    $errors = [];
                    array_push($errors, ['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]);
                    return response()->json([
                        'errors' => $errors
                    ], 200);
                }
            }
            if ($user->ref_code == null && isset($user->id)) {
                $ref_code = Helpers::generate_referer_code($user);
                DB::table('users')->where('phone', $user->phone)->update(['ref_code' => $ref_code]);
            }
            if (session()->has('guest_id') && isset($user->id)) {

                $userStoreIds = Cart::where('user_id', session()->get('guest_id'))
                    ->join('items', 'carts.item_id', '=', 'items.id')
                    ->pluck('items.store_id')
                    ->toArray();

                Cart::where('user_id', $user->id)
                    ->whereHas('item', function ($query) use ($userStoreIds) {
                        $query->whereNotIn('store_id', $userStoreIds);
                    })
                    ->delete();

                Cart::where('user_id', session()->get('guest_id'))->update(['user_id' => $user->id, 'is_guest' => 0]);
            }

            return response()->json(['token' => $token, 'message' => 'Successfully Login', 'is_phone_verified' => auth()->user()->is_phone_verified], 200);
        } else {
            $errors = [];
            array_push($errors, ['code' => 'auth-001', 'message' => 'Incorrect Credentials']);
            return response()->json([
                'errors' => $errors
            ], 200);
        }
    }


    public function login_attempt(Request $request) {}

    public function dashboard(Request $request, $tab = 'profile')
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $is_guest = auth('web')->user() ? 0 : 1;

        $coupons = $user_details = $user_addresses = $orders = $p_orders = $wishlists = $services = null;

        $user_details = User::where('id', $user_id)->first();
        // prx($tab);
        if ($tab == 'profile') {
            // user details =================
        } else if ($tab == 'address') {
            // user address ================
            $user_addresses = CustomerAddress::where('user_id', $user_id)->get();
        } else if ($tab == 'bookings') {
            // user services ================
            $services['history'] = _serviceHistory($user_id);
            $services['running'] = _serviceRunning($user_id);
        } else if ($tab == 'favourites') {
            // wishlist ================
            $wishlists['items'] = DB::table('wishlists')
                ->where('user_id', $user_id)
                ->join('items', 'items.id', '=', 'wishlists.item_id')
                ->when(config('module.current_module_data'), function ($query) {
                    $query->where('module_id', config('module.current_module_data')['id']);
                })
                ->whereNotNull('wishlists.item_id')
                ->select('items.*', 'wishlists.id')
                ->get();
            $wishlists['stores'] =  DB::table('wishlists')->where('user_id', $user_id)
                ->join('stores', 'stores.id', 'wishlists.store_id')
                ->select('stores.*', 'wishlists.id')
                ->whereNotNull('wishlists.store_id')->get();
        } else if ($tab == 'coupons') {
            // coupons ======================
            $coupons = Coupon::withoutGlobalScopes()
                ->with('store:id,name')
                ->active()
                ->whereDate('expire_date', '>=', now()->toDateString())
                ->whereJsonContains('customer_id', (string) $user_id) // ✅ MUST be string
                ->get();
        }

        // // current orders ================
        // $orders = Order::with(['store', 'delivery_man.rating', 'parcel_category'])->where('is_guest', $is_guest)
        //     ->withCount('details')->where(['user_id' => $user_id])->whereNotIn('order_status', ['delivered', 'canceled', 'refund_requested', 'refund_request_canceled', 'refunded', 'failed'])->Notpos()->get();
        // foreach ($orders as $key => $value) {
        //     $orders[$key]['items'] = DB::table('order_details')
        //         ->where('order_details.order_id', $value->id)
        //         ->get();
        // }

        // // previous orders =================
        // $p_orders = Order::with(['store', 'delivery_man.rating', 'parcel_category', 'refund:order_id,admin_note,customer_note'])->withCount('details')->where(['user_id' => $user_id])->whereIn('order_status', ['delivered', 'canceled', 'refund_requested', 'refund_request_canceled', 'refunded', 'failed'])->where(['user_id' => $user_id])
        //     ->Notpos()->get();


        // foreach ($p_orders as $key => $value) {
        //     $p_orders[$key]['items'] = DB::table('order_details')
        //         ->where('order_details.order_id', $value->id)
        //         ->get();
        // }

        // user services end ================================================

        // echo '<pre>';
        // foreach ($orders as $key => $order) {
        //     if (count($orders)) {
        //         foreach ($order['items'] as $key => $o_item) {
        //             // print_r(json_decode($o_item->item_details)?->image);
        //         }
        //     }
        // }

        return view('front-views.dashboard', compact('coupons', 'user_details', 'user_addresses', 'orders', 'p_orders', 'wishlists', 'services'));
    }

    public function submit_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_item_id' => 'required',
            'review' => 'required',
            'rating' => 'required|numeric|max:5',
        ]);
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $order_item = OrderDetail::find($request->order_item_id);

        $order = Order::find($order_item->order_id);

        if (isset($order) == false) {
            $validator->errors()->add('order_id', translate('messages.order_data_not_found'));
        }

        $item = Item::find($order_item->item_id);
        if (isset($order) == false) {
            $validator->errors()->add('item_id', translate('messages.item_not_found'));
        }

        $multi_review = Review::where(['item_id' => $order_item->item_id, 'user_id' => $user_id, 'order_id' => $order->id])->first();
        if (isset($multi_review)) {
            return response()->json([
                'errors' => [
                    ['code' => 'review', 'message' => translate('messages.already_submitted')]
                ]
            ]);
        } else {
            $review = new Review;
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image_array = [];
        if (!empty($request->file('attachment'))) {
            foreach ($request->file('attachment') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        $order?->OrderReference?->update([
            'is_reviewed' => 1
        ]);

        $review->user_id = $user_id;
        $review->item_id = $order_item->item_id;
        $review->order_id = $order_item->order_id;
        $review->module_id = $order->module_id;
        $review->comment = $request->review;
        $review->rating = $request->rating;
        $review->attachment = json_encode($image_array);
        $review->save();

        if ($item->store) {
            $store_rating = StoreLogic::update_store_rating($item->store->rating, (int)$request->rating);
            $item->store->rating = $store_rating;
            $item->store->save();
        }

        $item->rating = ProductLogic::update_rating($item->rating, (int)$request->rating);
        $item->avg_rating = ProductLogic::get_avg_rating(json_decode($item->rating, true));
        $item->save();
        $item->increment('rating_count');

        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }

    public function submit_service_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required',
            'review' => 'required',
            'rating' => 'required|numeric|max:5',
        ]);

        $order = AcceptedServiceRequest::find($request->service_id);
        if (isset($order) == false) {
            $validator->errors()->add('service_id', translate('messages.service_data_not_found'));
        } else if ($order->current_status != 'Completed') {
            $validator->errors()->add('not_completed', 'Service not completed yet');
        }
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $store_id = $order->vendor_id;

        $store = Store::find($store_id);
        if (isset($store) == false) {
            $validator->errors()->add('store_id', translate('messages.store_not_found'));
        }

        $multi_review = StoreReview::where(['store_id' => $store_id, 'user_id' => $user_id, 'order_id' => $request->service_id])->first();
        if (isset($multi_review)) {
            return response()->json([
                'errors' => [
                    ['code' => 'review', 'message' => translate('messages.already_submitted')]
                ]
            ]);
        } else {
            $review = new StoreReview;
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $image_array = [];
        if (!empty($request->file('attachment'))) {
            foreach ($request->file('attachment') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        $review->user_id = $user_id;
        $review->store_id = $store_id;
        $review->order_id = $request->service_id;
        $review->comment = $request->review;
        $review->rating = $request->rating;
        $review->attachment = json_encode($image_array);
        $review->save();

        $ratingData = DB::table('store_reviews')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(rating) as rating_count')
            ->where('store_id', $store_id)
            ->first();

        $store->rating_count = $ratingData->rating_count;
        $store->average_rating = $ratingData->avg_rating;
        $store_rating = StoreLogic::update_store_rating($store->rating, (int)$request->rating);
        $store->rating = $store_rating;
        $store->save();


        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }
    public function remove_wishlist(Request $request, $type, $id)
    {

        Wishlist::where('id', $id)->delete();
        return response()->json(['status' => true, 'message' => 'Removed Successfully']);
    }
    public function delete_address(Request $request)
    {
        CustomerAddress::find($request->id)->delete();
        return back();
    }

    public function edit_address(Request $request)
    {
        $addr = CustomerAddress::find($request->id);
        return view('front-views.edit_address', compact('addr'));
    }
    public function add_address(Request $request)
    {
        return view('front-views.add_address');
    }

    public function update_address(Request $request, $id)
    {
        prx($request->all());
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required|max:10',
            'address' => 'required',
            'longitude' => 'required',
            'latitude' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $zone = Zone::whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))->get(['id']);
        if (!$zone) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.service_not_available_in_this_area')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $address = [
            'user_id' => $request->user()->id,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'floor' => $request->floor,
            'road' => $request->road,
            'house' => $request->house,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'zone_id' => $zone[0]->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
        DB::table('customer_addresses')->where('id', $id)->update($address);
        return response()->json(['message' => translate('messages.updated_successfully'), 'zone_id' => $zone[0]->id], 200);
    }

    public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:users,email,' . $request->user()->id,
            'password' => ['nullable', Password::min(8)],
        ], [
            'f_name.required' => 'First name is required!',
            'l_name.required' => 'Last name is required!',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image = $request->file('image');

        if ($request->has('image')) {
            $imageName = Helpers::update('profile/', $request->user()->image, 'png', $request->file('image'));
        } else {
            $imageName = $request->user()->image;
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $request->user()->password;
        }

        $user = User::where(['id' => $request->user()->id])->first();
        $user->f_name = $request->f_name;
        $user->l_name = $request->l_name;
        $user->email = $request->email;
        $user->image = $imageName;
        $user->gst = $request->gst_number;
        $user->password = $pass;
        $user->save();

        if ($user->userinfo) {
            $userinfo = $user->userinfo;
            $userinfo->f_name = $request->f_name;
            $userinfo->l_name = $request->l_name;
            $userinfo->email = $request->email;
            $userinfo->image = $imageName;
            $userinfo->save();
        }

        return response()->json(['message' => translate('messages.successfully_updated')]);
    }
}
