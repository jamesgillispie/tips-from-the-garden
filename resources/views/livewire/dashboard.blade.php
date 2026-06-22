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
            <p class="mt-1 truncate text-base text-zinc-500">{{ auth()->user()->email }}</p>
        </div>
        {{-- The desk's primary action: straight to recording. Full-size and
             primary so it's the obvious next tap from any tab. Sign out still
             lives in the top-bar account menu. --}}
        <flux:button href="{{ route('home') }}" variant="primary" icon="microphone" class="shrink-0">
            Record a memo
        </flux:button>
    </div>

    {{-- Tabs --}}
    <flux:tabs wire:model.live="tab" variant="segmented" scrollable class="w-full">
        <flux:tab name="articles">Journal{!! $articles->isNotEmpty() ? ' <span class="hidden font-normal text-zinc-400 sm:inline">('.$articles->count().')</span>' : '' !!}</flux:tab>
        <flux:tab name="recordings">Recordings{!! $memos->isNotEmpty() ? ' <span class="hidden font-normal text-zinc-400 sm:inline">('.$memos->count().')</span>' : '' !!}</flux:tab>
        <flux:tab name="voice">My Voice{!! $samples->isNotEmpty() ? ' <span class="hidden font-normal text-zinc-400 sm:inline">('.$samples->count().')</span>' : '' !!}</flux:tab>
    </flux:tabs>

    {{-- ─────────────────────────  JOURNAL  ───────────────────────── --}}
    @if ($tab === 'articles')
        <section wire:key="tab-articles" class="space-y-4">
            {{-- Search across the title and full text of every journal entry. --}}
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" clearable
                placeholder="Search your journal entries…" aria-label="Search your journal entries" autocomplete="off" />

            @if ($articles->isEmpty())
                @if ($search !== '')
                    <flux:callout icon="magnifying-glass">
                        <flux:callout.text>
                            No journal entries match “{{ $search }}”.
                            <flux:callout.link href="#" wire:click.prevent="$set('search', '')">Clear search</flux:callout.link>
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <flux:callout icon="pencil-square">
                        <flux:callout.text>
                            No journal entries yet.
                            <flux:callout.link href="{{ route('home') }}">Record your first memo</flux:callout.link>
                            and your finished journal entry will show up here.
                        </flux:callout.text>
                    </flux:callout>
                @endif
            @else
                <flux:card class="!p-0">
                    <ul class="divide-y divide-zinc-100">
                        @foreach ($articles as $article)
                            <li class="flex items-center justify-between gap-4 px-4 py-4">
                                <div class="min-w-0">
                                    <a href="{{ $article->publicUrl() }}" class="text-lg font-medium text-garden-800 hover:underline">
                                        {{ $article->title }}
                                    </a>
                                    <p class="text-sm text-zinc-400">{{ $article->created_at->format('F j, Y') }}</p>
                                </div>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" aria-label="Entry actions" />
                                    <flux:menu>
                                        <flux:menu.item icon="arrow-down-tray"
                                            href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'pdf']) }}">
                                            Download PDF
                                        </flux:menu.item>
                                        <flux:menu.item icon="document-text"
                                            href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'md']) }}">
                                            Download as text
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete('article', {{ $article->id }})">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
            @endif
        </section>

    {{-- ────────────────────────  RECORDINGS  ───────────────────────── --}}
    @elseif ($tab === 'recordings')
        <section wire:key="tab-recordings" class="space-y-3">
            <flux:text>
                Every memo you've sent — recorded here, uploaded, typed, or emailed in —
                kept as a transcript you can read and download.
            </flux:text>

            @if ($memos->isEmpty())
                <flux:callout icon="microphone">
                    <flux:callout.text>
                        Nothing here yet.
                        <flux:callout.link href="{{ route('home') }}">Send your first memo</flux:callout.link>
                        and it'll be saved here.
                    </flux:callout.text>
                </flux:callout>
            @else
                <ul class="space-y-3">
                    @foreach ($memos as $memo)
                        <li wire:key="memo-{{ $memo->id }}">
                        <flux:card>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-garden-800">
                                        {{ $sourceLabels[$memo->source] ?? '🌱 Memo' }}
                                    </p>
                                    <p class="text-sm text-zinc-400">{{ $memo->created_at->format('F j, Y · g:i a') }}</p>
                                </div>
                                @php
                                    $statusColor = match (true) {
                                        $memo->isReady() => 'green',
                                        $memo->isFailed() => 'red',
                                        default => 'amber',
                                    };
                                @endphp
                                <flux:badge :color="$statusColor" class="shrink-0">{{ $memo->statusLabel() }}</flux:badge>
                            </div>

                            @if ($memo->transcript)
                                <flux:text class="mt-3">
                                    {{ \Illuminate\Support\Str::limit($memo->transcript->raw_text, 200) }}
                                </flux:text>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                @if ($memo->isReady() && $memo->article)
                                    <flux:link href="{{ $memo->article->publicUrl() }}">Read the journal entry</flux:link>
                                @elseif (! $memo->isReady() && ! $memo->isFailed())
                                    <flux:link href="{{ route('submissions.status', ['submission' => $memo->uuid]) }}">Watch progress</flux:link>
                                @endif
                                @if ($memo->transcript)
                                    <flux:link href="{{ route('memos.transcript', ['submission' => $memo->uuid]) }}">Download transcript (.md)</flux:link>
                                @endif
                                <button type="button" wire:click="confirmDelete('memo', {{ $memo->id }})"
                                    class="font-medium text-red-600 hover:underline">Delete</button>
                            </div>
                        </flux:card>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

    {{-- ─────────────────────────  MY VOICE  ────────────────────────── --}}
    @else
        <section wire:key="tab-voice" class="space-y-4">
            <flux:text>
                Paste samples of your own writing — blog posts, newsletters, garden journal
                entries. The more we have, the more your journal entries sound like you.
                Memos you send become samples automatically.
            </flux:text>

            @if ($profileText)
                <flux:callout icon="sparkles">
                    <flux:callout.heading>The voice we’ve learned</flux:callout.heading>
                    <flux:callout.text>{{ $profileText }}</flux:callout.text>
                </flux:callout>
            @endif

            <flux:card>
                <form wire:submit="addSample" class="space-y-4">
                    <flux:input wire:model="sampleTitle" placeholder="Title (optional)" />
                    <flux:textarea wire:model="sampleBody" rows="6" placeholder="Paste a piece of your writing here…" />
                    <flux:error name="sampleBody" />
                    <flux:button type="submit" variant="primary" icon="plus">Add writing sample</flux:button>
                </form>
            </flux:card>

            @if ($samples->isNotEmpty())
                <flux:card class="!p-0">
                    <ul class="divide-y divide-zinc-100">
                        @foreach ($samples as $sample)
                            <li class="flex items-center justify-between gap-4 px-4 py-4 text-base">
                                <div class="min-w-0">
                                    <p class="truncate font-medium {{ $sample->include_in_profile ? 'text-garden-800' : 'text-zinc-400' }}">
                                        {{ $sample->title ?? Str::limit($sample->body, 60) }}
                                    </p>
                                    <p class="text-sm text-zinc-400">
                                        {{ ucfirst($sample->source) }} · {{ $sample->created_at->format('M j, Y') }}
                                        · {{ Str::wordCount($sample->body) }} words
                                        @unless ($sample->include_in_profile) · not being used @endunless
                                    </p>
                                </div>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" aria-label="Sample actions" />
                                    <flux:menu>
                                        <flux:menu.item icon="{{ $sample->include_in_profile ? 'eye-slash' : 'eye' }}"
                                            wire:click="toggleSample({{ $sample->id }})">
                                            {{ $sample->include_in_profile ? 'Stop using' : 'Use again' }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete('sample', {{ $sample->id }})">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
            @endif
        </section>
    @endif

    {{-- The phone door — record on your phone's Voice Memos app and email the memo
         in. Lives on the desk so it's one tap away from any tab, even when the
         garden has no signal. --}}
    <flux:card>
        <flux:heading size="lg" class="font-serif text-garden-800">📱 Prefer your phone's Voice Memos app?</flux:heading>
        <flux:text class="mt-2">
            Record there, tap <strong>share</strong>, and email the memo to us.
            Works even when the garden has no signal — record now, send when
            you're back inside.
        </flux:text>
        <div class="mt-4 sm:max-w-md">
            <x-email-copy />
        </div>

        {{-- Identity guard: a memo is filed by the address it's sent FROM, so it
             has to come from the address on this account. Say so plainly, and
             give a one-tap way to switch accounts if this isn't them. --}}
        <flux:callout icon="information-circle" class="mt-4">
            <flux:callout.text>
                Send it from <strong>{{ auth()->user()->email }}</strong> — the address on this account.
                A memo emailed from any other address won't reach your desk.
                Not you?
                <button type="button" x-on:click="document.getElementById('logout-form').submit()"
                    class="font-semibold text-garden-700 underline">Sign out</button>
                to switch accounts.
            </flux:callout.text>
        </flux:callout>
    </flux:card>

    {{-- Shared confirm-delete dialog (driven by $pendingDelete + performDelete). --}}
    <flux:modal name="confirm-delete" class="w-full sm:min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $pendingDelete['heading'] ?? 'Delete this?' }}</flux:heading>
                <flux:text class="mt-2">{{ $pendingDelete['body'] ?? 'This can’t be undone.' }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="performDelete">
                    {{ $pendingDelete['confirm'] ?? 'Delete' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
