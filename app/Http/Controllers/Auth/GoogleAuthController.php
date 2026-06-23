<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Sign in with Google" via Laravel Socialite. This sits alongside Fortify's
 * password auth — it does not touch Fortify's pipeline. The redirect URI is
 * configured on the Google Cloud OAuth client and must match the callback
 * route exactly (services.google.redirect / GOOGLE_REDIRECT_URI).
 */
class GoogleAuthController extends Controller
{
    /**
     * Bounce the gardener to Google's consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google sends them back here. Find or create the matching account, then
     * log them straight in — no Fortify pipeline.
     */
    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        // Match on both google_id AND email so a Google sign-in never silently
        // takes over a pre-existing password account with the same address.
        $user = User::where('google_id', $googleUser->getId())
            ->where('email', $googleUser->getEmail())
            ->first();

        if (! $user) {
            $user = new User;
            $user->google_id = $googleUser->getId();
            $user->email = $googleUser->getEmail();
            $user->name = $googleUser->getName();
            // They authenticate through Google and won't use a password, but the
            // account still needs one set. The 'password' cast hashes it on save.
            $user->password = Str::random(40);
            // Google has already verified the address, so skip our own check.
            $user->email_verified_at = now();
            $user->save();

            // Keep parity with every other intake door: a user always has a
            // VoiceProfile (see User::fromEmail()).
            $user->voiceProfile()->firstOrCreate([]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }
}
