@props([
    'description' => 'Record a memo in your garden. Get back a journal entry in your own voice.',
    'ogImage' => null,
    'title' => config('app.name'),
])

@php
    $metaTitle = $title ?: config('app.name');
    $metaDescription = $description ?: 'Record a memo in your garden. Get back a journal entry in your own voice.';
    $metaImage = $ogImage ?: asset('og-image.png');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="theme-color" content="#e6f3df">
    <meta name="application-name" content="Tips">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Tips">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/app-icon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:secure_url" content="{{ $metaImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="A seedling above the word Tips on a pale green background">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-garden-50 text-soil-700 antialiased">
    <header class="border-b border-garden-100 bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('home') }}"
                class="flex shrink-0 items-center whitespace-nowrap font-serif text-lg font-semibold text-garden-800 sm:text-xl">
                🌱&nbsp;{{ config('app.name') }}
            </a>
            <nav class="flex shrink-0 items-center gap-5 text-base">
                @auth
                    <a href="{{ route('dashboard') }}" title="My garden desk" aria-label="My garden desk"
                        class="inline-flex items-center gap-2 font-medium text-garden-700 hover:text-garden-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.8" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
                        </svg>
                        <span class="hidden sm:inline">My garden desk</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="font-medium text-garden-700 hover:underline">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-3xl px-4 pb-10 text-center text-sm text-soil-700/70">
        Record a memo in your garden. Get back a journal entry in your own voice.
    </footer>

    @livewireScripts
</body>
</html>
