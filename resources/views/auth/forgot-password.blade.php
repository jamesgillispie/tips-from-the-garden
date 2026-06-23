<x-layouts.app :title="'Reset your password — '.config('app.name')">
    <x-auth.card heading="Reset your password"
        lede="Enter your email and we'll send a link to set a new password. Emailed a memo in but never set a password? This is how you claim your account.">

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" name="email" required :value="old('email')"
                    placeholder="you@example.com" autocomplete="email" inputmode="email" />
                <flux:error name="email" />
            </flux:field>

            <x-turnstile />

            <x-turnstile-submit>Email me a reset link</x-turnstile-submit>
        </form>

        <x-slot:footer>
            <flux:link href="{{ route('login') }}" class="font-semibold">Back to sign in</flux:link>
        </x-slot:footer>
    </x-auth.card>
</x-layouts.app>
