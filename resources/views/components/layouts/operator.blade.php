{{-- Operator back-office layout (design pass · Operator Screen 1). A DEDICATED shell for the operator
     screens so the shared unstyled stub layout (components/layouts/app) — and the owner layout — stay
     untouched.

     It reuses the SAME formalized Vyrobel design system extracted for the owner portal
     (docs/design/owner-portal-spec.md): a single LIGHT theme, frosted-glass surfaces (blur 22px /
     saturate 1.15), a warm layered shadow with a glossy top edge, terracotta accent (#C2410C), Sora
     display + Inter body, and the real keyframes lifted from the design bundle. The tokens are copied
     (not shared) so the certified owner layout is never edited by an operator change — the same
     "dedicated, self-contained layout per surface" decision the owner layout records.

     Presentation only — no data, no backend. Rack-specific CSS lives at the bottom. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Vyrobel — Operator' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">

    @livewireStyles

    <style>
        /* ---- Formalized design tokens (shared Vyrobel system; see owner-portal-spec.md) ---------- */
        :root {
            --vy-ink: rgb(43, 35, 32);
            --vy-muted: rgb(138, 128, 121);
            --vy-faint: rgb(166, 154, 146);
            --vy-accent: rgb(194, 65, 12);       /* terracotta primary (#C2410C) */
            --vy-accent-2: rgb(217, 102, 46);
            --vy-accent-3: rgb(232, 112, 58);    /* (#E8703A) */
            --vy-peach: rgb(240, 165, 122);
            --vy-amber: rgb(178, 106, 8);
            --vy-amber-2: rgb(224, 146, 30);
            --vy-dark: rgb(42, 33, 28);

            --vy-glass: rgba(255, 252, 248, 0.58);
            --vy-glass-2: rgba(255, 255, 255, 0.55);
            --vy-hairline: rgba(120, 70, 40, 0.10);
            --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-accent-border: rgba(232, 112, 58, 0.28);

            --vy-grad: linear-gradient(140deg, #E8703A, #C2410C);
            --vy-grad-peach: linear-gradient(90deg, #F0A57A, #C2410C);
            --vy-grad-dark: linear-gradient(160deg, #3A2E27, #251C17);

            --vy-shadow: rgba(150, 90, 50, 0.16) 0 14px 40px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-sm: rgba(150, 90, 50, 0.12) 0 8px 22px, inset rgba(255, 255, 255, 0.6) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;

            --vy-r-pill: 30px;
            --vy-r-lg: 20px;
            --vy-r-md: 12px;
            --vy-r-sm: 10px;

            --vy-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --vy-display: 'Sora', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }

        body.vy-body {
            font-family: var(--vy-sans);
            color: var(--vy-ink);
            font-size: 16px;
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            min-height: 100vh;
            background:
                radial-gradient(circle at 82% 8%, rgba(255, 255, 255, 0.35), transparent 68%),
                radial-gradient(circle at 12% 92%, rgba(255, 224, 200, 0.4), transparent 60%),
                linear-gradient(120deg, rgba(255, 240, 220, 0.5), transparent 55%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%);
            background-attachment: fixed;
        }

        body.vy-body::before {
            content: "";
            position: fixed;
            inset: -20% -10% auto auto;
            width: 46vw;
            height: 46vw;
            background: radial-gradient(circle, rgba(255, 210, 170, 0.5), transparent 70%);
            filter: blur(10px);
            z-index: 0;
            pointer-events: none;
            animation: vyDrift 26s ease-in-out infinite;
        }

        main { position: relative; z-index: 1; }
        a { color: inherit; text-decoration: none; }

        /* ---- The real design keyframes (lifted from the design bundle) -------------------------- */
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes vyShimmer { 0% { opacity: .25; transform: translateX(-4%) rotate(28deg); } 50% { opacity: .45; transform: translateX(3%) rotate(28deg); } 100% { opacity: .25; transform: translateX(-4%) rotate(28deg); } }
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(46px, 28px); } 100% { transform: translate(0, 0); } }

        /* ---- App shell ------------------------------------------------------------------------- */
        .vy-shell { display: grid; grid-template-columns: 232px minmax(0, 1fr); min-height: 100vh; }

        .vy-side {
            position: sticky; top: 0; align-self: start; height: 100vh;
            padding: 22px 16px; display: flex; flex-direction: column; gap: 20px;
            border-right: 1px solid var(--vy-hairline);
            background: rgba(255, 253, 250, 0.5);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            overflow-y: auto;
        }
        .vy-brand { display: flex; align-items: center; gap: 10px; padding: 4px 6px 8px; }
        .vy-brand-mark {
            width: 34px; height: 34px; border-radius: var(--vy-r-md); background: var(--vy-grad);
            color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 18px;
            display: grid; place-items: center; box-shadow: var(--vy-shadow-accent);
        }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 16px; letter-spacing: -0.01em; }
        .vy-brand-sub { font-size: 9.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-faint); }
        .vy-nav-group { display: flex; flex-direction: column; gap: 2px; }
        .vy-nav-label { font-size: 9.5px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--vy-faint); padding: 8px 10px 4px; }
        .vy-nav-item {
            display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--vy-r-sm);
            font-size: 13.5px; font-weight: 500; color: var(--vy-muted); cursor: pointer;
            transition: background .18s ease, color .18s ease;
        }
        .vy-nav-item .vy-ico { width: 15px; height: 15px; flex: none; opacity: .8; }
        .vy-nav-item:hover { background: rgba(255, 255, 255, 0.6); color: var(--vy-ink); }
        .vy-nav-item.is-active {
            background: rgba(255, 255, 255, 0.9); color: var(--vy-ink); font-weight: 600;
            box-shadow: var(--vy-shadow-sm); border: 1px solid var(--vy-glass-border);
        }
        .vy-nav-item[aria-disabled="true"] { cursor: default; opacity: .55; }
        .vy-nav-item[aria-disabled="true"]:hover { background: transparent; color: var(--vy-muted); }

        /* Main column */
        .vy-main { padding: 22px 30px 60px; max-width: 1480px; }
        .vy-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .vy-topbar h1 { font-family: var(--vy-display); font-weight: 600; font-size: 24px; letter-spacing: -0.02em; margin: 0; }
        .vy-topbar-sub { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; }
        .vy-topbar-actions { display: flex; align-items: center; gap: 12px; }

        .vy-bell { position: relative; width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); color: var(--vy-muted); }
        .vy-chip { display: flex; align-items: center; gap: 9px; padding: 5px 12px 5px 6px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 12px; font-weight: 600; display: grid; place-items: center; }
        .vy-chip-name { font-size: 13px; font-weight: 500; }

        /* ---- Reusable surfaces ----------------------------------------------------------------- */
        .vy-card {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 22px;
        }
        .vy-card--flush { padding: 0; overflow: hidden; }
        .vy-eyebrow { font-size: 10.5px; font-weight: 600; letter-spacing: 0.11em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-card-title { font-family: var(--vy-display); font-size: 15px; font-weight: 600; margin: 0; }
        .vy-muted { color: var(--vy-muted); }
        .vy-fade { opacity: 0; animation: vyFadeUp .6s cubic-bezier(.2, .7, .2, 1) forwards; }

        /* Buttons / pills */
        .vy-btn {
            display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: var(--vy-r-pill);
            font-family: var(--vy-sans); font-size: 13px; font-weight: 600; border: 1px solid transparent;
            cursor: pointer; transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .vy-btn--accent { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vy-btn--accent:hover { transform: translateY(-1px); }
        .vy-btn--ghost { background: rgba(255, 255, 255, 0.6); color: var(--vy-ink); border-color: var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-btn--ghost:hover { background: rgba(255, 255, 255, 0.85); }
        .vy-btn:focus-visible, .vy-nav-item:focus-visible, .vy-icobtn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* A control with no backend yet: visible, clearly non-functional, never a fake action. */
        .vy-btn.is-soon, .vy-icobtn.is-soon, .vy-pill.is-soon { opacity: .5; cursor: not-allowed; }
        .vy-soon-tag { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 2px 6px; }

        .vy-pill {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: var(--vy-r-pill);
            font-size: 12.5px; font-weight: 500; color: var(--vy-muted);
            background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); transition: all .16s ease;
        }
        .vy-icobtn { width: 36px; height: 36px; border-radius: var(--vy-r-sm); display: grid; place-items: center; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); color: var(--vy-ink); box-shadow: var(--vy-shadow-sm); cursor: pointer; }
        .vy-icobtn[aria-disabled="true"] { opacity: .4; cursor: default; }

        .vy-empty { text-align: center; color: var(--vy-muted); padding: 40px 20px; font-size: 13.5px; }
        .vy-deferred-note { font-size: 11.5px; color: var(--vy-muted); display: inline-flex; align-items: center; gap: 6px; }

        /* ---- Reservation rack (timeline grid) -------------------------------------------------- */
        .vy-rack-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
        .vy-rack-toolbar .vy-left, .vy-rack-toolbar .vy-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .vy-range { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.7); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); font-weight: 600; font-size: 13px; }

        .vy-rack-scroll { overflow-x: auto; }
        .vy-rack { min-width: 720px; }
        /* A fixed label column + a flex "nights" column. Header, availability and lane backgrounds all
           use the SAME `--night-cols` grid template (set inline on the .vy-nights element) over the
           flex area, so every night column stays pixel-aligned across rows regardless of night count. */
        .vy-rack-flexrow { display: flex; align-items: stretch; }
        .vy-rack-labelcol { width: 178px; flex: none; }
        .vy-nights { flex: 1; min-width: 0; display: grid; grid-template-columns: var(--night-cols); }

        .vy-rack-head { position: sticky; top: 0; z-index: 3; }
        .vy-rack-corner {
            background: rgba(255, 252, 248, 0.92); border-bottom: 1px solid var(--vy-hairline);
            padding: 12px 14px; font-size: 9.5px; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: var(--vy-faint); display: flex; align-items: flex-end;
        }
        .vy-rack-head .vy-nights { background: rgba(255, 252, 248, 0.92); }
        .vy-day {
            border-bottom: 1px solid var(--vy-hairline); border-left: 1px solid var(--vy-hairline);
            padding: 8px 2px; text-align: center;
        }
        .vy-day.is-weekend { background: rgba(232, 112, 58, 0.07); }
        .vy-day-dow { font-size: 9.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--vy-faint); }
        .vy-day-num { font-family: var(--vy-display); font-size: 14px; font-weight: 600; margin-top: 2px; }
        .vy-day-mon { font-size: 9px; color: var(--vy-faint); }

        .vy-rt-label {
            padding: 12px 14px; border-bottom: 1px solid var(--vy-hairline);
            display: flex; flex-direction: column; justify-content: center; gap: 4px;
            background: rgba(255, 255, 255, 0.35);
        }
        .vy-rt-name { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; }
        .vy-rt-meta { font-size: 10.5px; color: var(--vy-muted); }
        .vy-rt-kind { display: inline-block; font-size: 9px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent); background: rgba(232, 112, 58, 0.12); border-radius: 5px; padding: 1px 6px; align-self: flex-start; }

        /* Availability heat cells (real available count per night) */
        .vy-rt-availrow .vy-nights { border-bottom: 1px solid var(--vy-hairline); }
        .vy-avail {
            border-left: 1px solid var(--vy-hairline);
            min-height: 32px; display: flex; align-items: center; justify-content: center;
            font-size: 11.5px; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--vy-muted);
        }
        .vy-avail.is-weekend { background: rgba(232, 112, 58, 0.035); }
        .vy-avail.is-zero { color: var(--vy-accent); background: rgba(194, 65, 12, 0.10); }   /* sold out */
        .vy-avail.is-tight { color: var(--vy-amber); background: rgba(224, 146, 30, 0.09); }   /* 1 left  */

        /* Booking-bar lanes — one lane per line so overlapping units in a room type never collide.
           Two stacked N-column grids: a background cell grid (borders/weekend) + a bar grid on top. */
        .vy-lane { position: relative; min-height: 40px; }
        .vy-lane-bg { position: absolute; inset: 0; display: grid; grid-template-columns: var(--night-cols); }
        .vy-lane-bg > div { border-left: 1px solid var(--vy-hairline); border-bottom: 1px solid var(--vy-hairline); }
        .vy-lane-bg > div.is-weekend { background: rgba(232, 112, 58, 0.035); }
        .vy-lane-bars { position: relative; z-index: 1; display: grid; grid-template-columns: var(--night-cols); align-items: center; min-height: 40px; }
        .vy-bar {
            grid-row: 1; height: 30px; border-radius: 8px; margin: 0 3px;
            display: flex; align-items: center; gap: 7px; padding: 0 10px; overflow: hidden;
            font-size: 11.5px; font-weight: 600; color: #fff; white-space: nowrap;
            box-shadow: var(--vy-shadow-accent); cursor: default;
        }
        .vy-bar .vy-bar-guest { overflow: hidden; text-overflow: ellipsis; }
        .vy-bar .vy-bar-meta { font-weight: 500; opacity: .82; font-size: 10.5px; }
        .vy-bar .vy-bar-flag { margin-left: auto; font-size: 8.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; background: rgba(255,255,255,.22); border-radius: 5px; padding: 1px 5px; }
        .vy-bar.is-clip-start { border-top-left-radius: 3px; border-bottom-left-radius: 3px; }
        .vy-bar.is-clip-end { border-top-right-radius: 3px; border-bottom-right-radius: 3px; }
        .vy-rt-lanes { border-bottom: 1px solid var(--vy-hairline); }
        .vy-rt-lanes .vy-lane:last-child .vy-lane-bg > div { border-bottom: none; }

        /* Warm status tones (no green/red) — mapped ONLY to real reservation statuses */
        .vy-bar.tone-confirmed { background: var(--vy-grad); }
        .vy-bar.tone-in-house { background: linear-gradient(140deg, #C2410C, #8f2f08); }
        .vy-bar.tone-departed { background: linear-gradient(140deg, #b8a99f, #8a8079); box-shadow: none; }
        .vy-bar.tone-pending { background: linear-gradient(140deg, #E0921E, #B26A08); }
        .vy-bar.tone-no-show { background: repeating-linear-gradient(135deg, #B26A08, #B26A08 7px, #9a5c07 7px, #9a5c07 14px); }
        .vy-bar.tone-cancelled { background: linear-gradient(140deg, #c9beb6, #a89c93); box-shadow: none; opacity: .8; }

        /* Legend */
        .vy-legend { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 16px; font-size: 11.5px; color: var(--vy-muted); }
        .vy-legend-item { display: inline-flex; align-items: center; gap: 6px; }
        .vy-legend-swatch { width: 24px; height: 12px; border-radius: 4px; }
        .vy-legend-swatch.tone-confirmed { background: var(--vy-grad); }
        .vy-legend-swatch.tone-in-house { background: linear-gradient(140deg, #C2410C, #8f2f08); }
        .vy-legend-swatch.tone-departed { background: linear-gradient(140deg, #b8a99f, #8a8079); }
        .vy-legend-swatch.tone-pending { background: linear-gradient(140deg, #E0921E, #B26A08); }
        .vy-legend-swatch.tone-no-show { background: repeating-linear-gradient(135deg, #B26A08, #B26A08 5px, #9a5c07 5px, #9a5c07 10px); }

        /* ---- Front desk (arrivals / departures / in-house) ------------------------------------- */
        .vy-toast {
            display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding: 12px 16px;
            border-radius: var(--vy-r-md); background: rgba(232, 112, 58, 0.10);
            border: 1px solid var(--vy-accent-border); border-left: 3px solid var(--vy-accent);
            font-size: 13px; font-weight: 500; color: var(--vy-ink); box-shadow: var(--vy-shadow-sm);
        }
        .vy-toast svg { color: var(--vy-accent); flex: none; }

        .vy-summary { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .vy-summary-chip {
            display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-radius: var(--vy-r-md);
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm);
        }
        .vy-summary-chip .vy-sc-num { font-family: var(--vy-display); font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
        .vy-summary-chip .vy-sc-label { font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-summary-chip.is-due { border-color: var(--vy-accent-border); }
        .vy-summary-chip.is-due .vy-sc-num { color: var(--vy-accent); }

        .vy-fd-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 18px; align-items: start; }
        .vy-fd-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 20px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-fd-head .vy-fd-title { font-family: var(--vy-display); font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .vy-fd-count { font-size: 11px; font-weight: 700; color: var(--vy-accent); background: rgba(232, 112, 58, 0.12); border-radius: var(--vy-r-pill); padding: 2px 9px; }

        .vy-fd-row { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--vy-hairline); transition: background .15s ease; }
        .vy-fd-row:last-child { border-bottom: none; }
        .vy-fd-row:hover { background: rgba(255, 255, 255, 0.45); }
        .vy-fd-main { flex: 1; min-width: 0; }
        .vy-fd-guest { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-fd-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; color: var(--vy-muted); margin-top: 4px; }
        .vy-fd-meta .vy-sep { opacity: .5; }
        .vy-fd-right { display: flex; flex-direction: column; align-items: flex-end; gap: 7px; flex: none; }
        .vy-fd-actions { display: flex; align-items: center; gap: 7px; }
        .vy-fd-actions form { margin: 0; }

        .vy-balance { font-family: var(--vy-display); font-weight: 600; font-size: 13.5px; font-variant-numeric: tabular-nums; }
        .vy-balance.is-due { color: var(--vy-accent); }
        .vy-balance.is-settled { color: var(--vy-muted); font-weight: 500; }

        /* Warm status tags (no green/red) — keyed to the real reservation enum */
        .vy-tag { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: var(--vy-r-pill); font-size: 11px; font-weight: 600; white-space: nowrap; }
        .vy-tag .vy-dot { width: 6px; height: 6px; border-radius: 50%; flex: none; }
        .vy-tag.tone-confirmed { background: rgba(232, 112, 58, 0.14); color: var(--vy-accent); }
        .vy-tag.tone-confirmed .vy-dot { background: var(--vy-accent); }
        .vy-tag.tone-in-house { background: rgba(194, 65, 12, 0.14); color: #8f2f08; }
        .vy-tag.tone-in-house .vy-dot { background: #8f2f08; }
        .vy-tag.tone-departed { background: rgba(138, 128, 121, 0.16); color: var(--vy-muted); }
        .vy-tag.tone-departed .vy-dot { background: var(--vy-muted); }
        .vy-tag.tone-pending { background: rgba(224, 146, 30, 0.15); color: var(--vy-amber); }
        .vy-tag.tone-pending .vy-dot { background: var(--vy-amber-2); }
        .vy-tag.tone-no-show { background: rgba(178, 106, 8, 0.15); color: var(--vy-amber); }
        .vy-tag.tone-no-show .vy-dot { background: var(--vy-amber); }
        .vy-tag.tone-cancelled { background: rgba(166, 154, 146, 0.18); color: var(--vy-faint); }
        .vy-tag.tone-cancelled .vy-dot { background: var(--vy-faint); }

        /* Unit assignment chip / unassigned + assign link */
        .vy-unit-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: var(--vy-ink); background: rgba(120, 70, 40, 0.07); border-radius: 6px; padding: 2px 7px; }
        .vy-unassigned { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: var(--vy-amber); background: rgba(224, 146, 30, 0.12); border: 1px dashed rgba(224, 146, 30, 0.4); border-radius: 6px; padding: 1px 7px; }
        .vy-assign-link { font-size: 11px; font-weight: 600; color: var(--vy-accent); }
        .vy-assign-link:hover { text-decoration: underline; }

        /* Small row action buttons */
        .vy-act { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: var(--vy-r-pill); font-size: 12px; font-weight: 600; border: 1px solid transparent; cursor: pointer; transition: transform .15s ease, background .15s ease; }
        .vy-act--accent { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vy-act--accent:hover { transform: translateY(-1px); }
        .vy-act--ghost { background: rgba(255, 255, 255, 0.65); color: var(--vy-ink); border-color: var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-act--ghost:hover { background: rgba(255, 255, 255, 0.9); }
        .vy-act.is-soon { opacity: .5; cursor: not-allowed; background: rgba(255, 255, 255, 0.5); color: var(--vy-muted); border-color: var(--vy-glass-border); }

        /* ---- Housekeeping kanban --------------------------------------------------------------- */
        .vy-kanban { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; align-items: start; }
        .vy-col {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow-sm); overflow: hidden;
        }
        .vy-col-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 13px 15px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-col-head .vy-col-title { display: flex; align-items: center; gap: 8px; font-family: var(--vy-display); font-size: 13px; font-weight: 600; }
        .vy-col-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; }
        .vy-col-count { font-size: 11px; font-weight: 700; color: var(--vy-muted); background: rgba(120, 70, 40, 0.08); border-radius: var(--vy-r-pill); padding: 2px 9px; }
        /* Warm column accents (no green/red), keyed to the real status enum */
        .vy-col.tone-pending .vy-col-dot { background: var(--vy-amber-2); }
        .vy-col.tone-pending { border-top: 2px solid var(--vy-amber-2); }
        .vy-col.tone-in-progress .vy-col-dot { background: var(--vy-accent-3); }
        .vy-col.tone-in-progress { border-top: 2px solid var(--vy-accent-3); }
        .vy-col.tone-clean .vy-col-dot { background: var(--vy-accent); }
        .vy-col.tone-clean { border-top: 2px solid var(--vy-accent); }
        .vy-col.tone-inspected .vy-col-dot { background: var(--vy-dark); }
        .vy-col.tone-inspected { border-top: 2px solid var(--vy-dark); }

        .vy-col-body { padding: 12px; display: flex; flex-direction: column; gap: 10px; min-height: 60px; }
        .vy-hk-card {
            background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border);
            border-radius: var(--vy-r-md); box-shadow: var(--vy-shadow-sm); padding: 12px 13px;
            display: flex; flex-direction: column; gap: 9px;
        }
        .vy-hk-top { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
        .vy-hk-unit { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; }
        .vy-hk-date { font-size: 11px; color: var(--vy-muted); font-variant-numeric: tabular-nums; flex: none; }
        .vy-hk-type { font-size: 11.5px; color: var(--vy-muted); }
        .vy-hk-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .vy-prio { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; letter-spacing: .03em; border-radius: var(--vy-r-pill); padding: 2px 8px; }
        .vy-prio.tone-high { color: var(--vy-accent); background: rgba(194, 65, 12, 0.12); }
        .vy-prio.tone-normal { color: var(--vy-muted); background: rgba(120, 70, 40, 0.08); }
        .vy-prio.tone-low { color: var(--vy-faint); background: rgba(120, 70, 40, 0.05); }

        .vy-hk-assignee { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: var(--vy-ink); }
        .vy-hk-assignee .vy-mini-avatar { width: 18px; height: 18px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 9px; font-weight: 600; display: grid; place-items: center; }
        .vy-hk-unassigned { font-size: 11px; color: var(--vy-amber); }

        .vy-hk-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; padding-top: 3px; border-top: 1px solid var(--vy-hairline); margin-top: 1px; }
        .vy-hk-actions form { margin: 0; }
        .vy-hk-col-empty { text-align: center; color: var(--vy-faint); font-size: 12px; padding: 18px 8px; }

        /* ---- Reservation detail (folio ledger) ------------------------------------------------- */
        .vy-detail-cols { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 22px; align-items: start; }
        .vy-stack { display: flex; flex-direction: column; gap: 22px; }

        .vy-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 22px; }
        .vy-fact-label { font-size: 9.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-faint); }
        .vy-fact-val { font-size: 13.5px; font-weight: 500; margin-top: 3px; }
        .vy-detail-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
        .vy-detail-ref { font-family: var(--vy-display); font-size: 20px; font-weight: 600; letter-spacing: -0.01em; }

        .vy-unit-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vy-unit-row:last-child { border-bottom: none; }
        .vy-unit-row .vy-um-name { font-weight: 600; }
        .vy-unit-row .vy-um-sub { font-size: 11px; color: var(--vy-muted); margin-top: 2px; }

        /* Folio ledger */
        .vy-ledger { display: flex; flex-direction: column; }
        .vy-ledger-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-ledger-row:last-child { border-bottom: none; }
        .vy-ledger-main { flex: 1; min-width: 0; }
        .vy-ledger-label { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .vy-ledger-sub { font-size: 11px; color: var(--vy-muted); margin-top: 2px; }
        .vy-ledger-date { font-size: 11px; color: var(--vy-faint); flex: none; font-variant-numeric: tabular-nums; }
        .vy-ledger-amt { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; flex: none; min-width: 92px; text-align: right; }
        .vy-ledger-amt.tone-charge { color: var(--vy-ink); }
        .vy-ledger-amt.tone-paid { color: var(--vy-accent); }
        .vy-ledger-amt.tone-refund { color: var(--vy-amber); }
        .vy-ledger-amt.tone-hold, .vy-ledger-amt.tone-muted { color: var(--vy-muted); }
        .vy-ledger-flag { font-size: 9px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-muted); background: rgba(120, 70, 40, 0.08); border-radius: 5px; padding: 1px 6px; }
        .vy-refund-btn { margin-left: 10px; }

        /* Totals */
        .vy-totals { margin-top: 16px; border-top: 1.5px solid rgba(120, 70, 40, 0.16); padding-top: 14px; display: flex; flex-direction: column; gap: 9px; }
        .vy-total-row { display: flex; align-items: baseline; justify-content: space-between; font-size: 13px; }
        .vy-total-row .vy-t-label { color: var(--vy-muted); }
        .vy-total-row .vy-t-val { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-total-row.is-balance { margin-top: 4px; padding-top: 11px; border-top: 1px solid var(--vy-hairline); font-size: 15px; }
        .vy-total-row.is-balance .vy-t-label { color: var(--vy-ink); font-weight: 600; font-family: var(--vy-display); }
        .vy-total-row.is-balance .vy-t-val { font-size: 20px; color: var(--vy-accent); }
        .vy-total-row.is-balance.is-settled .vy-t-val { color: var(--vy-muted); }

        /* Payment form + inputs */
        .vy-field-label { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-muted); display: block; margin-bottom: 6px; }
        .vy-input-group { display: flex; gap: 8px; align-items: stretch; }
        .vy-input { flex: 1; min-width: 0; padding: 9px 12px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.75); font-family: var(--vy-sans); font-size: 13.5px; color: var(--vy-ink); }
        .vy-input:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 1px; }
        .vy-action-list { display: flex; flex-direction: column; gap: 8px; }
        .vy-action-list .vy-defer-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 12.5px; color: var(--vy-muted); padding: 8px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-action-list .vy-defer-item:last-child { border-bottom: none; }

        .vy-recon { font-size: 11px; color: var(--vy-muted); display: inline-flex; align-items: center; gap: 5px; margin-top: 12px; }
        .vy-recon svg { color: var(--vy-accent); }

        /* ---- Operator dashboard ---------------------------------------------------------------- */
        .vy-dash-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .vy-dash-kpi {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md);
            box-shadow: var(--vy-shadow-sm); padding: 16px 18px;
        }
        .vy-dash-kpi.is-feature { background: var(--vy-grad-dark); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: var(--vy-shadow-dark); color: rgba(255, 255, 255, 0.92); }
        .vy-dash-kpi.is-feature .vy-dk-label { color: rgba(255, 255, 255, 0.55); }
        .vy-dash-kpi.is-feature .vy-dk-sub { color: rgba(255, 255, 255, 0.6); }
        .vy-dk-label { font-size: 9.5px; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-dk-val { font-family: var(--vy-display); font-size: 30px; font-weight: 700; letter-spacing: -0.02em; margin-top: 8px; }
        .vy-dk-val .vy-dk-unit { font-size: 16px; font-weight: 600; color: var(--vy-muted); margin-left: 2px; }
        .vy-dash-kpi.is-feature .vy-dk-val .vy-dk-unit { color: rgba(255,255,255,.6); }
        .vy-dk-sub { font-size: 11.5px; color: var(--vy-muted); margin-top: 5px; }
        .vy-dk-track { height: 6px; border-radius: 6px; background: rgba(255, 255, 255, 0.18); overflow: hidden; margin-top: 10px; }
        .vy-dk-track > span { display: block; height: 100%; border-radius: 6px; background: var(--vy-grad-peach); }

        .vy-dash-cols { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 22px; align-items: start; margin-top: 20px; }

        .vy-arr-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--vy-hairline); transition: background .15s ease; }
        .vy-arr-row:last-child { border-bottom: none; }
        .vy-arr-row:hover { background: rgba(255, 255, 255, 0.4); }
        .vy-arr-name { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; }
        .vy-arr-ref { font-size: 11px; color: var(--vy-muted); margin-top: 2px; }
        .vy-arr-link { font-size: 11.5px; font-weight: 600; color: var(--vy-accent); }

        .vy-hk-snapshot { display: flex; flex-direction: column; gap: 10px; }
        .vy-hk-line { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 13px; }
        .vy-hk-line .vy-hk-k { display: inline-flex; align-items: center; gap: 8px; color: var(--vy-muted); }
        .vy-hk-line .vy-hk-v { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-hk-swatch { width: 9px; height: 9px; border-radius: 50%; }

        /* ---- Units table ----------------------------------------------------------------------- */
        .vy-utable-wrap { overflow-x: auto; }
        .vy-utable { width: 100%; border-collapse: collapse; min-width: 720px; }
        .vy-utable thead th { text-align: left; font-size: 9.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint); padding: 0 14px 12px; white-space: nowrap; }
        .vy-utable tbody tr { transition: background .15s ease; }
        .vy-utable tbody tr:hover { background: rgba(255, 255, 255, 0.5); }
        .vy-utable td { padding: 13px 14px; border-top: 1px solid var(--vy-hairline); font-size: 13px; vertical-align: middle; }
        .vy-utable .vy-u-code { font-family: var(--vy-display); font-weight: 600; }
        .vy-utable .vy-u-sub { font-size: 11px; color: var(--vy-muted); margin-top: 2px; }
        .vy-u-owner { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; }
        .vy-u-owner .vy-mini-avatar { width: 18px; height: 18px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 9px; font-weight: 600; display: grid; place-items: center; }
        .vy-u-operator { font-size: 11.5px; color: var(--vy-faint); }
        .vy-u-inactive { font-size: 11px; font-weight: 600; color: var(--vy-muted); background: rgba(120, 70, 40, .08); border-radius: var(--vy-r-pill); padding: 2px 9px; }

        /* Warm unit status tags (real enums; no green/red) */
        .vy-tag.tone-clean { background: rgba(232, 112, 58, 0.14); color: var(--vy-accent); }
        .vy-tag.tone-clean .vy-dot { background: var(--vy-accent); }
        .vy-tag.tone-dirty { background: rgba(224, 146, 30, 0.15); color: var(--vy-amber); }
        .vy-tag.tone-dirty .vy-dot { background: var(--vy-amber-2); }
        .vy-tag.tone-inspected { background: rgba(66, 52, 44, 0.12); color: var(--vy-dark); }
        .vy-tag.tone-inspected .vy-dot { background: var(--vy-dark); }
        .vy-tag.tone-in-service { background: rgba(138, 128, 121, 0.14); color: var(--vy-muted); }
        .vy-tag.tone-in-service .vy-dot { background: var(--vy-muted); }
        .vy-tag.tone-ooo { background: rgba(194, 65, 12, 0.14); color: var(--vy-accent); }
        .vy-tag.tone-ooo .vy-dot { background: var(--vy-accent); }
        .vy-tag.tone-oos { background: rgba(66, 52, 44, 0.12); color: var(--vy-dark); }
        .vy-tag.tone-oos .vy-dot { background: var(--vy-dark); }

        /* ---- Responsive ------------------------------------------------------------------------ */
        @media (max-width: 1100px) {
            .vy-kanban { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vy-detail-cols { grid-template-columns: minmax(0, 1fr); }
            .vy-dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vy-dash-cols { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 860px) {
            .vy-shell { grid-template-columns: 1fr; }
            .vy-side { position: static; height: auto; flex-direction: row; flex-wrap: wrap; align-items: center; overflow-x: auto; }
            .vy-nav-group { flex-direction: row; flex-wrap: wrap; }
            .vy-nav-label { display: none; }
            .vy-main { padding: 18px 16px 48px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .vy-fade { opacity: 1; }
        }
    </style>
</head>
<body class="vy-body">
    <main>
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
