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
            <div>
                <label for="name" class="block text-base font-semibold text-garden-800">Your name</label>
                <input type="text" id="name" name="name" required value="{{ old('name') }}" autocomplete="name"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('name') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-base font-semibold text-garden-800">Email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}"
                    placeholder="you@example.com" autocomplete="email" inputmode="email"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('email') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-base font-semibold text-garden-800">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('password') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-base font-semibold text-garden-800">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
            </div>

            <x-turnstile />

            <button type="submit" data-turnstile-gate disabled
                class="w-full rounded-xl bg-garden-700 px-6 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800 disabled:cursor-not-allowed disabled:opacity-60">
                Create account
            </button>
        </form>

        <p class="mt-6 text-center text-base text-soil-700/80">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-garden-700 hover:underline">Sign in</a>
        </p>
    </div>
</x-layouts.app>
