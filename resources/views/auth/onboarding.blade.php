<!DOCTYPE html>
{{-- Vyrobel onboarding / self-signup (Phase 7a) — the PUBLIC pre-auth wizard. One form, stepped with tiny
     vanilla JS (no dependency; degrades to a full form if JS is off). Submitting provisions a brand-new org
     atomically via TenantProvisioningService and logs the founder in. Real signup, not a stub. --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create your account · Vyrobel</title>
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        :root {
            --vy-ink: rgb(43, 35, 32); --vy-muted: rgb(95, 88, 83); --vy-faint: rgb(108, 100, 95);
            --vy-accent: rgb(194, 65, 12); --vy-accent-3: rgb(232, 112, 58); --vy-amber: rgb(178, 106, 8);
            --vy-glass: rgba(255, 252, 248, 0.62); --vy-hairline: rgba(120, 70, 40, 0.10);
            --vy-glass-border: rgba(255, 255, 255, 0.75); --vy-grad: linear-gradient(140deg, #E8703A, #C2410C);
            --vy-shadow: rgba(150, 90, 50, 0.18) 0 22px 60px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;
            --vy-r-lg: 22px; --vy-r-md: 12px; --vy-r-sm: 10px;
            --vy-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --vy-display: 'Sora', 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--vy-sans); color: var(--vy-ink); font-size: 16px; line-height: 1.45;
            -webkit-font-smoothing: antialiased; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: radial-gradient(circle at 80% 6%, rgba(255,255,255,.4), transparent 62%),
                radial-gradient(circle at 12% 92%, rgba(255,224,200,.45), transparent 58%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%); background-attachment: fixed;
        }
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        /* Polish Batch 4 — match the sibling auth screens (drift blob + button lift). */
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(40px, 24px); } 100% { transform: translate(0, 0); } }
        body::before { content: ""; position: fixed; inset: -20% -10% auto auto; width: 44vw; height: 44vw; background: radial-gradient(circle, rgba(255, 210, 170, .45), transparent 70%); filter: blur(10px); z-index: 0; pointer-events: none; animation: vyDrift 28s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; transition: none !important; } }
        .vy-card { position: relative; z-index: 1; width: 100%; max-width: 460px; background: var(--vy-glass);
            backdrop-filter: blur(24px) saturate(1.18); -webkit-backdrop-filter: blur(24px) saturate(1.18);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow);
            padding: 34px 32px; animation: vyFadeUp .6s cubic-bezier(.2,.7,.2,1) both; }
        .vy-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 18px; }
        .vy-brand-mark { width: 40px; height: 40px; border-radius: var(--vy-r-md); background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 21px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 18px; }
        .vy-title { font-family: var(--vy-display); font-weight: 700; font-size: 24px; letter-spacing: -0.02em; margin: 0; }
        .vy-sub { color: var(--vy-muted); font-size: 13.5px; margin: 6px 0 18px; }
        .vy-steps { display: flex; gap: 6px; margin-bottom: 20px; }
        .vy-steps span { flex: 1; height: 4px; border-radius: 3px; background: var(--vy-hairline); transition: background .2s; }
        .vy-steps span.on { background: var(--vy-grad); }
        .vy-field { margin-bottom: 14px; }
        .vy-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; }
        .vy-input { width: 100%; padding: 12px 14px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.8); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); }
        .vy-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232,112,58,.16); }
        .vy-two { display: flex; gap: 10px; } .vy-two > .vy-field { flex: 1; }
        /* Strength meter (presentational) — same treatment as reset/set-password (D14 auth uniformity). A VISUAL
           AID only: the floor reflects the real server rule (Password::min(8)); higher bars are advice, never a gate. */
        .vy-meter { display: flex; gap: 5px; margin-top: 2px; margin-bottom: 14px; }
        .vy-meter span { height: 4px; flex: 1; border-radius: 3px; background: rgba(120,70,40,.14); transition: background .2s ease; }
        .vy-meter.s1 span:nth-child(1) { background: #D9663A; }
        .vy-meter.s2 span:nth-child(-n+2) { background: #D9663A; }
        .vy-meter.s3 span:nth-child(-n+3) { background: var(--vy-accent); }
        .vy-meter.s4 span { background: var(--vy-accent); }
        .vy-strength { display: flex; justify-content: space-between; font-size: 11px; margin: -8px 0 14px; color: var(--vy-faint); }
        .vy-strength b { color: var(--vy-accent); font-weight: 600; }
        .vy-error { display: flex; align-items: center; gap: 9px; margin-bottom: 16px; padding: 11px 14px; border-radius: var(--vy-r-md); background: rgba(224,146,30,.12); border: 1px solid rgba(224,146,30,.4); border-left: 3px solid var(--vy-amber); font-size: 13px; font-weight: 500; }
        .vy-error svg { color: var(--vy-amber); flex: none; }
        .vy-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 30px; font-family: var(--vy-sans); font-size: 14px; font-weight: 600; color: #fff; background: var(--vy-grad); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); transition: transform .15s ease; }
        .vy-btn:hover { transform: translateY(-1px); }
        .vy-btn--ghost { background: rgba(255,255,255,.7); color: var(--vy-ink); border: 1px solid var(--vy-glass-border); box-shadow: none; }
        .vy-actions { display: flex; gap: 10px; margin-top: 8px; } .vy-actions .vy-btn { flex: 1; }
        .vy-note { font-size: 12px; color: var(--vy-muted); margin-top: 12px; }
        .vy-soon-tag { font-size: 8.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 1px 5px; }
        .vy-foot { text-align: center; color: var(--vy-faint); font-size: 11.5px; margin-top: 18px; }
        .vy-foot a { color: var(--vy-accent); text-decoration: none; font-weight: 500; }
        [data-step] { display: none; } [data-step].on { display: block; }
        /* Polish P3 (I4) — step cross-fade: when a step becomes active its content fades up, staggered,
           matching the prototype's exact step progression (vyFadeUp ~0.45s, staggered ~60ms). CSS-only,
           driven purely by the `.on` class toggle — the wizard's step LOGIC/validation/provisioning is
           unchanged. Re-fires each activation (display:none → block re-applies the animation). */
        [data-step].on > * { animation: vyFadeUp .45s cubic-bezier(.2,.7,.2,1) both; }
        [data-step].on > *:nth-child(2) { animation-delay: .06s; }
        [data-step].on > *:nth-child(3) { animation-delay: .12s; }
        [data-step].on > *:nth-child(4) { animation-delay: .18s; }
        [data-step].on > *:nth-child(n+5) { animation-delay: .22s; }
        .no-js [data-step] { display: block; } .no-js .vy-steps, .no-js [data-nav] { display: none; }
        /* No-JS: all steps shown at once — no per-step animation (nothing toggles `.on`). */
        .no-js [data-step] > * { animation: none; }
    </style>
    <x-vy-scrollbar />
</head>
<body class="no-js">
    <main class="vy-card">
        <div class="vy-brand"><span class="vy-brand-mark">V</span><span class="vy-brand-name">Vyrobel</span></div>
        <h1 class="vy-title">Create your account</h1>
        <p class="vy-sub">Set up your property business — you'll be running it in a couple of minutes.</p>

        {{-- ⚠️ ADMIN 01 · PHASE 1 — SIGN-UPS PAUSED BY A PLATFORM SWITCH. Said plainly on a normal 200 page: no
             form is rendered, so nothing can be typed in and then silently discarded. The submit is refused
             server-side as well (OnboardingController::store), so an old form cannot slip through either. --}}
        @if ($signupsPaused ?? false)
            <div class="vy-error" role="status" style="background: rgba(224, 146, 30, 0.12);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/></svg>
                New sign-ups are paused for the moment. If you already have an account, you can still sign in.
            </div>
        @else
        <div class="vy-steps" aria-hidden="true"><span class="on" data-dot="0"></span><span data-dot="1"></span><span data-dot="2"></span></div>

        @if ($errors->any())
            <div class="vy-error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('signup') }}" id="signup" novalidate>
            @csrf

            {{-- STEP 1 — the business --}}
            <section data-step="0" class="on">
                <div class="vy-field">
                    <label class="vy-label" for="org_name">Business name</label>
                    <input class="vy-input" id="org_name" name="org_name" value="{{ old('org_name') }}" placeholder="e.g. Seaside Group" required>
                </div>
                <div class="vy-field">
                    <label class="vy-label" for="location">Location <span style="text-transform:none;letter-spacing:0;color:var(--vy-faint)">· optional</span></label>
                    <input class="vy-input" id="location" name="location" value="{{ old('location') }}" placeholder="e.g. Faro, Portugal">
                </div>
                <div class="vy-two">
                    <div class="vy-field">
                        <label class="vy-label" for="property_type">Type</label>
                        <select class="vy-input" id="property_type" name="property_type">
                            <option value="villa" @selected(old('property_type') === 'villa')>Villas &amp; apartments</option>
                            <option value="hotel" @selected(old('property_type') === 'hotel')>Hotel</option>
                            <option value="hostel" @selected(old('property_type') === 'hostel')>Hostel</option>
                        </select>
                    </div>
                    <div class="vy-field">
                        <label class="vy-label" for="currency">Currency</label>
                        <select class="vy-input" id="currency" name="currency">
                            @foreach ($currencies as $c)
                                <option value="{{ $c }}" @selected(old('currency', 'EUR') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="vy-actions" data-nav><button type="button" class="vy-btn" data-next>Continue</button></div>
            </section>

            {{-- STEP 2 — the founder account --}}
            <section data-step="1">
                <div class="vy-field">
                    <label class="vy-label" for="name">Your name</label>
                    <input class="vy-input" id="name" name="name" value="{{ old('name', $social['name'] ?? '') }}" placeholder="e.g. Ada Okafor" autocomplete="name" required>
                </div>
                <div class="vy-field">
                    <label class="vy-label" for="email">Email</label>
                    <input class="vy-input" type="email" id="email" name="email" value="{{ old('email', $social['email'] ?? '') }}" autocomplete="username" @if ($social ?? null) readonly @endif required>
                    @if ($social ?? null)
                        {{-- Readonly because the server ignores a posted value here anyway and uses what Google
                             asserted — an editable field would look accepted and silently not be. --}}
                        <p style="font-size:11.5px;color:var(--vy-muted);margin-top:6px;">From your {{ \App\Support\Auth\SocialProviders::label($social['provider'] ?? 'google') }} account.</p>
                    @endif
                </div>
                @if ($social ?? null)
                    {{-- ⚠️ NO PASSWORD FIELD. This account signs in with Google, and asking for a password at
                         the very moment the person chose not to use one is the thing that makes "sign up with
                         Google" read as broken. One is asked for later — once, when a sensitive action first
                         needs confirming (docs/PASSWORDLESS-GATE.md). --}}
                    <p class="vy-strength" style="margin-top:2px;"><span>You’ll sign in with {{ \App\Support\Auth\SocialProviders::label($social['provider'] ?? 'google') }}. No password needed.</span></p>
                @else
                <div class="vy-two">
                    <div class="vy-field">
                        <label class="vy-label" for="password">Password</label>
                        <input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" required>
                    </div>
                    <div class="vy-field">
                        <label class="vy-label" for="password_confirmation">Confirm</label>
                        <input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                </div>
                {{-- Presentational strength meter (D14) — a VISUAL AID; the server enforces Password::min(8). --}}
                <div class="vy-meter" id="meter"><span></span><span></span><span></span><span></span></div>
                <div class="vy-strength"><span>{{ \App\Support\Auth\PasswordPolicy::hint() }}</span><b id="strength"></b></div>
                @endif
                <div class="vy-actions" data-nav>
                    <button type="button" class="vy-btn vy-btn--ghost" data-back>Back</button>
                    <button type="button" class="vy-btn" data-next>Continue</button>
                </div>
            </section>

            {{-- STEP 3 — the first property --}}
            <section data-step="2">
                <div class="vy-field">
                    <label class="vy-label" for="property_name">Your first property</label>
                    <input class="vy-input" id="property_name" name="property_name" value="{{ old('property_name') }}" placeholder="e.g. Seaside Villas" required>
                </div>
                <p class="vy-note">We'll create your organization + this property and sign you in. You'll add a room type, a unit and a rate plan next — connecting channels is optional (<span class="vy-soon-tag">Soon</span>), finish without them anytime.</p>
                <div class="vy-actions" data-nav>
                    <button type="button" class="vy-btn vy-btn--ghost" data-back>Back</button>
                    <button type="submit" class="vy-btn">Create my account</button>
                </div>
                {{-- No-JS fallback: a single submit is always available. --}}
                <noscript><button type="submit" class="vy-btn" style="width:100%;margin-top:10px">Create my account</button></noscript>
            </section>
        </form>
        @endif

        <div class="vy-foot">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
    </main>

    @unless ($signupsPaused ?? false)
    <script>
        (function () {
            document.body.classList.remove('no-js');
            var steps = Array.prototype.slice.call(document.querySelectorAll('[data-step]'));
            var dots = Array.prototype.slice.call(document.querySelectorAll('[data-dot]'));
            var i = 0;
            function show(n) {
                i = Math.max(0, Math.min(steps.length - 1, n));
                steps.forEach(function (s, k) { s.classList.toggle('on', k === i); });
                dots.forEach(function (d, k) { d.classList.toggle('on', k <= i); });
                var first = steps[i].querySelector('input, select'); if (first) first.focus();
            }
            document.querySelectorAll('[data-next]').forEach(function (b) {
                b.addEventListener('click', function () {
                    // Native validity check for the current step's required fields before advancing.
                    var ok = true;
                    steps[i].querySelectorAll('[required]').forEach(function (f) { if (!f.reportValidity()) ok = false; });
                    if (ok) show(i + 1);
                });
            });
            document.querySelectorAll('[data-back]').forEach(function (b) {
                b.addEventListener('click', function () { show(i - 1); });
            });

            // Polish Batch 4 · GATE 1 — double-submit guard (this page has no Alpine, so a vanilla one).
            // Provisioning an org is SLOW + state-changing; a second submit before navigation is blocked → fires ONCE.
            // Front-line UX guard ON TOP of the server's rate-limit + atomic provisioning (not a replacement).
            var form = document.getElementById('signup');
            if (form) {
                form.addEventListener('submit', function (e) {
                    if (form.dataset.submitting) { e.preventDefault(); return; }
                    form.dataset.submitting = '1';
                    var btn = form.querySelector('button[type="submit"]');
                    if (btn) { btn.disabled = true; btn.textContent = 'Creating your account…'; }
                });
            }

            // Polish P3 (D14) — presentational strength meter. A VISUAL AID only: the server enforces the real
            // minimum (Password::min(8)); this reflects the floor (8 chars) + advises on strength. Never a gate.
            var pw = document.getElementById('password'), meter = document.getElementById('meter'), sLabel = document.getElementById('strength');
            var words = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            pw && meter && pw.addEventListener('input', function () {
                var v = pw.value, s = 0;
                if (v.length >= 8) s++;
                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
                if (/[0-9]/.test(v) || /[^A-Za-z0-9]/.test(v)) s++;
                if (v.length >= 12) s++;
                meter.className = 'vy-meter' + (v ? ' s' + s : '');
                sLabel.textContent = v ? words[s] : '';
            });

            show(0);
        })();
    </script>
    @endunless
</body>
</html>
