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

    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">

    @livewireStyles

    <style>
        /* ---- Formalized design tokens (the design inlined these literals; named here) --------- */
        :root {
            --vy-ink: rgb(43, 35, 32);          /* espresso near-black */
            --vy-muted: rgb(95, 88, 83);
            --vy-faint: rgb(108, 100, 95);

            /* Text that sits DIRECTLY on the page gradient rather than on a glass card. --vy-bg here is
               byte-identical to the operator shell's, so these carry the same measured values: 4.97:1 and
               4.67:1 against the gradient's darkest sampled stop, rgb(241,185,150). Warm stone, not grey. */
            --vy-on-grad: rgb(84, 74, 68);
            --vy-on-grad-soft: rgb(88, 78, 72);

            /* Accent used as TEXT on glass. --vy-accent stays the BRAND fill (gradients/buttons/chips/
               borders) and is unchanged; see the operator shell for the measurements. */
            --vy-accent-text: rgb(176, 56, 8);

            /* Text that sits on its OWN low-alpha TINT (status pills, tag chips, the delta pill). The
               operator realm left its tinted chips alone because they measured 4.61 — but the OWNER glass
               is warmer and darker, so the SAME tint+text pairs measure 3.26–3.85 here (real-pixel
               sampled, not computed from the declared background). These three are the tint-safe
               counterparts; the plain --vy-green / --vy-amber / --vy-accent stay the BRAND fills (dots,
               icons, gradients, borders) and are unchanged. Measured after: 4.71 / 4.76 / 4.75. */
            /* ⚠️ Re-tuned at the Payouts gate, for the SAME reason the amber was at the Statement gate:
               it was fitted to the Statement/Dashboard rows and read 4.31–4.44 on the Payouts table's
               warmer rows. Now measured against the WORST background it meets — 5.86–6.04 there, and
               6.41–6.51 back on the cooler rows. Tune a token to the worst case, never the first. */
            --vy-green-text: rgb(20, 84, 62);
            /* ⚠️ Re-tuned at the Statement gate. Owner-1 set this to rgb(141,84,6) from ONE sampled
               background; on the Statement's slightly warmer glass the same pill measured 4.45–4.50 —
               marginal, one side of AA. Tuned against the WORST of the sampled backgrounds instead of
               the first: now 5.13–5.22 everywhere it is used. A token tuned to a single sample is a
               token that will fail on the next page. */
            --vy-amber-text: rgb(128, 76, 5);
            --vy-accent-tint-text: rgb(151, 54, 13);

            /* The SAME status pill also sits on the dark payout rail (.vy-card--dark), where every tone
               failed AA — paid 1.77, draft 1.65, pending 2.72 before the tint fix and 1.87 after. On a
               dark panel the accessible end of each ramp is the LIGHT one, so these are not "darker"
               variants: they are the mirror. Measured on the rail's sampled pixel: 4.56 / 5.07 / 6.00. */
            /* ⚠️ Measured against the pill's OWN tinted background (81,57,32), not the bare panel —
               --vy-amber-2 reads 4.24 there, still short. Tuned to the composited value: 5.10. */
            --vy-amber-on-dark: rgb(235, 165, 60);
            --vy-green-on-dark: rgb(90, 190, 150);
            --vy-muted-on-dark: rgb(201, 184, 172);

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
            --vy-wash: #F6EBE2;                   /* Polish Batch 1 — the faint warm-cream tint (review/team tag chips, washes). Resolves the previously-fallback-only token. */
            --vy-bg: linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%);  /* Owner-Overview exact-match — the decoded wireframe's warm peach→grey diagonal page background. */
            --vy-orb-1: #E8703A;                 /* the three ambient color orbs behind the frosted glass (decoded from the wireframe) */
            --vy-orb-2: #F0A57A;
            --vy-orb-3: #FBE3CE;

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
        [x-cloak] { display: none !important; }   /* prevent a flash of the notification panel before Alpine boots */

        html, body { margin: 0; padding: 0; }

        body.vy-body {
            font-family: var(--vy-sans);
            color: var(--vy-ink);
            font-size: 16px;
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            min-height: 100vh;
            background: var(--vy-bg);
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient light streaks + floating color orbs (decoded from the wireframe) — behind everything,
           they make the frosted glass visible. z-index 0; content sits at z-index 1. The animated ones are
           stilled by the reduced-motion floor below. */
        .vy-amb { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .vy-amb > div { position: absolute; pointer-events: none; }
        .vy-amb-streak1 { top: -180px; right: -120px; width: 640px; height: 640px; background: radial-gradient(circle, rgba(255, 255, 255, .35), transparent 68%); filter: blur(10px); }
        .vy-amb-streak2 { top: 22%; left: -10%; width: 70%; height: 260px; background: linear-gradient(120deg, rgba(255, 240, 220, .5), transparent); transform: rotate(28deg); filter: blur(24px); animation: vyShimmer 9s ease-in-out infinite; }
        .vy-amb-orb { width: 380px; height: 380px; border-radius: 50%; filter: blur(70px); opacity: .55; }
        .vy-amb-orb1 { top: 80px; left: 24%; width: 380px; height: 380px; background: var(--vy-orb-1); border-radius: 50%; filter: blur(70px); opacity: .55; animation: vyDrift 14s ease-in-out infinite; }
        .vy-amb-orb2 { top: 470px; left: 36%; width: 320px; height: 320px; background: var(--vy-orb-2); border-radius: 50%; filter: blur(70px); opacity: .55; }
        .vy-amb-orb3 { top: 220px; right: 4%; width: 300px; height: 300px; background: var(--vy-orb-3); border-radius: 50%; filter: blur(70px); opacity: .55; }

        main { position: relative; z-index: 1; }

        a { color: inherit; text-decoration: none; }

        /* ---- The real design keyframes (lifted verbatim from the design bundle) --------------- */
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes vyShimmer { 0% { opacity: .25; transform: translateX(-4%) rotate(28deg); } 50% { opacity: .45; transform: translateX(3%) rotate(28deg); } 100% { opacity: .25; transform: translateX(-4%) rotate(28deg); } }
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(46px, 28px); } 100% { transform: translate(0, 0); } }
        @keyframes vySwapNext { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes vySwapPrev { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        /* ---- App shell ----------------------------------------------------------------------- */
        .vy-shell { display: grid; grid-template-columns: 200px minmax(0, 1fr); min-height: 100vh; }

        /* Sidebar — the frosted nav floats directly on the gradient (no panel bg / border), exact-match. */
        .vy-side {
            position: sticky; top: 0; align-self: start; height: 100vh;
            padding: 26px 16px; display: flex; flex-direction: column; gap: 26px;
            overflow-y: auto;
            animation: vyFadeUp .45s cubic-bezier(.22, .61, .36, 1) both;
        }
        .vy-brand { display: flex; align-items: center; gap: 10px; padding: 0 8px; }
        .vy-brand-mark {
            width: 34px; height: 34px; border-radius: var(--vy-r-sm); background: var(--vy-grad);
            color: #fff; font-family: var(--vy-display); font-weight: 600; font-size: 17px;
            display: grid; place-items: center; box-shadow: var(--vy-shadow-accent);
        }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 17px; letter-spacing: -0.01em; }
        .vy-nav { display: flex; flex-direction: column; gap: 16px; overflow-y: auto; min-height: 0; }
        .vy-nav-group { display: flex; flex-direction: column; gap: 3px; }
        .vy-nav-label { font-size: 10.5px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--vy-on-grad-soft); padding: 0 12px 6px; }
        .vy-nav-item {
            display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: var(--vy-r-md);
            font-size: 13.5px; font-weight: 500; color: var(--vy-on-grad); cursor: pointer;
            transition: background .16s ease, color .16s ease;
        }
        .vy-nav-item .vy-ico { width: 16px; height: 16px; flex: none; }
        .vy-nav-item:hover { background: rgba(255, 255, 255, 0.35); }
        /* ⚠️ --vy-accent measured 4.49 on the active pill's own frosted background — a hair under AA, on the
           one nav item that tells the owner where they are. --vy-accent-text reads 5.34. */
        .vy-nav-item.is-active {
            background: rgba(255, 255, 255, 0.72); color: var(--vy-accent-text); font-weight: 500;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 14px rgba(150, 90, 50, 0.14); border: 1px solid rgba(255, 255, 255, 0.8);
        }
        .vy-nav-item[aria-disabled="true"] { cursor: default; opacity: .55; }
        .vy-nav-item[aria-disabled="true"]:hover { background: transparent; color: var(--vy-on-grad); }

        /* Main column */
        .vy-main { padding: 30px 34px 40px 22px; max-width: 1314px; }
        .vy-topbar { position: relative; z-index: 60; display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; animation: vyFadeUp .45s cubic-bezier(.22, .61, .36, 1) .06s both; }
        .vy-topbar h1 { font-family: var(--vy-display); font-weight: 600; font-size: 24px; letter-spacing: -0.02em; margin: 0; }
        .vy-topbar h1.is-greeting { font-size: 30px; }
        .vy-topbar-actions { display: flex; align-items: center; gap: 10px; }

        /* Notification bell — 42px frosted glass (exact-match). Scoped to the owner topbar; the `!important`
           beats the shared bell component's inline background:none/border:none (operator shell is a separate file). */
        .vy-topbar .vy-bell {
            width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center;
            background: rgba(255, 252, 248, 0.62) !important; border: 1px solid rgba(255, 255, 255, 0.75) !important;
            box-shadow: 0 14px 40px rgba(150, 90, 50, .12), inset 0 1px 0 rgba(255, 255, 255, .65);
            backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            color: var(--vy-ink); transition: transform .14s ease;
        }
        .vy-topbar .vy-bell:hover { transform: translateY(-1px); }

        /* Account menu — a dropdown (avatar + name + chevron → Profile / Security / Sign out), exact-match. */
        .vy-acct { position: relative; }
        .vy-acct-btn {
            display: inline-flex; align-items: center; gap: 9px; padding: 5px 10px 5px 14px; border-radius: var(--vy-r-pill);
            background: rgba(255, 252, 248, 0.62); border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow: 0 8px 24px rgba(150, 90, 50, .10), inset 0 1px 0 rgba(255, 255, 255, .6);
            backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            color: var(--vy-ink); font-family: var(--vy-sans); cursor: pointer; transition: transform .14s ease;
        }
        .vy-acct-btn:hover { transform: translateY(-1px); }
        .vy-acct-name { font-size: 13px; font-weight: 500; }
        .vy-acct-btn .vy-avatar { width: 30px; height: 30px; }
        .vy-acct-chev { width: 15px; height: 15px; color: var(--vy-faint); transition: transform .18s ease; }
        .vy-acct-btn[aria-expanded="true"] .vy-acct-chev { transform: rotate(180deg); }
        .vy-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 12px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vy-acct-menu {
            position: absolute; top: calc(100% + 10px); right: 0; z-index: 70; min-width: 210px; padding: 6px;
            background: rgba(255, 252, 248, 0.97); border: 1px solid rgba(255, 255, 255, 0.85); border-radius: var(--vy-r-md);
            box-shadow: 0 24px 60px rgba(60, 30, 10, .22), inset 0 1px 0 rgba(255, 255, 255, .75);
            backdrop-filter: blur(30px) saturate(1.15); -webkit-backdrop-filter: blur(30px) saturate(1.15);
        }
        .vy-acct-head { padding: 9px 11px 8px; border-bottom: 1px solid var(--vy-hairline); margin-bottom: 5px; }
        .vy-acct-head .n { font-family: var(--vy-display); font-weight: 600; font-size: 13.5px; }
        .vy-acct-head .r { font-size: 11px; color: var(--vy-muted); margin-top: 2px; }
        .vy-acct-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px; border: none; background: transparent; border-radius: var(--vy-r-sm); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); text-decoration: none; text-align: left; cursor: pointer; transition: background .14s ease; }
        .vy-acct-item:hover { background: rgba(120, 70, 40, .06); }
        .vy-acct-item svg { width: 16px; height: 16px; color: var(--vy-muted); flex: none; }
        .vy-acct-item.is-danger { color: var(--vy-accent-text); }
        .vy-acct-item.is-danger svg { color: var(--vy-accent-text); }
        .vy-acct-sep { height: 1px; background: var(--vy-hairline); margin: 5px 6px; }
        .vy-chip { display: flex; align-items: center; gap: 9px; padding: 5px 12px 5px 6px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-chip-name { font-size: 13px; font-weight: 500; }

        /* ---- Reusable surfaces --------------------------------------------------------------- */
        .vy-card {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 22px;
        }
        .vy-card--accent { border-color: var(--vy-accent-border); }
        /* Polish Batch 2 — the shared hover for an interactive TAP-TARGET card (opt-in; e.g. the real unit cards, NOT the
           dashed "List a new property · Soon" placeholder). Prototype-matched: a small lift + an elevated shadow. */
        .vy-card--tappable { cursor: pointer; transition: transform .16s ease, box-shadow .16s ease; }
        .vy-card--tappable:hover { transform: translateY(-2px); box-shadow: 0 18px 44px rgba(150, 90, 50, .18); }
        .vy-card--dark {
            background: var(--vy-grad-dark); color: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: var(--vy-shadow-dark);
        }
        .vy-card--dark .vy-eyebrow { color: rgba(255, 255, 255, 0.55); }
        /* Polish Batch 3 — child overrides so ANY .vy-card--dark styles its content for the dark surface (anti-drift:
           the Booking-Detail NET-TO-YOU rail + the Tax-Center "About your tax" panel reuse this, not a bespoke dark style).
           .vy-hero-figure has no own color so it inherits the panel's white; these set the muted/title text light. */
        .vy-card--dark .vy-line-sub { color: rgba(255, 255, 255, 0.6); }
        .vy-card--dark .vy-card-title { color: #fff; }
        .vy-card--dark .vy-hr { border-color: rgba(255, 255, 255, 0.14); }

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
        .vy-status--pending { background: rgba(224, 146, 30, 0.14); color: var(--vy-amber-text); }
        .vy-status--pending .vy-dot { background: var(--vy-amber-2); }
        .vy-status--paid { background: rgba(31, 122, 90, 0.13); color: var(--vy-green-text); }
        .vy-status--paid .vy-dot { background: var(--vy-green); }
        .vy-status--draft { background: rgba(138, 128, 121, 0.15); color: var(--vy-muted); }
        .vy-status--draft .vy-dot { background: var(--vy-muted); }
        /* ⚠️ ON THE DARK PAYOUT RAIL, DARKENING IS BACKWARDS — every tone of this pill also renders on
           .vy-card--dark, and every one of them failed AA there (paid 1.77, draft 1.65, pending 1.87).
           The dot is decorative (each pill already carries its status in WORDS, so this is not
           colour-only per 1.4.1) but the LABEL has to be readable. Mirrored, not darkened. */
        .vy-card--dark .vy-status--pending { background: rgba(224, 146, 30, .18); color: var(--vy-amber-on-dark); }
        .vy-card--dark .vy-status--pending .vy-dot { background: var(--vy-amber-on-dark); }
        .vy-card--dark .vy-status--paid { background: rgba(90, 190, 150, .16); color: var(--vy-green-on-dark); }
        .vy-card--dark .vy-status--paid .vy-dot { background: var(--vy-green-on-dark); }
        .vy-card--dark .vy-status--draft { background: rgba(201, 184, 172, .16); color: var(--vy-muted-on-dark); }
        .vy-card--dark .vy-status--draft .vy-dot { background: var(--vy-muted-on-dark); }

        .vy-provisional { font-size: 10.5px; font-weight: 600; color: var(--vy-amber-text); background: rgba(224, 146, 30, 0.12); border-radius: 6px; padding: 2px 7px; letter-spacing: .02em; }

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
        .vy-line-amt.is-neg { color: var(--vy-accent-text); }
        .vy-line--net { margin-top: 4px; padding-top: 18px; border-top: 1.5px solid rgba(120, 70, 40, 0.16); }
        .vy-line--net .vy-line-label { font-family: var(--vy-display); font-size: 15px; font-weight: 600; }
        .vy-line--net .vy-line-amt { color: var(--vy-accent-text); font-size: 22px; }

        /* History table */
        .vy-table { width: 100%; border-collapse: collapse; }
        /* ⚠️ --vy-faint measured 4.47 here (a 9.5px column header on the warm glass) — under AA by a
           hair, on the labels that say what each money column IS. --vy-muted reads 5.39. */
        .vy-table thead th { text-align: left; font-size: 9.5px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--vy-muted); padding: 0 14px 12px; }
        .vy-table thead th.vy-num, .vy-table td.vy-num { text-align: right; }
        .vy-table tbody tr { transition: background .15s ease; }
        .vy-table tbody tr:hover { background: rgba(255, 255, 255, 0.5); }
        .vy-table tbody tr.is-current { background: rgba(232, 112, 58, 0.09); }
        .vy-table td { padding: 13px 14px; border-top: 1px solid var(--vy-hairline); font-size: 13.5px; vertical-align: middle; }
        .vy-table .vy-mono { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-table .vy-net { color: var(--vy-accent-text); }
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
        /* Count modifier: 4-up KPI band (opt-in — existing repeat(3) pages are untouched). */
        .vy-kpi-grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        @media (max-width: 900px) { .vy-kpi-grid--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        /* Count modifier: 2-up KPI band (opt-in — 2 uniform tiles span the full band width instead of leaving an empty 3rd column). */
        .vy-kpi-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 640px) { .vy-kpi-grid--2 { grid-template-columns: 1fr; } }
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
        {{-- ⚠️ The `.vy-delta--up/--down` tone pair that used to sit here was DEAD: the Overview block
             below redeclares both at equal specificity, so the later rule won every property on every
             page that uses the class (only this layout + the Overview do). Two definitions of one
             concept is exactly the drift hazard — the single live pair is now in the Overview block. --}}

        .vy-chart { display: flex; align-items: flex-end; gap: 10px; height: 150px; padding-top: 8px; }
        .vy-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 8px; min-width: 0; height: 100%; }
        .vy-bar { width: 100%; max-width: 44px; border-radius: 8px 8px 4px 4px; background: var(--vy-grad-peach); transition: height .3s ease; }
        .vy-bar.is-last { background: var(--vy-grad); }
        .vy-bar-label { font-size: 10.5px; color: var(--vy-muted); }

        .vy-list-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-list-row:last-child { border-bottom: none; }
        .vy-list-row:hover .vy-row-month { color: var(--vy-accent-text); }

        /* ============================================================================================
           OWNER OVERVIEW — exact-match components (decoded from docs/design/raw/Owner Dashboard.html).
           Scoped `.vy-ov-*` so they apply ONLY to the Overview; the shared .vy-card / .vy-card--dark /
           .vy-eyebrow / .vy-hero-figure / .vy-dark-figure / .vy-track / .vy-status are reused as-is (they
           already match the wireframe glass surfaces exactly). .vy-delta is Overview-only (restyled below).
           ============================================================================================ */
        .vy-ov-cols { display: grid; grid-template-columns: minmax(0, 1fr) 232px; gap: 20px; align-items: start; }
        .vy-ov-stack { display: flex; flex-direction: column; gap: 20px; }
        @media (max-width: 1100px) { .vy-ov-cols { grid-template-columns: minmax(0, 1fr); } }

        /* Hero delta pill (Overview-only) — the wireframe's terracotta "+8% vs …" accent for a positive MoM. */
        .vy-delta--up { background: rgba(217, 102, 46, .13); color: var(--vy-accent-tint-text); }
        /* --down keeps the wireframe's deeper #8F3110 (already darker than --vy-accent-tint-text on a
           lighter .09 tint); measured 6.05 — changing it would have been a regression, so it stands. */
        .vy-delta--down { background: rgba(143, 49, 16, .09); color: #8F3110; }

        /* Quick actions — 4 frosted glass pill buttons with a terracotta icon. */
        .vy-ov-qa { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 22px; }
        .vy-ov-qa-btn {
            display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; border-radius: var(--vy-r-pill);
            background: rgba(255, 252, 248, .62); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid rgba(255, 255, 255, .75); box-shadow: 0 8px 24px rgba(150, 90, 50, .10), inset 0 1px 0 rgba(255, 255, 255, .6);
            color: #4A3F38; font-family: var(--vy-sans); font-size: 13px; font-weight: 500; text-decoration: none; white-space: nowrap;
            cursor: pointer; transition: transform .14s ease, box-shadow .14s ease;
        }
        .vy-ov-qa-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(150, 90, 50, .16), inset 0 1px 0 rgba(255, 255, 255, .6); }
        .vy-ov-qa-btn svg { color: var(--vy-accent-text); flex: none; }

        /* Needs-your-attention bar. */
        .vy-ov-attn {
            position: relative; background: rgba(255, 252, 248, .6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-accent-border); border-radius: var(--vy-r-lg);
            box-shadow: 0 14px 40px rgba(150, 90, 50, .14), inset 0 1px 0 rgba(255, 255, 255, .65); padding: 18px 20px; margin-bottom: 20px;
        }
        .vy-ov-attn-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .vy-ov-attn-head.has-chips { margin-bottom: 14px; }
        .vy-ov-attn-l { display: flex; align-items: center; gap: 12px; }
        .vy-ov-attn-icon { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: var(--vy-r-md); background: var(--vy-grad); box-shadow: 0 5px 14px rgba(194, 65, 12, .30); flex: none; color: #fff; }
        .vy-ov-attn-t { display: flex; align-items: center; gap: 9px; }
        .vy-ov-attn-t h2 { margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 16.5px; letter-spacing: -.01em; }
        .vy-ov-attn-count { min-width: 21px; height: 21px; padding: 0 6px; box-sizing: border-box; border-radius: 20px; background: var(--vy-grad); color: #fff; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .vy-ov-attn-sub { font-size: 12.5px; color: var(--vy-muted); margin-top: 2px; }
        .vy-ov-attn-link { display: inline-flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 500; color: var(--vy-accent-text); text-decoration: none; flex: none; transition: gap .14s ease; }
        .vy-ov-attn-link:hover { gap: 8px; }
        .vy-ov-chips { display: flex; gap: 12px; flex-wrap: wrap; }
        .vy-ov-chip { flex: 1; min-width: 240px; display: flex; align-items: center; gap: 11px; padding: 12px 13px; border-radius: 14px; background: rgba(255, 255, 255, .5); border: 1px solid rgba(255, 255, 255, .72); text-decoration: none; color: var(--vy-ink); transition: transform .14s ease, box-shadow .14s ease, background .14s ease; }
        .vy-ov-chip:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(150, 90, 50, .14); background: rgba(255, 255, 255, .72); }
        .vy-ov-chip-ic { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: var(--vy-r-sm); flex: none; }
        .vy-ov-chip-main { min-width: 0; flex: 1; }
        .vy-ov-chip-title { display: block; font-size: 13px; font-weight: 600; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-ov-chip-sub { display: block; font-size: 11.5px; color: var(--vy-faint); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-ov-chip-tag { flex: none; padding: 3px 9px; border-radius: 20px; font-size: 10.5px; font-weight: 600; }
        .vy-ov-chip.is-warn .vy-ov-chip-ic { background: rgba(224, 146, 30, .15); color: var(--vy-amber); }
        .vy-ov-chip.is-warn .vy-ov-chip-tag { background: rgba(224, 146, 30, .15); color: var(--vy-amber-text); }
        .vy-ov-chip.is-info .vy-ov-chip-ic { background: rgba(232, 112, 58, .13); color: var(--vy-accent); }
        .vy-ov-chip.is-info .vy-ov-chip-tag { background: rgba(232, 112, 58, .13); color: var(--vy-accent-tint-text); }
        /* The wireframe's dashed "+N more" chip — the attention row shows at most 3 and counts the rest. */
        .vy-ov-chip-more { display: inline-flex; align-items: center; justify-content: center; gap: 5px; align-self: stretch; min-width: 92px; padding: 12px 16px; border-radius: 14px; background: rgba(255, 255, 255, .35); border: 1px dashed rgba(120, 70, 40, .22); text-decoration: none; color: var(--vy-accent-text); font-size: 12.5px; font-weight: 600; white-space: nowrap; transition: background .14s ease; }
        .vy-ov-chip-more:hover { background: rgba(255, 255, 255, .6); }

        /* Metric row — 3 glass link-cards with a ↗ corner + optional occupancy sparkline. */
        .vy-ov-metrics { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        @media (max-width: 640px) { .vy-ov-metrics { grid-template-columns: 1fr; } }
        .vy-ov-metric {
            display: block; text-decoration: none; color: inherit; background: var(--vy-glass);
            backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow);
            padding: 18px 20px; transition: transform .14s ease, box-shadow .14s ease;
        }
        .vy-ov-metric:hover { transform: translateY(-2px); box-shadow: 0 18px 48px rgba(150, 90, 50, .22), inset 0 1px 0 rgba(255, 255, 255, .65); }
        .vy-ov-metric-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
        .vy-ov-metric-label { font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-ov-metric-arrow { color: #C0B3A9; flex: none; }
        .vy-ov-metric-val { font-family: var(--vy-display); font-weight: 600; font-size: 32px; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
        .vy-ov-metric-val.is-deferred { color: var(--vy-faint); }
        .vy-ov-occ { display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; }
        .vy-ov-occ-fig { display: flex; align-items: baseline; gap: 8px; }
        .vy-ov-occ-delta { font-size: 13px; font-weight: 500; color: var(--vy-accent-text); }

        /* Section title (Recent statements / Earnings) — Sora 500 16. */
        .vy-ov-sec-title { font-family: var(--vy-display); font-weight: 500; font-size: 16px; margin: 0; }

        /* Earnings chart — segmented period toggle + full-width bars + a dark hover tooltip. */
        .vy-ov-seg { display: inline-flex; align-items: center; gap: 3px; padding: 3px; border-radius: var(--vy-r-pill); background: rgba(120, 70, 40, .06); border: 1px solid var(--vy-glass-border); }
        .vy-ov-seg-btn { padding: 5px 13px; border: none; border-radius: var(--vy-r-pill); cursor: pointer; font-family: var(--vy-sans); font-size: 11.5px; font-weight: 500; color: var(--vy-muted); background: transparent; box-shadow: none; transition: color .16s, background .16s, box-shadow .16s; }
        .vy-ov-seg-btn.is-active { font-weight: 600; color: var(--vy-ink); background: #fff; box-shadow: var(--vy-shadow-sm); }
        /* ⚠️ CHART GEOMETRY — the two numbers below are load-bearing, not decoration.
           `--vy-ov-bar-scale` is the wireframe's 78% headroom: the tallest bar stops at 78% of the
           150px box so the hover tooltip and the goal line have room. It is ALSO what keeps the chart
           LINEAR — the bar is a flex item beside the month label, so at ~84%+ flex-shrink silently
           compressed it (the tallest, most-read bar measured 126px where 150px was asked for, a 16%
           understatement of the current month). Capped at 78% nothing shrinks.
           `--vy-ov-label-block` is the month label (16px, pinned below) + the 8px gap, expressed as a
           % of the 150px box: the bars' baseline sits that far above the chart's own bottom, so the
           goal line — which is positioned against the chart — must be offset by it or it draws at a
           height that is NOT the value it labels. */
        .vy-ov-chart { --vy-ov-bar-scale: .78; --vy-ov-label-block: 16%; display: flex; align-items: flex-end; gap: 10px; height: 150px; position: relative; }
        .vy-ov-barcol { flex: 1; position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px; height: 100%; justify-content: flex-end; text-decoration: none; cursor: pointer; }
        /* Non-current months: the wireframe's VERTICAL translucent wash whose top-stop alpha rises with
           the bar's own real value (--vy-bar-a, set per bar from the same pct that drives its height) —
           not a flat saturated fill that makes every month read like the best one. */
        .vy-ov-bar { position: relative; width: 100%; border-radius: 6px 6px 0 0; background: linear-gradient(180deg, rgba(232, 112, 58, var(--vy-bar-a, .42)), rgba(240, 165, 122, .18)); transition: opacity .16s ease, filter .16s ease; }
        .vy-ov-bar.is-last { background: var(--vy-grad); }
        .vy-ov-barcol:hover .vy-ov-bar { filter: brightness(1.04); }
        .vy-ov-bar-month { font-size: 11px; line-height: 16px; height: 16px; color: var(--vy-muted); }
        /* Goal line + the wireframe's pill label sitting ON the line (was plain text floating above the
           chart, where it collided with the card header). */
        .vy-ov-goal { position: absolute; left: 0; right: 0; height: 0; border-top: 1.5px dashed rgba(194, 65, 12, .5); z-index: 6; pointer-events: none; }
        .vy-ov-goal-tag { position: absolute; right: 0; top: 0; transform: translateY(-50%); padding: 2px 8px; border-radius: 20px; background: #FBEFE7; border: 1px solid rgba(194, 65, 12, .22); color: var(--vy-accent-text); font-size: 9.5px; font-weight: 600; white-space: nowrap; }
        .vy-ov-tip { position: absolute; left: 50%; bottom: calc(100% + 9px); transform: translateX(-50%) translateY(4px); opacity: 0; transition: opacity .16s ease, transform .16s ease; pointer-events: none; white-space: nowrap; z-index: 20; }
        .vy-ov-barcol:hover .vy-ov-tip { opacity: 1; transform: translateX(-50%) translateY(0); }
        .vy-ov-tip-inner { position: relative; background: #2A211C; border-radius: 10px; padding: 7px 11px; box-shadow: 0 8px 22px rgba(60, 30, 10, .30); }
        .vy-ov-tip-inner::after { content: ""; position: absolute; left: 50%; top: 100%; width: 9px; height: 9px; background: #2A211C; transform: translateX(-50%) translateY(-50%) rotate(45deg); }
        .vy-ov-tip-m { font-size: 9.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #C9B8AC; }
        .vy-ov-tip-v { font-size: 13.5px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums; margin-top: 1px; }

        /* Dark next-payout rail — halo glow + masked bank-account pill + paused/resume branch. */
        .vy-ov-payout { position: relative; }
        .vy-ov-payout-halo { position: absolute; top: -28px; left: -30px; right: -30px; bottom: -34px; background: radial-gradient(ellipse at 50% 55%, rgba(232, 112, 58, .38), transparent 70%); filter: blur(26px); pointer-events: none; }
        .vy-ov-payout .vy-card { position: relative; padding: 20px; }
        .vy-ov-iban { display: inline-flex; align-items: center; gap: 7px; margin-top: 16px; padding: 7px 12px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-size: 12px; font-weight: 500; box-shadow: 0 4px 12px rgba(194, 65, 12, .35); }
        .vy-ov-payout-est { font-size: 13px; color: #B9ABA0; margin-top: 5px; }
        .vy-ov-pause-row { display: flex; align-items: center; gap: 11px; }
        .vy-ov-pause-ic { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 11px; background: rgba(232, 112, 58, .18); flex: none; color: var(--vy-peach); }
        .vy-ov-pause-fig { font-family: var(--vy-display); font-weight: 600; font-size: 26px; letter-spacing: -.02em; color: #fff; }
        .vy-ov-resume { display: inline-flex; align-items: center; gap: 6px; margin-top: 15px; padding: 8px 14px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-size: 12px; font-weight: 500; text-decoration: none; box-shadow: 0 4px 12px rgba(194, 65, 12, .35); }

        /* My units rail. */
        .vy-ov-units { display: flex; flex-direction: column; gap: 14px; }
        .vy-ov-unit-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 6px; }
        .vy-ov-unit-name { font-size: 13px; font-weight: 500; }
        .vy-ov-unit-pct { font-size: 12.5px; font-weight: 500; color: var(--vy-muted); font-variant-numeric: tabular-nums; }

        /* Upcoming stays rail. */
        .vy-ov-stays-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 15px; }
        .vy-ov-ctrl { display: inline-flex; align-items: center; gap: 5px; padding: 5px 9px; border-radius: 9px; border: 1px solid rgba(120, 70, 40, .16); background: rgba(255, 252, 248, .6); color: #6B615A; font-family: var(--vy-sans); font-size: 11.5px; font-weight: 500; cursor: pointer; transition: background .12s ease; }
        .vy-ov-ctrl:hover { background: rgba(255, 252, 248, .95); }
        .vy-ov-ctrl-count { display: inline-flex; align-items: center; justify-content: center; min-width: 15px; height: 15px; padding: 0 4px; border-radius: 20px; background: var(--vy-accent); color: #fff; font-size: 9.5px; font-weight: 700; }
        .vy-ov-menu { position: absolute; top: calc(100% + 6px); right: 0; z-index: 31; min-width: 178px; padding: 5px; border-radius: var(--vy-r-md); background: rgba(255, 252, 248, .98); backdrop-filter: blur(20px) saturate(1.2); -webkit-backdrop-filter: blur(20px) saturate(1.2); border: 1px solid rgba(255, 255, 255, .85); box-shadow: 0 16px 44px rgba(120, 70, 40, .24); }
        .vy-ov-menu-h { padding: 6px 9px 4px; font-size: 9.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #B0A49C; }
        .vy-ov-menu-item { display: flex; align-items: center; gap: 8px; width: 100%; padding: 7px 9px; border: none; background: transparent; border-radius: var(--vy-r-sm); font-family: var(--vy-sans); font-size: 12px; color: var(--vy-ink); cursor: pointer; text-align: left; transition: background .12s ease; }
        .vy-ov-menu-item:hover { background: rgba(120, 70, 40, .06); }
        .vy-ov-menu-dot { width: 7px; height: 7px; border-radius: 50%; flex: none; }
        .vy-ov-menu-sep { height: 1px; background: rgba(120, 70, 40, .1); margin: 5px 6px; }
        .vy-ov-stays { display: flex; flex-direction: column; gap: 15px; }
        .vy-ov-stay { display: block; text-decoration: none; color: inherit; margin: 0 -8px; padding: 7px 8px; border-radius: var(--vy-r-sm); transition: background .15s ease; }
        .vy-ov-stay:hover { background: rgba(120, 70, 40, .05); }
        .vy-ov-stay-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .vy-ov-stay-guest { display: inline-flex; align-items: center; gap: 7px; min-width: 0; flex: 1 1 auto; }
        .vy-ov-stay-dot { width: 6px; height: 6px; border-radius: 50%; flex: none; }
        .vy-ov-stay-name { font-size: 13.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* ⚠️ The wireframe gives this slot `flex-shrink: 0` because it only ever holds a short money
           figure. Ours also carries the HONEST "Not yet attributed" (a stay with no owner-ledger rows
           yet — #39, never a fake €0), which is ~2× wider and was pushing the guest NAME into an
           ellipsis on 3 of 4 rows. Capped + allowed to wrap: a money figure still sits on one line,
           the honest absence wraps instead of eating the name. */
        .vy-ov-stay-val { font-size: 13px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; flex: 0 1 auto; max-width: 38%; text-align: right; white-space: normal; line-height: 1.25; }
        .vy-ov-stay-bot { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 3px; }
        .vy-ov-stay-detail { font-size: 11.5px; color: var(--vy-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-ov-stay-meta { display: inline-flex; align-items: center; gap: 8px; flex: none; }
        .vy-ov-stay-status { display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 20px; font-size: 10.5px; font-weight: 600; }
        .vy-ov-stay-date { font-size: 11.5px; color: var(--vy-faint); }
        .vy-ov-stays-foot { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(120, 70, 40, .09); }
        .vy-ov-stays-foot-l { font-size: 10.5px; color: var(--vy-muted); letter-spacing: .01em; }
        .vy-ov-viewall { display: inline-flex; align-items: center; gap: 4px; font-size: 12.5px; font-weight: 500; color: var(--vy-accent-text); text-decoration: none; flex: none; }

        /* Download-statements pill (bottom of the right rail). */
        .vy-ov-dl { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; border-radius: var(--vy-r-pill); background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow); color: var(--vy-ink); font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; cursor: pointer; text-decoration: none; transition: transform .14s ease, box-shadow .14s ease; }
        .vy-ov-dl:hover { transform: translateY(-2px); box-shadow: 0 18px 48px rgba(150, 90, 50, .22), inset 0 1px 0 rgba(255, 255, 255, .65); }

        /* ============================================================================================
           OWNER MY UNITS — exact-match components (decoded from docs/design/raw/Owner My Units.html).
           Scoped `.vy-mu-*` so they apply ONLY to My Units; shared .vy-card / --dark / .vy-track reused.
           ============================================================================================ */
        @keyframes vyPing { 0% { transform: scale(1); opacity: .5; } 70%, 100% { transform: scale(2.35); opacity: 0; } }
        @keyframes vyMenuIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

        /* header + stats band */
        .vy-mu-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
        .vy-mu-addbtn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: var(--vy-r-pill); background: var(--vy-grad); border: none; color: #fff; font-family: var(--vy-sans); font-size: 13px; font-weight: 500; text-decoration: none; box-shadow: 0 6px 16px rgba(194, 65, 12, .34); transition: transform .14s ease; }
        .vy-mu-addbtn:hover { transform: translateY(-2px); }
        .vy-mu-stats { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 640px) { .vy-mu-stats { grid-template-columns: 1fr; } }
        .vy-mu-stat { background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 18px 20px; transition: transform .14s ease, box-shadow .14s ease; }
        .vy-mu-stat:hover { transform: translateY(-2px); box-shadow: 0 18px 48px rgba(150, 90, 50, .22), inset 0 1px 0 rgba(255, 255, 255, .65); }
        .vy-mu-stat-label { font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: #9C9089; margin-bottom: 8px; }
        .vy-mu-stat-val { font-family: var(--vy-display); font-weight: 600; font-size: 32px; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
        .vy-mu-stat-delta { font-size: 13px; font-weight: 500; color: var(--vy-accent-text); }

        /* action center */
        .vy-mu-ac { position: relative; background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 18px 22px; margin-bottom: 20px; }
        .vy-mu-ac-head { display: flex; align-items: center; gap: 13px; cursor: pointer; user-select: none; }
        .vy-mu-ac-icon { position: relative; width: 34px; height: 34px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .vy-mu-ac-ping { position: absolute; inset: 0; border-radius: 11px; background: var(--vy-accent-3); z-index: 0; animation: vyPing 1.9s cubic-bezier(.22, .61, .36, 1) infinite; }
        .vy-mu-ac-icon > .m { position: relative; z-index: 1; width: 34px; height: 34px; border-radius: 11px; background: var(--vy-grad); display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 14px rgba(194, 65, 12, .34); color: #fff; }
        .vy-mu-ac-title { margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 16.5px; letter-spacing: -.01em; }
        .vy-mu-ac-count { min-width: 21px; height: 21px; padding: 0 6px; box-sizing: border-box; border-radius: 20px; background: var(--vy-grad); color: #fff; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; font-variant-numeric: tabular-nums; box-shadow: 0 3px 9px rgba(194, 65, 12, .36); }
        .vy-mu-ac-sub { font-size: 12.5px; color: var(--vy-faint); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-mu-ac-toggle { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; padding: 8px 15px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, .55); border: 1px solid rgba(120, 70, 40, .16); color: #7A2E08; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; white-space: nowrap; cursor: pointer; transition: background .16s ease, transform .14s ease; }
        .vy-mu-ac-toggle:hover { background: rgba(255, 255, 255, .92); transform: translateY(-1px); }
        .vy-mu-ac-toggle svg { transition: transform .34s cubic-bezier(.22, .61, .36, 1); }
        .vy-mu-ac-reveal { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .44s cubic-bezier(.22, .61, .36, 1); }
        .vy-mu-ac-reveal.is-open { grid-template-rows: 1fr; }
        .vy-mu-ac-reveal > div { overflow: hidden; min-height: 0; }
        /* action-center toolbar (tabs + filters + bulk) */
        .vy-mu-ac-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 8px 0 2px; }
        .vy-mu-tabs { display: inline-flex; gap: 4px; padding: 4px; border-radius: var(--vy-r-pill); background: rgba(120, 70, 40, .06); border: 1px solid rgba(120, 70, 40, .10); }
        .vy-mu-tab { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: var(--vy-r-pill); border: none; cursor: pointer; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; background: transparent; color: var(--vy-muted); transition: background .16s, color .16s, box-shadow .16s; }
        .vy-mu-tab.is-on { background: #fff; color: var(--vy-ink); box-shadow: var(--vy-shadow-sm); }
        .vy-mu-tab-n { min-width: 18px; height: 18px; padding: 0 5px; box-sizing: border-box; border-radius: 20px; background: rgba(120, 70, 40, .12); color: var(--vy-muted); font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; font-variant-numeric: tabular-nums; }
        .vy-mu-pillbtn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, .6); border: 1px solid rgba(120, 70, 40, .18); color: #7A2E08; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; cursor: pointer; transition: background .14s ease, transform .14s ease; }
        .vy-mu-pillbtn:hover { background: rgba(255, 255, 255, .95); transform: translateY(-1px); }
        .vy-mu-pillbtn--accent { background: rgba(217, 102, 46, .12); border-color: rgba(217, 102, 46, .22); color: #B4400F; }
        .vy-mu-pillbtn--accent:hover { background: rgba(217, 102, 46, .2); }
        /* action-center item rows */
        .vy-mu-item { display: flex; align-items: center; gap: 14px; padding: 15px 0; border-top: 1px solid var(--vy-hairline); }
        .vy-mu-item:first-child { border-top: none; }
        .vy-mu-item-ic { position: relative; width: 38px; height: 38px; flex-shrink: 0; border-radius: 11px; background: rgba(217, 102, 46, .12); display: flex; align-items: center; justify-content: center; color: var(--vy-accent); }
        .vy-mu-item-unread { position: absolute; top: -3px; right: -3px; width: 11px; height: 11px; border-radius: 50%; background: linear-gradient(140deg, #F0824A, #D9662E); border: 2.5px solid #FCF4EC; box-sizing: border-box; box-shadow: 0 1px 3px rgba(194, 65, 12, .35); }
        .vy-mu-item-title { font-family: var(--vy-display); font-weight: 500; font-size: 14.5px; color: var(--vy-ink); }
        .vy-mu-item-tag { padding: 3px 10px; border-radius: var(--vy-r-pill); font-size: 10.5px; font-weight: 500; white-space: nowrap; }
        .vy-mu-item-sub { font-size: 12.5px; color: var(--vy-faint); margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-mu-item-link { display: inline-flex; align-items: center; padding: 7px 15px; border-radius: var(--vy-r-pill); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; text-decoration: none; white-space: nowrap; background: var(--vy-grad); color: #fff; box-shadow: 0 5px 14px rgba(194, 65, 12, .30); transition: transform .14s ease; }
        .vy-mu-item-link:hover { transform: translateY(-1px); }
        .vy-mu-item-ghost { padding: 7px 14px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, .55); border: 1px solid rgba(120, 70, 40, .18); color: var(--vy-muted); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; cursor: pointer; transition: background .14s ease; }
        .vy-mu-item-ghost:hover { background: rgba(255, 255, 255, .85); }
        .vy-mu-menu { position: absolute; z-index: 30; min-width: 200px; padding: 6px; border-radius: 14px; background: rgba(255, 252, 249, .98); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(120, 70, 40, .14); box-shadow: 0 18px 44px rgba(80, 40, 20, .2); animation: vyMenuIn .16s cubic-bezier(.22, .61, .36, 1) both; }
        .vy-mu-menu-item { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 11px; border-radius: 11px; background: transparent; border: none; cursor: pointer; text-align: left; font-family: var(--vy-sans); font-size: 13px; color: var(--vy-ink); transition: background .12s ease; }
        .vy-mu-menu-item:hover { background: rgba(120, 70, 40, .06); }
        .vy-mu-ac-empty { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 9px; padding: 26px 10px 14px; }
        .vy-mu-ac-empty-ic { width: 48px; height: 48px; border-radius: 15px; background: rgba(31, 138, 91, .12); color: var(--vy-green); display: flex; align-items: center; justify-content: center; }

        /* unit cards */
        .vy-mu-secttl { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .vy-mu-secttl h2 { margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 17px; letter-spacing: -.01em; color: #2A211C; }
        .vy-mu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 20px; align-items: start; }
        .vy-mu-card { background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); overflow: hidden; transition: transform .14s ease, box-shadow .14s ease; }
        .vy-mu-card:hover { transform: translateY(-2px); box-shadow: 0 18px 48px rgba(150, 90, 50, .22), inset 0 1px 0 rgba(255, 255, 255, .65); }
        .vy-mu-thumb { height: 92px; display: flex; align-items: center; justify-content: center; color: rgba(255, 255, 255, .85); background: var(--vy-grad); }
        .vy-mu-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vy-mu-card-body { padding: 16px 18px 18px; }
        .vy-mu-card-name { font-family: var(--vy-display); font-weight: 500; font-size: 16px; }
        .vy-mu-card-sub { font-size: 12.5px; color: var(--vy-faint); margin-top: 3px; }
        .vy-mu-badge { display: inline-flex; padding: 4px 11px; border-radius: var(--vy-r-pill); background: rgba(217, 102, 46, .13); color: #B4400F; font-size: 11px; font-weight: 500; flex-shrink: 0; }
        .vy-mu-badge--best { background: var(--vy-grad); color: #fff; }
        .vy-mu-uplabel { font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: #9C9089; }
        .vy-mu-stat2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--vy-hairline); }
        .vy-mu-stat2 .k { font-size: 11px; color: var(--vy-faint); margin-bottom: 2px; }
        .vy-mu-stat2 .v { font-family: var(--vy-display); font-weight: 500; font-size: 15px; font-variant-numeric: tabular-nums; }
        .vy-mu-rate-row { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--vy-hairline); }
        .vy-mu-cardbtn { flex: 1; min-width: 0; display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 7px 8px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, .6); border: 1px solid rgba(120, 70, 40, .16); color: #6B5D54; font-family: var(--vy-sans); font-size: 11.5px; font-weight: 500; white-space: nowrap; cursor: pointer; transition: background .14s ease, border-color .14s ease; }
        .vy-mu-cardbtn:hover { background: rgba(255, 255, 255, .9); border-color: rgba(217, 102, 46, .3); }
        .vy-mu-panel { margin-top: 12px; padding: 13px 14px; border-radius: 14px; background: rgba(255, 252, 248, .72); border: 1px solid rgba(217, 102, 46, .2); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6); }
        .vy-mu-step { width: 34px; height: 34px; flex-shrink: 0; border-radius: 10px; background: rgba(255, 255, 255, .8); border: 1px solid rgba(120, 70, 40, .16); color: #6B5D54; font-size: 19px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .14s ease; }
        .vy-mu-step:hover { background: #fff; }
        .vy-mu-rate-input { flex: 1; min-width: 0; display: flex; align-items: center; gap: 4px; padding: 0 12px; height: 34px; border-radius: 10px; background: #fff; border: 1px solid rgba(217, 102, 46, .28); }
        .vy-mu-rate-input input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; font-family: var(--vy-display); font-weight: 500; font-size: 16px; color: var(--vy-ink); font-variant-numeric: tabular-nums; padding: 0; }
        .vy-mu-view { display: inline-flex; align-items: center; gap: 4px; margin-top: 13px; font-size: 13px; font-weight: 500; color: var(--vy-accent-text); text-decoration: none; }
        .vy-mu-addcard { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; min-height: 180px; border-radius: var(--vy-r-lg); border: 1.5px dashed rgba(120, 70, 40, .28); background: rgba(255, 252, 248, .35); text-decoration: none; color: #B4400F; cursor: pointer; transition: transform .14s ease, background .14s ease; }
        .vy-mu-addcard:hover { transform: translateY(-2px); background: rgba(255, 252, 248, .55); }

        /* calendar */
        .vy-mu-cols { display: grid; grid-template-columns: minmax(0, 1fr) 232px; gap: 20px; align-items: start; }
        @media (max-width: 1100px) { .vy-mu-cols { grid-template-columns: minmax(0, 1fr); } }
        .vy-mu-calnav { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 9px; background: rgba(255, 255, 255, .55); border: 1px solid rgba(120, 70, 40, .14); color: #7A2E08; cursor: pointer; text-decoration: none; transition: background .13s ease; }
        .vy-mu-calnav:hover { background: rgba(255, 255, 255, .95); }
        .vy-mu-calbtn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 12px 7px 11px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, .55); border: 1px solid rgba(120, 70, 40, .16); color: #7A2E08; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background .14s ease, transform .1s ease; }
        .vy-mu-calbtn:hover { background: rgba(255, 255, 255, .9); }
        .vy-mu-calbtn--accent { background: rgba(232, 112, 58, .1); border-color: rgba(194, 65, 12, .28); color: var(--vy-accent); font-weight: 600; }
        .vy-mu-calbtn--accent:hover { background: rgba(232, 112, 58, .18); transform: translateY(-1px); }
        .vy-mu-calgrid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
        .vy-mu-calhead { text-align: center; font-size: 11px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: #9C9089; padding: 4px 0; }
        .vy-mu-day { aspect-ratio: 1.35; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 500; font-variant-numeric: tabular-nums; border: 1px solid rgba(120, 70, 40, .06); cursor: pointer; transition: transform .1s ease, box-shadow .12s ease; }
        .vy-mu-day:hover { transform: translateY(-1px); }
        .vy-mu-day.is-empty { border: none; cursor: default; }
        .vy-mu-day.is-sel { box-shadow: 0 0 0 2px var(--vy-accent); }
        .vy-mu-cal-foot { margin-top: 15px; padding-top: 13px; border-top: 1px solid var(--vy-hairline); }

        /* ---- Messages (Wave 3) — the owner portal's first FORM + first transcript ---------------
           Built on the OWNER tokens above, not copied from the operator layout: the two design systems
           are deliberately separate files (the owner one is the warmer, calmer of the two). The shapes
           mirror the operator inbox because a conversation is a conversation. */
        /* ⚠️ This sub-line sits DIRECTLY ON THE PAGE GRADIENT, not on glass — --vy-muted measured 3.80
           against the gradient's sampled pixel. --vy-on-grad is the token made for exactly this surface
           (4.68). The same distinction the sidebar needed at the operator realm's first a11y gate. */
        .vy-topbar-sub { font-size: 12px; color: var(--vy-on-grad); margin-top: 3px; }
        .vy-flash { display: flex; align-items: center; gap: 9px; padding: 11px 14px; border-radius: var(--vy-r-md); font-size: 13px; font-weight: 500; margin-bottom: 16px; }
        .vy-flash.is-ok { background: rgba(31, 122, 90, 0.10); color: var(--vy-green); border: 1px solid rgba(31, 122, 90, 0.18); }
        .vy-flash.is-err { background: rgba(178, 106, 8, 0.10); color: var(--vy-amber); border: 1px solid rgba(178, 106, 8, 0.2); }

        .vy-input { flex: 1; min-width: 0; padding: 10px 13px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.7); font: inherit; font-size: 13.5px; color: var(--vy-ink); outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
        .vy-input:focus { border-color: var(--vy-accent-border); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.10); }
        .vy-input::placeholder { color: var(--vy-faint); }
        .vy-input-group { display: flex; gap: 8px; align-items: stretch; }
        /* Polish Batch 1 — the form field label + wrapper (block-calendar / payout-method / tax-center / rates / add-unit).
           Previously undefined in the owner system → labels rendered with browser defaults. Mirrors the operator layout. */
        .vy-field { margin-bottom: 15px; }
        .vy-field-label { display: block; font-size: 10px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 7px; }
        .vy-field-label .vy-fl-note { font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--vy-faint); }
        .vy-field-label .vy-fl-right { float: right; font-weight: 600; text-transform: none; letter-spacing: 0; color: var(--vy-muted); }

        /* Conversation rail */
        .vy-msg-cols { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 18px; align-items: start; }
        @media (max-width: 900px) { .vy-msg-cols { grid-template-columns: minmax(0, 1fr); } }
        .vy-convo-row { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: var(--vy-r-md); text-decoration: none; color: inherit; border-bottom: 1px solid var(--vy-hairline); transition: background .15s ease, transform .15s ease; }
        .vy-convo-row:last-child { border-bottom: none; }
        .vy-convo-row:hover { background: rgba(255, 255, 255, 0.55); transform: translateX(2px); }
        .vy-convo-row.is-unread { background: rgba(232, 112, 58, 0.06); }
        .vy-convo-row.is-active { background: #fff; box-shadow: var(--vy-shadow-sm); }
        .vy-convo-av { position: relative; flex: none; width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(140deg, var(--vy-peach), var(--vy-accent)); color: #fff; display: grid; place-items: center; font-family: var(--vy-display); font-weight: 600; font-size: 13.5px; }
        .vy-convo-main { flex: 1; min-width: 0; }
        .vy-convo-name { font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); }
        .vy-convo-row.is-unread .vy-convo-name { font-weight: 600; }
        .vy-convo-role { font-size: 11px; color: var(--vy-faint); margin-top: 1px; }
        .vy-convo-preview { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-convo-row.is-unread .vy-convo-preview { color: var(--vy-ink); font-weight: 500; }
        .vy-convo-meta { flex: none; display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
        .vy-convo-time { font-size: 11px; color: var(--vy-faint); white-space: nowrap; }
        .vy-unread-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--vy-accent); }

        /* Transcript */
        .vy-day-sep { display: flex; align-items: center; gap: 10px; margin: 18px 0 12px; color: var(--vy-faint); font-size: 11px; }
        .vy-day-sep::before, .vy-day-sep::after { content: ""; flex: 1; height: 1px; background: var(--vy-hairline); }
        .vy-bubble-row { display: flex; margin-bottom: 12px; }
        .vy-bubble-row.is-mine { justify-content: flex-end; }
        .vy-bubble { max-width: 76%; padding: 11px 14px; border-radius: 16px; font-size: 13.5px; line-height: 1.55; }
        /* Theirs — cream. Mine — terracotta. The owner's OWN messages are `inbound` (org-relative), so the
           component mirrors the direction; the CSS just paints what it is told. */
        .vy-bubble.is-theirs { background: rgba(255, 252, 248, 0.9); border: 1px solid var(--vy-glass-border); border-bottom-left-radius: 5px; color: var(--vy-ink); }
        .vy-bubble.is-mine { background: var(--vy-grad); color: #fff; border-bottom-right-radius: 5px; }
        .vy-bubble-meta { font-size: 10.5px; color: var(--vy-faint); margin-top: 5px; }
        .vy-bubble-row.is-mine .vy-bubble-meta { text-align: right; }
        .vy-bubble-failed { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 600; color: var(--vy-amber); }
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

        /* ---- Add-Unit wizard (Screen · Owner Add Unit) — .vy-au-* --------------------------------- */
        .vy-au-back { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--vy-muted); text-decoration: none; font-weight: 500; margin-bottom: 10px; }
        .vy-au-back:hover { color: var(--vy-accent-text); }
        .vy-au-back svg { width: 13px; height: 13px; }
        .vy-au-steplabel { font-size: 12.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vy-au-wrap { margin-top: 22px; display: flex; flex-wrap: wrap; gap: 22px; align-items: flex-start; }
        .vy-au-left { flex: 1 1 440px; min-width: 0; }
        .vy-au-rail { display: flex; align-items: center; gap: 6px; margin-bottom: 18px; }
        .vy-au-rail-seg { flex: 1; height: 4px; border-radius: 3px; background: rgba(120,70,40,.14); transition: background .3s; }
        .vy-au-rail-seg.is-on { background: var(--vy-grad); }
        .vy-au-card { background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 24px; }
        .vy-au-step { animation: vyExpand .24s cubic-bezier(.22,.61,.36,1) both; }
        @keyframes vyExpand { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        .vy-au-steptitle { font-family: var(--vy-display); font-weight: 600; font-size: 20px; letter-spacing: -.02em; color: var(--vy-ink); }
        .vy-au-stepsub { font-size: 12.5px; color: var(--vy-muted); margin-top: 4px; margin-bottom: 20px; }
        .vy-au-field { margin-bottom: 16px; }
        .vy-au-flabel { font-size: 10.5px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 7px; }
        .vy-au-input { width: 100%; box-sizing: border-box; border: 1px solid rgba(120,70,40,.14); background: rgba(255,255,255,.78); border-radius: 14px; padding: 12px 14px; font-family: var(--vy-sans); font-size: 14px; color: var(--vy-ink); outline: none; transition: border-color .16s, box-shadow .16s; }
        .vy-au-input:focus { border-color: var(--vy-accent-border); box-shadow: 0 0 0 3px rgba(232,112,58,.12); }
        .vy-au-chips { display: flex; gap: 8px; flex-wrap: wrap; }
        .vy-au-chip { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: var(--vy-r-pill); background: rgba(255,255,255,.6); border: 1px solid rgba(120,70,40,.14); color: #5C534C; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; cursor: pointer; transition: transform .14s, background .16s, border-color .16s, color .16s; }
        .vy-au-chip:hover { transform: translateY(-1px); }
        .vy-au-chip.is-sel { background: rgba(232,112,58,.14); border-color: var(--vy-accent); color: var(--vy-accent); }
        .vy-au-chip svg { width: 13px; height: 13px; }
        .vy-au-group { border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.45); border-radius: 14px; padding: 4px 16px; margin-bottom: 20px; }
        .vy-au-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 13px 0; border-bottom: 1px solid rgba(120,70,40,.08); }
        .vy-au-row:last-child { border-bottom: none; }
        .vy-au-row-name { font-size: 13.5px; font-weight: 500; color: var(--vy-ink); }
        .vy-au-row-sub { display: block; font-size: 11px; color: var(--vy-faint); }
        .vy-au-stepper { display: flex; align-items: center; gap: 12px; }
        .vy-au-num { min-width: 18px; text-align: center; font-size: 14px; font-weight: 500; font-variant-numeric: tabular-nums; color: var(--vy-ink); }
        .vy-au-pm { width: 30px; height: 30px; border-radius: 50%; border: 1px solid rgba(120,70,40,.18); background: rgba(255,255,255,.7); color: var(--vy-accent); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .14s; }
        .vy-au-pm:hover { background: rgba(255,255,255,.95); }
        .vy-au-pm:disabled { opacity: .35; cursor: default; }
        .vy-au-pm svg { width: 13px; height: 13px; }
        .vy-au-money { display: flex; align-items: center; gap: 6px; border: 1px solid rgba(120,70,40,.14); background: rgba(255,255,255,.78); border-radius: 14px; padding: 11px 14px; }
        .vy-au-money-cur { font-family: var(--vy-display); font-size: 16px; color: var(--vy-faint); font-weight: 600; }
        .vy-au-money input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; padding: 0; }
        .vy-au-note { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-radius: 14px; background: rgba(217,102,46,.08); border: 1px solid rgba(232,112,58,.24); margin-bottom: 22px; }
        .vy-au-note svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 1px; }
        .vy-au-note span { font-size: 12px; color: #8A5A3A; line-height: 1.5; }
        .vy-au-drops { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .vy-au-drop { position: relative; height: 130px; border-radius: 14px; overflow: hidden; border: 1px dashed rgba(120,70,40,.28); background: rgba(255,255,255,.4); cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; text-align: center; transition: border-color .16s, background .16s; }
        .vy-au-drop:hover { border-color: var(--vy-accent-border); background: rgba(255,255,255,.6); }
        .vy-au-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .vy-au-drop svg { width: 22px; height: 22px; color: var(--vy-peach); }
        .vy-au-drop-label { font-size: 11.5px; color: var(--vy-faint); font-weight: 500; padding: 0 8px; }
        .vy-au-drop-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .vy-au-commission { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; background: var(--vy-grad-dark); margin-bottom: 22px; }
        .vy-au-commission-ico { width: 38px; height: 38px; border-radius: 11px; background: rgba(232,112,58,.2); color: #F3B98E; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .vy-au-commission-ico svg { width: 18px; height: 18px; }
        .vy-au-commission-main { min-width: 0; flex: 1; }
        .vy-au-commission-l1 { display: block; font-size: 12.5px; color: #F3E7DD; }
        .vy-au-commission-l2 { display: block; font-size: 11.5px; color: #B9ABA0; margin-top: 2px; }
        .vy-au-commission-net { color: #F3B98E; font-weight: 500; }
        .vy-au-cta { width: 100%; padding: 13px 0; border-radius: var(--vy-r-pill); background: var(--vy-grad); border: none; color: #fff; font-family: var(--vy-sans); font-size: 14px; font-weight: 500; cursor: pointer; box-shadow: rgba(194,65,12,.40) 0 8px 22px; transition: transform .14s; text-align: center; text-decoration: none; display: block; box-sizing: border-box; }
        .vy-au-cta:hover { transform: translateY(-2px); color: #fff; }
        .vy-au-cta--wait { background: rgba(120,70,40,.12); color: #A69A92; cursor: default; box-shadow: none; }
        .vy-au-cta--wait:hover { transform: none; color: #A69A92; }
        .vy-au-cta--ghost { width: 100%; margin-top: 10px; padding: 11px 0; border-radius: var(--vy-r-pill); background: transparent; border: 1px solid rgba(120,70,40,.16); color: var(--vy-muted); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; cursor: pointer; box-shadow: none; text-align: center; text-decoration: none; display: block; box-sizing: border-box; }
        .vy-au-cta--ghost:hover { background: rgba(120,70,40,.06); transform: none; color: var(--vy-muted); }
        /* live preview */
        .vy-au-preview { flex: 0 0 288px; max-width: 288px; min-width: 260px; position: sticky; top: 20px; }
        .vy-au-preview-label { font-size: 10.5px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 10px; padding-left: 2px; }
        .vy-au-preview-card { background: rgba(255,252,248,.62); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); overflow: hidden; }
        .vy-au-preview-cover { position: relative; height: 150px; background: linear-gradient(135deg, #F3B98E, #E8703A 60%, #C2410C); display: flex; align-items: center; justify-content: center; }
        .vy-au-preview-cover img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .vy-au-preview-cover-ph { font-size: 11.5px; color: rgba(255,255,255,.9); font-weight: 500; }
        .vy-au-badge { position: absolute; top: 12px; left: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: var(--vy-r-pill); background: rgba(255,252,248,.92); color: #8F6414; font-size: 10.5px; font-weight: 600; box-shadow: rgba(60,30,10,.18) 0 4px 12px; }
        .vy-au-badge span { width: 6px; height: 6px; border-radius: 50%; background: #D99A3D; }
        .vy-au-preview-body { padding: 16px 18px 18px; }
        .vy-au-preview-name { font-family: var(--vy-display); font-weight: 600; font-size: 16px; letter-spacing: -.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--vy-ink); }
        .vy-au-preview-sub { font-size: 12px; color: var(--vy-faint); margin-top: 3px; }
        .vy-au-preview-meta { display: flex; align-items: center; gap: 12px; margin-top: 13px; padding-top: 13px; border-top: 1px solid var(--vy-hairline); font-size: 12px; color: #5C534C; }
        .vy-au-preview-meta span { display: inline-flex; align-items: center; gap: 5px; }
        .vy-au-preview-meta svg { width: 13px; height: 13px; }
        .vy-au-preview-am { display: flex; align-items: center; gap: 6px; margin-top: 12px; flex-wrap: wrap; }
        .vy-au-preview-am-chip { font-size: 11px; color: #5C534C; background: rgba(120,70,40,.07); border-radius: 20px; padding: 3px 9px; }
        .vy-au-preview-rate { display: flex; align-items: baseline; gap: 5px; margin-top: 14px; }
        .vy-au-preview-rate b { font-family: var(--vy-display); font-weight: 600; font-size: 20px; letter-spacing: -.02em; font-variant-numeric: tabular-nums; color: var(--vy-ink); }
        .vy-au-preview-rate i { font-size: 12px; color: var(--vy-faint); font-style: normal; }
        .vy-au-preview-cap { text-align: center; font-size: 11px; color: var(--vy-faint); margin-top: 12px; line-height: 1.5; }
        /* done */
        .vy-au-done { max-width: 560px; margin: 26px auto 0; }
        .vy-au-done-card { background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: 22px; box-shadow: rgba(150,90,50,.2) 0 18px 50px, inset rgba(255,255,255,.65) 0 1px 0; padding: 30px 28px; }
        .vy-au-done-badge { position: relative; width: 84px; height: 84px; margin: 6px auto 16px; }
        .vy-au-done-badge-glow { position: absolute; inset: -14px; border-radius: 50%; background: radial-gradient(circle, rgba(232,112,58,.30), transparent 70%); filter: blur(10px); }
        .vy-au-done-badge-disc { position: relative; width: 84px; height: 84px; border-radius: 50%; background: var(--vy-grad); box-shadow: rgba(194,65,12,.42) 0 14px 34px; display: flex; align-items: center; justify-content: center; }
        .vy-au-done-badge-disc svg { width: 38px; height: 38px; }
        .vy-au-done-title { font-family: var(--vy-display); font-weight: 600; font-size: 23px; letter-spacing: -.02em; text-align: center; color: var(--vy-ink); }
        .vy-au-done-sub { font-size: 13px; color: var(--vy-muted); margin: 7px auto 0; text-align: center; line-height: 1.55; max-width: 380px; }
        .vy-au-checklist { border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.45); border-radius: 14px; padding: 4px 16px; margin: 22px 0 20px; }
        .vy-au-check { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-bottom: 1px solid rgba(120,70,40,.08); }
        .vy-au-check:last-child { border-bottom: none; }
        .vy-au-check-ico { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .vy-au-check-ico svg { width: 14px; height: 14px; }
        .vy-au-check-ico--done { background: var(--vy-grad); color: #fff; }
        .vy-au-check-ico--wait { background: rgba(217,154,61,.2); color: #8F6414; }
        .vy-au-check-ico--home { background: rgba(217,102,46,.13); color: #B4400F; }
        .vy-au-check-txt { min-width: 0; flex: 1; font-size: 12.5px; color: #5C534C; }
        .vy-au-check-txt b { font-weight: 500; color: var(--vy-ink); }
        .vy-au-done-actions { display: flex; gap: 10px; margin-top: 10px; }
        .vy-au-done-actions .vy-au-cta--ghost { margin-top: 0; background: rgba(255,255,255,.6); border-color: rgba(120,70,40,.14); color: var(--vy-ink); flex: 1; }
        .vy-au-done-actions .vy-au-cta--ghost:hover { background: rgba(255,255,255,.85); color: var(--vy-ink); }
        @media (max-width: 640px) { .vy-au-drops { grid-template-columns: 1fr; } }

        /* NOTE — the Owner Bookings page's exact-match components (breadcrumb, "over time" chart, the
           target ring, filters, sortable headers, pagination) are PAGE-SCOPED in
           resources/views/livewire/owner/bookings.blade.php, NOT here: their class names/comments must not
           leak into every owner page's <style>, where owner-isolation tests assert the ABSENCE of certain
           words on the Balance/Dashboard pages (shared-layout CSS would otherwise trip those substrings). */

        /* ---- Motion & accessibility floor ---------------------------------------------------- */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            html { scroll-behavior: auto; }
            .vy-fade { opacity: 1; }
        }


    </style>
    <x-vy-scrollbar />
</head>
<body class="vy-body">
    <div class="vy-amb" aria-hidden="true">
        <div class="vy-amb-streak1"></div>
        <div class="vy-amb-streak2"></div>
        <div class="vy-amb-orb1"></div>
        <div class="vy-amb-orb2"></div>
        <div class="vy-amb-orb3"></div>
    </div>
    <main>
        {{ $slot }}
    </main>
    @livewireScripts
    @include('partials.count-up')   {{-- Polish Batch 5 — the ONE shared KPI/hero count-up (replaces the per-view @script duplicates) --}}
</body>
</html>
