{{-- SUPER ADMIN · GATE 0 — the platform-admin AUTH layout (login / 2FA challenge / 2FA setup). A DELIBERATELY
     distinct "control-room" aesthetic — cool deep-slate + indigo — so the highest-privilege realm never looks
     or feels like a tenant portal (the warm apricot/terracotta glass), while being just as polished. Built well
     from the start: shared tokens/components the whole panel inherits. Self-contained; noindex. --}}
@props(['title' => 'Platform admin', 'heading' => '', 'sub' => ''])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>{{ $title }} · Vyrobel Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ad-bg-0: #0C1322; --ad-bg-1: #111B2E; --ad-bg-2: #16223A;
            --ad-ink: #E8EDF6; --ad-muted: #94A3B8; --ad-faint: #64748B;
            --ad-accent: #818CF8; --ad-accent-2: #6366F1; --ad-accent-soft: rgba(129, 140, 248, 0.16);
            --ad-glass: rgba(255, 255, 255, 0.045); --ad-glass-2: rgba(255, 255, 255, 0.06);
            --ad-border: rgba(148, 163, 184, 0.18); --ad-border-strong: rgba(148, 163, 184, 0.30);
            --ad-grad: linear-gradient(135deg, #818CF8, #6366F1);
            --ad-danger: #F59E9E; --ad-ok: #6EE7B7;
            --ad-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
            --ad-r-lg: 20px; --ad-r-md: 12px; --ad-r-sm: 9px;
            --ad-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --ad-display: 'Sora', 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--ad-sans); color: var(--ad-ink); font-size: 15px; line-height: 1.5;
            -webkit-font-smoothing: antialiased; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background:
                radial-gradient(circle at 84% 4%, rgba(99, 102, 241, 0.16), transparent 56%),
                radial-gradient(circle at 8% 96%, rgba(56, 189, 248, 0.10), transparent 54%),
                linear-gradient(160deg, var(--ad-bg-0) 0%, var(--ad-bg-1) 58%, var(--ad-bg-2) 100%);
            background-attachment: fixed;
        }
        @keyframes adFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes adDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(36px, 22px); } 100% { transform: translate(0, 0); } }
        body::before { content: ""; position: fixed; inset: -20% -10% auto auto; width: 44vw; height: 44vw; background: radial-gradient(circle, rgba(99, 102, 241, 0.22), transparent 70%); filter: blur(14px); z-index: 0; pointer-events: none; animation: adDrift 30s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; } }
        .ad-card {
            position: relative; z-index: 1; width: 100%; max-width: 430px; background: var(--ad-glass);
            backdrop-filter: blur(22px) saturate(1.1); -webkit-backdrop-filter: blur(22px) saturate(1.1);
            border: 1px solid var(--ad-border); border-radius: var(--ad-r-lg); box-shadow: var(--ad-shadow);
            padding: 34px 32px; animation: adFadeUp .55s cubic-bezier(.2,.7,.2,1) both;
        }
        .ad-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 8px; }
        .ad-brand-mark { width: 40px; height: 40px; border-radius: var(--ad-r-md); background: var(--ad-grad); color: #fff; font-family: var(--ad-display); font-weight: 700; font-size: 21px; display: grid; place-items: center; box-shadow: 0 6px 18px rgba(99, 102, 241, 0.4); }
        .ad-brand-name { font-family: var(--ad-display); font-weight: 600; font-size: 17px; letter-spacing: -0.01em; }
        .ad-brand-tag { font-size: 10.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--ad-accent); }
        .ad-title { font-family: var(--ad-display); font-weight: 700; font-size: 23px; letter-spacing: -0.02em; margin: 18px 0 0; }
        .ad-sub { color: var(--ad-muted); font-size: 13.5px; margin: 7px 0 20px; }
        .ad-field { margin-bottom: 15px; }
        .ad-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--ad-muted); margin-bottom: 7px; }
        .ad-input { width: 100%; padding: 12px 14px; border-radius: var(--ad-r-sm); border: 1px solid var(--ad-border); background: rgba(9, 14, 24, 0.55); font-family: var(--ad-sans); font-size: 14.5px; color: var(--ad-ink); transition: border-color .15s ease, box-shadow .15s ease; }
        .ad-input::placeholder { color: var(--ad-faint); }
        .ad-input:focus { outline: none; border-color: var(--ad-accent); box-shadow: 0 0 0 3px var(--ad-accent-soft); }
        .ad-input:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-btn { width: 100%; margin-top: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 12px; font-family: var(--ad-sans); font-size: 14px; font-weight: 600; color: #fff; background: var(--ad-grad); border: none; cursor: pointer; box-shadow: 0 6px 18px rgba(99, 102, 241, 0.35); transition: transform .15s ease, box-shadow .15s ease; }
        .ad-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 26px rgba(99, 102, 241, 0.45); }
        .ad-btn:focus-visible, .ad-link:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-error { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--ad-r-md); background: rgba(245, 158, 158, 0.10); border: 1px solid rgba(245, 158, 158, 0.34); border-left: 3px solid var(--ad-danger); font-size: 13px; font-weight: 500; color: #FBD5D5; }
        .ad-note { font-size: 12.5px; color: var(--ad-muted); margin-top: 14px; line-height: 1.55; }
        .ad-link { color: var(--ad-accent); text-decoration: none; font-weight: 500; }
        .ad-link:hover { text-decoration: underline; }
        .ad-secure { display: flex; align-items: center; justify-content: center; gap: 7px; margin-top: 20px; color: var(--ad-faint); font-size: 11.5px; }
        .ad-secure svg { color: var(--ad-accent); }
    </style>
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
