<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
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
