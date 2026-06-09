<div @if (! $submission->isReady() && ! $submission->isFailed()) wire:poll.3s @endif
    role="status" aria-live="polite" class="mx-auto max-w-xl text-center">

    @if ($submission->isReady() && $submission->article)
        <div class="rounded-2xl border border-garden-100 bg-white p-10">
            <div class="text-6xl">🌻</div>
            <h1 class="mt-4 font-serif text-3xl font-semibold text-garden-800">
                {{ $submission->article->title }}
            </h1>
            <p class="mt-3 text-lg text-soil-700/80">Your article is ready.</p>
            <a href="{{ $submission->article->publicUrl() }}"
                class="mt-6 inline-block rounded-xl bg-garden-700 px-8 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800">
                Read your article
            </a>
        </div>
    @elseif ($submission->isFailed())
        <div class="rounded-2xl border border-red-200 bg-white p-10">
            <div class="text-6xl">🥀</div>
            <h1 class="mt-4 font-serif text-2xl font-semibold">Something went wrong</h1>
            <p class="mt-3 text-base text-soil-700/80">
                We hit a snag turning your memo into an article — it does happen
                now and then. Your recording reached us safely, so please try
                sending it once more.
            </p>
            <a href="{{ route('home') }}"
                class="mt-6 inline-block rounded-xl bg-garden-700 px-8 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800">
                Try again
            </a>
        </div>
    @else
        <div class="rounded-2xl border border-garden-100 bg-white p-10">
            <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-garden-100 border-t-garden-700"></div>
            <h1 class="mt-6 font-serif text-2xl font-semibold text-garden-800">
                {{ $submission->statusLabel() }}
            </h1>
            <p class="mt-3 text-base text-soil-700/80">
                This usually takes a few minutes. You can close this page —
                we'll email you the article either way.
            </p>

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
                            {{ $i <= $current ? 'bg-garden-700 text-white' : 'bg-garden-100 text-soil-700/60' }}">
                            {{ $i + 1 }}
                        </span>
                        <span class="{{ $i <= $current ? 'text-garden-800 font-semibold' : 'text-soil-700/60' }}">{{ $label }}</span>
                        @if ($i < count($steps) - 1)
                            <span class="h-px w-6 bg-garden-100"></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</div>
