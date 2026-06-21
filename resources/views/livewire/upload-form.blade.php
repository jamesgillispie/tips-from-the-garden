<div>
    <div class="mb-6 text-center sm:mb-8">
        <h1 class="font-serif text-3xl font-semibold text-garden-800 sm:text-4xl">
            Talk to your garden
        </h1>
    </div>

    <flux:card class="space-y-6 p-3">
        {{-- Switch how you share — recording is the default and first tab. --}}
        {{-- .live so selecting a tab commits $mode to the server, which swaps the
             server-rendered panel below and fires updatedMode() to clear stale errors. --}}
        <flux:tabs wire:model.live="mode" variant="segmented" scrollable class="w-full">
            <flux:tab name="record" icon="microphone">Record</flux:tab>
            <flux:tab name="audio" icon="arrow-up-tray">Upload</flux:tab>
            <flux:tab name="paste" icon="pencil-square">Type</flux:tab>
        </flux:tabs>

        <form wire:submit="submit" class="space-y-7">
            @if ($mode === 'record')
                <div wire:key="mode-record" x-data="voiceRecorder" aria-live="polite" class="text-center">

                    {{-- This browser can't record --}}
                    <flux:callout x-show="state === 'unsupported'" x-cloak icon="information-circle" class="text-left">
                        <flux:callout.text>
                            This browser can't record audio, but no matter — use the
                            <strong>Upload</strong> tab above, or email your memo to us
                            instead (see below).
                        </flux:callout.text>
                    </flux:callout>

                    {{-- Ready to record --}}
                    <div x-show="state === 'idle'">
                        <button type="button" x-on:click="start"
                            class="mx-auto flex h-44 w-44 flex-col items-center justify-center gap-1 rounded-full bg-garden-700 text-white shadow-lg transition hover:bg-garden-800 active:scale-95">
                            <span class="text-4xl" aria-hidden="true">🎙️</span>
                            <span class="text-lg font-semibold leading-tight">Press to<br>record</span>
                        </button>
                        <p class="mx-auto mt-5 max-w-sm text-base text-zinc-600">
                            Just talk, like you're telling a friend.
                        </p>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-zinc-400">
                            Keep it between <strong>5 seconds</strong> and <strong>3 minutes</strong> —
                            recording stops on its own at 3:00.
                        </p>
                    </div>

                    {{-- Recording — live timer front and centre --}}
                    <div x-show="state === 'recording'" x-cloak>
                        <div class="flex items-center justify-center gap-2 text-red-600">
                            <span class="inline-block h-3 w-3 animate-pulse rounded-full bg-red-600" aria-hidden="true"></span>
                            <span class="text-base font-semibold uppercase tracking-wide">Recording — we're listening</span>
                        </div>
                        <p class="mt-2 text-6xl font-bold tabular-nums text-garden-800"
                            x-text="clock" aria-label="Recording time" aria-live="off"></p>
                        <button type="button" x-on:click="stop"
                            class="relative mx-auto mt-6 flex h-44 w-44 flex-col items-center justify-center gap-1 rounded-full bg-red-600 text-white shadow-lg transition hover:bg-red-700 active:scale-95">
                            <span class="absolute inset-0 -z-10 animate-ping rounded-full bg-red-400 opacity-40" aria-hidden="true"></span>
                            <span class="text-4xl" aria-hidden="true">⏹</span>
                            <span class="text-lg font-semibold leading-tight">Press when<br>you're done</span>
                        </button>
                        <p class="mx-auto mt-4 max-w-sm text-sm text-zinc-400">
                            Give it at least 5 seconds — we'll stop automatically at 3 minutes.
                        </p>
                    </div>

                    {{-- Saving the recording --}}
                    <div x-show="state === 'uploading'" x-cloak class="mx-auto max-w-md">
                        <p class="text-lg font-medium text-garden-800">Saving your recording…</p>
                        <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-garden-100">
                            <div class="h-full rounded-full bg-garden-600 transition-all"
                                x-bind:style="'width: ' + progress + '%'"></div>
                        </div>
                    </div>

                    {{-- Got it — handing the recording straight off to be written --}}
                    <div x-show="state === 'submitting'" x-cloak class="mx-auto max-w-md">
                        <flux:icon.loading class="mx-auto size-12 text-garden-700" />
                        <p class="mt-5 text-lg font-semibold text-garden-800">
                            ✅ Got it — sending your memo off to be written…
                        </p>
                        <p class="mt-1 text-base text-zinc-600">
                            Taking you to the progress page now.
                        </p>
                    </div>

                    {{-- Too short to use --}}
                    <flux:callout x-show="state === 'error' && error === 'short'" x-cloak variant="warning" icon="exclamation-triangle" class="text-left">
                        <flux:callout.text>
                            That was too quick to work with — a recording needs to be at
                            least <strong>5 seconds</strong>.
                            <button type="button" x-on:click="start" class="font-semibold text-garden-700 underline">Record again</button>.
                        </flux:callout.text>
                    </flux:callout>

                    {{-- Microphone blocked --}}
                    <flux:callout x-show="state === 'error' && error === 'denied'" x-cloak variant="warning" icon="exclamation-triangle" class="text-left">
                        <flux:callout.text>
                            We couldn't reach your microphone. If your browser asked for
                            permission, choose <strong>Allow</strong> and
                            <button type="button" x-on:click="start" class="font-semibold text-garden-700 underline">try again</button>.
                            Or use the <strong>Upload</strong> tab above.
                        </flux:callout.text>
                    </flux:callout>

                    {{-- Upload hiccup --}}
                    <flux:callout x-show="state === 'error' && error === 'upload'" x-cloak variant="warning" icon="exclamation-triangle" class="text-left">
                        <flux:callout.text>
                            Hmm, your recording didn't save — your internet connection
                            may have hiccuped.
                            <button type="button" x-on:click="finish" class="font-semibold text-garden-700 underline">Try saving it again</button>
                            or <button type="button" x-on:click="discard" class="font-semibold text-garden-700 underline">start over</button>.
                        </flux:callout.text>
                    </flux:callout>

                    @error('audio') <p class="mt-4 text-base font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            @elseif ($mode === 'audio')
                <div wire:key="mode-audio" class="mx-auto max-w-md">
                    <flux:field>
                        <flux:label>Your voice memo file</flux:label>
                        <flux:description>
                            The file from your Voice Memos or recorder app — m4a, mp3, and wav all work.
                        </flux:description>
                        <input type="file" id="audio" wire:model="audio"
                            accept="audio/*,.m4a,.mp3,.wav,.aac,.ogg,.flac,.webm"
                            class="mt-2 block w-full text-base file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-garden-100 file:px-5 file:py-3 file:text-base file:font-semibold file:text-garden-800 hover:file:bg-garden-100/70">
                        <div wire:loading wire:target="audio" class="mt-2 flex items-center gap-2 text-base font-medium text-garden-700">
                            <flux:icon.loading class="size-4" /> Uploading your file…
                        </div>
                        <div wire:loading.remove wire:target="audio">
                            @if ($audio)
                                <div class="mt-2"><flux:badge color="green" icon="check">File attached</flux:badge></div>
                            @endif
                        </div>
                        <flux:error name="audio" />
                    </flux:field>
                </div>
            @else
                <div wire:key="mode-paste" class="mx-auto max-w-md">
                    <flux:field>
                        <flux:label>Your notes</flux:label>
                        <flux:description>
                            Type or paste what you'd say out loud — rough notes are perfect.
                        </flux:description>
                        <flux:textarea wire:model="transcript" rows="7"
                            placeholder="The tomatoes finally set fruit after that cold snap, and the basil needs pinching back…" />
                        <flux:error name="transcript" />
                    </flux:field>
                </div>
            @endif

            <p class="mx-auto max-w-md text-center text-base text-zinc-500">
                Posting as <span class="font-semibold text-garden-800">{{ auth()->user()->email }}</span> —
                it'll land on your garden desk.
            </p>

            {{-- Record mode submits itself the moment the clip finishes uploading,
                 so the manual button only shows for Upload and Type. --}}
            @if ($mode !== 'record')
                <div class="mx-auto max-w-md">
                    <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full"
                        wire:loading.attr="disabled" wire:target="audio">
                        Turn it into a journal entry
                    </flux:button>
                </div>
            @endif
        </form>
    </flux:card>

    {{-- The phone door --}}
    <flux:card class="mt-8 sm:flex sm:items-center sm:justify-between sm:gap-6">
        <div>
            <flux:heading size="lg" class="font-serif text-garden-800">📱 Prefer your phone's Voice Memos app?</flux:heading>
            <flux:text class="mt-2">
                Record there, tap <strong>share</strong>, and email the memo to us.
                Works even when the garden has no signal — record now, send when
                you're back inside.
            </flux:text>
        </div>
        {{-- email_off tells Cloudflare's Scrape Shield NOT to obfuscate this into
             a "[email protected]" link — the gardener needs to read and copy it. --}}
        <p class="mt-4 shrink-0 rounded-xl bg-garden-50 px-5 py-3 text-center text-lg font-semibold text-garden-700 select-all sm:mt-0">
            <!--email_off-->{{ config('pipeline.inbound.address') ?: 'memos@'.(parse_url(config('app.url'), PHP_URL_HOST) ?? 'tipsfromthegarden.test') }}<!--/email_off-->
        </p>
    </flux:card>
</div>
