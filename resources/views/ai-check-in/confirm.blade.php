<x-layouts.app :title="'Confirm your AI choices — '.config('app.name')">
    <div class="mx-auto max-w-xl">
        <flux:card class="space-y-6">
            @if ($action === 'disable')
                <div>
                    <flux:heading size="xl">Stop all AI processing?</flux:heading>
                    <flux:text class="mt-2">
                        New recordings will not be transcribed, rewritten or used for voice learning. Existing work stays until you delete it.
                    </flux:text>
                </div>

                <flux:callout icon="information-circle">
                    <flux:callout.text>
                        You will still be able to type and save your own notes without AI.
                    </flux:callout.text>
                </flux:callout>
            @else
                <div>
                    <flux:heading size="xl">Keep your current AI choices?</flux:heading>
                    <flux:text class="mt-2">
                        Confirm that the settings below are still right for your garden memos.
                    </flux:text>
                </div>

                <ul class="divide-y divide-zinc-100 rounded-xl border border-garden-100 text-sm">
                    <li class="flex items-center justify-between gap-4 px-4 py-3">
                        <span>Transcription</span>
                        <flux:badge :color="$user->canTranscribe() ? 'green' : 'zinc'">
                            {{ $user->canTranscribe() ? 'On' : 'Off' }}
                        </flux:badge>
                    </li>
                    <li class="flex items-center justify-between gap-4 px-4 py-3">
                        <span>Journal entry writing</span>
                        <flux:badge :color="$user->canWriteArticles() ? 'green' : 'zinc'">
                            {{ $user->canWriteArticles() ? 'On' : 'Off' }}
                        </flux:badge>
                    </li>
                    <li class="flex items-center justify-between gap-4 px-4 py-3">
                        <span>Voice learning</span>
                        <flux:badge :color="$user->canLearnVoice() ? 'green' : 'zinc'">
                            {{ $user->canLearnVoice() ? 'On' : 'Off' }}
                        </flux:badge>
                    </li>
                </ul>
            @endif

            <form method="POST" action="{{ $actionUrl }}" class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                @csrf
                <flux:button href="{{ route('login') }}" variant="ghost">Not now</flux:button>
                <flux:button type="submit" :variant="$action === 'disable' ? 'danger' : 'primary'"
                    :icon="$action === 'disable' ? 'no-symbol' : 'check'">
                    {{ $action === 'disable' ? 'Stop AI processing' : 'Keep these choices' }}
                </flux:button>
            </form>
        </flux:card>
    </div>
</x-layouts.app>
