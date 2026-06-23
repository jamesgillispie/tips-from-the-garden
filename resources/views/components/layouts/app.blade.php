@props([
    'description' => 'Record a memo in your garden. Get back a journal entry in your own voice.',
    'ogImage' => null,
    'title' => config('app.name'),
])

@php
    $metaTitle = $title ?: config('app.name');
    // $description already defaults to the brand tagline (see @props above).
    $metaDescription = $description ?: config('app.name');
    $metaImage = $ogImage ?: asset('og-image.png');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Google Consent Mode v2 — boot with everything DENIED, before any tag
         loads. The cookie banner (resources/js/cookie-consent.js) flips
         analytics_storage to 'granted' only when the visitor opts in. This
         block stays even without GTM: it's harmless and makes the consent
         decision meaningful the instant a container is added. --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            wait_for_update: 500,
        });
    </script>

    {{-- Google Tag Manager — only loads once GTM_ID is set in .env. --}}
    @if ($gtmId = config('services.google.gtm_id'))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
    @endif

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
<body class="min-h-screen antialiased bg-garden-50 text-soil-700">

    {{-- GTM <noscript> fallback (only when GTM_ID is set). --}}
    @if ($gtmId = config('services.google.gtm_id'))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    {{-- One header/footer across every page; the shared elements carry
         view-transition-name hooks so they morph in place across navigations. --}}
    <x-site-header />

    <main class="mx-auto max-w-3xl px-4 py-8 sm:py-10">
        {{ $slot }}
    </main>

    <x-site-footer />

    @livewireScripts
    @fluxScripts

    {{-- App-wide toast region (Flux). Persisted so it survives Livewire navigations. --}}
    @persist('toast')
        <flux:toast position="top right" />
    @endpersist
</body>
</html>
