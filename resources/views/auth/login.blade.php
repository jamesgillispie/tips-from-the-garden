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
                <flux:input type="email" id="email" name="email" required :value="old('email')"
                    placeholder="you@example.com" autocomplete="email" inputmode="email" />
                <flux:error name="email" />
            </flux:field>

            <div>
                <div class="flex items-baseline justify-between">
                    <label for="password" class="block text-base font-semibold text-garden-800">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-garden-700 hover:underline">Forgot?</a>
                </div>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('password') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-base text-soil-700">
                <input type="checkbox" name="remember" value="1"
                    class="h-4 w-4 rounded border-garden-200 text-garden-700 focus:ring-garden-600">
                Keep me signed in
            </label>

            <x-turnstile />

            <flux:button type="submit" id="signin-submit" variant="primary" class="w-full" disabled data-turnstile-gate>
                Sign in
            </flux:button>
        </form>

        <p class="mt-6 text-center text-base text-soil-700/80">
            New here?
            <a href="{{ route('register') }}" class="font-semibold text-garden-700 hover:underline">Create an account</a>
        </p>
    </div>
</x-layouts.app>
