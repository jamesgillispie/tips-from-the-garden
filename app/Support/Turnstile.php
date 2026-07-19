<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Cloudflare Turnstile token server-side. Guards the public auth
 * forms (sign in, register, reset request) so bots can't spray them.
 */
class Turnstile
{
    public static function verify(?string $token, ?string $ip = null): bool
    {
        $secret = config('services.turnstile.secret_key');

        // No secret configured. Outside local/testing this is a misconfiguration,
        // not a feature — fail closed and shout, rather than silently leaving the
        // public auth forms open to bots. A broken sign-in is recoverable; an
        // undetected open door is not.
        if (empty($secret)) {
            if (app()->environment('local', 'testing')) {
                return true;
            }

            Log::critical('Turnstile secret is not configured; refusing to verify. Set TURNSTILE_SECRET_KEY.');

            return false;
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
