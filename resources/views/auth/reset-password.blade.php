<!DOCTYPE html>
{{-- Reset password (Auth Phase B · finishing sweep) — styled from "Vyrobel Reset Password" onto the
     shared Vyrobel auth tokens, consistent with login + set-password. The broker's tokened link is the
     credential; the password is hashed server-side and other sessions are signed out. The strength meter
     is presentational only — the server enforces the real minimum. --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a new password · Vyrobel</title>
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        :root {
            --vy-ink: rgb(43, 35, 32); --vy-muted: rgb(95, 88, 83); --vy-faint: rgb(108, 100, 95);
            --vy-accent: rgb(194, 65, 12); --vy-accent-3: rgb(232, 112, 58); --vy-amber: rgb(178, 106, 8);
            --vy-glass: rgba(255, 252, 248, 0.62); --vy-hairline: rgba(120, 70, 40, 0.10); --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-grad: linear-gradient(140deg, #E8703A, #C2410C);
            --vy-shadow: rgba(150, 90, 50, 0.18) 0 22px 60px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;
            --vy-r-lg: 22px; --vy-r-md: 12px; --vy-r-sm: 10px;
            --vy-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --vy-display: 'Sora', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--vy-sans); color: var(--vy-ink); font-size: 16px; line-height: 1.45;
            -webkit-font-smoothing: antialiased; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: radial-gradient(circle at 80% 6%, rgba(255,255,255,.4), transparent 62%),
                radial-gradient(circle at 12% 92%, rgba(255,224,200,.45), transparent 58%),
                linear-gradient(120deg, rgba(255,240,220,.5), transparent 55%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%);
            background-attachment: fixed;
        }
        @keyframes vyDrift { 0% { transform: translate(0,0);} 50% { transform: translate(40px,26px);} 100% { transform: translate(0,0);} }
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px);} to { opacity: 1; transform: translateY(0);} }
        body::before { content:""; position: fixed; inset: -18% -8% auto auto; width: 46vw; height: 46vw; background: radial-gradient(circle, rgba(255,205,165,.55), transparent 70%); filter: blur(12px); z-index: 0; pointer-events: none; animation: vyDrift 26s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; } }
        .vy-card { position: relative; z-index: 1; width: 100%; max-width: 410px; background: var(--vy-glass); backdrop-filter: blur(24px) saturate(1.18); -webkit-backdrop-filter: blur(24px) saturate(1.18); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 34px 32px; animation: vyFadeUp .6s cubic-bezier(.2,.7,.2,1) both; }
        .vy-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 22px; }
        .vy-brand-mark { width: 40px; height: 40px; border-radius: var(--vy-r-md); background: var(--vy-grad); color:#fff; font-family: var(--vy-display); font-weight: 700; font-size: 21px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 18px; letter-spacing: -0.01em; }
        .vy-title { font-family: var(--vy-display); font-weight: 700; font-size: 24px; letter-spacing: -0.02em; margin: 0; }
        .vy-sub { color: var(--vy-muted); font-size: 13.5px; margin: 6px 0 18px; }
        /* Identity pill — whose password is being reset */
        .vy-idpill { display: flex; align-items: center; gap: 11px; padding: 11px 14px; margin-bottom: 20px; border-radius: var(--vy-r-md); background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); }
        .vy-idpill .vy-ava { width: 32px; height: 32px; border-radius: 50%; background: var(--vy-grad); color:#fff; font-size: 12px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vy-idpill .vy-id-k { font-size: 11px; color: var(--vy-muted); }
        .vy-idpill .vy-id-v { font-size: 13.5px; font-weight: 600; }
        .vy-field { margin-bottom: 15px; }
        .vy-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; }
        .vy-input-wrap { position: relative; }
        .vy-input { width: 100%; padding: 12px 14px; padding-right: 44px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.8); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); transition: border-color .15s ease, box-shadow .15s ease; }
        .vy-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232,112,58,.16); }
        .vy-eye { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 30px; height: 30px; display: grid; place-items: center; border: none; background: transparent; color: var(--vy-muted); cursor: pointer; }
        .vy-hint { font-size: 11px; color: var(--vy-faint); margin-top: 6px; }
        /* Strength meter (presentational) */
        .vy-meter { display: flex; gap: 5px; margin-top: 9px; }
        .vy-meter span { height: 4px; flex: 1; border-radius: 3px; background: rgba(120,70,40,.14); transition: background .2s ease; }
        .vy-meter.s1 span:nth-child(1) { background: #D9663A; }
        .vy-meter.s2 span:nth-child(-n+2) { background: #D9663A; }
        .vy-meter.s3 span:nth-child(-n+3) { background: var(--vy-accent); }
        .vy-meter.s4 span { background: var(--vy-accent); }
        .vy-strength { display: flex; justify-content: space-between; font-size: 11px; margin-top: 6px; color: var(--vy-faint); }
        .vy-strength b { color: var(--vy-accent); font-weight: 600; }
        .vy-error { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--vy-r-md); background: rgba(224,146,30,.12); border: 1px solid rgba(224,146,30,.4); border-left: 3px solid var(--vy-amber); font-size: 13px; font-weight: 500; }
        .vy-error svg { color: var(--vy-amber); flex: none; }
        .vy-btn { width: 100%; margin-top: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 30px; font-family: var(--vy-sans); font-size: 14px; font-weight: 600; color:#fff; background: var(--vy-grad); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); transition: transform .15s ease; }
        .vy-btn:hover { transform: translateY(-1px); }
        .vy-btn:focus-visible, .vy-input:focus-visible, .vy-eye:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-foot { text-align: center; color: var(--vy-faint); font-size: 11.5px; margin-top: 18px; }
    </style>
    <x-vy-scrollbar />
</head>
<body>
    <main class="vy-card">
        <div class="vy-brand"><span class="vy-brand-mark">V</span><span class="vy-brand-name">Vyrobel</span></div>
        <h1 class="vy-title">Choose a new password</h1>
        <p class="vy-sub">Pick a strong password — you’ll use it to sign in from now on.</p>

        @if ($email !== '')
            @php
                $parts = preg_split('/[@.]/', $email) ?: [];
                $initials = strtoupper(mb_substr($parts[0] ?? 'V', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
            @endphp
            <div class="vy-idpill">
                <span class="vy-ava">{{ $initials }}</span>
                <span><span class="vy-id-k">Setting a new password for</span><br><span class="vy-id-v">{{ $email }}</span></span>
            </div>
        @endif

        @if ($errors->any())
            <div class="vy-error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="vy-field">
                <label class="vy-label" for="password">New password</label>
                <div class="vy-input-wrap">
                    <input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" autofocus required>
                    <button type="button" class="vy-eye" data-toggle="password" aria-label="Show password">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="vy-meter" id="meter"><span></span><span></span><span></span><span></span></div>
                <div class="vy-strength"><span>{{ \App\Support\Auth\PasswordPolicy::hint() }}</span><b id="strength"></b></div>
            </div>

            <div class="vy-field">
                <label class="vy-label" for="password_confirmation">Confirm password</label>
                <div class="vy-input-wrap">
                    <input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    <button type="button" class="vy-eye" data-toggle="password_confirmation" aria-label="Show password">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="vy-btn">Set password &amp; sign in</button>
        </form>

        <div class="vy-foot">Signs you out of other devices for safety.</div>
    </main>

    <script>
        document.querySelectorAll('[data-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-toggle'));
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });
        // Presentational strength hint only — the server enforces the real minimum.
        var pw = document.getElementById('password'), meter = document.getElementById('meter'), label = document.getElementById('strength');
        var words = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        pw && pw.addEventListener('input', function () {
            var v = pw.value, s = 0;
            if (v.length >= 8) s++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
            if (/[0-9]/.test(v) || /[^A-Za-z0-9]/.test(v)) s++;
            if (v.length >= 12) s++;
            meter.className = 'vy-meter' + (v ? ' s' + s : '');
            label.textContent = v ? words[s] : '';
        });
    </script>
</body>
</html>
