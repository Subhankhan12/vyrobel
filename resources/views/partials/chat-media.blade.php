{{-- VOICE NOTES, SHARED (2026-10-06) — the one recorder and the one player, used by team chat and by the Inbox's replies to
     guests and owners, so the two can never record or play differently.

     `window.VyRecorder.start({ limit, onTick })` asks for the microphone (allowed on the two Messages pages only —
     SecurityHeaders) and records: Opus in WebM where the browser can (Chrome, Firefox), MP4 where it cannot (Safari). It
     resolves to a controller — `stop()`, `cancel()`, and `finished` (the recording, or null when cancelled or empty) — or
     throws an Error whose message is the sentence to show. `Alpine.data('vyVoice', file)` is a voice note's player: one
     plays at a time; its length comes from the server when the recording carries none (a WebM from MediaRecorder does not).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@once
<script>
    (() => {
        if (window.VyRecorder) return;
        const TYPES = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/webm', 'audio/ogg;codecs=opus'];

        window.VyRecorder = {
            supported() { return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder); },
            async start({ limit, onTick }) {
                if (!this.supported()) throw new Error('This browser cannot record voice notes.');
                let stream;
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                } catch (e) {
                    throw new Error(e && e.name === 'NotAllowedError' ? 'Allow the microphone for this site to record a voice note.'
                        : (e && e.name === 'NotFoundError' ? 'No microphone was found.' : 'The microphone could not be started.'));
                }
                const mime = TYPES.find((t) => window.MediaRecorder.isTypeSupported && window.MediaRecorder.isTypeSupported(t)) || '';
                const recorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
                const chunks = [];
                let seconds = 0, cancelled = false, timer = null;
                const finished = new Promise((resolve) => {
                    recorder.ondataavailable = (e) => { if (e.data && e.data.size) chunks.push(e.data); };
                    recorder.onstop = () => {
                        clearInterval(timer);
                        stream.getTracks().forEach((t) => t.stop());
                        if (cancelled) { resolve(null); return; }
                        const type = recorder.mimeType || mime || 'audio/webm';
                        const blob = new Blob(chunks, { type });
                        resolve(blob.size ? { blob, mime: type, seconds: Math.max(1, seconds) } : null);
                    };
                });
                const stop = () => { if (recorder.state !== 'inactive') recorder.stop(); return finished; };
                timer = setInterval(() => {
                    seconds++;
                    if (onTick) onTick(seconds);
                    if (seconds >= limit) stop();
                }, 1000);
                recorder.start(250);
                return { stop, finished, cancel() { cancelled = true; stop(); } };
            },
            /** The file name a recording is sent under, by its type. */
            fileName(mime) { return 'voice.' + (String(mime).includes('mp4') ? 'm4a' : (String(mime).includes('ogg') ? 'ogg' : 'webm')); },
        };

        const registerPlayer = () => {
            window.Alpine.data('vyVoice', (file) => ({
                playing: false,
                at: 0,
                length: (file && file.seconds) || 0,
                audio: null,
                init() {
                    document.addEventListener('vy:voice', (e) => { if (e.detail !== this && this.audio && this.playing) this.audio.pause(); });
                },
                get pct() { return this.length ? Math.min(100, (this.at / this.length) * 100) : 0; },
                fmt(seconds) {
                    const s = Math.round(seconds || 0);
                    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
                },
                clock() { return this.fmt(this.playing || this.at ? this.at : this.length); },
                /** What a screen reader says for the slider: "0:12 of 0:40". */
                spoken() { return this.fmt(this.at) + ' of ' + this.fmt(this.length); },
                ensure() {
                    if (this.audio) return this.audio;
                    const a = new Audio(file.url);
                    a.preload = 'metadata';
                    a.addEventListener('loadedmetadata', () => { if (isFinite(a.duration) && a.duration > 0) this.length = a.duration; });
                    a.addEventListener('timeupdate', () => { this.at = a.currentTime; });
                    a.addEventListener('play', () => { this.playing = true; });
                    a.addEventListener('pause', () => { this.playing = false; });
                    a.addEventListener('ended', () => { this.playing = false; this.at = 0; });
                    this.audio = a;
                    return a;
                },
                toggle() {
                    const a = this.ensure();
                    if (this.playing) { a.pause(); return; }
                    document.dispatchEvent(new CustomEvent('vy:voice', { detail: this }));
                    a.play().catch(() => {});
                },
                seek(e) {
                    if (!this.length) return;
                    const r = e.currentTarget.getBoundingClientRect();
                    const a = this.ensure();
                    a.currentTime = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)) * this.length;
                    this.at = a.currentTime;
                },
                /** The slider by keyboard: ←/→ (and ↓/↑) move 5 seconds, Page Down/Up 15, Home/End the ends. */
                key(e) {
                    const step = { ArrowRight: 5, ArrowUp: 5, ArrowLeft: -5, ArrowDown: -5, PageUp: 15, PageDown: -15,
                        Home: -Infinity, End: Infinity }[e.key];
                    if (step === undefined || !this.length) return;
                    e.preventDefault();
                    const a = this.ensure();
                    a.currentTime = Math.max(0, Math.min(this.length, this.at + step));
                    this.at = a.currentTime;
                },
            }));
        };
        if (window.Alpine) registerPlayer(); else document.addEventListener('alpine:init', registerPlayer);
    })();
</script>
@endonce
