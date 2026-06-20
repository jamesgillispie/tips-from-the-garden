@props([
    'description' => 'Record a memo in your garden. Get back an article in your own voice.',
    'ogImage' => null,
    'title' => config('app.name'),
])

@php
    $metaTitle = $title ?: config('app.name');
    $metaDescription = $description ?: 'Record a memo in your garden. Get back an article in your own voice.';
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
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="font-serif text-xl font-semibold text-garden-800">
                🌱 {{ config('app.name') }}
            </a>
            <nav class="flex items-center gap-5 text-base">
                @auth
                    <a href="{{ route('dashboard') }}" class="font-medium text-garden-700 hover:underline">My garden desk</a>
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
        Record a memo in your garden. Get back an article in your own voice.
    </footer>

    @livewireScripts
</body>
</html>
