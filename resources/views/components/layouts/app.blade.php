@props([
    'description' => 'Record a memo in your garden. Get back a journal entry in your own voice.',
    'ogImage' => null,
    'title' => config('app.name'),
    // When true, the page wears the neutral "app" chrome (record / desk / status).
    // When false (default), it keeps the warm garden brand (login, article view).
    'appShell' => false,
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

    {{-- Inter gives the neutral app screens a crisp, product feel; the garden
         brand still leans on the serif for headings + article reading. --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{-- This is a light-mode-only app. Pin Flux's appearance to light *before*
         its boot script reads localStorage, so a visitor whose OS prefers dark
         never gets a half-dark UI (dark Flux components on light garden surfaces). --}}
    <script>window.localStorage.setItem('flux.appearance', 'light');</script>
    @fluxAppearance
</head>
<body class="min-h-screen antialiased {{ $appShell ? 'bg-zinc-50 text-zinc-800' : 'bg-garden-50 text-soil-700' }}">

    @if ($appShell)
        {{-- ───────────────────────  APP CHROME  ─────────────────────── --}}
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
                <a href="{{ route('home') }}"
                    class="flex shrink-0 items-center whitespace-nowrap font-serif text-lg font-semibold text-garden-800">
                    🌱&nbsp;{{ config('app.name') }}
                </a>

                @auth
                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="subtle" size="sm" icon="user-circle" icon:trailing="chevron-down">
                            <span class="hidden max-w-[16ch] truncate sm:inline">{{ auth()->user()->email }}</span>
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item href="{{ route('dashboard') }}" icon="squares-2x2">My garden desk</flux:menu.item>
                            <flux:menu.item href="{{ route('home') }}" icon="microphone">Record a memo</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="arrow-right-start-on-rectangle"
                                x-on:click="document.getElementById('logout-form').submit()">
                                Sign out
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    {{-- Hidden POST form the Sign out menu item submits (keeps CSRF). --}}
                    <form id="logout-form" method="POST" action="{{ route('auth.logout') }}" class="hidden">
                        @csrf
                    </form>
                @else
                    <flux:button href="{{ route('login') }}" variant="primary" size="sm">Sign in</flux:button>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8 sm:py-10">
            {{ $slot }}
        </main>

        <footer class="mx-auto max-w-3xl px-4 pb-10 text-center text-sm text-zinc-400">
            Record a memo in your garden. Get back a journal entry in your own voice.
        </footer>
    @else
        {{-- ──────────────────────  GARDEN BRAND  ────────────────────── --}}
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
    @endif

    @livewireScripts
    @fluxScripts

    {{-- App-wide toast region (Flux). Persisted so it survives Livewire navigations. --}}
    @persist('toast')
        <flux:toast position="top right" />
    @endpersist
</body>
</html>
