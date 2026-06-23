<x-layouts.app :title="'Sign in — '.config('app.name')">
    <x-auth.card hero heading="Welcome back" separator-text="new to the garden?"
        lede="Sign in to record a memo and pick up your journal entries.">

        <x-auth.google />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

            <x-turnstile-submit id="signin-submit">Sign in</x-turnstile-submit>
        </form>

        <x-slot:footer>
            <flux:link href="{{ route('register') }}" class="font-semibold">Create an account</flux:link>
        </x-slot:footer>
    </x-auth.card>
</x-layouts.app>
