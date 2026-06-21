<x-layouts.app :title="'Set a new password — '.config('app.name')">
    <div class="mx-auto mt-4 max-w-md rounded-2xl border border-garden-100 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-serif text-2xl font-semibold text-garden-800">Set a new password</h2>
        <p class="mt-2 text-base text-soil-700/80">
            Pick a new password for your garden desk.
        </p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="block text-base font-semibold text-garden-800">Email</label>
                <input type="email" id="email" name="email" required value="{{ old('email', $request->email) }}"
                    autocomplete="email" inputmode="email"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('email') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-base font-semibold text-garden-800">New password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                @error('password') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-base font-semibold text-garden-800">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                    class="mt-2 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
            </div>

            <button type="submit"
                class="w-full rounded-xl bg-garden-700 px-6 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800">
                Save new password
            </button>
        </form>
    </div>
</x-layouts.app>
