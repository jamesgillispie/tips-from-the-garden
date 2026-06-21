<x-layouts.app :title="'Set a new password — '.config('app.name')">
    <div class="mx-auto mt-4 max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Set a new password</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Pick a new password for your garden desk.
        </p>

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
    </div>
</x-layouts.app>
