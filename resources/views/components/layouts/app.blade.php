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
            <nav class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-garden-700 hover:underline">My garden desk</a>
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit" class="text-soil-700/60 hover:underline">Sign out</button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-3xl px-4 pb-10 text-center text-xs text-soil-700/50">
        Record a memo in your garden. Get back an article in your own voice.
    </footer>

    @livewireScripts
</body>
</html>
