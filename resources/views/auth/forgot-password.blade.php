<x-layouts.app :title="'Reset your password — '.config('app.name')">
    <div class="mx-auto mt-4 max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Reset your password</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Enter your email and we'll send a link to set a new password. Emailed a
            memo in but never set a password? This is how you claim your account.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-xl bg-garden-100 px-4 py-3 text-base text-garden-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-base font-semibold text-garden-800">Email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}"
                    placeholder="you@example.com" autocomplete="email" inputmode="email"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('email') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <x-turnstile />

            <button type="submit" data-turnstile-gate disabled
                class="w-full rounded-xl bg-garden-700 px-6 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800 disabled:cursor-not-allowed disabled:opacity-60">
                Email me a reset link
            </button>
        </form>

        <p class="mt-6 text-center text-base text-soil-700/80">
            <a href="{{ route('login') }}" class="font-semibold text-garden-700 hover:underline">Back to sign in</a>
        </p>
    </div>
</x-layouts.app>
