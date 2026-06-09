// Livewire's JS (and Alpine) is injected automatically via @livewireScripts.
// App-level JS sprinkles live here.

// In-page voice recorder for the upload form. Records with MediaRecorder,
// then pushes the finished clip into the Livewire `audio` upload property —
// from there it flows through the exact same pipeline as an uploaded file.
document.addEventListener('alpine:init', () => {
    Alpine.data('voiceRecorder', () => ({
        // idle | recording | uploading | attached | error | unsupported
        state: 'idle',
        error: null, // 'denied' | 'upload'
        seconds: 0,
        progress: 0,
        previewUrl: null,
        recorder: null,
        stream: null,
        chunks: [],
        timer: null,
        cancelled: false,
        maxSeconds: 3600,

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
            this.error = null;
            this.cancelled = false;

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch {
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
            this.timer = setInterval(() => {
                this.seconds++;
                if (this.seconds >= this.maxSeconds) this.stop();
            }, 1000);
        },

        stop() {
            clearInterval(this.timer);
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

            const type = this.recorder?.mimeType || 'audio/webm';
            const ext = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
            const blob = new Blob(this.chunks, { type });
            const file = new File([blob], `voice-memo.${ext}`, { type });

            this.previewUrl = URL.createObjectURL(blob);
            this.progress = 0;
            this.state = 'uploading';

            this.$wire.upload(
                'audio',
                file,
                () => { this.state = 'attached'; },
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
