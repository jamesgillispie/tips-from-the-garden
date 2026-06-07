<div @if (! $submission->isReady() && ! $submission->isFailed()) wire:poll.3s @endif class="mx-auto max-w-xl text-center">

    @if ($submission->isReady() && $submission->article)
        <div class="rounded-2xl border border-garden-100 bg-white p-10">
            <div class="text-5xl">🌻</div>
            <h1 class="mt-4 font-serif text-3xl font-semibold text-garden-800">
                {{ $submission->article->title }}
            </h1>
            <p class="mt-2 text-soil-700/70">Your article is ready.</p>
            <a href="{{ $submission->article->publicUrl() }}"
                class="mt-6 inline-block rounded-lg bg-garden-700 px-6 py-3 font-semibold text-white hover:bg-garden-800">
                Read your article
            </a>
        </div>
    @elseif ($submission->isFailed())
        <div class="rounded-2xl border border-red-200 bg-white p-10">
            <div class="text-5xl">🥀</div>
            <h1 class="mt-4 font-serif text-2xl font-semibold">Something went wrong</h1>
            <p class="mt-2 text-sm text-soil-700/70">
                We hit a snag processing your memo. We've been notified — please
                try sending it again in a little while.
            </p>
            <a href="{{ route('home') }}" class="mt-6 inline-block text-garden-700 underline">Try again</a>
        </div>
    @else
        <div class="rounded-2xl border border-garden-100 bg-white p-10">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-garden-100 border-t-garden-700"></div>
            <h1 class="mt-6 font-serif text-2xl font-semibold text-garden-800">
                {{ $submission->statusLabel() }}
            </h1>
            <p class="mt-2 text-sm text-soil-700/70">
                This usually takes a few minutes. You can close this page —
                we'll email you the article either way.
            </p>

            @php
                $steps = [
                    'received' => 'Received',
                    'transcribing' => 'Transcribing',
                    'writing' => 'Writing',
                    'ready' => 'Ready',
                ];
                $order = ['received' => 0, 'transcribing' => 1, 'transcribed' => 1, 'writing' => 2, 'ready' => 3];
                $current = $order[$submission->status] ?? 0;
            @endphp

            <ol class="mt-8 flex items-center justify-center gap-2 text-xs">
                @foreach (array_values($steps) as $i => $label)
                    <li class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full
                            {{ $i <= $current ? 'bg-garden-700 text-white' : 'bg-garden-100 text-soil-700/50' }}">
                            {{ $i + 1 }}
                        </span>
                        <span class="{{ $i <= $current ? 'text-garden-800 font-medium' : 'text-soil-700/50' }}">{{ $label }}</span>
                        @if ($i < count($steps) - 1)
                            <span class="h-px w-6 bg-garden-100"></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</div>
