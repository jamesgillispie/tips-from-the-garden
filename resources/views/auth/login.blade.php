<x-layouts.app :title="'Sign in — '.config('app.name')">
    <div class="mx-auto max-w-md rounded-2xl border border-garden-100 bg-white p-8">
        <h1 class="font-serif text-2xl font-semibold text-garden-800">Sign in</h1>
        <p class="mt-2 text-base text-soil-700/80">
            No password to remember — we'll email you a link, and one click
            signs you in.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-xl bg-garden-100 px-4 py-3 text-base text-garden-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.magic.send') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-base font-semibold text-garden-800">Your email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}"
                    placeholder="you@example.com" autocomplete="email" inputmode="email"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('email') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                class="w-full rounded-xl bg-garden-700 px-6 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800">
                Email me a sign-in link
            </button>
        </form>
    </div>
</x-layouts.app>
