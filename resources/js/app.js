// Livewire's JS (and Alpine) is injected automatically via @livewireScripts.
// App-level JS sprinkles live here.

// Cookie consent banner + analytics gating (see cookie-consent.js).
import './cookie-consent.js';

// In-page voice recorder for the upload form. Records with MediaRecorder,
// then pushes the finished clip into the Livewire `audio` upload property —
// from there it flows through the exact same pipeline as an uploaded file.
document.addEventListener('alpine:init', () => {
    Alpine.data('voiceRecorder', () => ({
        // idle | recording | uploading | submitting | error | unsupported
        state: 'idle',
        error: null, // 'denied' | 'upload' | 'short'
        seconds: 0,
        progress: 0,
        previewUrl: null,
        recorder: null,
        stream: null,
        chunks: [],
        timer: null,
        cancelled: false,
        starting: false,
        tooShort: false,
        minSeconds: 5,
        maxSeconds: 180,

        init() {
            if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
                this.state = 'unsupported';
            }
        },

        destroy() {
            this.cancelled = true;
            clearInterval(this.timer);
            this.stopTracks();
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
        },

        get clock() {
            const m = Math.floor(this.seconds / 60);
            const s = String(this.seconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        },

        mimeType() {
            // Chrome/Firefox record webm or ogg; Safari (iPhone/iPad) records mp4.
            return ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus']
                .find((type) => MediaRecorder.isTypeSupported(type)) ?? '';
        },

        async start() {
            // Ignore a second tap while we're already starting or recording, so a
            // quick double-press can't spin up two recorders at once.
            if (this.starting || this.state === 'recording') return;
            this.starting = true;
            this.error = null;
            this.cancelled = false;
            this.tooShort = false;

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch {
                this.starting = false;
                this.state = 'error';
                this.error = 'denied';
                return;
            }

            const type = this.mimeType();
            this.chunks = [];
            this.recorder = new MediaRecorder(this.stream, type ? { mimeType: type } : {});
            this.recorder.ondataavailable = (e) => {
                if (e.data.size > 0) this.chunks.push(e.data);
            };
            this.recorder.onstop = () => this.finish();
            this.recorder.start(1000);

            this.seconds = 0;
            this.state = 'recording';
            this.starting = false;
            this.timer = setInterval(() => {
                this.seconds++;
                // Hard cap: stop on its own at the 3-minute mark.
                if (this.seconds >= this.maxSeconds) this.stop();
            }, 1000);
        },

        stop() {
            clearInterval(this.timer);
            // Too brief to be worth transcribing — remember it so finish() bails
            // out instead of uploading a near-empty clip.
            this.tooShort = this.state === 'recording' && this.seconds < this.minSeconds;
            if (this.recorder && this.recorder.state !== 'inactive') {
                this.recorder.stop();
            }
            this.stopTracks();
        },

        stopTracks() {
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
        },

        finish() {
            if (this.cancelled) {
                this.cancelled = false;
                return;
            }

            // A quick stop never reaches the transcriber — we ask for a redo.
            if (this.tooShort) {
                this.tooShort = false;
                this.chunks = [];
                this.seconds = 0;
                this.state = 'error';
                this.error = 'short';
                return;
            }

            const type = this.recorder?.mimeType || 'audio/webm';
            const ext = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
            const blob = new Blob(this.chunks, { type });
            const file = new File([blob], `voice-memo.${ext}`, { type });

            this.progress = 0;
            this.state = 'uploading';

            this.$wire.upload(
                'audio',
                file,
                // Upload finished — hand it straight off to be written and send
                // the gardener to the live processing page. No extra tap needed.
                () => {
                    this.state = 'submitting';
                    this.$wire.submit().then(() => {
                        // Still here after the round-trip means it didn't redirect
                        // (rate limit, oversize file…). Drop back so they can retry;
                        // the server's error message shows below.
                        if (this.state === 'submitting') this.discard();
                    });
                },
                () => { this.state = 'error'; this.error = 'upload'; },
                (event) => { this.progress = event.detail.progress; },
            );
        },

        discard() {
            this.cancelled = true;
            this.stop();
            if (this.previewUrl) {
                URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
            }
            this.chunks = [];
            this.seconds = 0;
            this.error = null;
            this.state = 'idle';
            this.$wire.set('audio', null);
        },
    }));
});
