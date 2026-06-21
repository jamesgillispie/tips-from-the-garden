{{-- Cloudflare Turnstile — a quiet "are you human?" check shared by the sign-in,
     register, and reset-request forms. The submit button stays disabled until
     the widget hands us a fresh token, and re-disables if that token expires or
     errors, so we never POST a stale one (which Turnstile rejects as a
     duplicate). Gate any button with `data-turnstile-gate`. --}}
<div>
    <div class="cf-turnstile"
        data-sitekey="{{ config('services.turnstile.site_key') }}"
        data-theme="light"
        data-callback="tftgTurnstileReady"
        data-expired-callback="tftgTurnstileStale"
        data-error-callback="tftgTurnstileStale"
        data-refresh-expired="auto"></div>
    @error('turnstile') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
</div>

{{-- Defined before the async api.js so the callbacks exist when it calls them.
     @once keeps it to a single copy even if the component is rendered twice. --}}
@once
    <script>
        function tftgTurnstileReady() {
            document.querySelectorAll('button[data-turnstile-gate]').forEach(function (b) { b.disabled = false; });
        }
        function tftgTurnstileStale() {
            document.querySelectorAll('button[data-turnstile-gate]').forEach(function (b) { b.disabled = true; });
        }
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endonce
