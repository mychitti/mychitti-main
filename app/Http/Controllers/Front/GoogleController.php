<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Brian2694\Toastr\Facades\Toastr;

use Throwable;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        session(['google_login_type' => 'user']);
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function vendorRedirectToGoogle()
    {
        session(['google_login_type' => 'vendor']);
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $loginType = session('google_login_type', 'user');

            session()->forget('google_login_type'); // cleanup
            $fullName = $googleUser->getName();
            $nameParts = explode(' ', $fullName);

            $firstName = $nameParts[0] ?? '';
            $lastName  = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
            /*
        |--------------------------------------------------------------------------
        | USER LOGIN FLOW
        |--------------------------------------------------------------------------
        */
        // prx( $loginType);
            if ($loginType === 'user') {

                $user = User::where('email', $googleUser->getEmail())->first();

                if (!$user) {
                    $user = User::create([
                        'f_name'    => $firstName,
                        'l_name'    => $lastName,
                        'email'     => $googleUser->getEmail(),
                        'password'  => bcrypt(str()->random(12)),
                        'google_id' => $googleUser->getId(),
                    ]);
                }

                Auth::guard('web')->login($user);

                return redirect('/');
            }

            /*
        |--------------------------------------------------------------------------
        | VENDOR LOGIN FLOW
        |--------------------------------------------------------------------------
        */
            if ($loginType === 'vendor') {

                $vendor = Vendor::where('email', $googleUser->getEmail())->first();
                if (!$vendor) {
                    Toastr::error('No vendor account associated with this Google account. Please contact support.');
                    return redirect()->route('login', ['tab' => 'store']);
                } else if (!$vendor->status) {
                    Toastr::error('Your vendor account is inactive. Please contact support.');
                    return redirect()->route('login', ['tab' => 'store']);
                }

                Auth::guard('vendor')->login($vendor);

                if (auth('vendor')->check()) {
                    return redirect()->route('vendor.dashboard');
                } else {
                    Toastr::error('Some error occured.');
                    return redirect()->route('login', ['tab' => 'store']);
                }
            }
        } catch (Throwable $e) {
            die($e);
            Toastr::error('Something went wrong!');
            return redirect('/login');
        }
    }
}
