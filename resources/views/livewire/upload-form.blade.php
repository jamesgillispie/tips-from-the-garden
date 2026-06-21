<div>
    <div class="mb-6 text-center sm:mb-8">
        <h1 class="font-serif pb-10 text-2xl font-semibold text-garden-800 sm:text-4xl">
            Talk to your garden
        </h1>
    </div>

    <div class="rounded-2xl border border-garden-100 bg-white p-5 shadow-sm sm:p-8">
        <form wire:submit="submit" class="space-y-7">
            {{-- The active input — recording is the default and sits right up top. --}}
            @if ($mode === 'record')
                <div wire:key="mode-record" x-data="voiceRecorder" aria-live="polite" class="text-center">

                    {{-- This browser can't record --}}
                    <div x-show="state === 'unsupported'" x-cloak
                        class="mx-auto max-w-md rounded-xl bg-garden-50 p-5 text-base text-soil-700">
                        This browser can't record audio, but no matter — use the
                        <strong>Upload</strong> option below, or email
                        your memo to us instead (see below).
                    </div>

                    {{-- Ready to record --}}
                    <div x-show="state === 'idle'">
                        <button type="button" x-on:click="start"
                            class="mx-auto flex h-44 w-44 flex-col items-center justify-center gap-1 rounded-full bg-garden-700 text-white shadow-lg transition hover:bg-garden-800 active:scale-95">
                            <span class="text-4xl" aria-hidden="true">🎙️</span>
                            <span class="text-lg font-semibold leading-tight">Press to<br>record</span>
                        </button>
                        <p class="mx-auto mt-5 max-w-sm text-base text-soil-700/80">
                            Just talk, like you're telling a friend.
                        </p>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-soil-700/60">
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
                        <p class="mx-auto mt-4 max-w-sm text-sm text-soil-700/60">
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
                        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-garden-100 border-t-garden-700"></div>
                        <p class="mt-5 text-lg font-semibold text-garden-800">
                            ✅ Got it — sending your memo off to be written…
                        </p>
                        <p class="mt-1 text-base text-soil-700/80">
                            Taking you to the progress page now.
                        </p>
                    </div>

                    {{-- Too short to use --}}
                    <div x-show="state === 'error' && error === 'short'" x-cloak
                        class="mx-auto max-w-md rounded-xl bg-amber-50 p-5 text-base text-soil-700">
                        That was too quick to work with — a recording needs to be at
                        least <strong>5 seconds</strong>.
                        <button type="button" x-on:click="start" class="font-semibold text-garden-700 underline">Record again</button>.
                    </div>

                    {{-- Microphone blocked --}}
                    <div x-show="state === 'error' && error === 'denied'" x-cloak
                        class="mx-auto max-w-md rounded-xl bg-amber-50 p-5 text-base text-soil-700">
                        We couldn't reach your microphone. If your browser asked for
                        permission, choose <strong>Allow</strong> and
                        <button type="button" x-on:click="start" class="font-semibold text-garden-700 underline">try again</button>.
                        Or use the <strong>Upload</strong> option below.
                    </div>

                    {{-- Upload hiccup --}}
                    <div x-show="state === 'error' && error === 'upload'" x-cloak
                        class="mx-auto max-w-md rounded-xl bg-amber-50 p-5 text-base text-soil-700">
                        Hmm, your recording didn't save — your internet connection
                        may have hiccuped.
                        <button type="button" x-on:click="finish" class="font-semibold text-garden-700 underline">Try saving it again</button>
                        or <button type="button" x-on:click="discard" class="font-semibold text-garden-700 underline">start over</button>.
                    </div>

                    @error('audio') <p class="mt-4 text-base font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            @elseif ($mode === 'audio')
                <div wire:key="mode-audio" class="mx-auto max-w-md">
                    <label for="audio" class="block text-base font-semibold text-garden-800">Your voice memo file</label>
                    <p class="mt-1 text-base text-soil-700/80">
                        The file from your Voice Memos or recorder app — m4a, mp3, and wav all work.
                    </p>
                    <input type="file" id="audio" wire:model="audio"
                        accept="audio/*,.m4a,.mp3,.wav,.aac,.ogg,.flac,.webm"
                        class="mt-3 block w-full text-base file:mr-4 file:cursor-pointer file:rounded-xl file:border-0 file:bg-garden-100 file:px-5 file:py-3 file:text-base file:font-semibold file:text-garden-800">
                    <div wire:loading wire:target="audio" class="mt-2 text-base font-medium text-garden-700">Uploading your file…</div>
                    <div wire:loading.remove wire:target="audio">
                        @if ($audio)
                            <p class="mt-2 text-base font-medium text-garden-700">✅ File attached.</p>
                        @endif
                    </div>
                    @error('audio') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            @else
                <div wire:key="mode-paste" class="mx-auto max-w-md">
                    <label for="transcript" class="block text-base font-semibold text-garden-800">Your notes</label>
                    <p class="mt-1 text-base text-soil-700/80">
                        Type or paste what you'd say out loud — rough notes are perfect.
                    </p>
                    <textarea id="transcript" wire:model="transcript" rows="7"
                        placeholder="The tomatoes finally set fruit after that cold snap, and the basil needs pinching back…"
                        class="mt-3 block w-full rounded-xl border-2 border-garden-100 px-4 py-3 text-base"></textarea>
                    @error('transcript') <p class="mt-2 text-base font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Switch how you share — recording is the default, front-and-center above. --}}
            <div class="grid grid-cols-3 gap-2 rounded-xl bg-garden-50 p-2"
                role="tablist" aria-label="How would you like to share your memo?">
                <button type="button" wire:click="setMode('record')" role="tab"
                    aria-selected="{{ $mode === 'record' ? 'true' : 'false' }}"
                    class="rounded-lg px-3 py-2.5 text-sm font-semibold transition sm:text-base
                        {{ $mode === 'record' ? 'bg-white text-garden-800 shadow' : 'text-soil-700/80 hover:bg-white/60' }}">
                    🎙️ Record
                </button>
                <button type="button" wire:click="setMode('audio')" role="tab"
                    aria-selected="{{ $mode === 'audio' ? 'true' : 'false' }}"
                    class="rounded-lg px-3 py-2.5 text-sm font-semibold transition sm:text-base
                        {{ $mode === 'audio' ? 'bg-white text-garden-800 shadow' : 'text-soil-700/80 hover:bg-white/60' }}">
                    📁 Upload
                </button>
                <button type="button" wire:click="setMode('paste')" role="tab"
                    aria-selected="{{ $mode === 'paste' ? 'true' : 'false' }}"
                    class="rounded-lg px-3 py-2.5 text-sm font-semibold transition sm:text-base
                        {{ $mode === 'paste' ? 'bg-white text-garden-800 shadow' : 'text-soil-700/80 hover:bg-white/60' }}">
                    ✏️ Type
                </button>
            </div>

            <p class="mx-auto max-w-md text-center text-base text-soil-700/70">
                Posting as <span class="font-semibold text-garden-800">{{ auth()->user()->email }}</span> —
                it'll land on your garden desk.
            </p>

            {{-- Record mode submits itself the moment the clip finishes uploading,
                 so the manual button only shows for Upload and Type. --}}
            @if ($mode !== 'record')
                <button type="submit" wire:loading.attr="disabled" wire:target="audio,submit"
                    class="mx-auto block w-full max-w-md rounded-xl bg-garden-700 px-6 py-4 text-lg font-semibold text-white shadow transition hover:bg-garden-800 active:scale-[.99] disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Turn it into a journal entry</span>
                    <span wire:loading wire:target="submit">Sending it off…</span>
                </button>
            @endif
        </form>
    </div>

    {{-- The phone door --}}
    <div class="mt-8 rounded-2xl border border-garden-100 bg-white p-6 sm:flex sm:items-center sm:justify-between sm:gap-6">
        <div>
            <h2 class="font-serif text-xl font-semibold text-garden-800">📱 Prefer your phone's Voice Memos app?</h2>
            <p class="mt-2 text-base text-soil-700/80">
                Record there, tap <strong>share</strong>, and email the memo to us.
                Works even when the garden has no signal — record now, send when
                you're back inside.
            </p>
        </div>
        {{-- email_off tells Cloudflare's Scrape Shield NOT to obfuscate this into
             a "[email protected]" link — the gardener needs to read and copy it. --}}
        <p class="mt-4 shrink-0 rounded-xl bg-garden-50 px-5 py-3 text-center text-lg font-semibold text-garden-700 select-all sm:mt-0">
            <!--email_off-->{{ config('pipeline.inbound.address') ?: 'memos@'.(parse_url(config('app.url'), PHP_URL_HOST) ?? 'tipsfromthegarden.test') }}<!--/email_off-->
        </p>
    </div>
</div>
