<x-layouts.app :title="config('app.name')">
    <div class="mb-10 text-center">
        <h1 class="font-serif text-4xl font-semibold text-garden-800 sm:text-5xl">
            Talk to your garden.<br>We'll write it down.
        </h1>
        <p class="mx-auto mt-5 max-w-xl text-lg text-soil-700/80">
            Sign in with just your email — no password to remember — then record a
            voice memo from your garden. We'll turn it into a polished article in
            your own voice and email it right back to you.
        </p>
    </div>

    <div class="mx-auto max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Sign in to get started</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Enter your email and we'll send a one-click sign-in link. New here? This
            sets up your garden desk automatically.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-xl bg-garden-100 px-4 py-3 text-base text-garden-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('devLoginUrl'))
            <a href="{{ session('devLoginUrl') }}"
                class="mt-4 block rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 px-4 py-3 text-center text-base font-semibold text-amber-800 hover:bg-amber-100">
                🔧 Local dev — sign in now
            </a>
        @endif

        <form method="POST" action="{{ route('auth.magic.send') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-base font-semibold text-garden-800">Your email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}"
                    placeholder="you@example.com" autocomplete="email" inputmode="email"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('email') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Cloudflare Turnstile — a quiet "are you human?" check. --}}
            <div>
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="light"></div>
                @error('turnstile') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full rounded-xl bg-garden-700 px-6 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800">
                Email me a sign-in link
            </button>
        </form>
    </div>

    {{-- The phone door — no sign-in needed; emailing a memo sets up the account. --}}
    <div class="mx-auto mt-8 max-w-md rounded-2xl border border-garden-100 bg-white p-6 text-center">
        <h2 class="font-serif text-xl font-semibold text-garden-800">📱 Or email a memo from your phone</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Record in your phone's Voice Memos app, tap <strong>share</strong>, and
            email it to us — we'll set up your desk and send the article back.
        </p>
        {{-- email_off keeps Cloudflare's Scrape Shield from obfuscating the address. --}}
        <p class="mt-4 rounded-xl bg-garden-50 px-5 py-3 text-lg font-semibold text-garden-700 select-all">
            <!--email_off-->{{ config('pipeline.inbound.address') ?: 'memos@'.(parse_url(config('app.url'), PHP_URL_HOST) ?? 'tipsfromthegarden.test') }}<!--/email_off-->
        </p>
    </div>

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</x-layouts.app>
