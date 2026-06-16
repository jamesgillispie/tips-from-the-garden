<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
        // Shared secret appended to the inbound webhook URL (?token=...).
        'inbound_token' => env('POSTMARK_INBOUND_TOKEN'),
    ],

    'turnstile' => [
        // Cloudflare Turnstile guards the sign-in form. Defaults are Cloudflare's
        // official "always passes" TEST keys so local dev works with no setup;
        // set real keys (from a widget for your domain) in production.
        'site_key' => env('TURNSTILE_SITE_KEY', '1x00000000000000000000AA'),
        'secret_key' => env('TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA'),
    ],

];
