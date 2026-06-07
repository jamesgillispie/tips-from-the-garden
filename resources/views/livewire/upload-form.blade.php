<div>
    <div class="mb-10 text-center">
        <h1 class="font-serif text-4xl font-semibold text-garden-800">
            Talk to your garden.<br>We'll write it down.
        </h1>
        <p class="mx-auto mt-4 max-w-xl text-soil-700/70">
            Record a voice memo while you walk your garden — or just paste in your
            own notes — and we'll turn it into a polished article in your own voice
            and email it right back to you.
        </p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        {{-- Door A: email from your phone --}}
        <div class="rounded-2xl border border-garden-100 bg-white p-6">
            <h2 class="font-serif text-lg font-semibold text-garden-800">📱 From your phone</h2>
            <p class="mt-2 text-sm text-soil-700/70">
                Record in your Voice Memos app, tap share, and email it to:
            </p>
            <p class="mt-3 rounded-lg bg-garden-50 px-3 py-2 text-center font-mono text-sm text-garden-700 select-all">
                memos@{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'tipsfromthegarden.test' }}
            </p>
            <p class="mt-3 text-xs text-soil-700/50">
                Works even when the garden has no signal — record now, send when
                you're back inside.
            </p>
        </div>

        {{-- Door B: upload a file or paste a transcript from this device --}}
        <div class="rounded-2xl border border-garden-100 bg-white p-6">
            <h2 class="font-serif text-lg font-semibold text-garden-800">💻 From this device</h2>

            {{-- Toggle between uploading audio and pasting text --}}
            <div class="mt-4 inline-flex rounded-lg bg-garden-50 p-1 text-sm">
                <button type="button" wire:click="setMode('audio')"
                    class="rounded-md px-3 py-1.5 font-medium {{ $mode === 'audio' ? 'bg-white text-garden-800 shadow-sm' : 'text-soil-700/60' }}">
                    Upload audio
                </button>
                <button type="button" wire:click="setMode('paste')"
                    class="rounded-md px-3 py-1.5 font-medium {{ $mode === 'paste' ? 'bg-white text-garden-800 shadow-sm' : 'text-soil-700/60' }}">
                    Paste text
                </button>
            </div>

            <form wire:submit="submit" class="mt-4 space-y-4">
                @if ($mode === 'paste')
                    <div>
                        <label for="transcript" class="block text-sm font-medium">Your transcript or notes</label>
                        <textarea id="transcript" wire:model="transcript" rows="6"
                            placeholder="Paste what you wrote or dictated — we'll shape it into an article in your voice."
                            class="mt-1 block w-full rounded-lg border border-garden-100 px-3 py-2 text-sm"></textarea>
                        @error('transcript') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label for="audio" class="block text-sm font-medium">Voice memo file</label>
                        <input type="file" id="audio" wire:model="audio"
                            accept="audio/*,.m4a,.mp3,.wav,.aac,.ogg,.flac"
                            class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-garden-100 file:px-3 file:py-2 file:text-garden-800">
                        <div wire:loading wire:target="audio" class="mt-1 text-xs text-garden-600">Uploading…</div>
                        @error('audio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label for="email" class="block text-sm font-medium">Your email</label>
                    <input type="email" id="email" wire:model="email" placeholder="you@example.com"
                        class="mt-1 block w-full rounded-lg border border-garden-100 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-soil-700/50">We'll send your finished article here.</p>
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="audio,submit"
                    class="w-full rounded-lg bg-garden-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-garden-800 disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Turn it into an article</span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </button>
            </form>
        </div>
    </div>

    <p class="mt-8 text-center text-sm text-soil-700/60">
        Been here before?
        <a href="{{ route('dashboard') }}" class="text-garden-700 underline">Sign in</a>
        to see your past articles and teach us your writing voice.
    </p>
</div>
