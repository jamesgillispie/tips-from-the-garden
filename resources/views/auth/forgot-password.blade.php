<x-layouts.app :title="'Reset your password — '.config('app.name')">
    <div class="mx-auto mt-4 max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Reset your password</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Enter your email and we'll send a link to set a new password. Emailed a
            memo in but never set a password? This is how you claim your account.
        </p>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle" class="mt-4">
                <flux:callout.text>{{ session('status') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" name="email" required :value="old('email')"
                    placeholder="you@example.com" autocomplete="email" inputmode="email" />
                <flux:error name="email" />
            </flux:field>

            <x-turnstile />

            {{-- Disabled until Turnstile is ready; data-turnstile-gate is the toggle hook. --}}
            <flux:button type="submit" variant="primary" class="w-full" disabled data-turnstile-gate>
                Email me a reset link
            </flux:button>
        </form>

        <flux:separator class="my-7" />

        <p class="text-center text-base text-soil-700/80">
            <flux:link href="{{ route('login') }}" class="font-semibold">Back to sign in</flux:link>
        </p>
    </div>
</x-layouts.app>
