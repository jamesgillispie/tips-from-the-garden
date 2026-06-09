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

];
