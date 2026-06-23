@props([
    // Card heading (e.g. "Welcome back") and the supporting line under it.
    'heading',
    'lede' => null,
    // When true the brand hero crowns the page and supplies the top spacing;
    // otherwise the card carries its own top margin.
    'hero' => false,
    // Optional centred label on the footer separator (null = plain rule).
    'separatorText' => null,
])

@if ($hero)
    <x-auth.hero />
@endif

<div @class([
    'mx-auto max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8',
    'mt-4' => ! $hero,
])>
    <h2 class="font-serif text-2xl font-semibold text-garden-800">{{ $heading }}</h2>
    @if ($lede)
        <p class="mt-2 text-base text-soil-700/80">{{ $lede }}</p>
    @endif

    {{-- Renders nothing unless a flash status is present. --}}
    <x-status-callout class="mt-4" />

    {{ $slot }}

    {{-- Optional separator + cross-link row (sign in ↔ register, etc.). --}}
    @isset($footer)
        <flux:separator class="my-7" :text="$separatorText" />
        <p class="text-center text-base text-soil-700/80">{{ $footer }}</p>
    @endisset
</div>
