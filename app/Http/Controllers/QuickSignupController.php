<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\CentralLogics\StoreLogic;
use App\Mail\VendorSelfRegistration;
use App\Models\BusinessSetting;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Quick signup — verify identity (phone OTP or Google), collect the three fields a store row
 * cannot exist without, then create the account immediately so the vendor can finish their
 * profile later in the panel.
 *
 * Contrast with VendorController::store_ajax(), which collects everything up front and writes
 * nothing until the last step. Both end at the same place: a store with status 0 waiting in the
 * admin's pending-requests queue.
 */
class QuickSignupController extends Controller
{
    private const SESSION_KEY = 'quick_signup';

    /** The choice page: quick signup, or the full listing form. */
    public function start()
    {
        // Same reasoning as VendorController::create() — this page wears MC Vendor Hub's chrome,
        // whose nav links are relative and only resolve on that host. Host-checked so the redirect
        // cannot point at itself: one application serves both hostnames.
        if (!str_contains(request()->getHost(), 'mcvendorhub.com')) {
            return redirect()->away('https://mcvendorhub.com/list-your-business/start', 301);
        }

        return view('mc-vendor.signup.start');
    }

    /*
    |--------------------------------------------------------------------------
    | Step 1 — phone OTP
    |--------------------------------------------------------------------------
    */

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Enter a valid phone number.']);
        }

        $phone = $request->phone;

        if (Store::where('phone', $phone)->exists()) {
            return response()->json(['status' => false, 'message' => 'This phone is already registered. Try logging in instead.']);
        }

        $check = _check_otp_send_allowed($phone);
        if (!$check['allowed']) {
            return response()->json(['status' => false, 'message' => $check['message']], 429);
        }

        $otp = rand(1000, 9999);
        _sendSMS($phone, 'Dear User , Your OTP for Mobile verification is ' . $otp . ' - Regards MY CHITTI APP.');
        _store_otp($phone, $otp);

        return response()->json(['status' => true, 'message' => 'OTP sent.']);
    }

    public function verifyOtp(Request $request)
    {
        $phone = $request->phone;
        $otp   = is_array($request->otp) ? implode('', $request->otp) : $request->otp;

        if (!_verify_otp($phone, $otp, true)) {
            return response()->json(['status' => false, 'message' => 'Incorrect or expired OTP.']);
        }

        session([self::SESSION_KEY => [
            'via'   => 'otp',
            'phone' => $phone,
            'email' => null,
            'name'  => null,
        ]]);

        return response()->json(['status' => true, 'redirect' => route('quick-signup.form')]);
    }

    /*
    |--------------------------------------------------------------------------
    | Step 2 — the three fields a store row needs
    |--------------------------------------------------------------------------
    */

    public function form()
    {
        $identity = session(self::SESSION_KEY);
        if (!$identity) {
            return redirect()->route('new-store.start');
        }

        $zones = Zone::active()->orderBy('name')->get();

        return view('mc-vendor.signup.quick', compact('identity', 'zones'));
    }

    public function store(Request $request)
    {
        $identity = session(self::SESSION_KEY);
        if (!$identity) {
            return redirect()->route('new-store.start');
        }

        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if (!isset($status) || $status->value == '0') {
            return redirect()->route('home')->with('error', 'Registrations are closed right now.');
        }

        // Whichever contact detail the identity step did not give us is asked for here.
        $rules = [
            'business_name' => 'required|string|max:191',
            'zone_id'       => 'required|exists:zones,id',
            'owner_name'    => 'required|string|max:191',
        ];
        if (empty($identity['phone'])) {
            $rules['phone'] = 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:vendors,phone';
        }
        if (empty($identity['email'])) {
            $rules['email'] = 'required|email|unique:vendors,email';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $phone = $identity['phone'] ?: $request->phone;
        $email = $identity['email'] ?: $request->email;

        if (Store::where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'This phone is already registered.']);
        }

        // No address was collected, so anchor the store on the centre of its city. The vendor
        // moves the pin when they complete their profile.
        [$latitude, $longitude] = $this->zoneCentre($request->zone_id);

        $ownerName = trim($request->owner_name);
        $firstName = Str::before($ownerName, ' ');
        $lastName  = trim(Str::after($ownerName, ' '));

        $vendor = null;

        try {
            DB::transaction(function () use ($request, $phone, $email, $firstName, $lastName, $latitude, $longitude, &$vendor) {

                $vendor = new Vendor();
                $vendor->f_name = $firstName;
                $vendor->l_name = $lastName !== $firstName ? $lastName : '';
                $vendor->email = $email;
                $vendor->phone = $phone;
                $vendor->image = 'def.png';
                // Identity is already proven by OTP/Google and no password was collected. The
                // vendor signs in with phone OTP and can set a password in the panel.
                $vendor->password = bcrypt(Str::random(40));
                // Active so they can get in and finish the profile; the STORE stays unapproved
                // (status 0) so the listing is not public yet.
                $vendor->status = 1;
                $vendor->save();

                $store = new Store;
                $store->name = $request->business_name;
                $store->phone = $phone;
                $store->email = $email;
                $store->logo = 'def.png';
                $store->cover_photo = null;
                $store->address = '';
                $store->latitude = $latitude;
                $store->longitude = $longitude;
                $store->pin_code = getPincodeFromCoordinates($latitude, $longitude);
                $store->vendor_id = $vendor->id;
                $store->zone_id = $request->zone_id;
                $store->vendor_type = 'regular';
                $store->module_id = 6;
                $store->delivery_time = '0-0 min';
                $store->gst = json_encode(['status' => 0, 'code' => '']);
                $store->status = 0;
                $store->save();

                // The same post-creation steps the full form runs (VendorController::store_ajax),
                // so a quick-signup store is not missing its coupons, trial, ledger or opening
                // hours the first time the vendor opens the panel.
                Helpers::_addWelcomeCouponsIfExist($store);
                Helpers::_assignFreeTrial($store);
                Helpers::_createDefaultLedgerAccounts();
                StoreLogic::insert_schedule($store->id);

                $store->module->increment('stores_count');

                _inAppNotification(
                    'New Quick Signup',
                    $store->name . ' signed up via quick signup and still has to complete their profile.',
                    '',
                    0,
                    '/store/pending-requests',
                    'admin'
                );

                try {
                    if ($vendor->email) {
                        Mail::to($vendor->email)->send(new VendorSelfRegistration('pending', $store->name));
                    }
                } catch (\Exception $ex) {
                    info('Quick signup email error: ' . $ex->getMessage());
                }
            });
        } catch (\Throwable $e) {
            info('Quick signup failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['business_name' => 'Something went wrong creating your account. Please try again.']);
        }

        session()->forget(self::SESSION_KEY);

        return redirect()->route('quick-signup.done');
    }

    public function done()
    {
        return view('mc-vendor.signup.done');
    }

    /*
    |--------------------------------------------------------------------------
    | Google
    |--------------------------------------------------------------------------
    */

    /** Whether Google sign-in can actually be offered — see the button guard in start.blade.php. */
    public static function googleReady(): bool
    {
        return !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret'));
    }

    public function googleRedirect()
    {
        if (!static::googleReady()) {
            return redirect()->route('new-store.start')
                ->with('error', 'Google sign-in is not available right now. Please use your phone number.');
        }

        return \Laravel\Socialite\Facades\Socialite::driver('google')
            ->stateless()
            ->redirectUrl(route('quick-signup.google.callback'))
            ->redirect();
    }

    public function googleCallback()
    {
        try {
            $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')
                ->stateless()
                ->redirectUrl(route('quick-signup.google.callback'))
                ->user();
        } catch (\Throwable $e) {
            info('Quick signup Google callback error: ' . $e->getMessage());
            return redirect()->route('new-store.start')->with('error', 'Could not sign you in with Google. Please try again.');
        }

        $email = $googleUser->getEmail();

        if ($email && Vendor::where('email', $email)->exists()) {
            return redirect()->away('https://vendor.mcvendorhub.com/login')
                ->with('error', 'You already have an account with this email. Please log in.');
        }

        session([self::SESSION_KEY => [
            'via'   => 'google',
            'phone' => null,
            'email' => $email,
            'name'  => $googleUser->getName(),
        ]]);

        return redirect()->route('quick-signup.form');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Centre of the zone polygon, so a quick-signup store sits inside its own city rather than at
     * 0,0. Falls back to null if the geometry is unreadable.
     */
    private function zoneCentre($zoneId): array
    {
        try {
            $row = DB::table('zones')
                ->where('id', $zoneId)
                ->selectRaw('ST_Y(ST_Centroid(coordinates)) as lat, ST_X(ST_Centroid(coordinates)) as lng')
                ->first();

            if ($row && $row->lat !== null && $row->lng !== null) {
                return [$row->lat, $row->lng];
            }
        } catch (\Throwable $e) {
            info('zoneCentre failed for zone ' . $zoneId . ': ' . $e->getMessage());
        }

        return [null, null];
    }
}
