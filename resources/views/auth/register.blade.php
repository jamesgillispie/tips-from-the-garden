<x-layouts.app :title="'Create your account — '.config('app.name')">
    <x-auth.card hero heading="Create your account" separator-text="already growing with us?"
        lede="Set up your garden desk — it only takes a moment.">

        <x-auth.google />

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
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

            <x-turnstile-submit>Create account</x-turnstile-submit>
        </form>

        <x-slot:footer>
            <flux:link href="{{ route('login') }}" class="font-semibold">Sign in</flux:link>
        </x-slot:footer>
    </x-auth.card>
</x-layouts.app>
