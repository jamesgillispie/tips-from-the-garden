<div class="space-y-8">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-serif text-3xl font-semibold text-garden-800">Account settings</h1>
            <p class="mt-1 truncate text-base text-zinc-500">{{ auth()->user()->email }}</p>
        </div>
        <flux:button href="{{ route('dashboard') }}" variant="ghost" size="sm" icon="arrow-left" class="shrink-0">
            <span class="hidden sm:inline">Back to desk</span>
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- ─────────────────────────  YOUR DETAILS  ───────────────────────── --}}
    <flux:card>
        <form wire:submit="updateProfile" class="space-y-5">
            <div>
                <flux:heading size="lg">Your details</flux:heading>
                <flux:subheading>A little context helps your journal entries land in the right season and place.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label badge="Optional">Growing region</flux:label>
                    <flux:select wire:model="region" placeholder="Choose your USDA zone…">
                        <flux:select.option value="">Prefer not to say</flux:select.option>
                        @foreach ($this->zones() as $zone)
                            <flux:select.option value="{{ $zone }}">Zone {{ $zone }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="region" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Optional">Birth year</flux:label>
                    <flux:input type="number" wire:model="birthYear" min="1900" max="{{ now()->year }}"
                        placeholder="e.g. 1968" inputmode="numeric" />
                    <flux:error name="birthYear" />
                </flux:field>
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Save details</flux:button>
            </div>
        </form>
    </flux:card>

    {{-- ─────────────────────────  EMAIL ADDRESS  ───────────────────────── --}}
    <flux:card>
        <form wire:submit="requestEmailChange" class="space-y-5">
            <div>
                <flux:heading size="lg">Email address</flux:heading>
                <flux:subheading>This is how you sign in. We'll send a confirmation link to the new address before switching.</flux:subheading>
            </div>

            @if ($pendingEmail)
                <flux:callout icon="clock">
                    <flux:callout.heading>Confirmation pending</flux:callout.heading>
                    <flux:callout.text>
                        We sent a link to <strong>{{ $pendingEmail }}</strong>. Your email changes once you open it.
                    </flux:callout.text>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                        <flux:link href="#" wire:click.prevent="resendEmailChange">Resend link</flux:link>
                        <flux:link href="#" wire:click.prevent="cancelEmailChange" variant="subtle">Cancel change</flux:link>
                    </div>
                </flux:callout>
            @endif

            <flux:field>
                <flux:label>New email</flux:label>
                <flux:input type="email" wire:model="newEmail" placeholder="you@example.com"
                    autocomplete="email" inputmode="email" />
                <flux:error name="newEmail" />
            </flux:field>

            <flux:field>
                <flux:label>Current password</flux:label>
                <flux:input type="password" wire:model="emailPassword" autocomplete="current-password" viewable />
                <flux:error name="emailPassword" />
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Send confirmation link</flux:button>
            </div>
        </form>
    </flux:card>

    {{-- ─────────────────────────  PASSWORD  ───────────────────────── --}}
    <flux:card>
        <form wire:submit="updatePassword" class="space-y-5">
            <div>
                <flux:heading size="lg">Password</flux:heading>
                <flux:subheading>Pick something long you'll remember — you'll need your current one to change it.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Current password</flux:label>
                <flux:input type="password" wire:model="currentPassword" autocomplete="current-password" viewable />
                <flux:error name="currentPassword" />
            </flux:field>

            <flux:field>
                <flux:label>New password</flux:label>
                <flux:input type="password" wire:model="password" autocomplete="new-password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Confirm new password</flux:label>
                <flux:input type="password" wire:model="password_confirmation" autocomplete="new-password" viewable />
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Update password</flux:button>
            </div>
        </form>
    </flux:card>

    {{-- ─────────────────────────  DANGER ZONE  ───────────────────────── --}}
    <flux:card class="border-red-200">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg" class="text-red-700">Danger zone</flux:heading>
                <flux:subheading>These can't be undone. Take a breath first.</flux:subheading>
            </div>

            <div class="flex flex-col gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-base font-medium text-garden-800">Wipe my data</p>
                    <p class="text-sm text-zinc-500">Delete every recording, journal entry and writing sample. Your account stays.</p>
                </div>
                <flux:modal.trigger name="wipe-data" class="shrink-0">
                    <flux:button variant="danger" icon="trash">Wipe data</flux:button>
                </flux:modal.trigger>
            </div>

            <div class="flex flex-col gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-base font-medium text-garden-800">Delete my account</p>
                    <p class="text-sm text-zinc-500">Remove your account and everything in it, for good.</p>
                </div>
                <flux:modal.trigger name="delete-account" class="shrink-0">
                    <flux:button variant="danger" icon="exclamation-triangle">Delete account</flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </flux:card>

    {{-- Wipe-data confirmation. --}}
    <flux:modal name="wipe-data" class="w-full sm:min-w-[24rem]">
        <form wire:submit="wipeData" class="space-y-6">
            <div>
                <flux:heading size="lg">Wipe all your data?</flux:heading>
                <flux:text class="mt-2">
                    Your recordings, journal entries and writing samples will be deleted, and the
                    voice we've learned is reset. Your account stays. This can't be undone.
                </flux:text>
            </div>
            <flux:field>
                <flux:label>Type <strong>wipe</strong> to confirm</flux:label>
                <flux:input wire:model="wipeConfirmation" placeholder="wipe" autocomplete="off" />
                <flux:error name="wipeConfirmation" />
            </flux:field>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" icon="trash">Wipe data</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete-account confirmation. --}}
    <flux:modal name="delete-account" class="w-full sm:min-w-[24rem]">
        <form wire:submit="deleteAccount" class="space-y-6">
            <div>
                <flux:heading size="lg">Delete your account?</flux:heading>
                <flux:text class="mt-2">
                    Your account and everything attached to it — recordings, journal entries,
                    writing samples and your learned voice — will be permanently deleted.
                    This can't be undone.
                </flux:text>
            </div>
            <flux:field>
                <flux:label>Enter your password to confirm</flux:label>
                <flux:input type="password" wire:model="deletePassword" autocomplete="current-password" viewable />
                <flux:error name="deletePassword" />
            </flux:field>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" icon="exclamation-triangle">Delete account</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
