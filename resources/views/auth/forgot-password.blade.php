<!DOCTYPE html>
{{-- Forgot password (Auth Phase B · finishing sweep) — styled on the shared Vyrobel auth tokens,
     consistent with login + set-password. Enter an email → a reset link is sent (ACTIVE users only);
     the response is ALWAYS the same generic message (no enumeration). --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password · Vyrobel</title>
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        :root {
            --vy-ink: rgb(43, 35, 32); --vy-muted: rgb(95, 88, 83); --vy-faint: rgb(108, 100, 95);
            --vy-accent: rgb(194, 65, 12); --vy-accent-3: rgb(232, 112, 58); --vy-amber: rgb(178, 106, 8);
            --vy-glass: rgba(255, 252, 248, 0.62); --vy-hairline: rgba(120, 70, 40, 0.10); --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-grad: linear-gradient(140deg, #D14102, #C2410C);
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
        .vy-sub { color: var(--vy-muted); font-size: 13.5px; margin: 6px 0 20px; }
        .vy-field { margin-bottom: 15px; }
        .vy-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; }
        .vy-input { width: 100%; padding: 12px 14px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.8); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); transition: border-color .15s ease, box-shadow .15s ease; }
        .vy-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232,112,58,.16); }
        .vy-note { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--vy-r-md); font-size: 13px; font-weight: 500; }
        .vy-note.is-ok { background: rgba(52,140,90,.12); border: 1px solid rgba(52,140,90,.34); color: rgb(34,110,68); }
        .vy-note.is-warn { background: rgba(224,146,30,.12); border: 1px solid rgba(224,146,30,.4); border-left: 3px solid var(--vy-amber); color: var(--vy-ink); }
        .vy-note svg { flex: none; }
        .vy-btn { width: 100%; margin-top: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 30px; font-family: var(--vy-sans); font-size: 14px; font-weight: 600; color:#fff; background: var(--vy-grad); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); transition: transform .15s ease; }
        .vy-btn:hover { transform: translateY(-1px); }
        .vy-btn:focus-visible, .vy-input:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-foot { text-align: center; font-size: 12px; margin-top: 18px; }
        .vy-foot a { color: var(--vy-accent); text-decoration: none; font-weight: 600; }
        .vy-foot a:hover { text-decoration: underline; }
    </style>
    <x-vy-scrollbar />
</head>
<body>
    <main class="vy-card">
        <div class="vy-brand"><span class="vy-brand-mark">V</span><span class="vy-brand-name">Vyrobel</span></div>
        <h1 class="vy-title">Reset your password</h1>
        {{-- WAVE 3 — the link's life is REAL (config/auth.php passwords.expire) and was simply never stated,
             so a guest who came back to an expired link had no way to know why. Read from config, not
             hardcoded, so the sentence cannot drift from the setting that governs it. The login-approval
             page already states its own 30 minutes — this matches that precedent. --}}
        <p class="vy-sub">Enter the email on your account and we’ll send you a link to choose a new password. The link is valid for {{ config('auth.passwords.users.expire', 60) }} minutes.</p>

        @if (session('status'))
            <div class="vy-note is-ok" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="vy-note is-warn" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf
            <div class="vy-field">
                <label class="vy-label" for="email">Email</label>
                <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
            </div>
            <button type="submit" class="vy-btn">Send reset link</button>
        </form>

        <div class="vy-foot"><a href="{{ route('login') }}">&larr; Back to sign in</a></div>
    </main>
</body>
</html>
