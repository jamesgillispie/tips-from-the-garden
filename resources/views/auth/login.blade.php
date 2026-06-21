<x-layouts.app :title="'Sign in — '.config('app.name')">
    <div class="mb-10 text-center">
        <h1 class="font-serif text-4xl font-semibold text-garden-800 sm:text-5xl">
            Talk to your garden.<br>We'll write it down.
        </h1>
    </div>

    <div class="mx-auto max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Welcome back</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Sign in to record a memo and pick up your journal entries.
        </p>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle" class="mt-4">
                <flux:callout.text>{{ session('status') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf
            <flux:field>
                <flux:label>Your email</flux:label>
                <flux:input type="email" name="email" required :value="old('email')"
                    placeholder="you@example.com" autocomplete="email" inputmode="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                {{-- Label row carries the "Forgot?" link — a plain anchor, so the
                     Turnstile gate (which only touches <button>s) never disables it. --}}
                <div class="flex items-center justify-between">
                    <flux:label>Password</flux:label>
                    <flux:link href="{{ route('password.request') }}" variant="subtle" class="text-sm">Forgot?</flux:link>
                </div>
                <flux:input type="password" name="password" required autocomplete="current-password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:checkbox name="remember" value="1" label="Keep me signed in" />

            <x-turnstile />

            {{-- Stays disabled until Turnstile hands us a fresh token (see x-turnstile);
                 data-turnstile-gate is the hook its callbacks toggle. --}}
            <flux:button type="submit" id="signin-submit" variant="primary" class="w-full" disabled data-turnstile-gate>
                Sign in
            </flux:button>
        </form>

        <flux:separator class="my-7" text="new to the garden?" />

        <p class="text-center text-base text-soil-700/80">
            <flux:link href="{{ route('register') }}" class="font-semibold">Create an account</flux:link>
        </p>
    </div>
</x-layouts.app>
