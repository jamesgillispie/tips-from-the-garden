<x-layouts.app :title="'Sign in — '.config('app.name')">
    <div class="mx-auto max-w-md rounded-2xl border border-garden-100 bg-white p-8">
        <h1 class="font-serif text-2xl font-semibold text-garden-800">Sign in</h1>
        <p class="mt-2 text-sm text-soil-700/70">
            No password needed — we'll email you a sign-in link.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-garden-100 px-4 py-3 text-sm text-garden-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.magic.send') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium">Your email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}"
                    placeholder="you@example.com"
                    class="mt-1 block w-full rounded-lg border border-garden-100 px-3 py-2 text-sm">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                class="w-full rounded-lg bg-garden-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-garden-800">
                Email me a sign-in link
            </button>
        </form>
    </div>
</x-layouts.app>
