<?php

return [

    'admin_app_path' => env('ADMIN_APP_PATH', 'admin'),

    'admin_app_url' => env('ADMIN_APP_URL', env('APP_URL')),

    'enabled' => [
        'users-management' => true,
        'media-library' => true,
        'file-library' => false,
        'settings' => false,
        'dashboard' => true,
    ],

];
