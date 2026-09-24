{{-- SUPER ADMIN — the platform-admin AUTH layout (login / 2FA challenge / 2FA setup).

     ⚠️ DESIGN RULING (founder, 2026-09-24) — see the note at the top of components/admin/shell.blade.php.
     This door shipped in a deliberately distinct cool deep-slate "control-room" aesthetic; the founder
     ruled that the admin realm must look like the rest of Vyrobel. The `--ad-*` token NAMES are kept (every
     `.ad-*` rule and all four admin auth views keep working) and their VALUES now come from the canonical
     `--vy-*` palette. Self-contained; noindex. --}}
@props(['title' => 'Platform admin', 'heading' => '', 'sub' => ''])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>{{ $title }} · Vyrobel Platform</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23C2410C'/%3E%3Ctext x='16' y='23' font-family='sans-serif' font-size='19' font-weight='700' fill='white' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    {{-- ⚠️ THE CANONICAL PALETTE FIRST (DO-TOKEN-1) — this layout's own <style> follows, so it can read it. --}}
    <x-design.tokens />
    <style>
        :root {
            /* ⚠️ Per-shell tokens absent from the shared palette file — see the shell's note. Referencing
               one without declaring it silently drops the whole declaration. */
            --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;
            /* The `--ad-*` → `--vy-*` mapping. NAMES kept, VALUES adopted. See the shell for the full note;
               in particular --ad-accent is the BRAND FILL and --ad-accent-text is the darkened TEXT hue. */
            --ad-ink: var(--vy-ink); --ad-muted: var(--vy-muted); --ad-faint: var(--vy-faint);
            --ad-accent: var(--vy-accent); --ad-accent-2: var(--vy-accent-2);
            --ad-accent-text: var(--vy-accent-text);
            --ad-accent-soft: rgba(232, 112, 58, 0.16);
            --ad-glass: rgba(255, 252, 248, 0.62); --ad-glass-2: rgba(255, 255, 255, 0.78);
            --ad-border: var(--vy-glass-border); --ad-border-strong: var(--vy-accent-border);
            --ad-grad: var(--vy-grad);
            --ad-danger: #8F3110; --ad-ok: var(--vy-green-text);
            --ad-shadow: rgba(150, 90, 50, 0.18) 0 22px 60px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --ad-r-lg: 22px; --ad-r-md: var(--vy-r-md); --ad-r-sm: 10px; --ad-r-pill: var(--vy-r-pill);
            --ad-sans: var(--vy-sans);
            --ad-display: var(--vy-display);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--ad-sans); color: var(--ad-ink); font-size: 15px; line-height: 1.5;
            -webkit-font-smoothing: antialiased; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background:
                radial-gradient(circle at 80% 6%, rgba(255, 255, 255, 0.4), transparent 62%),
                radial-gradient(circle at 12% 92%, rgba(255, 224, 200, 0.45), transparent 58%),
                linear-gradient(120deg, rgba(255, 240, 220, 0.5), transparent 55%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%);
            background-attachment: fixed;
        }
        @keyframes adFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes adDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(40px, 26px); } 100% { transform: translate(0, 0); } }
        body::before { content: ""; position: fixed; inset: -18% -8% auto auto; width: 46vw; height: 46vw; background: radial-gradient(circle, rgba(255, 205, 165, 0.55), transparent 70%); filter: blur(12px); z-index: 0; pointer-events: none; animation: adDrift 26s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; } }
        .ad-card {
            position: relative; z-index: 1; width: 100%; max-width: 430px; background: var(--ad-glass);
            backdrop-filter: blur(24px) saturate(1.18); -webkit-backdrop-filter: blur(24px) saturate(1.18);
            border: 1px solid var(--ad-border); border-radius: var(--ad-r-lg); box-shadow: var(--ad-shadow);
            padding: 34px 32px; animation: adFadeUp .6s cubic-bezier(.2,.7,.2,1) both;
        }
        .ad-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 8px; }
        .ad-brand-mark { width: 40px; height: 40px; border-radius: var(--ad-r-md); background: var(--ad-grad); color: #fff; font-family: var(--ad-display); font-weight: 700; font-size: 21px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .ad-brand-name { font-family: var(--ad-display); font-weight: 600; font-size: 18px; letter-spacing: -0.01em; }
        .ad-brand-tag { font-size: 10.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--ad-accent-text); }
        .ad-title { font-family: var(--ad-display); font-weight: 700; font-size: 26px; letter-spacing: -0.02em; margin: 18px 0 0; }
        .ad-sub { color: var(--ad-muted); font-size: 13.5px; margin: 7px 0 20px; }
        .ad-field { margin-bottom: 15px; }
        .ad-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--ad-muted); margin-bottom: 7px; }
        .ad-input { width: 100%; padding: 12px 14px; border-radius: var(--ad-r-sm); border: 1px solid var(--ad-border); background: rgba(255, 255, 255, 0.8); font-family: var(--ad-sans); font-size: 14.5px; color: var(--ad-ink); transition: border-color .15s ease, box-shadow .15s ease; }
        .ad-input::placeholder { color: var(--ad-faint); }
        .ad-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px var(--ad-accent-soft); }
        .ad-input:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-btn { width: 100%; margin-top: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: var(--ad-r-pill); font-family: var(--ad-sans); font-size: 14px; font-weight: 600; color: #fff; background: var(--ad-grad); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); transition: transform .15s ease; }
        .ad-btn:hover { transform: translateY(-1px); }
        .ad-btn:focus-visible, .ad-link:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-notice { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--ad-r-md); background: rgba(52, 140, 90, .12); border: 1px solid rgba(52, 140, 90, .34); border-left: 3px solid rgb(34, 110, 68); font-size: 13px; font-weight: 500; color: var(--vy-green-text); }
        .ad-notice svg { color: rgb(34, 110, 68); flex: none; }
        .ad-error { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--ad-r-md); background: rgba(143, 49, 16, .10); border: 1px solid rgba(143, 49, 16, .30); border-left: 3px solid var(--ad-danger); font-size: 13px; font-weight: 500; color: var(--ad-danger); }
        .ad-error svg { color: var(--ad-danger); flex: none; }
        .ad-note { font-size: 12.5px; color: var(--ad-muted); margin-top: 14px; line-height: 1.55; }
        .ad-link { color: var(--ad-accent-text); text-decoration: none; font-weight: 500; }
        .ad-link:hover { text-decoration: underline; }
        .ad-secure { display: flex; align-items: center; justify-content: center; gap: 7px; margin-top: 20px; color: var(--ad-faint); font-size: 11.5px; }
        .ad-secure svg { color: var(--ad-accent); }
        /* A recessed panel (recovery codes, the TOTP secret) — was a dark slate well; now warm cream. */
        .ad-well { background: rgba(255, 255, 255, 0.62); border: 1px solid var(--ad-border); border-radius: var(--ad-r-md); }
    </style>
    <x-vy-scrollbar />
</head>
<body>
    <main class="ad-card">
        <div class="ad-brand">
            <span class="ad-brand-mark">V</span>
            <span>
                <div class="ad-brand-name">Vyrobel</div>
                <div class="ad-brand-tag">Platform admin</div>
            </span>
        </div>
        @if ($heading !== '')
            <h1 class="ad-title">{{ $heading }}</h1>
        @endif
        @if ($sub !== '')
            <p class="ad-sub">{{ $sub }}</p>
        @endif

        {{-- ⚠️ F-5 — THE OUTCOME OF AN EMAIL CHANGE LANDED HERE AND NOTHING RENDERED IT. All four outcomes
             (valid · expired · already-in-use · tampered) redirect to an admin auth page with `status`, so the
             message is the ONLY thing distinguishing them — and it was invisible. Placed in the shared auth
             component rather than one view, so every door in this realm shows it. ⚠️ `role="status"`, not
             "alert": this is an outcome, not an error, and the two announce differently. --}}
        @if (session('status'))
            <div class="ad-notice" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="ad-error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        {{ $slot }}

        <div class="ad-secure">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Hardened platform access · 2FA required
        </div>
    </main>
</body>
</html>
