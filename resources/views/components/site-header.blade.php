{{-- One header across the whole app — the warm garden brand chrome (soft-green
     border, growing logo) stays constant on every page so navigating feels
     continuous. Only the right-hand control is contextual: the account menu when
     signed in, a Sign in link when not. The view-transition-name hooks let the
     stable bits morph in place across navigations. --}}
<header class="border-b border-garden-100 bg-white [view-transition-name:site-header]">
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
        <a href="{{ route('home') }}"
            class="flex shrink-0 items-center whitespace-nowrap font-serif text-lg font-semibold text-garden-800 sm:text-xl [view-transition-name:site-logo]">
            🌱&nbsp;{{ config('app.name') }}
        </a>

        @auth
            <flux:dropdown position="bottom" align="end" class="absolute right-2 [view-transition-name:site-nav]">
                <flux:button variant="subtle" size="sm" icon="user-circle" icon:trailing="chevron-down">
                    <span class="hidden max-w-[16ch] truncate sm:inline">{{ auth()->user()->email }}</span>
                </flux:button>

                <flux:menu>
                    <flux:menu.item href="{{ route('dashboard') }}" icon="squares-2x2">My garden desk</flux:menu.item>
                    <flux:menu.item href="{{ route('home') }}" icon="microphone">Record a memo</flux:menu.item>
                    <flux:menu.item href="{{ route('account') }}" icon="cog-6-tooth">Account settings</flux:menu.item>
                    <flux:menu.separator />
                    {{-- Native form submit: Flux menu items don't run Alpine's x-on:click,
                         so Sign out must POST the logout form itself (keeps CSRF). --}}
                    <x-logout-form>
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">
                            Sign out
                        </flux:menu.item>
                    </x-logout-form>
                </flux:menu>
            </flux:dropdown>
        @else
            <a href="{{ route('login') }}"
                class="font-medium text-garden-700 hover:underline [view-transition-name:site-nav]">Sign in</a>
        @endauth
    </div>
</header>
