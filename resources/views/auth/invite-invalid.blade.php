<!DOCTYPE html>
{{-- Generic "invalid invitation" page (Auth Phase B). The SAME response for any invalid / expired /
     already-used / unknown token — never reveals which invites exist or why exactly it failed. --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation · Vyrobel</title>
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        :root {
            --vy-ink: rgb(43, 35, 32); --vy-muted: rgb(95, 88, 83); --vy-faint: rgb(108, 100, 95);
            --vy-accent: rgb(194, 65, 12); --vy-amber: rgb(178, 106, 8);
            --vy-glass: rgba(255, 252, 248, 0.62); --vy-hairline: rgba(120, 70, 40, 0.10); --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-grad: linear-gradient(140deg, #D14102, #C2410C);
            --vy-shadow: rgba(150, 90, 50, 0.18) 0 22px 60px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;
            --vy-r-lg: 22px; --vy-r-md: 12px;
            --vy-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --vy-display: 'Sora', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--vy-sans); color: var(--vy-ink); min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: radial-gradient(circle at 80% 6%, rgba(255,255,255,.4), transparent 62%),
                radial-gradient(circle at 12% 92%, rgba(255,224,200,.45), transparent 58%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%);
        }
        .vy-card { position: relative; z-index: 1; width: 100%; max-width: 400px; text-align: center; background: var(--vy-glass); backdrop-filter: blur(24px) saturate(1.18); -webkit-backdrop-filter: blur(24px) saturate(1.18); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 40px 32px; animation: vyFadeUp .6s cubic-bezier(.2, .7, .2, 1) both; }
        .vy-ico { width: 56px; height: 56px; border-radius: 50%; background: rgba(224,146,30,.14); color: var(--vy-amber); display: grid; place-items: center; margin: 0 auto 18px; }
        .vy-title { font-family: var(--vy-display); font-weight: 700; font-size: 22px; letter-spacing: -0.01em; margin: 0; }
        .vy-sub { color: var(--vy-muted); font-size: 13.5px; margin: 10px 0 22px; }
        .vy-btn { display: inline-flex; align-items: center; justify-content: center; padding: 11px 22px; border-radius: 30px; font-size: 14px; font-weight: 600; color:#fff; background: var(--vy-grad); box-shadow: var(--vy-shadow-accent); transition: transform .15s ease; }
        /* Polish Batch 4 — match the sibling auth screens (fade-in card, drift blob, button lift). */
        .vy-btn:hover { transform: translateY(-1px); }
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(40px, 24px); } 100% { transform: translate(0, 0); } }
        body::before { content: ""; position: fixed; inset: -20% -10% auto auto; width: 44vw; height: 44vw; background: radial-gradient(circle, rgba(255, 210, 170, .45), transparent 70%); filter: blur(10px); z-index: 0; pointer-events: none; animation: vyDrift 28s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; transition: none !important; } }
    </style>
    <x-vy-scrollbar />
</head>
<body>
    <main class="vy-card">
        <div class="vy-ico">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
        </div>
        <h1 class="vy-title">This invitation link isn’t valid</h1>
        <p class="vy-sub">It may have expired or already been used. Please ask your operator to send a new invitation.</p>
        <a class="vy-btn" href="{{ route('login') }}">Go to sign in</a>
    </main>
</body>
</html>
