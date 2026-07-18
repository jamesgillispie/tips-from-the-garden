<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pipeline drivers
    |--------------------------------------------------------------------------
    |
    | Every stage of the voice-to-article pipeline is provider-agnostic.
    | Swap drivers with an .env change — no code changes required.
    |
    | transcriber: whisper_cpp | fake
    | writer:      ollama | anthropic | fake
    |
    */

    'transcriber' => env('TRANSCRIBER_DRIVER', 'whisper_cpp'),

    'writer' => env('WRITER_DRIVER', 'ollama'),

    'whisper_cpp' => [
        'binary' => env('WHISPER_BINARY', '/opt/homebrew/bin/whisper-cli'),
        'model' => env('WHISPER_MODEL', '/opt/homebrew/share/whisper-cpp/ggml-large-v3-turbo.bin'),
        'ffmpeg' => env('FFMPEG_BINARY', '/opt/homebrew/bin/ffmpeg'),
        'threads' => env('WHISPER_THREADS', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Garden lexicon
    |--------------------------------------------------------------------------
    |
    | New-England plant/cultivar vocabulary (App\Pipeline\Support\GardenLexicon)
    | that primes whisper's recognition and corrects known mishearings in the
    | transcript (e.g. "rucola" -> "arugula"). Toggle off with GARDEN_LEXICON=false.
    |
    */

    'lexicon' => [
        'enabled' => env('GARDEN_LEXICON', true),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:32b'),
        'timeout' => env('OLLAMA_TIMEOUT', 600),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-6'),
        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 8000),
        'timeout' => env('ANTHROPIC_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio intake
    |--------------------------------------------------------------------------
    */

    'audio' => [
        'disk' => env('AUDIO_DISK', 'local'),
        'path' => 'audio',
        'max_size_kb' => env('AUDIO_MAX_SIZE_KB', 102400), // 100 MB
        // webm/weba are what browsers produce when recording on the page itself.
        'mimes' => ['m4a', 'mp3', 'wav', 'aac', 'ogg', 'oga', 'flac', 'mp4', 'caf', 'webm', 'weba'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo intake
    |--------------------------------------------------------------------------
    |
    | Photos captured alongside a recording. Every photo is re-encoded on
    | intake (JPEG, capped long edge, EXIF stripped) and the original is
    | discarded — see docs/adr/0003. Stored on a private disk and served only
    | through the app's token-gated route — see docs/adr/0002. Production
    | sets PHOTO_DISK=s3; audio stays on local disk (whisper.cpp needs a
    | local file) so the two disks are configured separately.
    |
    */

    'photos' => [
        'disk' => env('PHOTO_DISK', 'local'),
        'path' => 'photos',
        'max_per_submission' => env('PHOTO_MAX_PER_SUBMISSION', 4),
        'max_size_kb' => env('PHOTO_MAX_SIZE_KB', 15360), // 15 MB — room for phone originals
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'],
        'max_edge' => 2000,   // display copy: long edge cap in px
        'thumb_edge' => 640,  // thumbnail: long edge cap in px
        'quality' => 82,      // JPEG quality for both re-encodes
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice profiles
    |--------------------------------------------------------------------------
    |
    | Regenerate a user's voice profile after this many new samples.
    |
    */

    'voice_profile' => [
        'regenerate_every' => env('VOICE_PROFILE_REGENERATE_EVERY', 3),
        'max_samples_in_prompt' => env('VOICE_PROFILE_MAX_SAMPLES', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbound email door
    |--------------------------------------------------------------------------
    |
    | The address gardeners email voice memos to, shown on the homepage. Kept
    | separate from APP_URL because the web host (e.g. a tunnel subdomain) is
    | usually a CNAME that can't also carry mail. Falls back to the APP_URL
    | host when unset.
    |
    */

    'inbound' => [
        'address' => env('INBOUND_EMAIL_ADDRESS'),
    ],

];
