<!DOCTYPE html>
{{-- Vyrobel login (Auth Phase A) — the shared operator/owner front door. Styled from the approved
     "Vyrobel Login" design onto the shared Vyrobel tokens (warm glass, terracotta, Sora/Inter, drifting
     blob). Real, CSRF-protected, rate-limited login — not a stub. Self-contained (unauthenticated). --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Vyrobel</title>

    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">

    <style>
        :root {
            --vy-ink: rgb(43, 35, 32);
            --vy-muted: rgb(95, 88, 83);
            --vy-faint: rgb(108, 100, 95);
            --vy-accent: rgb(194, 65, 12);
            --vy-accent-3: rgb(232, 112, 58);
            --vy-amber: rgb(178, 106, 8);
            --vy-glass: rgba(255, 252, 248, 0.62);
            --vy-hairline: rgba(120, 70, 40, 0.10);
            --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-grad: linear-gradient(140deg, #E8703A, #C2410C);
            --vy-shadow: rgba(150, 90, 50, 0.18) 0 22px 60px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;
            --vy-r-lg: 22px;
            --vy-r-md: 12px;
            --vy-r-sm: 10px;
            --vy-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --vy-display: 'Sora', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--vy-sans); color: var(--vy-ink); font-size: 16px; line-height: 1.45;
            -webkit-font-smoothing: antialiased; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background:
                radial-gradient(circle at 80% 6%, rgba(255, 255, 255, 0.4), transparent 62%),
                radial-gradient(circle at 12% 92%, rgba(255, 224, 200, 0.45), transparent 58%),
                linear-gradient(120deg, rgba(255, 240, 220, 0.5), transparent 55%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%);
            background-attachment: fixed;
        }
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(40px, 26px); } 100% { transform: translate(0, 0); } }
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        body::before {
            content: ""; position: fixed; inset: -18% -8% auto auto; width: 46vw; height: 46vw;
            background: radial-gradient(circle, rgba(255, 205, 165, 0.55), transparent 70%);
            filter: blur(12px); z-index: 0; pointer-events: none; animation: vyDrift 26s ease-in-out infinite;
        }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; } }

        .vy-card {
            position: relative; z-index: 1; width: 100%; max-width: 400px;
            background: var(--vy-glass); backdrop-filter: blur(24px) saturate(1.18);
            -webkit-backdrop-filter: blur(24px) saturate(1.18);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 34px 32px;
            animation: vyFadeUp .6s cubic-bezier(.2, .7, .2, 1) both;
        }
        .vy-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 24px; }
        .vy-brand-mark { width: 40px; height: 40px; border-radius: var(--vy-r-md); background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 21px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 18px; letter-spacing: -0.01em; }
        .vy-title { font-family: var(--vy-display); font-weight: 700; font-size: 26px; letter-spacing: -0.02em; margin: 0; }
        .vy-sub { color: var(--vy-muted); font-size: 13.5px; margin: 6px 0 22px; }

        .vy-field { margin-bottom: 15px; }
        .vy-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; }
        .vy-input { width: 100%; padding: 12px 14px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.8); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); transition: border-color .15s ease, box-shadow .15s ease; }
        .vy-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.16); }

        .vy-note { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--vy-r-md); font-size: 13px; font-weight: 500; }
        .vy-note.is-ok { background: rgba(52,140,90,.12); border: 1px solid rgba(52,140,90,.34); color: rgb(34,110,68); }
        .vy-note svg { flex: none; }
        .vy-error { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--vy-r-md); background: rgba(224, 146, 30, 0.12); border: 1px solid rgba(224, 146, 30, 0.4); border-left: 3px solid var(--vy-amber); font-size: 13px; font-weight: 500; color: var(--vy-ink); }
        .vy-error svg { color: var(--vy-amber); flex: none; }

        .vy-btn { width: 100%; margin-top: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 30px; font-family: var(--vy-sans); font-size: 14px; font-weight: 600; color: #fff; background: var(--vy-grad); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); transition: transform .15s ease; }
        .vy-btn:hover { transform: translateY(-1px); }
        .vy-btn:focus-visible, .vy-input:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        .vy-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
        .vy-forgot { font-size: 11.5px; color: var(--vy-accent); text-decoration: none; font-weight: 500; }
        .vy-forgot:hover { text-decoration: underline; }
        .vy-soon-tag { font-size: 8.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 1px 5px; margin-left: 4px; }
        .vy-foot { text-align: center; color: var(--vy-faint); font-size: 11.5px; margin-top: 20px; }
    </style>
    <x-vy-scrollbar />
</head>
<body>
    <main class="vy-card">
        <div class="vy-brand">
            <span class="vy-brand-mark">V</span>
            <span class="vy-brand-name">Vyrobel</span>
        </div>

        <h1 class="vy-title">Welcome back</h1>
        <p class="vy-sub">Sign in to your Vyrobel account.</p>

        {{-- ⚠️ F-4 — THE OUTCOME OF AN EMAIL CHANGE LANDS HERE AND WAS RENDERED BY NOBODY. OwnerAccountController
             and the reset/approval paths all redirect to this page with `status`, and this view never read it:
             a message set is not a message seen. Copied from `auth/forgot-password.blade.php`, which already
             renders this exact key in this exact realm — not invented, and not borrowed from another shell. --}}
        @if (session('status'))
            <div class="vy-note is-ok" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="vy-error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <div class="vy-field">
                <label class="vy-label" for="email">Email</label>
                <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
            </div>
            <div class="vy-field">
                <div class="vy-row">
                    <label class="vy-label" for="password" style="margin-bottom:0;">Password</label>
                    <a class="vy-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                </div>
                <input class="vy-input" type="password" id="password" name="password" autocomplete="current-password" required style="margin-top:7px;">
            </div>

            <button type="submit" class="vy-btn">Sign in</button>
        </form>

        {{-- ⚠️ CONTINUE WITH GOOGLE — rendered ONLY when the founder has configured the console credentials.
             There is no disabled state and no "coming soon": with nothing configured the route 404s, so a
             button here would be a link to an error page. Absent is the honest presentation of absent. --}}
        @if (app(\App\Contracts\Auth\SocialIdentityProvider::class)->enabled())
            <a href="{{ route('social.google.redirect') }}" class="vy-btn" style="display:block;margin-top:10px;background:transparent;color:var(--vy-fg);border:1px solid var(--vy-line);text-align:center;text-decoration:none;">
                <svg width="16" height="16" viewBox="0 0 48 48" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.2-.4-4.7H24v8.9h11.8c-.5 2.7-2 5-4.4 6.6v5.5h7.1c4.2-3.8 6.6-9.5 6.6-16.3z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-7.1-5.5c-2 1.3-4.5 2.1-7.4 2.1-5.7 0-10.5-3.8-12.2-9H4.5v5.7C8.1 41.3 15.5 46 24 46z"/><path fill="#FBBC05" d="M11.8 28.3c-.4-1.3-.7-2.7-.7-4.3s.3-3 .7-4.3v-5.7H4.5C2.9 17.2 2 20.5 2 24s.9 6.8 2.5 10l7.3-5.7z"/><path fill="#EA4335" d="M24 10.7c3.2 0 6.1 1.1 8.4 3.3l6.3-6.3C34.9 4.1 29.9 2 24 2 15.5 2 8.1 6.7 4.5 13.7l7.3 5.7c1.7-5.2 6.5-9 12.2-9z"/></svg>
                Continue with Google
            </a>
        @endif

        {{-- Passkey sign-in (Wave B · GATE 4) — passwordless WebAuthn; only shown where the browser supports it --}}
        <button type="button" id="vy-passkey-btn" class="vy-btn" style="display:none;margin-top:10px;background:transparent;color:var(--vy-accent);border:1px solid var(--vy-accent);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Sign in with a passkey
        </button>
        <p id="vy-passkey-msg" style="display:none;font-size:12px;color:var(--vy-muted);margin-top:8px;text-align:center;"></p>

        <div class="vy-foot">New to Vyrobel? <a class="vy-forgot" href="{{ route('signup') }}">Create an account</a></div>
    </main>
    <script>
        (function () {
            if (!window.PublicKeyCredential) return;
            var btn = document.getElementById('vy-passkey-btn');
            var msg = document.getElementById('vy-passkey-msg');
            var token = document.querySelector('input[name="_token"]')?.value;
            btn.style.display = '';
            btn.addEventListener('click', async function () {
                btn.disabled = true; msg.style.display = 'none';
                try {
                    var optRes = await fetch('{{ route('passkeys.login.options') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
                    if (!optRes.ok) throw new Error('options');
                    var options = PublicKeyCredential.parseRequestOptionsFromJSON(await optRes.json());
                    var assertion = await navigator.credentials.get({ publicKey: options });
                    var res = await fetch('{{ route('passkeys.login') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' }, body: JSON.stringify(assertion.toJSON()) });
                    var data = await res.json();
                    if (data.ok && data.redirect) { location.href = data.redirect; return; }
                    msg.textContent = data.message || 'Passkey sign-in failed.'; msg.style.display = '';
                } catch (e) {
                    msg.textContent = 'Passkey sign-in was cancelled or not available.'; msg.style.display = '';
                }
                btn.disabled = false;
            });
        })();
    </script>
</body>
</html>
