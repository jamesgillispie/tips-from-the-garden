@php
    // How each memo arrived — shown as a friendly label on its card.
    $sourceLabels = [
        \App\Models\Submission::SOURCE_RECORD => '🎙️ Recorded here',
        \App\Models\Submission::SOURCE_UPLOAD => '📁 Uploaded',
        \App\Models\Submission::SOURCE_PASTE => '✏️ Typed',
        \App\Models\Submission::SOURCE_EMAIL => '📧 Emailed in',
    ];
@endphp

<div class="space-y-8">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-serif text-3xl font-semibold text-garden-800">My garden desk</h1>
            <p class="mt-1 truncate text-base text-soil-700/70">{{ auth()->user()->email }}</p>
        </div>
        <form method="POST" action="{{ route('auth.logout') }}" class="shrink-0">
            @csrf
            <button type="submit" class="text-base font-medium text-soil-700/70 hover:underline">Sign out</button>
        </form>
    </div>

    {{-- Tabs --}}
    <div class="grid grid-cols-3 gap-2 rounded-xl bg-garden-50 p-2" role="tablist" aria-label="Your garden desk">
        @foreach (['articles' => 'Articles', 'recordings' => 'Recordings', 'voice' => 'My Voice'] as $key => $label)
            @php
                $count = match ($key) {
                    'articles' => $articles->count(),
                    'recordings' => $memos->count(),
                    'voice' => $samples->count(),
                };
            @endphp
            <button type="button" wire:click="setTab('{{ $key }}')" role="tab"
                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                class="rounded-lg px-3 py-3 text-base font-semibold transition
                    {{ $tab === $key ? 'bg-white text-garden-800 shadow' : 'text-soil-700/80 hover:bg-white/60' }}">
                {{ $label }}@if ($count) <span class="text-sm font-normal text-soil-700/50">({{ $count }})</span>@endif
            </button>
        @endforeach
    </div>

    {{-- ─────────────────────────  ARTICLES  ───────────────────────── --}}
    @if ($tab === 'articles')
        <section wire:key="tab-articles">
            @if ($articles->isEmpty())
                <p class="rounded-xl border border-dashed border-garden-100 bg-white p-6 text-base text-soil-700/70">
                    No articles yet. <a href="{{ route('home') }}" class="font-medium text-garden-700 underline">Record your first memo</a>
                    and your finished article will show up here.
                </p>
            @else
                <ul class="divide-y divide-garden-100 rounded-xl border border-garden-100 bg-white">
                    @foreach ($articles as $article)
                        <li class="flex items-center justify-between gap-4 px-4 py-4">
                            <div class="min-w-0">
                                <a href="{{ $article->publicUrl() }}" class="text-lg font-medium text-garden-800 hover:underline">
                                    {{ $article->title }}
                                </a>
                                <p class="text-sm text-soil-700/60">{{ $article->created_at->format('F j, Y') }}</p>
                            </div>
                            <div class="flex shrink-0 gap-3 text-sm">
                                <a class="font-medium text-garden-700 underline"
                                    href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'pdf']) }}">PDF</a>
                                <a class="font-medium text-garden-700 underline"
                                    href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'md']) }}">Text</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

    {{-- ────────────────────────  RECORDINGS  ───────────────────────── --}}
    @elseif ($tab === 'recordings')
        <section wire:key="tab-recordings" class="space-y-3">
            <p class="text-base text-soil-700/70">
                Every memo you've sent — recorded here, uploaded, typed, or emailed in —
                kept as a transcript you can read and download.
            </p>

            @if ($memos->isEmpty())
                <p class="rounded-xl border border-dashed border-garden-100 bg-white p-6 text-base text-soil-700/70">
                    Nothing here yet. <a href="{{ route('home') }}" class="font-medium text-garden-700 underline">Send your first memo</a>
                    and it'll be saved here.
                </p>
            @else
                <ul class="space-y-3">
                    @foreach ($memos as $memo)
                        <li class="rounded-xl border border-garden-100 bg-white p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-garden-800">
                                        {{ $sourceLabels[$memo->source] ?? '🌱 Memo' }}
                                    </p>
                                    <p class="text-sm text-soil-700/60">{{ $memo->created_at->format('F j, Y · g:i a') }}</p>
                                </div>
                                @php
                                    $pill = match (true) {
                                        $memo->isReady() => 'bg-garden-100 text-garden-800',
                                        $memo->isFailed() => 'bg-red-50 text-red-700',
                                        default => 'bg-amber-50 text-amber-800',
                                    };
                                @endphp
                                <span class="shrink-0 rounded-full px-3 py-1 text-sm font-medium {{ $pill }}">
                                    {{ $memo->statusLabel() }}
                                </span>
                            </div>

                            @if ($memo->transcript)
                                <p class="mt-3 text-base text-soil-700/80">
                                    {{ \Illuminate\Support\Str::limit($memo->transcript->raw_text, 200) }}
                                </p>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-4 text-sm">
                                @if ($memo->isReady() && $memo->article)
                                    <a href="{{ $memo->article->publicUrl() }}" class="font-medium text-garden-700 underline">Read the article</a>
                                @elseif (! $memo->isFailed())
                                    <a href="{{ route('submissions.status', ['submission' => $memo->uuid]) }}" class="font-medium text-garden-700 underline">Watch progress</a>
                                @endif
                                @if ($memo->transcript)
                                    <a href="{{ route('memos.transcript', ['submission' => $memo->uuid]) }}" class="font-medium text-garden-700 underline">Download transcript (.md)</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

    {{-- ─────────────────────────  MY VOICE  ────────────────────────── --}}
    @else
        <section wire:key="tab-voice" class="space-y-4">
            <p class="text-base text-soil-700/70">
                Paste samples of your own writing — blog posts, newsletters, garden journal
                entries. The more we have, the more your articles sound like you.
                Memos you send become samples automatically.
            </p>

            @if ($profileText)
                <div class="rounded-xl border border-garden-100 bg-garden-50 p-4">
                    <p class="text-sm font-semibold uppercase tracking-wide text-garden-700">The voice we've learned</p>
                    <p class="mt-2 text-base text-soil-700/80">{{ $profileText }}</p>
                </div>
            @endif

            @if (session('sample-added'))
                <div class="rounded-xl bg-garden-100 px-4 py-3 text-base text-garden-800">
                    {{ session('sample-added') }}
                </div>
            @endif

            <form wire:submit="addSample" class="space-y-3 rounded-xl border border-garden-100 bg-white p-4">
                <input type="text" wire:model="sampleTitle" placeholder="Title (optional)"
                    class="block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base">
                <textarea wire:model="sampleBody" rows="6"
                    placeholder="Paste a piece of your writing here…"
                    class="block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base"></textarea>
                @error('sampleBody') <p class="text-base font-medium text-red-600">{{ $message }}</p> @enderror
                <button type="submit"
                    class="rounded-xl bg-garden-700 px-5 py-3 text-base font-semibold text-white shadow transition hover:bg-garden-800">
                    Add writing sample
                </button>
            </form>

            @if ($samples->isNotEmpty())
                <ul class="divide-y divide-garden-100 rounded-xl border border-garden-100 bg-white">
                    @foreach ($samples as $sample)
                        <li class="flex items-center justify-between gap-4 px-4 py-4 text-base">
                            <div class="min-w-0">
                                <p class="truncate font-medium {{ $sample->include_in_profile ? '' : 'text-soil-700/50' }}">
                                    {{ $sample->title ?? Str::limit($sample->body, 60) }}
                                </p>
                                <p class="text-sm text-soil-700/60">
                                    {{ ucfirst($sample->source) }} · {{ $sample->created_at->format('M j, Y') }}
                                    · {{ Str::wordCount($sample->body) }} words
                                    @unless ($sample->include_in_profile) · not being used @endunless
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-4 text-sm">
                                <button wire:click="toggleSample({{ $sample->id }})" class="font-medium text-garden-700 underline">
                                    {{ $sample->include_in_profile ? 'Stop using' : 'Use again' }}
                                </button>
                                <button wire:click="deleteSample({{ $sample->id }})"
                                    wire:confirm="Delete this sample?" class="font-medium text-red-600 underline">
                                    Delete
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
