<div class="space-y-10">
    <div>
        <h1 class="font-serif text-3xl font-semibold text-garden-800">My garden desk</h1>
        <p class="mt-1 text-sm text-soil-700/60">{{ auth()->user()->email }}</p>
    </div>

    {{-- In-flight submissions --}}
    @if ($submissions->isNotEmpty())
        <section>
            <h2 class="font-serif text-xl font-semibold text-garden-800">In progress</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($submissions as $submission)
                    <li class="flex items-center justify-between rounded-xl border border-garden-100 bg-white px-4 py-3 text-sm">
                        <span>{{ $submission->original_filename ?? 'Voice memo' }} — {{ $submission->statusLabel() }}</span>
                        @unless ($submission->isFailed())
                            <a href="{{ route('submissions.status', ['submission' => $submission->uuid]) }}"
                                class="text-garden-700 underline">watch</a>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Article library --}}
    <section>
        <h2 class="font-serif text-xl font-semibold text-garden-800">Your articles</h2>
        @if ($articles->isEmpty())
            <p class="mt-3 rounded-xl border border-dashed border-garden-100 bg-white p-6 text-sm text-soil-700/60">
                No articles yet. <a href="{{ route('home') }}" class="text-garden-700 underline">Send your first voice memo</a>
                and it'll show up here.
            </p>
        @else
            <ul class="mt-3 divide-y divide-garden-100 rounded-xl border border-garden-100 bg-white">
                @foreach ($articles as $article)
                    <li class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="min-w-0">
                            <a href="{{ $article->publicUrl() }}" class="font-medium text-garden-800 hover:underline">
                                {{ $article->title }}
                            </a>
                            <p class="text-xs text-soil-700/50">{{ $article->created_at->format('F j, Y') }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2 text-xs">
                            <a class="text-garden-700 underline"
                                href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'pdf']) }}">PDF</a>
                            <a class="text-garden-700 underline"
                                href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'md']) }}">MD</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Writing samples --}}
    <section>
        <h2 class="font-serif text-xl font-semibold text-garden-800">Your writing voice</h2>
        <p class="mt-1 text-sm text-soil-700/60">
            Paste samples of your own writing — blog posts, newsletters, garden journal
            entries. The more we have, the more your articles sound like you.
            Memos you send become samples automatically.
        </p>

        @if (session('sample-added'))
            <div class="mt-3 rounded-lg bg-garden-100 px-4 py-3 text-sm text-garden-800">
                {{ session('sample-added') }}
            </div>
        @endif

        <form wire:submit="addSample" class="mt-4 space-y-3 rounded-xl border border-garden-100 bg-white p-4">
            <input type="text" wire:model="sampleTitle" placeholder="Title (optional)"
                class="block w-full rounded-lg border border-garden-100 px-3 py-2 text-sm">
            <textarea wire:model="sampleBody" rows="6"
                placeholder="Paste a piece of your writing here…"
                class="block w-full rounded-lg border border-garden-100 px-3 py-2 text-sm"></textarea>
            @error('sampleBody') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit"
                class="rounded-lg bg-garden-700 px-4 py-2 text-sm font-semibold text-white hover:bg-garden-800">
                Save sample
            </button>
        </form>

        @if ($samples->isNotEmpty())
            <ul class="mt-4 divide-y divide-garden-100 rounded-xl border border-garden-100 bg-white">
                @foreach ($samples as $sample)
                    <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium {{ $sample->include_in_profile ? '' : 'text-soil-700/40' }}">
                                {{ $sample->title ?? Str::limit($sample->body, 60) }}
                            </p>
                            <p class="text-xs text-soil-700/50">
                                {{ ucfirst($sample->source) }} · {{ $sample->created_at->format('M j, Y') }}
                                · {{ Str::wordCount($sample->body) }} words
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 text-xs">
                            <button wire:click="toggleSample({{ $sample->id }})" class="text-garden-700 underline">
                                {{ $sample->include_in_profile ? 'Mute' : 'Use' }}
                            </button>
                            <button wire:click="deleteSample({{ $sample->id }})"
                                wire:confirm="Delete this sample?" class="text-red-500 underline">
                                Delete
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
