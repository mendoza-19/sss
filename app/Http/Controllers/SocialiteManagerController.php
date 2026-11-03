<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;

class SocialiteManagerController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => bcrypt(uniqid()),
                    'email_verified_at' => now(),
                    'googlepassword' => true,
                    'iscustomer' => true,
                ]
            );

            $user->cliente()->create([
                'nombre' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'fecharegistro' => now(),
                'foto' => $googleUser->getAvatar(),
            ]);

            Auth::login($user);
            return redirect()->route('menu');
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }
    }
}
