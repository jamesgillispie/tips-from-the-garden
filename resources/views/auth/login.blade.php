<x-layouts.app :title="config('app.name')">
    <div class="mb-10 text-center">
        <h1 class="font-serif text-4xl font-semibold text-garden-800 sm:text-5xl">
            Talk to your garden.<br>We'll write it down.
        </h1>
    </div>

    <div class="mx-auto max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Sign in to get started</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Enter your email and we'll send a one-click sign-in link. New here? This
            sets up your garden desk automatically.
        </p>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle" class="mt-4">
                <flux:callout.text>{{ session('status') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if (session('devLoginUrl'))
            <a href="{{ session('devLoginUrl') }}"
                class="mt-4 block rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 px-4 py-3 text-center text-base font-semibold text-amber-800 hover:bg-amber-100">
                🔧 Local dev — sign in now
            </a>
        @endif

        <form method="POST" action="{{ route('auth.magic.send') }}" class="mt-6 space-y-5">
            @csrf
            <flux:field>
                <flux:label>Your email</flux:label>
                <flux:input type="email" id="email" name="email" required :value="old('email')"
                    placeholder="you@example.com" autocomplete="email" inputmode="email" />
                <flux:error name="email" />
            </flux:field>

            {{-- Cloudflare Turnstile — a quiet "are you human?" check. The submit
                 button stays disabled until the widget hands us a fresh token, and
                 re-disables if that token expires or errors, so we never POST a
                 stale/expired token (which Turnstile rejects as duplicate). --}}
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

            <flux:button type="submit" id="signin-submit" variant="primary" class="w-full" disabled>
                Email me a sign-in link
            </flux:button>
        </form>
    </div>

    {{-- Gate the sign-in button on a fresh Turnstile token. Defined before the
         async api.js so the callbacks exist when the widget invokes them. --}}
    <script>
        function tftgTurnstileReady() {
            var b = document.getElementById('signin-submit');
            if (b) b.disabled = false;
        }
        function tftgTurnstileStale() {
            var b = document.getElementById('signin-submit');
            if (b) b.disabled = true;
        }
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</x-layouts.app>
