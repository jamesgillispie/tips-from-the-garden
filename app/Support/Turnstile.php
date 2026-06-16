<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Verifies a Cloudflare Turnstile token server-side. Guards the sign-in form
 * so a bot can't fire off magic-link emails in bulk.
 */
class Turnstile
{
    public static function verify(?string $token, ?string $ip = null): bool
    {
        $secret = config('services.turnstile.secret_key');

        // No secret configured (e.g. in the test suite) — treat as disabled.
        if (empty($secret)) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]),
            );
        } catch (\Throwable) {
            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
