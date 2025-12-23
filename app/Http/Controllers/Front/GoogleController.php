<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect(); 
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists
            $user = User::where('email', $googleUser->getEmail())->first();

            $fullName = $googleUser->getName();
            $nameParts = explode(' ', $fullName);

            $firstName = $nameParts[0];
            $lastName  = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

            if (!$user) {
                // Create new user
                $user = User::create([
                    'f_name' => $firstName,
                    'l_name' => $lastName,
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(str()->random(12)), // random password
                    'google_id' => $googleUser->getId(),
                ]);
            }

            Auth::login($user);

            return redirect('/'); // redirect where you want
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Something went wrong');
        }
    }
}
