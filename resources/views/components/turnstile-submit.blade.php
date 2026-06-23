{{-- A full-width primary submit button that stays disabled until Cloudflare
     Turnstile hands the page a fresh token. `data-turnstile-gate` is the hook the
     x-turnstile callbacks toggle, so we never POST a stale (rejected) token.
     Pair with <x-turnstile/> in the same form. Extra attributes (e.g. id) pass
     through. --}}
<flux:button type="submit" variant="primary" class="w-full" disabled data-turnstile-gate
    {{ $attributes }}>
    {{ $slot }}
</flux:button>
