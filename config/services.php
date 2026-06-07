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

];
