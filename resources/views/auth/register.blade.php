<x-layouts.app :title="'Create your account — '.config('app.name')">
    <div class="mb-10 text-center">
        <h1 class="font-serif text-4xl font-semibold text-garden-800 sm:text-5xl">
            Talk to your garden.<br>We'll write it down.
        </h1>
    </div>

    <div class="mx-auto max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Create your account</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Set up your garden desk — it only takes a moment.
        </p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
            @csrf
            <flux:field>
                <flux:label>Your name</flux:label>
                <flux:input type="text" name="name" required :value="old('name')" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" name="email" required :value="old('email')"
                    placeholder="you@example.com" autocomplete="email" inputmode="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Password</flux:label>
                <flux:input type="password" name="password" required autocomplete="new-password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Confirm password</flux:label>
                <flux:input type="password" name="password_confirmation" required autocomplete="new-password" viewable />
            </flux:field>

            <x-turnstile />

            {{-- Disabled until Turnstile is ready; data-turnstile-gate is the toggle hook. --}}
            <flux:button type="submit" variant="primary" class="w-full" disabled data-turnstile-gate>
                Create account
            </flux:button>
        </form>

        <flux:separator class="my-7" text="already growing with us?" />

        <p class="text-center text-base text-soil-700/80">
            <flux:link href="{{ route('login') }}" class="font-semibold">Sign in</flux:link>
        </p>
    </div>
</x-layouts.app>
