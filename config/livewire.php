<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary file uploads
    |--------------------------------------------------------------------------
    |
    | Livewire's default temporary-upload cap is 12 MB, which a long voice
    | memo blows straight past. Match the pipeline's audio limit and give
    | slow rural connections plenty of time to finish uploading.
    | (Only this key is overridden; the rest of Livewire's config merges
    | from the package defaults.)
    |
    */

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', 'max:'.env('AUDIO_MAX_SIZE_KB', 102400)],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 30,
        'cleanup' => true,
    ],

];
