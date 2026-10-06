{{-- THE PULSE (team chat, 2026-10-06 — `docs/TEAM-CHAT-DESIGN.md`). Every back-office page asks `app.pulse` for its live
     counts — unread notifications, unread team messages, guest and owner conversations waiting (for who may read them) —
     and keeps the bell's badge, the Messages badge, the Guests | Team counts and the tab title true without a reload.
     When a count rises it plays a short chime: synthesised here (Web Audio — no sound file), on unless switched off
     (`VyLive.setSound()` — the switch in team chat), and only after the first click or key on the page (browsers refuse
     sound before that). It never chimes for what was already there when the page loaded.

     ⚠️ Every 20 seconds while the tab is visible, every minute when hidden, and NOT AT ALL after 30 minutes without a key
     or a click — an open tab must not keep a sign-in alive for ever; the next key or click starts it again. Signed out
     (401, 419, or sent to another page — the sign-in, the paywall) stops it: polling a redirect helps nobody. A busy or
     failing server (429, 5xx, no connection) only slows it, doubling the wait up to five minutes (the review, 2026-10-06).

     `window.VyLive` is shared with the pages: team chat tells it what it has already seen (`observe()`), so the pulse
     does not chime a second time for the same message. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@auth
@once
<script>
(() => {
    if (window.VyLive) return;
    const PULSE = @js(route('app.pulse'));
    const SOUND_KEY = 'vy.sound';
    const VISIBLE_MS = 20000, HIDDEN_MS = 60000, IDLE_MS = 30 * 60 * 1000;
    const baseTitle = document.title.replace(/^\(\d+\+?\)\s+/, '');
    let last = null, timer = null, busy = false, stopped = false, lastActive = Date.now(), lastChime = 0, ctx = null, gesture = false;
    let fails = 0, bellTimer = null;

    const store = {
        get(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
        set(k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* private window — the switch lasts this page */ } },
    };

    function audio() {
        if (!ctx) {
            const C = window.AudioContext || window.webkitAudioContext;
            if (!C) return null;
            ctx = new C();
        }
        if (ctx.state === 'suspended') ctx.resume().catch(() => {});
        return ctx;
    }

    function tone(c, freq, start, length, peak) {
        const osc = c.createOscillator(), gain = c.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, start);
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(peak, start + 0.012);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
        osc.connect(gain).connect(c.destination);
        osc.start(start);
        osc.stop(start + length + 0.03);
    }

    function setCount(el, n) {
        el.textContent = n > 99 ? '99+' : String(n);
        el.hidden = n <= 0;
    }

    function render(c) {
        const messages = (c.team || 0) + (c.guests || 0);
        document.querySelectorAll('[data-vy-badge="messages"]').forEach((el) => setCount(el, messages));
        document.querySelectorAll('[data-vy-badge="team"]').forEach((el) => setCount(el, c.team || 0));
        document.querySelectorAll('[data-vy-badge="guests"]').forEach((el) => setCount(el, c.guests || 0));
        document.querySelectorAll('[data-vy-bell-badge]').forEach((el) => {
            const n = c.notifications || 0;
            el.textContent = n > 9 ? '9+' : (n > 0 ? String(n) : '');
            el.style.display = n > 0 ? 'flex' : 'none';
            el.setAttribute('aria-label', n + ' unread');
        });
        document.title = (messages > 0 ? '(' + (messages > 99 ? '99+' : messages) + ') ' : '') + baseTitle;
    }

    /** JSON, or null: signed out stops the pulse; anything else that failed counts towards the slower wait. */
    async function getJson(url) {
        const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' });
        if (res.redirected || res.status === 401 || res.status === 419) {
            stopped = true;   // signed out, or the workspace is behind the paywall — nothing to poll
            return null;
        }
        if (!res.ok || !(res.headers.get('content-type') || '').includes('application/json')) {
            fails++;
            return null;
        }
        fails = 0;
        return res.json();
    }

    /** The bell's own list, re-drawn once a burst of changes settles (a chat can move the count twice in a second). */
    function refreshBellSoon() {
        clearTimeout(bellTimer);
        bellTimer = setTimeout(refreshBell, 1500);
    }

    async function refreshBell() {
        const bell = document.querySelector('[data-vy-bell]');
        if (!bell) return;
        if (window.Alpine && bell._x_dataStack && window.Alpine.$data(bell).open) return;   // never under an open panel
        const data = await getJson(PULSE + '?bell=1').catch(() => null);
        if (!data || typeof data.bell !== 'string') return;
        const t = document.createElement('template');
        t.innerHTML = data.bell.trim();
        const fresh = t.content.firstElementChild;
        if (fresh && fresh.matches('[data-vy-bell]')) bell.replaceWith(fresh);
    }

    const VyLive = {
        soundOn() { return store.get(SOUND_KEY) !== 'off'; },
        setSound(on) {
            store.set(SOUND_KEY, on ? 'on' : 'off');
            document.dispatchEvent(new CustomEvent('vy:sound', { detail: { on } }));
            if (on) this.chime(true);   // a sample, so the switch is heard
        },
        chime(sample) {
            if (!sample && (!this.soundOn() || !gesture)) return;
            const now = Date.now();
            if (!sample && now - lastChime < 2500) return;
            lastChime = now;
            const c = audio();
            if (!c) return;
            const t = c.currentTime + 0.01;
            tone(c, 784, t, 0.16, 0.07);
            tone(c, 1174.7, t + 0.11, 0.30, 0.055);
        },
        /** A page that has already seen these counts (and chimed for them) tells the pulse, so it stays quiet. */
        observe(counts) {
            last = Object.assign({}, last || {}, counts);
            render(last);
        },
        counts() { return last; },
        /** Ask now — a page that has just changed what the counts read (the Inbox after it refreshed itself). */
        refresh() { tick(); },
        refreshBell: refreshBellSoon,
    };
    window.VyLive = VyLive;

    async function tick() {
        if (busy || stopped) return;
        busy = true;
        try {
            const c = await getJson(PULSE);
            if (!c) return;
            const before = last;
            last = c;
            render(c);
            document.dispatchEvent(new CustomEvent('vy:pulse', { detail: c }));
            if (before) {
                const rose = (c.team || 0) > (before.team || 0) || (c.notifications || 0) > (before.notifications || 0) || (c.guests || 0) > (before.guests || 0);
                if (rose) VyLive.chime();
                if ((c.notifications || 0) !== (before.notifications || 0)) refreshBellSoon();
            }
        } catch (e) {
            fails++;   // offline for a moment — the next tick tries again, a little later
        } finally {
            busy = false;
        }
    }

    function schedule() {
        clearTimeout(timer);
        if (stopped || Date.now() - lastActive > IDLE_MS) return;   // idle: the next key or click starts it again
        const wait = (document.hidden ? HIDDEN_MS : VISIBLE_MS) * Math.pow(2, Math.min(fails, 4));
        timer = setTimeout(() => { tick().finally(schedule); }, Math.min(wait, 5 * 60 * 1000));
    }

    function activity() {
        const wasIdle = Date.now() - lastActive > IDLE_MS;
        lastActive = Date.now();
        if (!gesture) { gesture = true; audio(); }   // the first click or key unlocks sound
        if (wasIdle) { tick().finally(schedule); }
    }

    ['pointerdown', 'keydown'].forEach((type) => window.addEventListener(type, activity, { passive: true }));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { tick().finally(schedule); } });
    tick().finally(schedule);   // the first reading is the baseline: it never chimes
})();
</script>
@endonce
@endauth
