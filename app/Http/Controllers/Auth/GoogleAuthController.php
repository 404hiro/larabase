<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google authentication failed.');
        }

        $user = User::where('google_id', $googleUser->id)->first();

        if ($user) {
            Auth::login($user);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $user = User::create([
            'name' => $googleUser->name,
            'google_id' => $googleUser->id,
            // 'avatar' => $googleUser->avatar, // You might want to handle avatar download/storage here
        ]);

        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
