{{-- Owner-portal layout (design pass · Screen 1). A DEDICATED shell for the owner portal so the
     shared unstyled stub layout (components/layouts/app) stays untouched for the other role stubs.

     This formalizes the Claude Design "Owner" system extracted in docs/design/owner-portal-spec.md:
     a single LIGHT theme, frosted-glass surfaces (blur 22px / saturate 1.15), a warm layered shadow
     with a glossy top edge, terracotta accent (#C2410C), Sora display + Inter body, and the real
     keyframes lifted verbatim from the design bundle (vyFadeUp / vyShimmer / vyDrift / vySwapNext /
     vySwapPrev). vyAcctUp is a JS count-up behaviour (see the view), not a CSS keyframe.

     Presentation only — no data, no backend. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Vyrobel — Owner Portal' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">

    @livewireStyles

    <style>
        /* ---- Formalized design tokens (the design inlined these literals; named here) --------- */
        :root {
            --vy-ink: rgb(43, 35, 32);          /* espresso near-black */
            --vy-muted: rgb(138, 128, 121);
            --vy-faint: rgb(166, 154, 146);
            --vy-accent: rgb(194, 65, 12);      /* terracotta primary  (#C2410C) */
            --vy-accent-2: rgb(217, 102, 46);
            --vy-accent-3: rgb(232, 112, 58);   /* (#E8703A) */
            --vy-peach: rgb(240, 165, 122);
            --vy-amber: rgb(178, 106, 8);       /* pending / warning */
            --vy-amber-2: rgb(224, 146, 30);
            --vy-green: rgb(31, 122, 90);        /* paid / positive */
            --vy-dark: rgb(42, 33, 28);          /* dark feature card */

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
            --vy-shadow-dark: rgba(60, 30, 10, 0.28) 0 10px 30px;

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

        /* Ambient drifting highlight behind everything */
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

        /* ---- The real design keyframes (lifted verbatim from the design bundle) --------------- */
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes vyShimmer { 0% { opacity: .25; transform: translateX(-4%) rotate(28deg); } 50% { opacity: .45; transform: translateX(3%) rotate(28deg); } 100% { opacity: .25; transform: translateX(-4%) rotate(28deg); } }
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(46px, 28px); } 100% { transform: translate(0, 0); } }
        @keyframes vySwapNext { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes vySwapPrev { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        /* ---- App shell ----------------------------------------------------------------------- */
        .vy-shell { display: grid; grid-template-columns: 232px minmax(0, 1fr); min-height: 100vh; }

        /* Sidebar */
        .vy-side {
            position: sticky; top: 0; align-self: start; height: 100vh;
            padding: 22px 16px; display: flex; flex-direction: column; gap: 20px;
            border-right: 1px solid var(--vy-hairline);
            background: rgba(255, 253, 250, 0.5);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            overflow-y: auto;
        }
        .vy-brand { display: flex; align-items: center; gap: 10px; padding: 4px 6px 8px; }
        .vy-brand-mark {
            width: 34px; height: 34px; border-radius: var(--vy-r-md); background: var(--vy-grad);
            color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 18px;
            display: grid; place-items: center; box-shadow: var(--vy-shadow-accent);
        }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 16px; letter-spacing: -0.01em; }
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
        .vy-main { padding: 22px 30px 60px; max-width: 1320px; }
        .vy-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .vy-topbar h1 { font-family: var(--vy-display); font-weight: 600; font-size: 24px; letter-spacing: -0.02em; margin: 0; }
        .vy-topbar-actions { display: flex; align-items: center; gap: 12px; }

        .vy-bell { position: relative; width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-bell .vy-badge { position: absolute; top: -3px; right: -3px; min-width: 17px; height: 17px; padding: 0 4px; border-radius: 50%; background: var(--vy-accent); color: #fff; font-size: 10.5px; font-weight: 600; display: grid; place-items: center; }
        .vy-chip { display: flex; align-items: center; gap: 9px; padding: 5px 12px 5px 6px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 12px; font-weight: 600; display: grid; place-items: center; }
        .vy-chip-name { font-size: 13px; font-weight: 500; }

        /* ---- Reusable surfaces --------------------------------------------------------------- */
        .vy-card {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 22px;
        }
        .vy-card--accent { border-color: var(--vy-accent-border); }
        .vy-card--dark {
            background: var(--vy-grad-dark); color: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: var(--vy-shadow-dark);
        }
        .vy-card--dark .vy-eyebrow { color: rgba(255, 255, 255, 0.55); }

        .vy-eyebrow { font-size: 10.5px; font-weight: 600; letter-spacing: 0.11em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-card-title { font-family: var(--vy-display); font-size: 15px; font-weight: 600; margin: 0; }

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
        .vy-btn:focus-visible, .vy-nav-item:focus-visible, .vy-pill:focus-visible, .vy-period-btn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* A control that has no backend yet: visible, clearly non-functional, never a fake action. */
        .vy-btn.is-soon, .vy-icobtn.is-soon { opacity: .5; cursor: not-allowed; }
        .vy-soon-tag { font-size: 9.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 2px 6px; }

        .vy-pill {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: var(--vy-r-pill);
            font-size: 12.5px; font-weight: 500; color: var(--vy-muted); cursor: pointer;
            background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); transition: all .16s ease;
        }
        .vy-pill:hover { color: var(--vy-ink); }
        .vy-pill.is-active { background: var(--vy-ink); color: #fff; border-color: transparent; }

        .vy-icobtn { width: 32px; height: 32px; border-radius: var(--vy-r-sm); display: grid; place-items: center; background: rgba(255, 255, 255, 0.55); border: 1px solid var(--vy-glass-border); color: var(--vy-muted); cursor: pointer; }

        /* Status badges */
        .vy-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: var(--vy-r-pill); font-size: 12px; font-weight: 600; }
        .vy-status .vy-dot { width: 7px; height: 7px; border-radius: 50%; }
        .vy-status--pending { background: rgba(224, 146, 30, 0.14); color: var(--vy-amber); }
        .vy-status--pending .vy-dot { background: var(--vy-amber-2); }
        .vy-status--paid { background: rgba(31, 122, 90, 0.13); color: var(--vy-green); }
        .vy-status--paid .vy-dot { background: var(--vy-green); }
        .vy-status--draft { background: rgba(138, 128, 121, 0.15); color: var(--vy-muted); }
        .vy-status--draft .vy-dot { background: var(--vy-muted); }

        .vy-provisional { font-size: 10.5px; font-weight: 600; color: var(--vy-amber); background: rgba(224, 146, 30, 0.12); border-radius: 6px; padding: 2px 7px; letter-spacing: .02em; }

        /* Progress bar */
        .vy-track { height: 7px; border-radius: 6px; background: rgba(120, 70, 40, 0.1); overflow: hidden; }
        .vy-track > span { display: block; height: 100%; border-radius: 6px; background: var(--vy-grad-peach); }

        /* Layout regions for the statement page */
        .vy-cols { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 22px; align-items: start; }
        .vy-stack { display: flex; flex-direction: column; gap: 22px; }

        /* Statement hero */
        .vy-hero-figure { font-family: var(--vy-display); font-weight: 700; font-size: 58px; line-height: 1; letter-spacing: -0.03em; margin: 6px 0 0; }
        .vy-hero-sub { color: var(--vy-muted); font-size: 13px; margin-top: 10px; }

        /* Period selector */
        .vy-period { display: inline-flex; align-items: center; gap: 8px; }
        .vy-period-btn { width: 36px; height: 36px; border-radius: var(--vy-r-sm); display: grid; place-items: center; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); color: var(--vy-ink); cursor: pointer; }
        .vy-period-btn[aria-disabled="true"] { opacity: .4; cursor: default; }
        .vy-period-cur { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.7); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); font-weight: 600; font-size: 13.5px; }
        .vy-swap-next { animation: vySwapNext .4s ease; }

        /* Breakdown rows */
        .vy-line { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; padding: 15px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-line:last-child { border-bottom: none; }
        .vy-line-label { font-size: 13.5px; font-weight: 500; }
        .vy-line-sub { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; }
        .vy-line-amt { font-family: var(--vy-display); font-size: 15px; font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-line-amt.is-neg { color: var(--vy-accent); }
        .vy-line--net { margin-top: 4px; padding-top: 18px; border-top: 1.5px solid rgba(120, 70, 40, 0.16); }
        .vy-line--net .vy-line-label { font-family: var(--vy-display); font-size: 15px; font-weight: 600; }
        .vy-line--net .vy-line-amt { color: var(--vy-accent); font-size: 22px; }

        /* History table */
        .vy-table { width: 100%; border-collapse: collapse; }
        .vy-table thead th { text-align: left; font-size: 9.5px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--vy-faint); padding: 0 14px 12px; }
        .vy-table thead th.vy-num, .vy-table td.vy-num { text-align: right; }
        .vy-table tbody tr { transition: background .15s ease; }
        .vy-table tbody tr:hover { background: rgba(255, 255, 255, 0.5); }
        .vy-table tbody tr.is-current { background: rgba(232, 112, 58, 0.09); }
        .vy-table td { padding: 13px 14px; border-top: 1px solid var(--vy-hairline); font-size: 13.5px; vertical-align: middle; }
        .vy-table .vy-mono { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-table .vy-net { color: var(--vy-accent); }
        .vy-row-month { font-weight: 600; }
        .vy-row-sub { font-size: 11px; color: var(--vy-muted); margin-top: 2px; }
        .vy-row-link { display: block; }

        /* Right-rail dark next-payout */
        .vy-dark-figure { font-family: var(--vy-display); font-weight: 700; font-size: 30px; letter-spacing: -0.02em; margin: 8px 0 2px; }

        /* Deferred stub block (visually present, honestly labelled) */
        .vy-deferred { border: 1px dashed var(--vy-hairline); border-radius: var(--vy-r-md); padding: 16px; color: var(--vy-muted); font-size: 12.5px; background: rgba(255, 255, 255, 0.28); }
        .vy-deferred .vy-eyebrow { display: block; margin-bottom: 6px; }
        .vy-skeleton { height: 7px; border-radius: 6px; background: linear-gradient(90deg, rgba(120,70,40,.08), rgba(120,70,40,.16), rgba(120,70,40,.08)); background-size: 200% 100%; }

        .vy-fx-note { font-size: 11.5px; color: var(--vy-muted); margin-top: 8px; }
        .vy-muted { color: var(--vy-muted); }
        .vy-empty { text-align: center; color: var(--vy-muted); padding: 40px 20px; font-size: 13.5px; }

        /* ---- Dashboard components (shared) --------------------------------------------------- */
        .vy-kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .vy-kpi {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md);
            box-shadow: var(--vy-shadow-sm); padding: 16px 18px;
        }
        .vy-kpi-label { font-size: 9.5px; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-kpi-val { font-family: var(--vy-display); font-size: 30px; font-weight: 700; letter-spacing: -0.02em; margin-top: 8px; }
        .vy-kpi-val.is-deferred { color: var(--vy-faint); }
        .vy-kpi-sub { font-size: 11.5px; color: var(--vy-muted); margin-top: 4px; }

        .vy-delta { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: var(--vy-r-pill); font-size: 12px; font-weight: 600; }
        .vy-delta--up { background: rgba(31, 122, 90, .12); color: var(--vy-green); }
        .vy-delta--down { background: rgba(224, 146, 30, .14); color: var(--vy-amber); }

        .vy-chart { display: flex; align-items: flex-end; gap: 10px; height: 150px; padding-top: 8px; }
        .vy-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 8px; min-width: 0; height: 100%; }
        .vy-bar { width: 100%; max-width: 44px; border-radius: 8px 8px 4px 4px; background: var(--vy-grad-peach); transition: height .3s ease; }
        .vy-bar.is-last { background: var(--vy-grad); }
        .vy-bar-label { font-size: 10.5px; color: var(--vy-muted); }

        .vy-list-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-list-row:last-child { border-bottom: none; }
        .vy-list-row:hover .vy-row-month { color: var(--vy-accent); }
        .vy-quick { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

        /* Rail pill (e.g. "Bank transfer") */
        .vy-rail { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: var(--vy-r-pill); background: rgba(120, 70, 40, .07); font-size: 11.5px; font-weight: 500; color: var(--vy-muted); }

        /* Payout lifecycle stepper (rendered on the dark next-payout card, REAL states only) */
        .vy-steps { display: flex; align-items: center; gap: 8px; margin-top: 16px; }
        .vy-step { display: flex; align-items: center; gap: 6px; }
        .vy-step-dot { width: 11px; height: 11px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .28); flex: none; }
        .vy-step-label { font-size: 11px; color: rgba(255, 255, 255, .6); white-space: nowrap; }
        .vy-step-line { flex: 1; height: 2px; min-width: 14px; background: rgba(255, 255, 255, .15); border-radius: 2px; }
        .vy-step.is-done .vy-step-dot { background: var(--vy-accent-3); border-color: var(--vy-accent-3); }
        .vy-step.is-done + .vy-step-line { background: var(--vy-accent-3); }
        .vy-step.is-active .vy-step-dot { background: #fff; border-color: #fff; box-shadow: 0 0 0 4px rgba(255, 255, 255, .14); }
        .vy-step.is-active .vy-step-label { color: #fff; font-weight: 600; }
        .vy-step.is-failed .vy-step-dot { background: var(--vy-amber-2); border-color: var(--vy-amber-2); }
        .vy-step.is-failed .vy-step-label { color: var(--vy-amber-2); }
        .vy-step.is-reversed .vy-step-dot { background: var(--vy-faint); border-color: var(--vy-faint); }

        /* Unit cards (My Units) */
        .vy-unit-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
        .vy-unit-card {
            border-radius: var(--vy-r-lg); overflow: hidden; background: var(--vy-glass);
            backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow);
        }
        .vy-unit-cover { height: 108px; overflow: hidden; }
        .vy-unit-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vy-unit-head { background: var(--vy-grad); color: #fff; padding: 16px 18px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .vy-unit-head .vy-unit-name { font-family: var(--vy-display); font-weight: 600; font-size: 15px; }
        .vy-unit-badge { font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, .22); }
        .vy-unit-body { padding: 16px 18px; }
        .vy-unit-stat { display: flex; justify-content: space-between; font-size: 12.5px; padding: 5px 0; }
        .vy-unit-stat .vy-muted { color: var(--vy-muted); }

        /* Occupancy calendar heatmap (owner-scoped) */
        .vy-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
        .vy-cal-head { font-size: 9.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); text-align: center; padding-bottom: 2px; }
        .vy-cal-cell { aspect-ratio: 1; border-radius: var(--vy-r-sm); display: flex; align-items: flex-start; justify-content: flex-end; padding: 5px 7px; font-size: 11.5px; font-weight: 500; border: 1px solid rgba(120, 70, 40, .06); }
        .vy-cal-empty { aspect-ratio: 1; }
        .vy-cal-legend { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--vy-muted); margin-top: 14px; flex-wrap: wrap; }
        .vy-cal-swatch { width: 15px; height: 15px; border-radius: 4px; border: 1px solid rgba(120, 70, 40, .1); }

        /* ---- Responsive ---------------------------------------------------------------------- */
        @media (max-width: 1100px) {
            .vy-cols { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 860px) {
            .vy-shell { grid-template-columns: 1fr; }
            .vy-side { position: static; height: auto; flex-direction: row; flex-wrap: wrap; align-items: center; overflow-x: auto; }
            .vy-nav-group { flex-direction: row; flex-wrap: wrap; }
            .vy-nav-label { display: none; }
            .vy-main { padding: 18px 16px 48px; }
            .vy-hero-figure { font-size: 44px; }
        }

        /* ---- Motion & accessibility floor ---------------------------------------------------- */
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
