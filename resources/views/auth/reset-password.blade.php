<x-layouts.app :title="'Set a new password — '.config('app.name')">
    <x-auth.card heading="Set a new password" lede="Pick a new password for your garden desk.">

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            {{-- Carries the signed reset token from the emailed link. --}}
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" name="email" required :value="old('email', $request->email)"
                    autocomplete="email" inputmode="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>New password</flux:label>
                <flux:input type="password" name="password" required autocomplete="new-password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Confirm new password</flux:label>
                <flux:input type="password" name="password_confirmation" required autocomplete="new-password" viewable />
            </flux:field>

            {{-- No Turnstile here — the signed token in the link already gates access,
                 so the button is live immediately. --}}
            <flux:button type="submit" variant="primary" class="w-full">
                Save new password
            </flux:button>
        </form>
    </x-auth.card>
</x-layouts.app>
