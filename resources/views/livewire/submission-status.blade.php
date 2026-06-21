<div @if (! $submission->isReady() && ! $submission->isFailed()) wire:poll.3s @endif
    role="status" aria-live="polite" class="mx-auto max-w-xl">

    @if ($submission->isReady() && $submission->article)
        <flux:card class="text-center">
            <div class="text-6xl">🌻</div>
            <h1 class="mt-4 font-serif text-3xl font-semibold text-garden-800">
                {{ $submission->article->title }}
            </h1>
            <flux:text class="mt-3 text-lg">Your journal entry is ready.</flux:text>
            <div class="mt-6">
                <flux:button href="{{ $submission->article->publicUrl() }}" variant="primary" icon="book-open">
                    Read your journal entry
                </flux:button>
            </div>
        </flux:card>
    @elseif ($submission->isFailed())
        <flux:card class="text-center">
            <div class="text-6xl">🥀</div>
            <h1 class="mt-4 font-serif text-2xl font-semibold text-zinc-800">Something went wrong</h1>
            <flux:text class="mt-3">
                We hit a snag turning your memo into a journal entry — it does happen
                now and then. Your recording reached us safely, so please try
                sending it once more.
            </flux:text>
            <div class="mt-6">
                <flux:button href="{{ route('home') }}" variant="primary" icon="arrow-path">Try again</flux:button>
            </div>
        </flux:card>
    @elseif ($submission->isReady())
        {{-- Ready, but the journal entry has since been deleted from the garden desk. --}}
        <flux:card class="text-center">
            <div class="text-6xl">🍂</div>
            <h1 class="mt-4 font-serif text-2xl font-semibold text-garden-800">This journal entry was deleted</h1>
            <flux:text class="mt-3">
                It's no longer on the garden desk. You can record a new memo any time.
            </flux:text>
            <div class="mt-6">
                <flux:button href="{{ route('home') }}" variant="primary" icon="microphone">Record a memo</flux:button>
            </div>
        </flux:card>
    @else
        <flux:card class="text-center">
            <flux:icon.loading class="mx-auto size-12 text-garden-700" />
            <h1 class="mt-6 font-serif text-2xl font-semibold text-garden-800">
                {{ $submission->statusLabel() }}
            </h1>
            <flux:text class="mt-3">
                This usually takes a few minutes. You can close this page —
                we'll email you the journal entry either way.
            </flux:text>

            @php
                $steps = [
                    'received' => 'Received',
                    'transcribing' => 'Listening',
                    'writing' => 'Writing',
                    'ready' => 'Ready',
                ];
                $order = ['received' => 0, 'transcribing' => 1, 'transcribed' => 1, 'writing' => 2, 'ready' => 3];
                $current = $order[$submission->status] ?? 0;
            @endphp

            <ol class="mt-8 flex items-center justify-center gap-2 text-sm">
                @foreach (array_values($steps) as $i => $label)
                    <li class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full font-semibold
                            {{ $i <= $current ? 'bg-garden-700 text-white' : 'bg-zinc-100 text-zinc-400' }}">
                            {{ $i + 1 }}
                        </span>
                        <span class="{{ $i <= $current ? 'font-semibold text-garden-800' : 'text-zinc-400' }}">{{ $label }}</span>
                        @if ($i < count($steps) - 1)
                            <span class="h-px w-6 bg-zinc-200"></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </flux:card>
    @endif
</div>
