{{-- Shared guest-auth layout (Phase 4a) — the self-contained public front door for the GUEST realm, on the same
     brand tokens as the operator/owner login. Each page fills @section('card'). RAW-BLADE RULE: every `@` follows
     a space, newline or tag. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Vyrobel') · Vyrobel</title>
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        :root {
            --vy-ink: rgb(43, 35, 32); --vy-muted: rgb(95, 88, 83); --vy-faint: rgb(108, 100, 95);
            --vy-accent: rgb(194, 65, 12); --vy-accent-3: rgb(232, 112, 58); --vy-amber: rgb(178, 106, 8);
            --vy-glass: rgba(255, 252, 248, 0.62); --vy-glass-border: rgba(255, 255, 255, 0.75);
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
            background:
                radial-gradient(circle at 80% 6%, rgba(255, 255, 255, 0.4), transparent 62%),
                radial-gradient(circle at 12% 92%, rgba(255, 224, 200, 0.45), transparent 58%),
                linear-gradient(120deg, rgba(255, 240, 220, 0.5), transparent 55%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%);
            background-attachment: fixed;
        }
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; } }
        .vy-card {
            position: relative; z-index: 1; width: 100%; max-width: 400px;
            background: var(--vy-glass); backdrop-filter: blur(24px) saturate(1.18);
            -webkit-backdrop-filter: blur(24px) saturate(1.18);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 34px 32px; animation: vyFadeUp .6s cubic-bezier(.2, .7, .2, 1) both;
        }
        .vy-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 24px; }
        /* G11 · WHITE TEXT NEEDS A SOLID PLATE, NOT THE GRADIENT. --vy-grad runs to #E8703A at its light end,
           where white measures ~3.8; on solid --vy-accent it is 5.18. The shell learned this at G1/G9 — but
           this is a SEPARATE layout and none of those fixes carried, so it is re-established here on ITS OWN
           measurements. The mark passed only because 21px bold counts as large text (3.0); the BUTTON label
           at 14px did not (3.88 against 4.5). Both now sit on the solid accent. The gradient stays for
           decorative fills. ⚠️ Measured on THIS layout, not assumed from the shell: --vy-accent as LINK text
           reads 4.80–5.11 on this lighter card and passes, so no *-text token is introduced here — the same
           token failed on the shell's darker plate. A translucent token has no fixed contrast. */
        .vy-brand-mark { width: 40px; height: 40px; border-radius: var(--vy-r-md); background: var(--vy-accent); color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 21px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 18px; letter-spacing: -0.01em; }
        .vy-title { font-family: var(--vy-display); font-weight: 700; font-size: 26px; letter-spacing: -0.02em; margin: 0; }
        .vy-sub { color: var(--vy-muted); font-size: 13.5px; margin: 6px 0 22px; }
        .vy-field { margin-bottom: 15px; }
        .vy-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; }
        .vy-input { width: 100%; padding: 12px 14px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.8); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); transition: border-color .15s ease, box-shadow .15s ease; }
        .vy-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.16); }
        .vy-error { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 11px 14px; border-radius: var(--vy-r-md); background: rgba(224, 146, 30, 0.12); border: 1px solid rgba(224, 146, 30, 0.4); border-left: 3px solid var(--vy-amber); font-size: 13px; font-weight: 500; color: var(--vy-ink); }
        .vy-notice { margin-bottom: 18px; padding: 12px 14px; border-radius: var(--vy-r-md); background: rgba(120, 160, 110, 0.12); border: 1px solid rgba(120, 160, 110, 0.35); border-left: 3px solid rgb(90, 140, 80); font-size: 13px; color: var(--vy-ink); }
        .vy-btn { width: 100%; margin-top: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 30px; font-family: var(--vy-sans); font-size: 14px; font-weight: 600; color: #fff; background: var(--vy-accent); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); transition: transform .15s ease; }
        .vy-btn:hover { transform: translateY(-1px); }
        .vy-btn:focus-visible, .vy-input:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-link { font-size: 12.5px; color: var(--vy-accent-text); text-decoration: none; font-weight: 500; }   /* the second copy — §N.8f */
        .vy-link:hover { text-decoration: underline; }
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
        @yield('card')
    </main>
</body>
</html>
