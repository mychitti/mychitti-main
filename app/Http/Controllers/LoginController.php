<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Module;
use App\Models\Vendor;
use App\Models\DataSetting;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\VendorEmployee;
use Illuminate\Support\Carbon;
use App\Models\BusinessSetting;
use App\CentralLogics\SMS_module;
use App\Models\PhoneVerification;
use Illuminate\Support\Facades\DB;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordResetMail;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use App\Mail\PasswordResetRequestMail;
use App\Models\Store;
use App\Models\StoreDocument;
use App\Models\VendorModuleInstruction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Modules\Gateways\Traits\SmsGateway;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin,vendor', ['except' => 'logout']);
    }

    public function login_old($login_url)
    {
        $language = BusinessSetting::where('key', 'system_language')->first();
        if ($language) {
            foreach (json_decode($language->value, true) as $key => $data) {
                if ($data['default']) {
                    $lang = $data['code'];
                    $direction = $data['direction'];
                }
            }
        }
        $data = array_column(DataSetting::whereIn('key', [
            'store_employee_login_url',
            'store_login_url',
            'admin_employee_login_url',
            'admin_login_url'
        ])->get(['key', 'value'])->toArray(), 'value', 'key');

        $loginTypes = [
            'admin' => 'admin_login_url',
            'admin_employee' => 'admin_employee_login_url',
            'vendor' => 'store_login_url',
            'vendor_employee' => 'store_employee_login_url'
        ];
        $siteDirections = [
            'admin' => session()?->get('site_direction') ?? $direction ??  'ltr',
            'admin_employee' => session()?->get('site_direction') ?? $direction ?? 'ltr',
            'vendor' => session()?->get('vendor_site_direction') ?? $direction ?? 'ltr',
            'vendor_employee' => session()?->get('vendor_site_direction') ?? $direction ?? 'ltr'
        ];
        $locals = [
            'admin' => session()?->get('local') ?? $lang ?? 'en',
            'admin_employee' => session()?->get('local') ?? $lang ?? 'en',
            'vendor' => session()?->get('vendor_local') ?? $lang ?? 'en',
            'vendor_employee' => session()?->get('vendor_local') ?? $lang ?? 'en'
        ];
        $role = null;

        $user_type = array_search($login_url, $data);
        abort_if(!$user_type, 404);
        $role = array_search($user_type, $loginTypes, true);

        abort_if($role == null, 404);
        $site_direction = $siteDirections[$role];
        $locale = $locals[$role];
        App::setLocale($locale);
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        $email =  null;
        $password = null;
        if (Cookie::has('p_token') && Cookie::has('e_token') && Cookie::has('role')  &&  Cookie::get('role') == $role) {
            $email = Crypt::decryptString(Cookie::get('e_token'));
            $password = Crypt::decryptString(Cookie::get('p_token'));
        }

        return view('auth.login', compact('custome_recaptcha', 'email', 'password', 'role', 'site_direction', 'locale'));
    }
    public function login($login_url = null)
    {
        $language = BusinessSetting::where('key', 'system_language')->first();
        if ($language) {
            foreach (json_decode($language->value, true) as $key => $data) {
                if ($data['default']) {
                    $lang = $data['code'];
                    $direction = $data['direction'];
                }
            }
        }
        $host = request()->getHost();

        if (!$login_url) {
            switch ($host) {
                case 'vendor.mcvendorhub.com':
                    $login_url = 'store';
                    break;

                case 'vendor-staff.mcvendorhub.com':
                    $login_url = 'store-employee';
                    break;

                default:
            }
        }
        $data = array_column(DataSetting::whereIn('key', [
            'store_employee_login_url',
            'store_login_url',
            'admin_employee_login_url',
            'admin_login_url'
        ])->get(['key', 'value'])->toArray(), 'value', 'key');

        $loginTypes = [
            'admin' => 'admin_login_url',
            'admin_employee' => 'admin_employee_login_url',
            'vendor' => 'store_login_url',
            'vendor_employee' => 'store_employee_login_url'
        ];
        $siteDirections = [
            'admin' => session()?->get('site_direction') ?? $direction ??  'ltr',
            'admin_employee' => session()?->get('site_direction') ?? $direction ?? 'ltr',
            'vendor' => session()?->get('vendor_site_direction') ?? $direction ?? 'ltr',
            'vendor_employee' => session()?->get('vendor_site_direction') ?? $direction ?? 'ltr'
        ];
        $locals = [
            'admin' => session()?->get('local') ?? $lang ?? 'en',
            'admin_employee' => session()?->get('local') ?? $lang ?? 'en',
            'vendor' => session()?->get('vendor_local') ?? $lang ?? 'en',
            'vendor_employee' => session()?->get('vendor_local') ?? $lang ?? 'en'
        ];
        $role = null;
        $user_type = array_search($login_url, $data);

        abort_if(!$user_type, 404);
        $role = array_search($user_type, $loginTypes, true);

        abort_if($role == null, 404);
        $site_direction = $siteDirections[$role];
        $locale = $locals[$role];
        App::setLocale($locale);
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        $email =  null;
        $password = null;
        if (Cookie::has('p_token') && Cookie::has('e_token') && Cookie::has('role')  &&  Cookie::get('role') == $role) {
            $email = Crypt::decryptString(Cookie::get('e_token'));
            $password = Crypt::decryptString(Cookie::get('p_token'));
        }
        if ($role == 'admin') {
            return view('auth.login_old', compact('custome_recaptcha', 'email', 'password', 'role', 'site_direction', 'locale'));
        } else {
            return view('auth.login_new', compact('custome_recaptcha', 'email', 'password', 'role', 'site_direction', 'locale'));
        }
    }

    public function vendor_homepage()
    {
        $vendor_modules = VendorModuleInstruction::all();
        $lines = DataSetting::whereIn('key', ['mc_first_line', 'mc_second_line', 'mc_third_line'])->get();

        return view('auth.vendor_homepage', compact('vendor_modules', 'lines'));
    }
    public function login_attemp($role, $email, $password, $remember = false)
    {
        $auth = ($role == 'admin_employee' ? 'admin' : $role);
        if (auth($auth)->attempt(['email' => $email, 'password' => $password], $remember)) {

            // return redirect()->route('vendor.dashboard');
            if ($remember) {
                Cookie::queue('role', $role, 120);
                Cookie::queue('e_token', Crypt::encryptString($email), 120);
                Cookie::queue('p_token', Crypt::encryptString($password), 120);
            } else {
                $user = auth($auth)?->user();
                $user?->update([
                    'remember_token' => null
                ]);
                Cookie::forget('role');
                Cookie::forget('e_token');
                Cookie::forget('p_token');
            }
            if ($auth == 'admin') {
                return 'admin';
            } else {
                return 'vendor';
            }
        }
        return false;
    }

    public function submit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);
        $host = request()->getHost();

        switch ($host) {
            case 'vendor.mcvendorhub.com':
                $role = 'vendor';

            case 'vendor-staff.mcvendorhub.com':
                $role = 'vendor_employee';

            default:
                $role = $request->role ?? 'admin';
        }

        if ($role == 'admin_employee') {
            $data = Admin::where('email', $request->email)->where('role_id', 1)->exists();
            if ($data) {
                return redirect()->back()->withInput($request->only('email', 'remember'))
                    ->withErrors(['Credentials does not match.']);
            }
        } elseif ($role == 'vendor') {
            $vendor = Vendor::where('email', $request->email)->first();
            if ($vendor) {
                if ($vendor->stores[0]->suspended == 1) {
                    return redirect()->back()->withInput($request->only('email', 'remember'))
                        ->withErrors([translate('messages.your_account_is_suspended')]);
                } elseif ($vendor->stores[0]->status == 0) {
                    return redirect()->back()->withInput($request->only('email', 'remember'))
                        ->withErrors([translate('messages.inactive_vendor_warning')]);
                }
            } else {
            }
        } elseif ($role == 'vendor_employee') {
            $employee = VendorEmployee::where('email', $request->email)->first();
            if ($employee) {
                if ($employee?->terminate) {
                    return redirect()->back()->withInput($request->only('email', 'remember'))
                        ->withErrors([translate('messages.you are terminated')]);
                }
                if ($employee?->store?->status == 0 || $employee?->status == 0) {
                    return redirect()->back()->withInput($request->only('email', 'remember'))
                        ->withErrors([translate('messages.inactive_vendor_warning')]);
                }
            }
        }

        $data = $this->login_attemp($role, $request->email, $request->password, $request->remember);
        if ($data == 'admin') {
            $admin = Admin::find(auth('admin')->id());
            $admin->is_logged_in = 1;
            $admin->save();
            $modules = Module::Active()->get();
            if (isset($modules) && ($modules->count() > 0)) {

                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('admin.business-settings.business-setup');
        }

        // dd(config('session.cookie'));
        $domain = $request->getHost();

        if ($data == 'vendor') {
            // dd(auth('vendor')->check(), auth('vendor')->user());

            if ($request->role === 'vendor_employee') {
                $employee = VendorEmployee::where('email', $request->email)->first();
                $employee->is_logged_in = 1;
                $employee->save();

                if ($domain == 'staging.mychitti.net' || $domain == 'www.staging.mychitti.net') {
                    return redirect()->to('https://staging.mychitti.net/store-panel/dashboard');
                } else {
                    return redirect()->to('https://vendor-staff.mcvendorhub.com/dashboard');
                }

            }
            if ($domain == 'staging.mychitti.net' || $domain == 'www.staging.mychitti.net') {
                return redirect()->to('https://staging.mychitti.net/store-panel/dashboard');
            } else {
                return redirect()->route('vendor.dashboard');
            }
        }

        return redirect()->back()->withInput($request->only('email', 'remember'))
            ->withErrors(['Credentials does not match.']);
    }
    public function login_otp(Request $request)
    {
        $request->validate([
            'otp.*' => 'required',
            'phone' => 'required'
        ]);

        $otp = implode('', $request->otp); //array to string otp 

        $store = Store::where('phone', $request->phone)->first();
        $vendor = Vendor::where('id', $store->vendor_id)->first();

        if (!$store) {
            // die('phone not exist');
            return redirect()->back()->withInput($request->only('phone'))
                ->withErrors(['Phone number does not exist.']);
        }
        // otp in db 
        $dbOtp = DB::table('phone_otp')->where('phone', $request->phone)->where('otp', $otp)->first();

        if ($dbOtp && $dbOtp->otp != $otp) {
            // die('invalid otp');
            return redirect()->back()->withErrors(['Invalid OTP.']);
        }

        if ($vendor->stores[0]->status == 0) {
            // die('inactive vendor');
            return redirect()->back()->withErrors(['Vendor is inactive.']);
        }
        Auth::guard('vendor')->login($vendor);

        if (auth('vendor')->check()) {
            DB::table('phone_otp')->where('phone', $request->phone)->where('otp', $otp)->delete();
            return redirect()->route('vendor.dashboard');
        } else {
            // die('otp login failed');
            return redirect()->back()->withErrors(['OTP login failed.']);
        }
    }
    public function business_verify(Request $request)
    {
        // prx($request->all());

        $store = Store::where('phone', $request->phone)->first();
        if (!$store) {
            return response()->json([
                'status' => false,
                'message' => 'Business not found.'
            ]);
        }
        if (!$request->hasFile('gst_doc') &&  !$request->hasFile('id_doc')) {

            return response()->json([
                'status' => false,
                'message' => 'Please upload a valid document.'
            ]);
        }
        $storeId = $store->id;

        //  Check history
        $hasHistory = StoreDocument::where('store_id', $storeId)
            ->exists();

        //  Deactivate old documents
        StoreDocument::where('store_id', $storeId)
            ->update(['status' => 0]);


        if (isset($request->gst_doc)) {
            $fileFieldFront = 'gst_doc';
            $type = 'gst_doc'; // id_doc | gst_doc

            $file = $request->file($fileFieldFront);
            $extension = $file->getClientOriginalExtension();
            $uploadedPath = Helpers::upload('store/docs/', $extension, $file);

            StoreDocument::create([
                'store_id'   => $storeId,
                'doc_type'   => $type,
                'file_path' => $uploadedPath,
                'back_side' => '',   //  SAME ROW
                'status'    => 1,
            ]);
            //  ADMIN NOTIFICATION
            $msg = "New " . ($type == 'id_doc' ? 'ID Document' : 'GST Document') .
                " uploaded by " . $store->name . ". Please verify the document.";

            $url = route('admin.store.view', [
                'store' => $storeId,
                'tab'   => 'documents'
            ]);

            _inAppNotification("New Vendor Document", $msg, null, 0, $url, 'admin');
        }
        if (isset($request->id_doc)) {
            $fileFieldFront = 'id_doc';
            $type = 'id_doc'; // id_doc | gst_doc

            $file = $request->file($fileFieldFront);
            $extension = $file->getClientOriginalExtension();
            $uploadedPath = Helpers::upload('store/docs/', $extension, $file);

            StoreDocument::create([
                'store_id'   => $storeId,
                'doc_type'   => $type,
                'file_path' => $uploadedPath,
                'back_side' => '',   //  SAME ROW
                'status'    => 1,
            ]);
            //  ADMIN NOTIFICATION
            $msg = "New " . ($type == 'id_doc' ? 'ID Document' : 'GST Document') .
                " uploaded by " . $store->name . ". Please verify the document.";

            $url = route('admin.store.view', [
                'store' => $storeId,
                'tab'   => 'documents'
            ]);

            _inAppNotification("New Vendor Document", $msg, null, 0, $url, 'admin');
        }

        return response()->json([
            'status' => true,
            'message' => 'Document updated successfully.'
        ]);
    }
    public function business_verification(Request $request)
    {
        return view('business_verification');
    }
    public function login_otp_ajax(Request $request)
    {
        $validator = FacadesValidator::make($request->all(), [
            'otp' => 'required',
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $otp = $request->otp; // join OTP input

        $store = Store::where('phone', $request->phone)->first();

        if (!$store) {
            return response()->json([
                'status' => false,
                'message' => 'Phone number does not exist.'
            ]);
        }

        $dbOtp = DB::table('phone_otp')
            ->where('phone', $request->phone)
            ->where('otp', $otp)
            ->first();

        if (!$dbOtp || $dbOtp->otp != $otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP.'
            ]);
        }

        DB::table('phone_otp')
            ->where('phone', $request->phone)
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'OTP verification successful',
        ]);

        return response()->json([
            'status' => false,
            'message' => 'OTP verification failed.'
        ]);
    }
    public function reloadCaptcha()
    {
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        return response()->json([
            'view' => view('auth.custom-captcha', compact('custome_recaptcha'))->render()
        ], 200);
    }

    public function reset_password_request(Request $request)
    {
        $admin = Admin::where('role_id', 1)->first();

        if (isset($admin)) {
            $token = Helpers::generate_reset_password_code();
            DB::table('password_resets')->insert([
                'email' => $admin['email'],
                'token' => $token,
                'created_by' => 'admin',
                'created_at' => now(),
            ]);
            $url = url('/') . '/password-reset?token=' . $token;
            try {
                $mail_status = Helpers::get_mail_status('forget_password_mail_status_admin');
                if (config('mail.status') && $admin['email'] && $mail_status == '1') {
                    Mail::to($admin['email'])->send(new AdminPasswordResetMail($url, $admin['f_name']));
                    session()->put('log_email_succ', 1);
                } else {
                    Toastr::error(translate('messages.Failed_to_send_mail'));
                }
            } catch (\Throwable $th) {
                info($th->getMessage());
                Toastr::error(translate('messages.Failed_to_send_mail'));
            }
            return back();
        }
        Toastr::error(translate('messages.credential_doesnt_match'));
        return back();
    }

    public function vendor_reset_password_request(Request $request)
    {
        $request->validate([
            'email' => 'required'
        ]);
        $vendor = Vendor::where('email', $request['email'])->first();
        // echo 'fsdds1';
        if (isset($vendor)) {
            $token = Helpers::generate_reset_password_code();
            DB::table('password_resets')->insert([
                'email' => $vendor['email'],
                'token' => $token,
                'created_by' => 'vendor',
                'created_at' => now(),
            ]);
            $url = url('/') . '/password-reset?token=' . $token;
            try {
                if(config('mail.status') && $vendor['email'] ){
                Mail::to($vendor['email'])->send(new PasswordResetRequestMail($url, $vendor['f_name']));
                session()->put('log_email_succ', 1);
                }else{
                    Toastr::error(translate('messages.Failed_to_send_mail'));
                }
            } catch (\Throwable $th) {
                print_r($th);
                info($th->getMessage());
                Toastr::error(translate('messages.Failed_to_send_mail'));
            }
            return back();
        }
        Toastr::error(translate('messages.Email_does_not_exists'));
        return back();
    }
    public function reset_password(Request $request)
    {
        $language = BusinessSetting::where('key', 'system_language')->first();
        if ($language) {
            foreach (json_decode($language->value, true) as $key => $data) {
                if ($data['default']) {
                    $lang = $data['code'];
                    $direction = $data['direction'];
                }
            }
        }
        $data = DB::table('password_resets')->where(['token' => $request['token']])->first();
        if (!$data || Carbon::parse($data->created_at)->diffInMinutes(Carbon::now()) >= 60) {
            Toastr::error(translate('messages.link_expired'));
            return redirect()->route('home');
        }
        $token = $request['token'];
        if ($data->created_by == 'admin') {
            $admin = Admin::where('email', $data->email)->where('role_id', 1)->first();
            $otp = rand(10000, 99999);
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $admin['phone']],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            //for payment and sms gateway addon
            $published_status = 0;
            $payment_published_status = config('get_payment_publish_status');
            if (isset($payment_published_status[0]['is_published'])) {
                $published_status = $payment_published_status[0]['is_published'];
            }

            if ($published_status == 1) {
                $response = SmsGateway::send($admin['phone'], $otp);
            } else {
                $response = SMS_module::send($admin['phone'], $otp);
            }
            $site_direction = session()?->get('site_direction') ?? $direction ??  'ltr';
            $locale = session()?->get('local') ??  $lang ?? 'en';

            App::setLocale($locale);
            if ($response != 'success') {
                return view('auth.reset-password', compact('token', 'admin', 'site_direction', 'locale'));
            }
            return view('auth.verify-otp', compact('token', 'admin', 'site_direction', 'locale'));
        } else {
            $site_direction = session()?->get('vendor_site_direction') ?? $direction ?? 'ltr';
            $locale = session()?->get('vendor_local') ??  $lang ?? 'en';
            App::setLocale($locale);
            return view('auth.reset-password-vendor', compact('token', 'site_direction', 'locale'));
        }
    }

    public function verify_token(Request $request)
    {
        $request->validate([
            'reset_token' => 'required',
            'opt-value' => 'required',
        ]);
        $language = BusinessSetting::where('key', 'system_language')->first();
        if ($language) {
            foreach (json_decode($language->value, true) as $key => $data) {
                if ($data['default']) {
                    $lang = $data['code'];
                    $direction = $data['direction'];
                }
            }
        }
        $token = $request['reset_token'];
        $admin = Admin::where('phone', $request['phone'])->where('role_id', 1)->first();

        $data = PhoneVerification::where([
            'phone' => $request['phone'],
            'token' => $request['opt-value'],
        ])->first();

        if (isset($data)) {
            $data?->delete();
            $site_direction = session()?->get('site_direction') ?? $direction ?? 'ltr';
            $locale = session()?->get('local') ?? $lang ??  'en';
            App::setLocale($locale);
            return view('auth.reset-password', compact('token', 'admin', 'site_direction', 'locale'));
        }

        Toastr::error(translate('messages.otp_doesnt_match'));
        return back();
    }

    public function reset_password_submit(Request $request)
    {
        $request->validate([
            'reset_token' => 'required',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
            'confirm_password' => 'required|same:password',
        ]);
        $data = DB::table('password_resets')->where(['token' => $request['reset_token']])->first();
        if (isset($data)) {
            if ($request['password'] == $request['confirm_password']) {
                if ($data->created_by == 'admin') {
                    DB::table('admins')->where(['email' => $data->email])->update([
                        'password' => bcrypt($request['confirm_password'])
                    ]);
                    $user_link = Helpers::get_login_url('admin_login_url');
                } else {
                    DB::table('vendors')->where(['email' => $data->email])->update([
                        'password' => bcrypt($request['confirm_password'])
                    ]);
                    $user_link = Helpers::get_login_url('store_login_url');
                }
                DB::table('password_resets')->where(['token' => $request['reset_token']])->delete();
                Toastr::success(translate('messages.password_changed_successfully'));
                return redirect()->route('login', [$user_link]);
            }
        }
        Toastr::error(translate('messages.something_went_wrong'));
        return back();
    }

    public function logout()
    {
        if (auth('vendor')?->check()) {
            $user_link = Helpers::get_login_url('store_login_url');

            $vendor = Vendor::find(Helpers::get_loggedin_user()->id);
            $vendor->cm_firebase_token = null;
            $vendor->save();

            auth()->guard('vendor')->logout();
        } elseif (auth('vendor_employee')?->check()) {
            $user_link = Helpers::get_login_url('store_employee_login_url');
            auth()->guard('vendor_employee')->logout();
        } else {
            if (!auth()?->guard('admin')?->user()?->role_id == 1) {
                $user_link = Helpers::get_login_url('admin_employee_login_url');
            } else {
                $user_link = Helpers::get_login_url('admin_login_url');
            }
            auth()?->guard('admin')?->logout();
        }
        return redirect()->route('login', [$user_link]);
    }
    public function vendor_logout()
    {
        if (auth('vendor')?->check()) {
            $vendor = Vendor::find(Helpers::get_loggedin_user()->id);
            $vendor->cm_firebase_token = null;
            $vendor->save();
            $user_link = Helpers::get_login_url('store_login_url');
            auth()->guard('vendor')->logout();
        } elseif (auth('vendor_employee')?->check()) {
            $user_link = Helpers::get_login_url('store_employee_login_url');
            auth()->guard('vendor_employee')->logout();
        }
        return redirect()->route('login', [$user_link ?? 'store_login_url']);
    }

    public function otp_resent(Request $request)
    {
        $data = DB::table('password_resets')->where(['token' => $request['token']])->first();
        if (!$data || Carbon::parse($data->created_at)->diffInMinutes(Carbon::now()) >= 60) {
            return response()->json(['errors' => 'link_expired']);
        }
        if ($data->created_by == 'admin') {
            $admin = Admin::where('email', $data->email)->where('role_id', 1)->first();
            $otp = rand(10000, 99999);
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $admin['phone']],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            //for payment and sms gateway addon
            $published_status = 0;
            $payment_published_status = config('get_payment_publish_status');
            if (isset($payment_published_status[0]['is_published'])) {
                $published_status = $payment_published_status[0]['is_published'];
            }

            if ($published_status == 1) {
                $response = SmsGateway::send($admin['phone'], $otp);
            } else {
                $response = SMS_module::send($admin['phone'], $otp);
            }
            if ($response != 'success') {
                return response()->json(['otp_fail' => 'otp_fail']);
            }
            return response()->json(['success' => 'otp_send']);
        }
    }
}
