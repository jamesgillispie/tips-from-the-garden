<x-layouts.app :title="$heading.' — '.config('app.name')">
    <div class="mx-auto max-w-xl">
        <flux:card class="space-y-6 text-center">
            <flux:icon.shield-check class="mx-auto size-12 text-garden-700" />
            <div>
                <flux:heading size="xl">{{ $heading }}</flux:heading>
                <flux:text class="mx-auto mt-2 max-w-md">{{ $message }}</flux:text>
            </div>
            <div class="flex flex-col justify-center gap-3 sm:flex-row">
                @auth
                    <flux:button href="{{ route('account') }}" variant="primary">Open account settings</flux:button>
                    <flux:button href="{{ route('dashboard') }}">Back to my garden desk</flux:button>
                @else
                    <flux:button href="{{ route('login') }}" variant="primary">Sign in to review settings</flux:button>
                @endauth
            </div>
        </flux:card>
    </div>
</x-layouts.app>
