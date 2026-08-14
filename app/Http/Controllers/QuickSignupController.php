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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    /** Cache prefix for the one-shot ticket that carries a new vendor across to the panel host. */
    private const HANDOFF_KEY = 'qs_login_';

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

        if ($this->phoneTaken($phone)) {
            // `login` rather than the page matching on the message text: this is the one refusal
            // the visitor can act on, and the composer shows them the way in when it is set.
            return response()->json([
                'status'  => false,
                'message' => 'This phone is already registered.',
                'login'   => true,
            ]);
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

        if ($this->phoneTaken($phone)) {
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

        // Straight into the panel rather than a "now go and log in" page — they proved who they
        // were a moment ago, and no password was collected for them to log in WITH.
        //
        // It cannot be done by logging them in here: signup runs on mcvendorhub.com, the panel on
        // vendor.mcvendorhub.com, and DynamicSessionCookie gives those hosts different session
        // cookies under different guards (laravel_session/web against vendor_session/vendor). A
        // session opened here would simply not exist over there. So the account is handed across
        // with a one-shot ticket that land() spends on arrival.
        return redirect()->away($this->panelHandoffUrl($vendor));
    }

    /**
     * A single-use ticket that logs this vendor in on the panel host, as a URL.
     *
     * Ten minutes and one use: it is a bearer credential, so it should be worth as little as
     * possible by the time it reaches anybody's logs or referrer header. Held in the cache rather
     * than signed into the URL because both hosts run on one server against one Redis, and a
     * cached key can be spent — a signature stays valid for its whole lifetime however many times
     * it is replayed.
     */
    private function panelHandoffUrl(Vendor $vendor): string
    {
        $token = Str::random(64);
        Cache::put(self::HANDOFF_KEY . $token, $vendor->id, now()->addMinutes(10));

        // Built against the panel host rather than route(), which would generate this host's URL —
        // the whole point is to arrive somewhere else. The path is the `land` route's own.
        return 'https://vendor.mcvendorhub.com/list-your-business/quick/land?token=' . $token;
    }

    /**
     * Spend the ticket and open the session, on the panel host where it belongs.
     *
     * Redirects to /dashboard as a path, not route('vendor.dashboard'): the panel routes carry a
     * `store-panel` prefix in the route table that this host does not use in its URLs, so the
     * generated route would 404 on the very host it is meant for.
     */
    public function land(Request $request)
    {
        // pull(), not get(): spending the ticket is what stops a shared or re-opened link being a
        // standing key to somebody else's account.
        $vendorId = Cache::pull(self::HANDOFF_KEY . (string) $request->query('token'));

        $vendor = $vendorId ? Vendor::find($vendorId) : null;
        if (!$vendor || !$vendor->status) {
            return redirect()->to('/login')->withErrors([
                'This sign-in link has already been used or has expired. Please log in with your phone number.',
            ]);
        }

        Auth::guard('vendor')->login($vendor);

        // The same log a normal login writes, so activity tracking does not treat a quick-signup
        // vendor as never having signed in.
        try {
            $log = \App\Models\VendorLoginLog::create([
                'vendor_id'        => $vendor->id,
                'login_at'         => now(),
                'last_activity_at' => now(),
            ]);
            session(['login_log_id' => $log->id]);
        } catch (\Throwable $e) {
            info('Quick signup login log failed: ' . $e->getMessage());
        }

        return redirect()->to('/dashboard');
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

    /**
     * Is this number already on a store?
     *
     * Matched on the last ten digits rather than the string, because the same number is written
     * several ways across this database: the listing form posts what its country-code field holds
     * ("+919876543210"), older rows are bare ten-digit numbers, and imports carry spaces and
     * dashes. An exact comparison answers "no" for a number that is plainly already registered,
     * and the caller's answer to that is to create a second account for the same business.
     *
     * The same RIGHT(REPLACE(...)) shape the WhatsApp audience queries use, so both agree on what
     * counts as one person.
     */
    private function phoneTaken(string $phone): bool
    {
        $digits = substr(preg_replace('/[^0-9]/', '', $phone) ?? '', -10);
        if (strlen($digits) < 10) {
            return false;
        }

        return Store::withoutGlobalScopes()
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$digits])
            ->exists();
    }

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
        $existing = $email ? Vendor::where('email', $email)->first() : null;

        if ($existing) {
            // They already have an account and Google has just proved they own the address on it,
            // so sign them in rather than sending them to a login form. Quick-signup accounts
            // never had a password set for them, which made "please log in" a dead end for
            // exactly the people most likely to arrive back here.
            if (!$existing->status) {
                return redirect()->away('https://vendor.mcvendorhub.com/login');
            }

            return redirect()->away($this->panelHandoffUrl($existing));
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
