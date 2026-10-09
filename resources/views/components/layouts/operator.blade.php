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

    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">

    @livewireStyles

    {{-- ⚠️ THE CANONICAL PALETTE FIRST — the 31 shared tokens, declared ONCE (DO-TOKEN-1, §N.8f).
         This shell's own <style> follows, so its DELIBERATE overrides win by cascade order.
         ⚠️ Moving this below the shell's styles would silently undo every override. --}}
    <x-design.tokens />
    <style>
        /* ---- Formalized design tokens (shared Vyrobel system; see owner-portal-spec.md) ---------- */
        :root {
            /* Text that sits DIRECTLY on the page gradient rather than on a glass card. The card tokens
               above are computed against the composited card background; the gradient is darker, so
               --vy-muted only reaches 4.03:1 there and fails AA. Warm stone, not cold grey, so the
               shell still reads warm.

               ⚠️ RE-DERIVED BY SAMPLING (DO-A11Y-2). The previous value rgb(84,74,68) was computed
               against ONE PICKED stop, rgb(241,185,150) — and the shell does not paint its text there.
               Sampling the plate behind every element that actually uses this token, across ten real
               operator pages, found points down to rgb(235,152,106), where rgb(84,74,68) measured
               4.33–4.48: a real AA FAILURE, not a headroom preference, on `.vy-topbar-sub`,
               `.vy-acct-role`, `.vy-convo-back` and the breadcrumbs.

               This is exactly the O2/O7 defect the OWNER shell already fixed by sampling 7,502 pixels,
               and whose note warns "Re-measure by SAMPLING, not by picking a pixel." The operator shell
               had never had that treatment. rgb(68,59,54) clears the darkest sampled point at 4.80.
               --vy-on-grad-soft is UNCHANGED: it never failed on any sampled page (it is used only on
               the sidebar labels, whose plates are lighter), and darkening a passing token is the
               headroom change posture A declined. */
            --vy-on-grad: rgb(68, 59, 54);
            --vy-on-grad-soft: rgb(88, 78, 72);

            /* Accent used as TEXT (links, accent labels) on a glass/card surface. --vy-accent itself is
               the BRAND fill — gradients, buttons, chips, borders, icons — and is deliberately unchanged.

               ⚠️ THE SAME PICKED-STOP ERROR, and the same correction (DO-A11Y-1). rgb(176,56,8) was set
               against a worst card plate of rgb(252,223,204). Sampling found the tokens's real users
               sitting on tints down to rgb(238,195,172) and rgb(249,194,166) — `.vy-tag.tone-confirmed`
               3.89, `.vy-u-code.vy-arr-link` 3.82, `.vy-assign-link` 3.96, `.vy-balance.is-due` 4.46.
               The premise that "a tinted chip carries its own surface and already measured 4.61" was
               false: the chip's tint composites over the CARD, which composites over the gradient, so
               the chip is DARKER than the card, not lighter. rgb(147,42,7) clears the darkest sampled
               tint at 5.06. The value now lives in the shared token file (DO-TOKEN-1); darkening it can
               only raise contrast in the other shells, never lower it. */
            /* terracotta primary (#C2410C) */
            /* (#E8703A) */

            /* ---- Housekeeping status palette -----------------------------------------------------
               The five room states, as tokens rather than hexes repeated in the view. `pending` is
               deliberately the same hue as --vy-accent-3 (the design uses the warm accent for "needs
               work"). These carry MEANING, so nothing here may be the sole carrier of it — every
               surface that uses a dot also prints the status word (WCAG 1.4.1). */
            --vy-hk-pending: rgb(232, 112, 58);
            --vy-hk-progress: rgb(224, 166, 58);
            --vy-hk-clean: rgb(51, 160, 172);
            --vy-hk-inspected: rgb(67, 160, 107);
            --vy-hk-ooo: rgb(138, 128, 121);
            --vy-glass-border: rgba(255, 255, 255, 0.75);
            /* Polish Batch 1 — the faint warm-cream tint (incoming bubbles / tag chips / washes). Shared value across realms. */

            /* #104 fidelity — the decoded operator wireframe's warm peach→grey diagonal page background + the
               three ambient orbs behind the frosted glass (identical to the owner shell, which already ships it). */
            --vy-shadow: rgba(150, 90, 50, 0.16) 0 14px 40px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;
            /* Polish Batch 1 — brief-exact (design-system-brief §"Dark hero panel"); matches the owner layout. */

            --vy-r-lg: 20px;
            --vy-r-sm: 10px;

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
            background: var(--vy-bg);
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
        }

        /* #104 fidelity — ambient light streaks + floating color orbs (decoded from the operator wireframe;
           identical to the owner shell). Behind everything (z-index 0), they make the frosted glass visible.
           The animated ones are stilled by the reduced-motion floor below. */
        .vy-amb { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .vy-amb > div { position: absolute; pointer-events: none; }
        .vy-amb-streak1 { top: -180px; right: -120px; width: 640px; height: 640px; background: radial-gradient(circle, rgba(255, 255, 255, .35), transparent 68%); filter: blur(10px); }
        .vy-amb-streak2 { top: 22%; left: -10%; width: 70%; height: 260px; background: linear-gradient(120deg, rgba(255, 240, 220, .5), transparent); transform: rotate(28deg); filter: blur(24px); animation: vyShimmer 9s ease-in-out infinite; }
        .vy-amb-orb1 { top: 80px; left: 24%; width: 380px; height: 380px; background: var(--vy-orb-1); border-radius: 50%; filter: blur(70px); opacity: .55; animation: vyDrift 14s ease-in-out infinite; }
        .vy-amb-orb2 { top: 470px; left: 36%; width: 320px; height: 320px; background: var(--vy-orb-2); border-radius: 50%; filter: blur(70px); opacity: .55; }
        .vy-amb-orb3 { top: 220px; right: 4%; width: 300px; height: 300px; background: var(--vy-orb-3); border-radius: 50%; filter: blur(70px); opacity: .55; }

        main { position: relative; z-index: 1; }
        a { color: inherit; text-decoration: none; }

        /* ---- The real design keyframes (lifted from the design bundle) -------------------------- */
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes vyShimmer { 0% { opacity: .25; transform: translateX(-4%) rotate(28deg); } 50% { opacity: .45; transform: translateX(3%) rotate(28deg); } 100% { opacity: .25; transform: translateX(-4%) rotate(28deg); } }
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(46px, 28px); } 100% { transform: translate(0, 0); } }

        /* ---- App shell ------------------------------------------------------------------------- */
        .vy-shell { display: grid; grid-template-columns: 230px minmax(0, 1fr); min-height: 100vh; }

        /* #104 fidelity — the frosted nav floats directly on the gradient (no panel bg / border), exact-match
           to the decoded operator wireframe (width 230px, padding 26px 16px 30px). */
        .vy-side {
            position: sticky; top: 0; align-self: start; height: 100vh;
            padding: 26px 16px 30px; display: flex; flex-direction: column; gap: 18px;
            overflow-y: auto;
            /* No entrance animation: the sidebar is the same on every page, so it stays still (2026-10-09, his report). */
        }
        .vy-brand { display: flex; align-items: center; gap: 10px; padding: 0 8px; }
        .vy-brand-mark {
            width: 34px; height: 34px; border-radius: var(--vy-r-sm); background: var(--vy-grad);
            color: #fff; font-family: var(--vy-display); font-weight: 600; font-size: 17px;
            display: grid; place-items: center; box-shadow: var(--vy-shadow-accent);
        }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 17px; letter-spacing: -0.01em; }
        .vy-nav-group { display: flex; flex-direction: column; gap: 3px; }
        .vy-nav-label { font-size: 10.5px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--vy-on-grad-soft); padding: 0 12px 6px; }
        .vy-nav-item {
            display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: var(--vy-r-md);
            font-size: 13.5px; font-weight: 500; color: var(--vy-on-grad); cursor: pointer;
            transition: background .16s ease, color .16s ease;
        }
        .vy-nav-item .vy-ico { width: 16px; height: 16px; flex: none; }
        .vy-nav-item:hover { background: rgba(255, 255, 255, 0.35); }
        /* #104 fidelity — the active item reads terracotta on frosted glass (wireframe/owner exact), not espresso. */
        .vy-nav-item.is-active {
            background: rgba(255, 255, 255, 0.72); color: var(--vy-accent-text)   /* --vy-accent as TEXT measured 4.49 on the active pill */; font-weight: 500;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 14px rgba(150, 90, 50, 0.14); border: 1px solid rgba(255, 255, 255, 0.8);
        }
        /* ⚠️ N.16.6 — DE-EMPHASIS BELONGS ON A REDUNDANT MARK, NEVER ON MEANING-BEARING TEXT. This dimmed the
           whole locked nav item to .55, which took its LABEL to 2.89 and its "PRO" tag to 2.42 — the worst
           readings in the operator shell. Modelling the painted pair, even .9 leaves the tag at 4.42; only
           full opacity clears it (5.18 / 9.40). The locked state loses nothing by it: the explicit "PRO"
           tag and aria-disabled="true" both still carry it, which is exactly the redundant mark the rule
           asks for. */
        .vy-nav-item[aria-disabled="true"] { cursor: default; }
        .vy-nav-item[aria-disabled="true"]:hover { background: transparent; color: var(--vy-on-grad); }
        /* Team chat (2026-10-06) — Messages' live unread count (partials/live-pulse fills it; hidden at 0). */
        .vy-menu-count { margin-left: auto; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; font-variant-numeric: tabular-nums; color: #fff; background: linear-gradient(140deg, #E8703A, #C2410C); box-shadow: 0 2px 6px rgba(194, 65, 12, .28); }
        .vy-menu-count[hidden] { display: none; }

        /* Messages: Guests | Team (team chat, 2026-10-06) — the two kinds of conversation, each with its unread count. */
        .vy-msg-toggle { display: inline-flex; align-items: center; gap: 4px; padding: 4px; margin: 0 0 16px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-msg-tab { display: inline-flex; align-items: center; gap: 8px; height: 36px; padding: 0 16px 0 12px; border-radius: var(--vy-r-pill); font-size: 13.5px; font-weight: 600; color: var(--vy-on-grad); text-decoration: none; transition: background .15s ease, color .15s ease, box-shadow .15s ease; }
        .vy-msg-tab svg { width: 17px; height: 17px; flex: none; }
        .vy-msg-tab:hover { color: var(--vy-ink); background: rgba(255, 255, 255, 0.6); }
        .vy-msg-tab.is-on { background: #fff; color: var(--vy-accent-text); box-shadow: var(--vy-shadow-sm); }
        .vy-msg-tab:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-msg-count { min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; font-variant-numeric: tabular-nums; color: #fff; background: var(--vy-accent); }
        .vy-msg-count[hidden] { display: none; }
        @media (max-width: 560px) {
            .vy-msg-toggle { display: flex; width: 100%; }
            .vy-msg-tab { flex: 1; justify-content: center; padding: 0 10px; }
        }

        /* Main column */
        .vy-main { padding: 22px 30px 60px; max-width: 1480px; }
        .vy-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        /* ⚠️ `white-space: nowrap` + a growing lead — MEASURED, not cosmetic. `.vy-topbar` is a
           space-between flex row and BOTH sides were `flex: 0 1 auto`, so the actions cluster (search 227 +
           date chip 174 + bell 38 + account 165 ≈ 639px) won the negotiation and squeezed the lead to 255px.
           Every operator title is short enough to survive that except the longest one — "Rates &
           availability" — which wrapped onto TWO lines (255×81 instead of ~259×35). On one line the title
           needs ~259px and the actions' min-content is ~623px = 898 of the 910 available, so it fits; the
           search pill keeps its own 210px floor. Other pages are unaffected — their titles never reached
           the wrap point. */
        /* Screen-reader-only text. Defined ONCE here (a generic utility, not a page concern) so a state
           carried visually — an unread dot, a status colour — can still have real text for assistive tech. */
        .vy-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .vy-topbar h1 { font-family: var(--vy-display); font-weight: 600; font-size: 28px; letter-spacing: -0.02em; margin: 0; white-space: nowrap; }
        /* ⚠️ DO-EMAIL-5-continued — this line sits DIRECTLY ON THE PAGE GRADIENT, not on a glass card, so the
           card tokens do not apply to it. Measured with tools/contrast-sampler.php against the real composited
           plate at its worst position: `--vy-muted` reads 3.65 there and fails AA. `--vy-on-grad` exists for
           exactly this case and reads 4.50.
           ⚠️ Note the margin is nil, and that is a real finding rather than a rounding: --vy-on-grad was
           derived against the gradient stop rgb(241,185,150) (4.97), but the page paints down to
           rgb(241,172,134) — the gradient is DARKER than the value the token was computed against. */
        .vy-topbar-sub { font-size: 12.5px; color: var(--vy-on-grad); margin-top: 3px; }
        /* 14px, not 12: the bell's unread badge sits on the circle's outer corner and reaches 6px past it (2026-10-09). */
        .vy-topbar-actions { display: flex; align-items: center; gap: 14px; }

        /* Notification bell — 38px frosted glass, matching the operator prototype's own topbar
           (a `dc-import` of "Notif Bell" at hint-size 38px) and the glass recipe its sibling
           search-pill / date-chip use. The shared notifications.bell component no longer sets
           background/border/padding inline, so this rule is the single owner of the bell's appearance
           and needs no `!important`. Badge offsets are tuned for 38px (the token defaults suit 42px). */
        .vy-bell { position: relative; width: 38px; height: 38px; padding: 0; border-radius: 50%; display: grid; place-items: center; background: rgba(255, 252, 248, 0.58); border: 1px solid var(--vy-glass-border); box-shadow: 0 14px 40px rgba(150, 90, 50, .12), inset 0 1px 0 rgba(255, 255, 255, .65); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); color: var(--vy-ink); transition: transform .14s ease; }
        .vy-bell:hover { transform: translateY(-1px); }
        .vy-chip { display: flex; align-items: center; gap: 9px; padding: 5px 12px 5px 6px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-avatar-bg); color: var(--vy-on-peach); font-size: 11px; font-weight: 500; display: grid; place-items: center; flex: none; }
        .vy-avatar--lg { width: 38px; height: 38px; font-size: 13px; font-weight: 600; }
        .vy-chip-name { font-size: 13px; font-weight: 500; }

        /* GATE 0 — bespoke-chrome topbar (prototype-exact): read-only pill · search pill · date chip · account dropdown */
        [x-cloak] { display: none !important; }
        .vy-topbar-lead { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1 1 auto; }
        .vy-ro-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 13px; border-radius: var(--vy-r-pill); background: rgba(217, 154, 61, 0.18); color: #8F6414; font-size: 11.5px; font-weight: 500; white-space: nowrap; flex: none; }
        .vy-glass-pill { display: inline-flex; align-items: center; gap: 8px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); }
        .vy-search-pill { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); color: var(--vy-on-grad); font-size: 13px; min-width: 210px; text-decoration: none; transition: color .15s; white-space: nowrap; }
        .vy-search-pill:hover { color: var(--vy-muted); }
        .vy-search-short { display: none; }
        @media (max-width: 1240px) {
            /* ⚠️ 2026-10-05 — at 1,024 px the label broke into two lines; on one line it pushed into the page title. Shorter here. */
            .vy-search-long { display: none; }
            .vy-search-short { display: inline; }
            .vy-search-pill { min-width: 0; }
        }
        .vy-date-chip { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); font-size: 13px; font-weight: 500; white-space: nowrap; }
        .vy-acct { position: relative; }
        .vy-acct-btn { display: flex; align-items: center; gap: 9px; padding: 5px 9px 5px 14px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); white-space: nowrap; cursor: pointer; font-family: var(--vy-sans); transition: background .15s; }
        .vy-acct-btn:hover { background: rgba(255, 255, 255, 0.72); }
        .vy-acct-id { text-align: right; line-height: 1.2; }
        .vy-acct-name { display: block; font-size: 12.5px; font-weight: 500; color: var(--vy-ink); line-height: 1.25; }
        .vy-acct-role { display: block; font-size: 10.5px; color: var(--vy-on-grad); line-height: 1.2; }
        .vy-acct-chev { color: #B9ADA4; transition: transform .18s; flex: none; }
        .vy-acct-menu { position: absolute; top: calc(100% + 10px); right: 0; width: 252px; z-index: 70; background: rgba(255, 252, 248, 0.96); backdrop-filter: blur(30px) saturate(1.15); -webkit-backdrop-filter: blur(30px) saturate(1.15); border: 1px solid rgba(255, 255, 255, 0.85); border-radius: 18px; box-shadow: 0 24px 60px rgba(60, 30, 10, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.75); padding: 8px; }
        .vy-acct-head { display: flex; align-items: center; gap: 11px; padding: 10px 11px 12px; }
        .vy-acct-hid { min-width: 0; }
        .vy-acct-hid .vy-acct-name { font-size: 13px; }
        .vy-acct-email { display: block; font-size: 11px; color: var(--vy-faint); line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-acct-link { display: flex; align-items: center; gap: 10px; width: 100%; box-sizing: border-box; padding: 9px 11px; border-radius: 11px; text-decoration: none; color: var(--vy-ink); font-size: 13px; font-weight: 500; font-family: var(--vy-sans); background: none; border: 0; cursor: pointer; text-align: left; transition: background .14s; }
        .vy-acct-link:hover { background: rgba(120, 70, 40, 0.06); }
        .vy-acct-link--danger { color: #8F3110; }
        .vy-acct-link--danger:hover { background: rgba(143, 49, 16, 0.08); }
        .vy-acct-link--danger svg { stroke: #8F3110; }
        .vy-acct-div { height: 1px; background: rgba(120, 70, 40, 0.10); margin: 6px 10px; }
        .vy-toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        @media (prefers-reduced-motion: reduce) { .vy-amb-orb1, .vy-side, [style*="vyFadeUp"] { animation: none !important; } html { scroll-behavior: auto; } }

        /* Property switcher (Phase 6a · multi-property) — #104 fidelity: now a full-width frosted block in the
           sidebar (below the brand), matching the decoded operator wireframe. */
        .vy-propsw { display: flex; align-items: center; gap: 8px; margin: 0 4px; padding: 9px 12px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); color: var(--vy-muted); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
        .vy-propsw-ico { color: var(--vy-accent-text); flex: none; }
        .vy-propsw-select { flex: 1; min-width: 0; border: 0; background: transparent; font-family: inherit; font-size: 13px; font-weight: 500; color: var(--vy-ink); padding: 2px 0; cursor: pointer; }
        .vy-propsw-select:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; border-radius: 4px; }
        .vy-propsw-static { font-size: 13px; font-weight: 500; color: var(--vy-ink); }

        /* Portfolio (Phase 6a · Step 2) — per-property cards over the accessible set. */
        .vy-portfolio-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; margin-top: 14px; }
        .vy-prop-card { background: var(--vy-surface, rgba(255,255,255,.7)); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md, 16px); padding: 0; overflow: hidden; box-shadow: var(--vy-shadow-sm); display: flex; flex-direction: column; }
        .vy-prop-card.is-current { border-color: var(--vy-accent-border); box-shadow: 0 0 0 1px var(--vy-accent-border), var(--vy-shadow-sm); }
        .vy-prop-body { padding: 14px 17px 16px; display: flex; flex-direction: column; gap: 12px; flex: 1; }
        /* The design's full-bleed gradient card band. ⚠️ The prototype's band runs #E8703A→#C2410C and sets
           white ink on it — white on the #E8703A end measures 3.10, under the 4.5 AA bar for the 15px name.
           We keep the band + white ink but run it from the DEEP end (#C2410C→#9A330A), which measures 5.22
           at its lightest point: the design's structure without its contrast defect. */
        .vy-prop-band { background: linear-gradient(140deg, #C2410C, #9A330A); padding: 15px 17px; display: flex; align-items: center; gap: 12px; }
        .vy-prop-head { display: flex; align-items: center; gap: 12px; }
        .vy-prop-mark { width: 38px; height: 38px; border-radius: 11px; background: var(--vy-grad); color: #fff; font-size: 15px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vy-prop-band .vy-prop-mark { background: rgba(255,255,255,.20); box-shadow: inset 0 0 0 1px rgba(255,255,255,.28); }
        .vy-prop-id { min-width: 0; flex: 1; }
        .vy-prop-name { display: block; font-family: var(--vy-display, inherit); font-size: 15px; font-weight: 600; color: var(--vy-ink); }
        .vy-prop-band .vy-prop-name { color: #fff; }
        .vy-prop-current { margin-left: 7px; font-size: 9.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; color: var(--vy-accent-text); background: rgba(232,112,58,.12); border-radius: 5px; padding: 1px 6px; vertical-align: middle; }
        .vy-prop-band .vy-prop-current { color: #fff; background: rgba(255,255,255,.22); }
        .vy-prop-loc { display: block; font-size: 12px; color: var(--vy-muted); margin-top: 2px; }
        .vy-prop-band .vy-prop-loc { color: rgba(255,255,255,.93); }
        .vy-prop-stats { display: flex; }
        .vy-prop-stats > div { flex: 1; padding: 0 10px; text-align: left; border-left: 1px solid var(--vy-glass-border); }
        .vy-prop-stats > div:first-child { padding-left: 0; border-left: none; }
        .vy-ps-val { display: block; font-size: 17px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
        .vy-ps-lab { display: block; font-size: 10px; color: var(--vy-faint); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px; }
        /* Per-card occupancy rail — the design's bar under the stats. Real occupancy only (#39). */
        .vy-prop-occ { height: 5px; border-radius: 3px; background: rgba(120,70,40,.10); overflow: hidden; }
        .vy-prop-occ > span { display: block; height: 100%; border-radius: 3px; background: var(--vy-grad); }
        /* The design's chip row. A chip is a LINK only where it cannot mislead about scope — see the view. */
        .vy-prop-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .vy-prop-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 500; color: var(--vy-muted); background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); border-radius: 999px; padding: 4px 10px; text-decoration: none; }
        a.vy-prop-chip:hover { color: var(--vy-accent-text); border-color: var(--vy-accent-border); }
        .vy-prop-chip.is-static { background: transparent; }
        .vy-prop-status { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 500; color: var(--vy-muted); }
        .vy-status-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--vy-muted); flex: none; }
        .vy-prop-status.vy-status-ok { color: #3d6b3d; } .vy-status-ok .vy-status-dot { background: var(--vy-accent-3, #E8703A); }
        .vy-prop-status.vy-status-warn { color: #8f6414; } .vy-status-warn .vy-status-dot { background: #d99a3d; }
        .vy-prop-status.vy-status-muted { color: var(--vy-faint); } .vy-status-muted .vy-status-dot { background: var(--vy-faint); }
        .vy-prop-actions { display: flex; gap: 8px; margin-top: auto; padding-top: 2px; }
        .vy-prop-actions .vy-btn { flex: 1; justify-content: center; }
        .vy-prop-actions form { flex: 1; display: flex; }
        .vy-prop-actions form .vy-btn { width: 100%; }

        /* ── Remove a property (2026-09-29) — the archive-not-destroy removal, started from its card. ─────────────
           Quiet until asked: an icon beside Open/Edit. A property that is LEAVING loses its colour (a warm-grey band,
           hatched) and says when; the confirmation's one memorable thing is the path — today → last checkout → gone.
           Ink tones reuse the page's measured ones: #8F3110 (the account menu's danger text), #3d6b3d (status ok). */
        .vy-prop-actions .vy-prop-remove { flex: none; width: 40px; padding-left: 0; padding-right: 0; justify-content: center; color: var(--vy-muted); }
        .vy-prop-remove:hover, .vy-prop-remove:focus-visible { color: #8F3110; border-color: rgba(143, 49, 16, 0.32); background: rgba(143, 49, 16, 0.06); }
        .vy-prop-card.is-leaving .vy-prop-band { background: repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.06) 0 7px, transparent 7px 14px), linear-gradient(140deg, #6E625B, #4E443F); }
        .vy-prop-leaving-badge { margin-left: auto; flex: none; align-self: flex-start; font-size: 11px; font-weight: 600; color: #fff; background: rgba(255, 255, 255, 0.16); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.30); border-radius: 999px; padding: 4px 10px; white-space: nowrap; }
        .vy-prop-leaving { display: flex; gap: 10px; align-items: flex-start; font-size: 12.5px; line-height: 1.45; color: var(--vy-ink); background: rgba(143, 49, 16, 0.05); border: 1px solid rgba(143, 49, 16, 0.16); border-radius: var(--vy-r-sm); padding: 10px 12px; }
        .vy-prop-leaving svg { flex: none; margin-top: 1px; color: #8F3110; }
        .vy-prop-leaving strong { display: block; color: #8F3110; font-weight: 600; }

        .vy-modal-ico.is-danger { background: linear-gradient(140deg, #A63A12, #7A2A0C); box-shadow: 0 4px 12px rgba(143, 49, 16, 0.30); }
        .vy-rm-path { list-style: none; margin: 2px 0 16px; padding: 0; }
        .vy-rm-step { position: relative; padding: 0 0 14px 28px; }
        .vy-rm-step:last-child { padding-bottom: 0; }
        .vy-rm-step::before { content: ""; position: absolute; left: 4px; top: 4px; width: 11px; height: 11px; border-radius: 50%; background: #fff; box-shadow: inset 0 0 0 2px var(--vy-accent-3, #E8703A); }
        .vy-rm-step:not(:last-child)::after { content: ""; position: absolute; left: 9px; top: 18px; bottom: 2px; width: 1px; background: rgba(120, 70, 40, 0.24); }
        .vy-rm-step.is-end::before { background: #8F3110; box-shadow: 0 0 0 3px rgba(143, 49, 16, 0.14); }
        .vy-rm-when { display: block; font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-rm-what { display: block; font-size: 12.5px; color: var(--vy-muted); margin-top: 2px; line-height: 1.45; }
        .vy-rm-ledger { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px; }
        .vy-rm-col { border-radius: var(--vy-r-sm); padding: 10px 12px 11px; font-size: 12.5px; line-height: 1.55; color: var(--vy-ink); }
        .vy-rm-col h4 { margin: 0 0 3px; font-size: 12.5px; font-weight: 600; }
        .vy-rm-col ul { margin: 0; padding-left: 16px; }
        .vy-rm-col.is-kept { background: rgba(61, 107, 61, 0.07); border: 1px solid rgba(61, 107, 61, 0.20); }
        .vy-rm-col.is-kept h4 { color: #3d6b3d; }
        .vy-rm-col.is-goes { background: rgba(143, 49, 16, 0.05); border: 1px solid rgba(143, 49, 16, 0.15); }
        .vy-rm-col.is-goes h4 { color: #8F3110; }
        .vy-rm-only { font-size: 13px; line-height: 1.55; color: var(--vy-ink); margin: 2px 0 6px; }
        .vy-rm-only strong { display: block; margin-top: 6px; }
        .vy-rm-check { display: block; font-size: 11.5px; margin-top: 6px; min-height: 1.35em; color: var(--vy-faint); }
        .vy-rm-check.is-ok { color: #3d6b3d; }
        .vy-btn--danger { background: linear-gradient(140deg, #A63A12, #7A2A0C); color: #fff; border-color: transparent; box-shadow: 0 4px 12px rgba(143, 49, 16, 0.28); }
        .vy-btn--danger:hover { filter: brightness(1.06); }
        .vy-btn--danger:disabled { opacity: 0.42; cursor: not-allowed; box-shadow: none; filter: none; }
        .vy-modal-foot .vy-btn--danger { flex: 1; justify-content: center; padding: 12px 16px; }
        @media (max-width: 520px) { .vy-rm-ledger { grid-template-columns: minmax(0, 1fr); } }
        /* the one moment of motion: the path draws itself when the confirmation opens */
        @media (prefers-reduced-motion: no-preference) {
            .vy-rm-step { animation: vy-rm-in 0.32s ease-out both; }
            .vy-rm-step:nth-child(2) { animation-delay: 0.07s; }
            .vy-rm-step:nth-child(3) { animation-delay: 0.14s; }
        }
        @keyframes vy-rm-in { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: none; } }

        /* ── Choice tiles and the searchable dropdown (2026-09-29) — components/operator/choices + combobox. ────
           ⚠️ Never write a component TAG in a comment here: Blade compiles it even inside CSS, and the page breaks.
           Real radios and a real <select> underneath; these only give them a face. Same tokens as the fields beside them. */
        .vy-choices { border: 0; padding: 0; margin: 0 0 15px; min-width: 0; }
        .vy-choices legend { padding: 0; }
        .vy-choice-row { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(0, 1fr); gap: 8px; }
        .vy-choice { position: relative; display: flex; cursor: pointer; min-width: 0; }
        .vy-choice input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
        .vy-choice-face { flex: 1; display: flex; flex-direction: column; align-items: flex-start; gap: 7px; min-width: 0; padding: 11px 12px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.62); color: var(--vy-ink); font-size: 13px; font-weight: 500; line-height: 1.25; transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease, color 0.15s ease; }
        .vy-choice-face svg { color: var(--vy-muted); transition: color 0.15s ease; }
        .vy-choice-text { min-width: 0; max-width: 100%; overflow-wrap: anywhere; hyphens: auto; }
        @media (max-width: 520px) { .vy-choice-face { padding: 10px 9px; font-size: 12.5px; } }
        .vy-choice:hover .vy-choice-face { border-color: var(--vy-accent-border, rgba(232, 112, 58, 0.4)); background: rgba(255, 255, 255, 0.85); }
        .vy-choice input:checked + .vy-choice-face { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent); }
        .vy-choice input:checked + .vy-choice-face svg { color: #fff; }
        .vy-choice input:focus-visible + .vy-choice-face { outline: 2px solid var(--vy-accent-3, #E8703A); outline-offset: 2px; }
        .vy-choices--segmented .vy-choice-row { gap: 0; padding: 3px; border-radius: var(--vy-r-sm); background: rgba(120, 70, 40, 0.07); border: 1px solid var(--vy-glass-border); }
        .vy-choices--segmented .vy-choice-face { flex-direction: row; justify-content: center; padding: 8px 10px; border: 0; background: transparent; border-radius: 8px; }
        .vy-choices--segmented .vy-choice input:checked + .vy-choice-face { box-shadow: 0 2px 8px rgba(194, 65, 12, 0.24); }

        .vy-combo { position: relative; }
        .vy-combo-native { position: absolute !important; width: 1px !important; height: 1px !important; opacity: 0 !important; pointer-events: none !important; overflow: hidden; }
        .vy-combo-trigger { width: 100%; display: flex; align-items: center; gap: 10px; text-align: left; cursor: pointer; position: relative; min-height: 40px; }
        .vy-combo .vy-combo-trigger { padding-right: 38px; }   /* ⚠️ scoped: .vy-input comes later in this sheet and resets the padding */
        .vy-combo-trigger::after { content: ""; position: absolute; right: 13px; top: 50%; width: 8px; height: 8px; margin-top: -6px; border-right: 1.6px solid var(--vy-muted); border-bottom: 1.6px solid var(--vy-muted); transform: rotate(45deg); transition: transform 0.15s ease; }
        .vy-combo-trigger[aria-expanded="true"]::after { transform: rotate(225deg); margin-top: -2px; }
        .vy-combo-trigger:focus-visible { outline: 2px solid var(--vy-accent-3, #E8703A); outline-offset: 2px; }
        .vy-combo-value { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-combo-hint { flex: none; font-size: 11.5px; font-weight: 600; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vy-combo-panel { margin-top: 6px; padding: 8px; border-radius: var(--vy-r-sm); background: rgba(255, 252, 248, 0.97); border: 1px solid var(--vy-glass-border); box-shadow: 0 12px 30px rgba(150, 90, 50, 0.16); }
        .vy-combo-search { width: 100%; padding: 8px 11px; border-radius: 8px; border: 1px solid var(--vy-glass-border); background: #fff; font-family: var(--vy-sans); font-size: 13px; color: var(--vy-ink); }
        .vy-combo-search:focus { outline: none; border-color: var(--vy-accent-3, #E8703A); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.14); }
        .vy-combo-list { list-style: none; margin: 6px 0 0; padding: 0; max-height: 220px; overflow-y: auto; }
        .vy-combo-list li { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; font-size: 13px; color: var(--vy-ink); cursor: pointer; }
        .vy-combo-list li.is-active { background: rgba(232, 112, 58, 0.11); }
        .vy-combo-list li[aria-selected="true"] { font-weight: 600; }
        .vy-combo-list li[aria-selected="true"] .vy-combo-hint { color: var(--vy-accent-text); }
        .vy-combo-opt { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-combo-empty { margin: 8px 4px 2px; font-size: 12.5px; color: var(--vy-muted); }
        @media (prefers-reduced-motion: reduce) { .vy-choice-face, .vy-combo-trigger::after { transition: none; } }

        /* Analytics — Insights + Reports (Phase 6b). */
        .vy-ins-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; margin-top: 14px; }
        @media (max-width: 900px) { .vy-ins-grid { grid-template-columns: minmax(0, 1fr); } }

        /* ---- Operator Insights — wireframe parity ------------------------------------------------
           Scoped to .vy-ins. The page previously reused the shared .vy-ins-grid (also used by Reports,
           Owners, Billing-checkout, Profile and Maintenance-costs), which is left untouched. */
        .vy-ins-cols { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
        .vy-ins-main, .vy-ins-rail { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
        .vy-ins .vy-dk-val { font-size: 32px; }
        .vy-ins .vy-ins-sub { font-size: 11.5px; color: var(--vy-muted); margin: 2px 0 12px; }
        /* The delta reads as a pill beside the figure. */
        .vy-ins-delta { padding: 3px 8px; border-radius: var(--vy-r-pill); background: rgba(217, 102, 46, 0.14); color: #B4400F; font-size: 11px; font-weight: 500; white-space: nowrap; }
        .vy-ins-delta.is-down { background: rgba(143, 49, 16, 0.12); color: #8F3110; }

        /* Occupancy-by-month chart. Fixed-width bars as in the design; the axis is a true 0-100%. */
        .vy-ins-chart { display: flex; align-items: flex-end; gap: 6px; height: 170px; margin-top: 16px; }
        .vy-ins-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 7px; min-width: 0; }
        .vy-ins-chart-plot { width: 100%; display: flex; align-items: flex-end; justify-content: center; gap: 3px; height: 140px; }
        .vy-ins-bar { border-radius: 4px 4px 2px 2px; }
        .vy-ins-bar.is-ly { width: 10px; background: rgba(120, 70, 40, 0.18); }
        .vy-ins-bar.is-ty { width: 12px; background: linear-gradient(180deg, #E8703A, #C2410C); }
        .vy-ins-bar.is-otb { width: 12px; background: repeating-linear-gradient(135deg, rgba(232,112,58,.8) 0 3px, rgba(232,112,58,.3) 3px 6px); }
        .vy-ins-chart-lbl { font-size: 10.5px; color: var(--vy-muted); }

        /* "Worth a look" — the design's icon-tile rows, divided, not a grid of mini-cards. */
        .vy-ins-obs { display: flex; flex-direction: column; margin-top: 8px; }
        .vy-ins-ob { display: flex; gap: 14px; padding: 16px 0; border-bottom: 1px solid rgba(120, 70, 40, 0.08); }
        .vy-ins-ob:last-child { border-bottom: none; padding-bottom: 4px; }
        .vy-ins-ob-ico { width: 36px; height: 36px; border-radius: 12px; background: rgba(217, 102, 46, 0.14); color: #B4400F; display: flex; align-items: center; justify-content: center; flex: none; }
        .vy-ins-ob.is-deferred .vy-ins-ob-ico { background: rgba(120, 70, 40, 0.08); color: var(--vy-muted); }
        .vy-ins-ob-t { font-size: 13.5px; font-weight: 500; color: var(--vy-ink); }
        .vy-ins-ob-b { font-size: 12.5px; color: var(--vy-muted); line-height: 1.55; margin: 3px 0 0; }
        /* The design's #B4400F measures 4.48:1 here — just under AA at this size. --vy-accent clears it. */
        .vy-ins-ob-a { display: inline-flex; align-items: center; gap: 6px; margin-top: 7px; font-size: 12px; font-weight: 500; color: var(--vy-accent-text); text-decoration: none; }

        /* By-property, as the design's compact rail table (the shared .vy-utable forces min-width:720px,
           which is why this clipped inside the rail). */
        .vy-ins-bp { display: grid; grid-template-columns: minmax(0, 1fr) 40px 46px 52px; gap: 4px 8px; align-items: center; margin-top: 4px; }
        .vy-ins-bp-h { font-size: 10px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); text-align: right; }
        .vy-ins-bp-c { font-size: 12.5px; padding: 7px 0; border-top: 1px solid rgba(120, 70, 40, 0.08); text-align: right; font-variant-numeric: tabular-nums; color: var(--vy-ink); }
        .vy-ins-bp-c.is-name { text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-ins-bp-c.is-group { font-weight: 600; }

        /* Nights that fill first — the design's near-square tiles with the value seated at the bottom. */
        .vy-ins-nights { display: flex; gap: 5px; }
        .vy-ins-night { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; min-width: 0; }
        .vy-ins-night-tile { width: 100%; aspect-ratio: 1 / 1.25; border-radius: 9px; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 5px; box-sizing: border-box; font-size: 10px; font-weight: 500; font-variant-numeric: tabular-nums; }
        .vy-ins-night-lbl { font-size: 10px; color: var(--vy-muted); }

        @media (max-width: 1100px) { .vy-ins-cols { grid-template-columns: minmax(0, 1fr); } }

        /* ---- Operator Reports — wireframe parity (scoped to .vy-rep) ----------------------------- */
        /* This wireframe's KPI tile is tighter than the Dashboard's; scoped, not pushed at the shared token. */
        .vy-rep .vy-dash-kpi { padding: 15px 18px; }
        .vy-rep .vy-dk-val { font-size: 25px; }
        /* Revenue by channel — the design's share list. */
        .vy-rep-ch-head, .vy-rep-ch-row { display: grid; grid-template-columns: 1.1fr .7fr .8fr 1.2fr; align-items: center; gap: 14px; }
        .vy-rep-ch-head { padding: 8px 10px 10px; font-size: 10.5px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-rep-ch-row { padding: 12px 10px; margin: 0 -10px; border-radius: 12px; border-top: 1px solid var(--vy-hairline); transition: background .14s; }
        .vy-rep-ch-row:hover { background: rgba(255, 255, 255, 0.45); }
        .vy-rep-ch-name { display: flex; align-items: center; gap: 9px; min-width: 0; }
        .vy-rep-ch-dot { width: 9px; height: 9px; border-radius: 3px; background: var(--vy-grad); flex: none; }
        .vy-rep-ch-lbl { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-rep-ch-rev { font-family: var(--vy-display); font-weight: 500; font-size: 13.5px; }
        .vy-rep-ch-share { display: flex; align-items: center; gap: 8px; }
        .vy-rep-ch-track { flex: 1; height: 6px; border-radius: 4px; background: rgba(120, 70, 40, 0.10); overflow: hidden; display: block; }
        .vy-rep-ch-track > span { display: block; height: 100%; border-radius: 4px; background: var(--vy-grad); }
        .vy-rep-ch-pct { font-size: 11px; color: var(--vy-muted); font-variant-numeric: tabular-nums; width: 34px; text-align: right; }
        .vy-rep-ch-total { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 0 2px; border-top: 1px solid rgba(120, 70, 40, 0.16); margin-top: 2px; font-family: var(--vy-display); font-weight: 600; }
        .vy-rep-ch-total > :first-child { font-size: 14px; }
        .vy-rep-ch-total > :last-child { font-size: 16px; color: var(--vy-accent-text); letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
        .vy-rep .vy-num { text-align: right; font-variant-numeric: tabular-nums; font-size: 13px; }

        /* ---- Operator Reservations (list) — wireframe parity (scoped to .vy-res) ----------------- */
        /* This wireframe's stat tile is tighter than the Dashboard's; scoped, not pushed at the token. */
        .vy-res .vy-dash-kpi { padding: 15px 18px; }
        .vy-res .vy-dk-val { font-size: 26px; }
        /* ---- Housekeeping (.vy-hk) -----------------------------------------------------------------
           The design's status strip is a row of standalone COUNT PILLS, not cards: a 9px dot, the
           status word, then the tally in the display face. Sizing per the prototype (9px 16px, pill
           radius, 15px/600 count) — the count sits at 600 like every other figure in this realm. */
        /* .vy-seg-opt is flex:1, which is right for a full-width two-up toggle but stretches a short
           filter row across the whole page. Opt-in modifier: size the options to their own labels.
           NOTE the doubled class: .vy-seg is declared TWICE in this file (pre-existing), and the later
           `display: flex` would beat a single-class modifier at equal specificity. Out-specify, don't
           reorder — moving a shared rule to win a cascade fight breaks whatever relied on the old order. */
        .vy-seg.vy-seg--inline { display: inline-flex; }
        .vy-seg--inline > .vy-seg-opt { flex: 0 0 auto; padding-left: 14px; padding-right: 14px; }

        /* ---- Team schedule (.vy-sch) ---------------------------------------------------------------
           The design puts the roster grid beside a 224px rail (coverage + open shifts) rather than
           stacking full-width cards down the page — the at-a-glance numbers belong next to the grid
           they describe. The time-off FORMS stay full-width below: they carry date pickers and a
           select, which a 224px column cannot hold usably. */
        .vy-sch-cols { display: flex; gap: 18px; align-items: flex-start; }
        .vy-sch-main { flex: 1; min-width: 0; }
        .vy-sch-rail { width: 224px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; }
        /* The rail's cards are stacked by the flex gap, so their own stacking margin must go. */
        .vy-sch-rail > .vy-card { margin-top: 0 !important; }

        /* ⚠️ The time-off pair below the roster. `minmax(0, 1fr)` rather than `1fr` for the same reason as
           everywhere else in this file, and they STACK on a phone rather than scroll: both panels are made
           of CONTROLS — a request form on the left, an approve/decline queue on the right — and horizontal
           scroll is this project's answer for wide TABLES, where a row carries the meaning, never for
           controls a thumb has to reach. Same argument as the kanban at :1716. */
        .vy-sch-panels { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 18px; }
        .vy-sch-panels--single { grid-template-columns: minmax(0, 1fr); }

        @media (max-width: 1100px) {
            .vy-sch-cols { display: block; }
            .vy-sch-rail { width: auto; margin-top: 18px; }
        }
        @media (max-width: 860px) {
            .vy-sch-panels { grid-template-columns: minmax(0, 1fr); }
        }

        /* ---- Inbox: the conversation's reservation context strip -----------------------------------
           Sits under the thread header so a replier can see who they are answering. Quiet by design —
           it is context, not a headline; the conversation stays the loud thing on the page. */
        .vy-thread-context { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 16px; padding: 10px 18px; border-bottom: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.28); }
        .vy-tc-item { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--vy-muted); }
        .vy-tc-item svg { color: var(--vy-faint); flex: none; }
        .vy-tc-link { margin-left: auto; font-size: 11.5px; color: var(--vy-accent-text); text-decoration: none; }
        .vy-tc-link:hover { text-decoration: underline; }
        @media (max-width: 700px) { .vy-tc-link { margin-left: 0; } }

        .vy-hk-tally { display: flex; flex-wrap: wrap; gap: 8px; margin: 4px 0 18px; }
        .vy-hk-tal { display: inline-flex; align-items: center; gap: 9px; padding: 9px 16px; border-radius: var(--vy-r-pill); background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6); }
        .vy-hk-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; }
        .vy-hk-tal-label { font-size: 12.5px; color: var(--vy-ink); }
        /* DO-EMAIL-7: was --vy-muted. The chip is --vy-glass — TRANSLUCENT over the page gradient — so its
           plate moves with the chip's position (§N.13.4). Sampled on the real render: --vy-muted reads 6.00 on
           a light part of the band but only 4.25 on a saturated one, i.e. it fails at FULL opacity there.
           --vy-ink clears everywhere (9.38 on the worst plate sampled). */
        .vy-hk-tal-n { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
        /* A zero tally is real information, but it should not compete with the states that need work. */
        .vy-hk-tal.is-zero .vy-hk-dot { opacity: .45; }
        /* DO-EMAIL-7: the whole chip used to drop to opacity .62, which dimmed its TEXT to 2.28 — an AA failure
           applied to a status word. The empty state is already carried by the "0" beside it, so the de-emphasis
           now rides the DOT (a redundant, non-text mark) and the label stays legible. */

        /* ---- The design's search control: ONE integrated pill (icon + field + inline action) ------
           An OPT-IN modifier on the shared .vy-input-group, defined ONCE. Three wireframes specify it
           (Reservations, Guests, Housekeeping); writing it per-page would be three copies of one rule,
           which is exactly how the drift this project keeps consolidating starts.
           The form stays a GET form — a search is a scope-dependent read (#20). */
        .vy-input-group--pill { align-items: center; gap: 0; padding: 3px 4px 3px 15px; border-radius: var(--vy-r-pill); background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); }
        .vy-input-group--pill:focus-within { border-color: var(--vy-accent-border); }
        .vy-input-group--pill .vy-search-ico { flex: none; margin-right: 9px; color: var(--vy-muted); }
        .vy-input-group--pill .vy-input { flex: 1; min-width: 0; border: none; background: transparent; box-shadow: none; padding: 7px 0; font-size: 13px; }
        .vy-input-group--pill .vy-input:focus { outline: none; }
        .vy-input-group--pill .vy-btn, .vy-input-group--pill .vy-pill { flex: none; padding: 6px 14px; font-size: 12.5px; }

        /* ---- Operator Guests (CRM list) — wireframe parity (scoped to .vy-gst) ------------------- */
        /* Same tighter stat tile this wireframe specifies (15px/18px, 26px figure) as Reports/Reservations. */
        .vy-gst .vy-dash-kpi { padding: 15px 18px; }
        .vy-gst .vy-dk-val { font-size: 26px; }

        /* ---- Operator Reservation DETAIL — wireframe parity (scoped to .vy-det) ------------------ */
        /* The design's folio line: date | description | amount. Same real rows, ordered as designed. */
        .vy-det .vy-ledger-row { display: grid; grid-template-columns: 64px minmax(0, 1fr) auto; align-items: center; gap: 14px; padding: 12px 10px; margin: 0 -10px; border-radius: 12px; transition: background .14s; }
        .vy-det .vy-ledger-row:hover { background: rgba(255, 255, 255, 0.45); }
        .vy-det .vy-ledger-date { font-size: 12px; color: var(--vy-muted); white-space: nowrap; }
        .vy-det .vy-ledger-main { display: flex; align-items: baseline; gap: 9px; flex-wrap: wrap; min-width: 0; }
        .vy-det .vy-ledger-label { font-size: 13.5px; font-weight: 500; }
        .vy-det .vy-ledger-sub { font-size: 11.5px; color: var(--vy-muted); }
        .vy-det .vy-ledger-amt { font-weight: 500; min-width: 0; }
        /* The design's totals band: the three figures side by side, the balance carrying the emphasis. */
        .vy-det .vy-totals { flex-direction: row; align-items: flex-end; gap: 26px; flex-wrap: wrap; padding-top: 18px; }
        .vy-det .vy-total-row { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; }
        /* `.vy-total-row.is-balance` (shared) sets a border-top + margin for the STACKED layout and is
           declared later in this file at equal specificity, so it must be out-specified, not just restated:
           in a row that border draws a stray rule over one cell. The band's own border already divides it. */
        .vy-det .vy-total-row.is-balance { border-top: none; margin-top: 0; padding-top: 0; }
        .vy-det .vy-t-label { font-size: 10.5px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-det .vy-t-val { font-family: var(--vy-display); font-weight: 500; font-size: 16px; font-variant-numeric: tabular-nums; }
        .vy-det .vy-total-row.is-balance .vy-t-label { color: var(--vy-accent-text); }
        .vy-det .vy-total-row.is-balance .vy-t-val { font-weight: 600; font-size: 24px; letter-spacing: -0.02em; color: var(--vy-accent-text); }
        /* A settled balance is not an alarm — it reads as ink, not accent. */
        .vy-det .vy-total-row.is-balance.is-settled .vy-t-label { color: var(--vy-muted); }
        .vy-det .vy-total-row.is-balance.is-settled .vy-t-val { color: var(--vy-ink); }
        /* Terminal-state banner (cancelled / no-show). The design tints the whole strip and leads with a
           round icon; the state is ALSO named in words beside it, so colour is never the only carrier
           (WCAG 1.4.1). Rose reads "cancelled", amber reads "no-show" — the design's own pairing. */
        .vy-det .vy-term { border-width: 1px; border-style: solid; }
        .vy-det .vy-term.is-cancelled { background: rgba(143, 49, 16, 0.08); border-color: rgba(143, 49, 16, 0.20); }
        .vy-det .vy-term.is-no-show { background: rgba(217, 154, 61, 0.14); border-color: rgba(217, 154, 61, 0.35); }
        .vy-det .vy-term-head { display: inline-flex; align-items: center; gap: 12px; min-width: 0; }
        .vy-det .vy-term-ico { width: 36px; height: 36px; border-radius: 50%; flex: none; display: flex; align-items: center; justify-content: center; }
        .vy-det .vy-term.is-cancelled .vy-term-ico { background: rgba(143, 49, 16, 0.12); color: #8F3110; }
        .vy-det .vy-term.is-no-show .vy-term-ico { background: rgba(217, 154, 61, 0.25); color: #8F6414; }
        .vy-det .vy-term.is-cancelled .vy-eyebrow { color: #8F3110; }
        .vy-det .vy-term.is-no-show .vy-eyebrow { color: #8F6414; }
        .vy-card-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 12px; }
        .vy-card-head h3 { margin: 0; font-family: var(--vy-display, inherit); font-size: 14px; font-weight: 600; color: var(--vy-ink); }
        .vy-card-note { font-size: 11.5px; color: var(--vy-faint); }
        .vy-trend { display: flex; align-items: flex-end; gap: 8px; height: 150px; padding-top: 6px; }
        .vy-trend-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; }
        .vy-trend-bar-wrap { flex: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; }
        .vy-trend-bar { display: block; width: 62%; min-height: 2px; border-radius: 5px 5px 0 0; background: var(--vy-grad); }
        .vy-trend-lab { font-size: 10.5px; color: var(--vy-muted); }
        .vy-mix-row { display: flex; align-items: center; gap: 10px; margin-bottom: 9px; }
        .vy-mix-lab { flex: 0 0 92px; font-size: 12.5px; color: var(--vy-ink); }
        .vy-mix-track { flex: 1; height: 8px; border-radius: 999px; background: rgba(120,70,40,.1); overflow: hidden; }
        .vy-mix-fill { display: block; height: 100%; border-radius: 999px; background: var(--vy-accent-3, #E8703A); }
        .vy-mix-val { flex: 0 0 38px; text-align: right; font-size: 12px; font-weight: 600; color: var(--vy-muted); }
        .vy-report-line { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--vy-glass-border); font-size: 13.5px; color: var(--vy-ink); }
        .vy-report-line:last-child { border-bottom: 0; }
        .vy-report-muted { color: var(--vy-muted); }
        .vy-report-total { font-weight: 700; border-top: 1.5px solid var(--vy-glass-border); border-bottom: 0; margin-top: 2px; }
        .vy-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .vy-table th { text-align: left; font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-muted); padding: 6px 8px; border-bottom: 1px solid var(--vy-glass-border); }
        .vy-table td { padding: 9px 8px; border-bottom: 1px solid var(--vy-glass-border); color: var(--vy-ink); }
        .vy-table tr:last-child td { border-bottom: 0; }
        .vy-table .vy-num { text-align: right; font-variant-numeric: tabular-nums; }

        /* Billing — Paywall + Checkout (Phase 7b). */
        .vy-bill-badge { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 600; padding: 5px 12px; border-radius: var(--vy-r-pill); border: 1px solid var(--vy-glass-border); }
        .vy-bill-trial { color: #8f6414; } .vy-bill-trial .vy-status-dot { background: #d99a3d; }
        .vy-bill-ok { color: #3d6b3d; } .vy-bill-ok .vy-status-dot { background: var(--vy-accent-3, #E8703A); }
        .vy-bill-warn { color: #8f3110; } .vy-bill-warn .vy-status-dot { background: #8f3110; }
        .vy-bill-line { font-size: 13.5px; color: var(--vy-ink); }
        .vy-bill-tiers { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        .vy-bill-tier { position: relative; background: rgba(255,255,255,.7); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md, 16px); padding: 22px 20px; box-shadow: var(--vy-shadow-sm); display: flex; flex-direction: column; }
        .vy-bill-tier.is-featured { border-color: var(--vy-accent-border); box-shadow: 0 0 0 1px var(--vy-accent-border), var(--vy-shadow-sm); }
        .vy-bill-tag { position: absolute; top: -10px; right: 16px; font-size: 9.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #fff; background: var(--vy-grad); border-radius: 6px; padding: 3px 8px; }
        .vy-bill-name { font-family: var(--vy-display, inherit); font-size: 18px; font-weight: 600; margin: 0 0 4px; }
        .vy-bill-blurb { font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px; }
        .vy-bill-price { font-size: 30px; font-weight: 700; color: var(--vy-ink); font-family: var(--vy-display, inherit); }
        .vy-bill-per { font-size: 14px; font-weight: 500; color: var(--vy-muted); }
        .vy-bill-yearly { font-size: 11.5px; color: var(--vy-faint); margin: 3px 0 16px; }
        .vy-bill-feats { list-style: none; margin: 0 0 18px; padding: 0; display: flex; flex-direction: column; gap: 9px; flex: 1; }
        .vy-bill-feats li { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--vy-ink); }
        .vy-bill-feats li svg { color: var(--vy-accent-3, #E8703A); flex: none; }
        .vy-bill-feats li.is-off { color: var(--vy-faint); } .vy-bill-feats li.is-off svg { color: var(--vy-faint); }
        .vy-bill-note { color: var(--vy-faint); font-size: 11.5px; }
        .vy-bill-disclaimer { font-size: 11.5px; color: var(--vy-faint); margin-top: 14px; }

        /* Operator Billing exact-match — settings sub-nav, invoices, retention modal */
        /* Settings sub-nav (General / Team / Billing) — the design's SEGMENTED PILL control, not an
           underline tab row. Spec taken from the decoded Operator Billing prototype: a glass pill container
           (3px pad, 2px gap, 30px radius, blur 22px) with 7px/16px pills; the active pill is a raised white
           surface. Rendered by the Billing page only, so this is page-local, not shared chrome — the
           Operator Settings prototype has no such control at all.
           ⚠️ The prototype's own colours are NOT copied: its #8A8079 label is the pre-AA --vy-muted value and
           its #C2410C active label is accent-as-text, which measured under AA in the P3 sweep. Both use the
           AA tokens instead (--vy-muted, --vy-accent-text) — matching the design's INTENT, not its contrast
           defect. */
        .vy-subnav { display: inline-flex; align-items: center; gap: 2px; padding: 3px; border-radius: var(--vy-r-pill); background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); margin-bottom: 22px; }
        .vy-subnav-tab { padding: 7px 16px; border-radius: var(--vy-r-pill); font-size: 12.5px; font-weight: 500; color: var(--vy-muted); text-decoration: none; transition: background .15s, color .15s; }
        .vy-subnav-tab:hover { background: rgba(255, 255, 255, 0.45); color: var(--vy-ink); }
        .vy-subnav-tab.is-active { background: rgba(255, 255, 255, 0.85); box-shadow: 0 2px 8px rgba(150, 90, 50, 0.14); color: var(--vy-accent-text); }
        .vy-inv-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-inv-row:last-child { border-bottom: none; }
        .vy-inv-ref { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; }
        .vy-inv-sub { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; }
        .vy-status--paid { display: inline-flex; align-items: center; font-weight: 700; color: #1B6B48; background: rgba(27,107,72,.12); border-radius: var(--vy-r-pill); padding: 2px 9px; }
        .vy-modal-scrim { position: fixed; inset: 0; background: rgba(42,33,28,.44); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px; z-index: 60; }
        .vy-modal { background: var(--vy-glass, #fffdfb); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow-lg, 0 20px 60px rgba(150,90,50,.24)); padding: 26px; width: 100%; max-width: 460px; }
        .vy-retain-opt { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-retain-opt:last-of-type { border-bottom: none; }
        [x-cloak] { display: none !important; }
        .vy-seg { display: inline-flex; border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-pill); overflow: hidden; }
        .vy-seg-btn { padding: 7px 16px; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); text-decoration: none; }
        .vy-seg-btn.is-on { background: var(--vy-grad); color: #fff; }
        .vy-bill-gate { display: flex; gap: 10px; align-items: flex-start; margin: 14px 0; padding: 12px 14px; border-radius: var(--vy-r-md); background: rgba(224,146,30,.1); border: 1px solid rgba(224,146,30,.32); font-size: 12.5px; color: var(--vy-ink); }
        .vy-bill-gate svg { color: var(--vy-amber, #b26a08); flex: none; margin-top: 1px; }
        .vy-plan-tag { margin-left: auto; font-size: 8.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #fff; background: var(--vy-accent); border-radius: 5px; padding: 1px 5px; }

        /* ---- Reusable surfaces ----------------------------------------------------------------- */
        .vy-card {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 22px;
        }

        /* ⚠️ DO-STATE-1 — A MODAL CARD IS NOT A PAGE CARD (the axis-1 finding).
           `--vy-glass` is rgba(255,252,248,.58), tuned for a card on the LIGHT page. Inside a modal the same
           card sits on a DARK scrim (rgba(43,35,32,.4)) which composites through it: the plate falls from
           ~rgb(249,223,207) to ~rgb(215,199,189), and every muted/faint label in every modal is measured
           against THAT. Measured on the forced-open Message dialog: --vy-faint 2.79, --vy-muted 3.38.
           Raising only the MODAL card's opacity fixes all of them at once; page cards are untouched (they
           already pass, and darkening them would violate posture A). The backdrop blur survives.
           ⚠️ PLACED AFTER `.vy-card` DELIBERATELY: both are single-class selectors, so specificity TIES and
           ORDER decides. Defined before it, this rule silently loses — which is what happened on the first
           two attempts, caught only by re-measuring the page (§N.21.6). */
        .vy-modal-card { background: rgba(255, 252, 248, 0.94); }
        .vy-card--flush { padding: 0; overflow: hidden; }
        .vy-card--accent { border-color: var(--vy-accent-border); }   /* Polish Batch 1 — the accent card treatment (mirrors the owner layout's canonical definition). */
        /* Polish Batch 2 — the shared hover for an interactive TAP-TARGET card (opt-in; never on a static/placeholder card).
           Prototype-matched: a small lift + an elevated shadow (proto card-hover = translateY(-1…-3px) + 0 18–44px soft shadow). */
        .vy-card--tappable { cursor: pointer; transition: transform .16s ease, box-shadow .16s ease; }
        .vy-card--tappable:hover { transform: translateY(-2px); box-shadow: 0 18px 44px rgba(150, 90, 50, .18); }
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
        .vy-btn:focus-visible, .vy-nav-item:focus-visible, .vy-icobtn:focus-visible, .vy-rowopen:focus-visible, .vy-detail-head:focus-visible,
        .vy-gl-chip:focus-visible, .vy-gl-sort:focus-visible, .vy-gl-name:focus-visible, .vy-gl-more:focus-visible, .vy-gl-menu-item:focus-visible,
        .vy-gl-size:focus-visible, .vy-gl-page:focus-visible, .vy-gl-clear:focus-visible, .vy-gl-link:focus-visible, .vy-modal-x:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* ⚠️⚠️ A BUTTON THAT INHERITS ITS SURROUNDINGS — the ONE reset, so a keyboard path can be added to
           a row without changing how that row looks to everyone else. Two surfaces needed a real control
           where the markup could not become one: a work-order row (it contains a checkbox and an assign
           button, so a `<button>` may not wrap it) and a housekeeping lane header (it can, and does).
           ⚠️ THE RING IS ON THE RULE ABOVE, NOT HERE. Same reason as the owner shell: this file's focus
           rule is CLASS-SCOPED and reaches nothing it does not name, so anything new must be named in it
           or it is focusable and invisible. */
        .vy-rowopen { background: none; border: none; padding: 0; margin: 0; font: inherit; color: inherit; text-align: left; cursor: pointer; }
        button.vy-detail-head { width: 100%; background: none; border: none; padding: 0; font: inherit; color: inherit; text-align: left; cursor: pointer; }

        /* A control with no backend yet: visible, clearly non-functional, never a fake action. */
        .vy-btn.is-soon, .vy-icobtn.is-soon, .vy-pill.is-soon { opacity: .5; cursor: not-allowed; }
        .vy-soon-tag { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-on-grad); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 2px 6px; }

        .vy-pill {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: var(--vy-r-pill);
            font-size: 12.5px; font-weight: 500; color: var(--vy-muted);
            background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); transition: all .16s ease;
        }
        /* Polish Batch 1 — the SELECTED range/filter pill (operator Insights/Reports). Prototype-consistent:
           the prototype's selected pills are gradient-filled with white text + an accent shadow (as `.vy-seg-btn.is-on`). */
        .vy-pill.is-on { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent); }
        .vy-icobtn { width: 36px; height: 36px; border-radius: var(--vy-r-sm); display: grid; place-items: center; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); color: var(--vy-ink); box-shadow: var(--vy-shadow-sm); cursor: pointer; }
        .vy-icobtn[aria-disabled="true"] { opacity: .4; cursor: default; }

        .vy-empty { text-align: center; color: var(--vy-muted); padding: 40px 20px; font-size: 13.5px; }
        .vy-deferred-note { font-size: 11.5px; color: var(--vy-on-grad); display: inline-flex; align-items: center; gap: 6px; }

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

        /* #104 GATE 2 — the shared x-charts.bars vertical chart (Reports revenue-by-month). SCOPED under
           .vy-chart so it never collides with the rack booking-bar `.vy-bar` above (same class, different
           element). Values mirror the owner layout's chart, so both realms' charts read identically. */
        .vy-chart { display: flex; align-items: flex-end; gap: 10px; height: 150px; padding-top: 8px; }
        .vy-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 8px; min-width: 0; height: 100%; }
        .vy-chart .vy-bar { grid-row: auto; display: block; width: 100%; max-width: 44px; height: auto; margin: 0; padding: 0; border-radius: 8px 8px 4px 4px; background: var(--vy-grad-peach); box-shadow: none; overflow: visible; transition: height .3s ease; }
        .vy-chart .vy-bar.is-last { background: var(--vy-grad); }
        .vy-chart .vy-bar-label { display: block; font-size: 10.5px; color: var(--vy-muted); white-space: nowrap; }
        .vy-bar .vy-bar-guest { overflow: hidden; text-overflow: ellipsis; }
        .vy-bar .vy-bar-meta { font-weight: 500; opacity: .82; font-size: 10.5px; }
        .vy-bar .vy-bar-flag { margin-left: auto; font-size: 8.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; background: rgba(255,255,255,.22); border-radius: 5px; padding: 1px 5px; }
        .vy-bar.is-clip-start { border-top-left-radius: 3px; border-bottom-left-radius: 3px; }
        .vy-bar.is-clip-end { border-top-right-radius: 3px; border-bottom-right-radius: 3px; }
        .vy-rt-lanes { border-bottom: 1px solid var(--vy-hairline); }
        .vy-rt-lanes .vy-lane:last-child .vy-lane-bg > div { border-bottom: none; }

        /* GATE 5.1 — one row per PHYSICAL UNIT under each room-type header. The narrow label reads
           "code + name"; the timeline lane carries that unit's bookings + its out-of-order bars. */
        .vy-unit-label {
            padding: 6px 14px 6px 24px; border-bottom: 1px solid var(--vy-hairline);
            display: flex; flex-direction: column; justify-content: center; gap: 1px;
            background: rgba(255, 255, 255, 0.16);
        }
        .vy-unit-code { font-size: 12px; font-weight: 600; color: var(--vy-ink); }
        .vy-unit-name { font-size: 10px; color: var(--vy-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-unit-row .vy-lane { min-height: 38px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-unit-row .vy-lane-bars { min-height: 38px; z-index: 2; }

        /* ---- Operator Calendar — wireframe parity -----------------------------------------------
           Scoped to .vy-cal: .vy-bar, .vy-day and .vy-unit-row are shared with other rack-like
           surfaces, so only the calendar takes the design's proportions. */
        /* The design's range switcher: a segmented pill in the right-hand control group. */
        .vy-cal .vy-seg-range { display: inline-flex; align-items: center; gap: 2px; padding: 3px; border-radius: var(--vy-r-pill); background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); }
        .vy-cal .vy-seg-btn { padding: 6px 13px; border-radius: var(--vy-r-pill); font-size: 12px; font-weight: 500; color: var(--vy-muted); text-decoration: none; transition: background .16s, color .16s; }
        .vy-cal .vy-seg-btn:hover { color: var(--vy-ink); }
        .vy-cal .vy-seg-btn.is-on { background: #fff; color: var(--vy-accent); box-shadow: var(--vy-shadow-sm); }
        .vy-cal .vy-seg-btn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* The design's room-type control reads as a glass pill with a chevron, not a bare select. */
        .vy-cal .vy-rack-filter { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px 7px 15px; border-radius: var(--vy-r-pill); background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); transition: background .16s; }
        .vy-cal .vy-rack-filter:hover { background: rgba(255, 255, 255, 0.75); }
        .vy-cal .vy-rack-filter .vy-propsw-select { font-size: 12.5px; color: var(--vy-muted); }

        /* Today's column head carries a short gradient rule, inset from the edges. */
        .vy-cal .vy-day.is-today { position: relative; }
        .vy-cal .vy-day.is-today::before { content: ''; position: absolute; top: 0; left: 18%; right: 18%; height: 3px; border-radius: 0 0 3px 3px; background: var(--vy-grad); }
        .vy-cal .vy-day.is-today .vy-day-num { color: var(--vy-accent-text); }

        /* Row rhythm + the design's two-line booking bar (name over a smaller detail line). */
        .vy-cal .vy-unit-row .vy-lane, .vy-cal .vy-unit-row .vy-lane-bars { min-height: 52px; }
        .vy-cal .vy-bar { height: 38px; align-items: center; gap: 6px; }
        .vy-cal .vy-bar .vy-bar-txt { min-width: 0; overflow: hidden; display: flex; flex-direction: column; justify-content: center; }
        .vy-cal .vy-bar .vy-bar-guest { display: block; font-size: 12px; font-weight: 500; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; }
        .vy-cal .vy-bar .vy-bar-sub { display: block; font-size: 9.5px; font-weight: 500; opacity: .75; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; }

        .vy-unit-row--unassigned .vy-unit-code { color: var(--vy-accent-text); }

        /* Out-of-order / block bars — a muted hatch UNDER the bookings (real inventory_blocks, never faked). */
        .vy-lane-ooo { position: absolute; inset: 0; z-index: 1; display: grid; grid-template-columns: var(--night-cols); align-items: center; }
        .vy-ooo {
            grid-row: 1; height: 26px; margin: 0 3px; border-radius: 6px; overflow: hidden;
            display: flex; align-items: center; padding: 0 8px;
            font-size: 9.5px; font-weight: 600; letter-spacing: .02em; color: #6f645c; white-space: nowrap;
            border: 1px dashed rgba(138, 128, 121, 0.5);
            background: repeating-linear-gradient(45deg, rgba(138,128,121,0.18) 0, rgba(138,128,121,0.18) 6px, rgba(138,128,121,0.05) 6px, rgba(138,128,121,0.05) 12px);
        }
        .vy-ooo.is-clip-start { border-top-left-radius: 3px; border-bottom-left-radius: 3px; }
        .vy-ooo.is-clip-end { border-top-right-radius: 3px; border-bottom-right-radius: 3px; }
        .vy-ooo-label { overflow: hidden; text-overflow: ellipsis; }
        /* Owner blocks read a touch warmer than pure maintenance, but stay muted (not a status tone). */
        .vy-ooo.ooo-owner { color: #8a5a2b; border-color: rgba(180, 128, 74, 0.5);
            background: repeating-linear-gradient(45deg, rgba(180,128,74,0.16) 0, rgba(180,128,74,0.16) 6px, rgba(180,128,74,0.04) 6px, rgba(180,128,74,0.04) 12px); }
        .vy-legend-swatch.swatch-ooo { border: 1px dashed rgba(138, 128, 121, 0.6);
            background: repeating-linear-gradient(45deg, rgba(138,128,121,0.22) 0, rgba(138,128,121,0.22) 4px, rgba(138,128,121,0.06) 4px, rgba(138,128,121,0.06) 8px); }

        /* A DATELESS non-bookable state (owner-paused / maintenance out-of-service): a full-window band so the
           unit row reconciles with the header count (which already subtracts it) and the operator sees WHY it's
           unbookable. NEVER a fabricated date-ranged bar — the condition has no end date. */
        .vy-ooo.vy-ooo--full { height: 28px; opacity: .9; }
        .vy-ooo.state-paused { color: #6a6f78; border-color: rgba(120, 128, 140, 0.5);
            background: repeating-linear-gradient(45deg, rgba(120,128,140,0.16) 0, rgba(120,128,140,0.16) 6px, rgba(120,128,140,0.04) 6px, rgba(120,128,140,0.04) 12px); }
        .vy-ooo.state-out_of_service { color: #9a4a24; border-color: rgba(194, 65, 12, 0.4);
            background: repeating-linear-gradient(45deg, rgba(194,65,12,0.14) 0, rgba(194,65,12,0.14) 6px, rgba(194,65,12,0.03) 6px, rgba(194,65,12,0.03) 12px); }
        /* A small state badge on the unit label. */
        .vy-unit-state { align-self: flex-start; font-size: 8.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; border-radius: 5px; padding: 1px 5px; }
        .vy-unit-state.state-paused { color: #565c66; background: rgba(120, 128, 140, 0.16); }
        .vy-unit-state.state-out_of_service { color: #9a4a24; background: rgba(194, 65, 12, 0.12); }
        .vy-unit-row.is-paused .vy-unit-code, .vy-unit-row.is-out_of_service .vy-unit-code { color: var(--vy-muted); }

        /* GATE 5.3 — drag-to-reschedule. A draggable bar; a per-night drop layer that is click-through
           (pointer-events:none) until a drag is in flight, so it never steals the bar's link click. */
        .vy-bar.is-draggable { cursor: grab; }
        .vy-bar.is-draggable:active { cursor: grabbing; }
        .vy-lane-drop { position: absolute; inset: 0; z-index: 4; display: grid; grid-template-columns: var(--night-cols); pointer-events: none; }
        .vy-lane-drop.is-dragging { pointer-events: auto; }
        .vy-lane-drop > div { border-radius: 6px; transition: background .1s, box-shadow .1s; }
        .vy-lane-drop.is-dragging > div:hover { background: rgba(232, 112, 58, 0.16); box-shadow: inset 0 0 0 2px var(--vy-accent-border); }

        /* GATE 6.2 — Maintenance bulk select mode. */
        .vy-unit-row.is-sel { background: rgba(232, 112, 58, 0.07); }
        .vy-mt-check { width: 16px; height: 16px; accent-color: var(--vy-accent); cursor: pointer; }

        /* The adults stepper and the source pills — used by the Calendar's New reservation dialog (its own rules are the
           `vy-rnr-` block further down, 2026-10-04). */
        .vy-stepper { display: flex; align-items: center; border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.7); border-radius: var(--vy-r-md); overflow: hidden; }
        .vy-stepper button { width: 30px; padding: 10px 0; border: none; background: transparent; color: var(--vy-muted); font-size: 15px; line-height: 1; cursor: pointer; transition: background .14s; }
        .vy-stepper button:hover { background: rgba(232,112,58,.08); color: var(--vy-accent); }
        .vy-stepper span { flex: 1; text-align: center; font-size: 12px; font-weight: 500; white-space: nowrap; font-variant-numeric: tabular-nums; }


        .vy-source-pill { padding: 8px 15px; border-radius: var(--vy-r-pill); font-size: 12.5px; font-weight: 500; color: var(--vy-muted); background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); cursor: pointer; transition: transform .12s ease, all .14s; }
        .vy-source-pill:hover { transform: translateY(-1px); }
        .vy-source-pill.on { color: var(--vy-accent); background: rgba(232,112,58,.10); border-color: var(--vy-accent-border); font-weight: 600; }
        .vy-source-pill:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* ---- The Calendar's New reservation dialog + its click-a-night layer (2026-10-04) ---------------------------------
           The founder's report: the names and their boxes were not aligned (a three-column grid holding five pieces). Now the
           Owners dialogs' structure (`.vyo-dlg`, `.vyo-pair`, the shared `.vy-field`), with these for its own parts. */
        .vy-rnr-stay { display: block; }
        .vy-rnr-when { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 136px; gap: 0 12px; }
        .vy-rnr-when .vy-field { min-width: 0; }
        .vy-rnr-stepper { height: 42px; }
        .vy-rnr-stepper button { height: 100%; width: 38px; padding: 0; }
        .vy-rnr-stepper button:disabled { opacity: .35; cursor: default; }
        .vy-rnr-stepper button:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: -2px; }
        .vy-rnr-stepper span { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-rnr-problem { margin: 0 0 12px; font-size: 12.5px; font-weight: 600; line-height: 1.45; color: #8F3110; }
        .vy-rnr-check { width: 100%; height: 42px; justify-content: center; }
        .vy-rnr-free { display: flex; flex-direction: column; gap: 8px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--vy-hairline); }
        .vy-rnr-free > .vy-field-label { margin-bottom: 2px; }
        .vy-rnr-units { display: flex; flex-direction: column; gap: 8px; }
        .vy-rnr-unit { display: flex; align-items: center; gap: 12px; width: 100%; padding: 11px 13px; border-radius: var(--vy-r-md); border: 1px solid rgba(120, 70, 40, 0.14); background: rgba(255, 255, 255, 0.72); font: inherit; color: var(--vy-ink); text-align: left; cursor: pointer; transition: border-color .14s, background .14s; }
        .vy-rnr-unit:hover { border-color: var(--vy-accent-border); background: #fff; }
        .vy-rnr-unit:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-rnr-unit.is-sel { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.09); box-shadow: 0 0 0 1px var(--vy-accent-border); }
        .vy-rnr-unit-ico { width: 34px; height: 34px; flex: none; display: grid; place-items: center; border-radius: 50%; background: rgba(217, 102, 46, 0.12); color: var(--vy-accent-text); }
        .vy-rnr-unit.is-sel .vy-rnr-unit-ico { background: var(--vy-grad); color: #fff; }
        .vy-rnr-unit-info { display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0; }
        .vy-rnr-unit-name { font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-rnr-unit-sub { font-size: 11.5px; color: var(--vy-muted); overflow-wrap: anywhere; }
        .vy-rnr-unit-rate { font-size: 13.5px; font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-rnr-radio { position: relative; width: 18px; height: 18px; flex: none; border-radius: 50%; border: 1.5px solid var(--vy-faint); }
        .vy-rnr-unit.is-sel .vy-rnr-radio { border-color: transparent; background: var(--vy-grad); box-shadow: var(--vy-shadow-accent); }
        .vy-rnr-unit.is-sel .vy-rnr-radio::after { content: ""; position: absolute; inset: 4px; border-radius: 50%; background: #fff; }
        .vy-rnr-hidden { display: none; }
        .vy-rnr-waiting { display: flex; flex-direction: column; gap: 3px; padding: 11px 13px; border-radius: var(--vy-r-md); border: 1px dashed rgba(232, 112, 58, 0.4); background: rgba(232, 112, 58, 0.05); font-size: 12.5px; line-height: 1.45; color: var(--vy-muted); }
        .vy-rnr-waiting strong { font-size: 13.5px; color: var(--vy-ink); }
        .vy-rnr-link { border-radius: 4px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none; }
        .vy-rnr-link:hover { text-decoration: underline; text-underline-offset: 2px; }
        .vy-rnr-link:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-rnr-empty { margin: 0; font-size: 13px; line-height: 1.5; color: var(--vy-muted); }
        .vy-rnr-empty.is-block { display: flex; flex-direction: column; gap: 6px; padding: 16px; border-radius: var(--vy-r-md); border: 1px dashed rgba(120, 70, 40, 0.22); background: rgba(255, 255, 255, 0.5); }
        .vy-rnr-empty.is-block strong { font-size: 14.5px; color: var(--vy-ink); }
        .vy-rnr-sources { min-width: 0; margin: 18px 0 0; padding: 0; border: 0; }
        .vy-rnr-pills { display: flex; flex-wrap: wrap; gap: 7px; }
        .vy-rnr-total { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; padding: 13px 15px; border-radius: var(--vy-r-md); background: rgba(232, 112, 58, 0.07); border: 1px solid var(--vy-accent-border); }
        .vy-rnr-total-lbl { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); }
        .vy-rnr-total-amt { white-space: nowrap; font-family: var(--vy-display); font-size: 18px; font-weight: 700; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; }
        .vy-rnr-fine { margin: 12px 0 0; font-size: 12px; line-height: 1.5; color: var(--vy-faint); }
        .vy-rnr .vy-modal-foot .vy-btn--accent:disabled { opacity: .5; cursor: not-allowed; }
        /* the click-a-night layer: under the bars (which stay clickable and draggable) and the blocks */
        .vy-lane-book { position: absolute; inset: 0; z-index: 0; display: grid; grid-template-columns: var(--night-cols); }
        .vy-lane-book-cell { display: grid; place-items: center; margin: 5px 3px; border-radius: 8px; color: transparent; font-size: 16px; font-weight: 600; text-decoration: none; transition: background .12s, color .12s; }
        a.vy-lane-book-cell:hover { background: rgba(232, 112, 58, 0.12); color: var(--vy-accent-text); }
        .vy-cal .vy-unit-row .vy-lane-bars, .vy-cal .vy-unit-row .vy-lane-ooo { pointer-events: none; }
        .vy-cal .vy-unit-row .vy-lane-bars .vy-bar, .vy-cal .vy-unit-row .vy-lane-ooo .vy-ooo { pointer-events: auto; }
        .vy-rack-notice { margin-bottom: 14px; }
        .vy-rack-hint { max-width: 110ch; margin: 14px 0 0; font-size: 12px; line-height: 1.55; color: var(--vy-on-grad); }
        .vy-rack-hint strong { color: var(--vy-ink); }
        /* ---- The Calendar, second pass (2026-10-04, his report: "it is not possible to do reservation") -------------------------
           A room with no price is fixed from inside the dialog (Set price · a saved season's Apply view), a property with no rooms
           offers the ones that have some, and the grid marks the nights it cannot sell. ⚠️ One visual word throughout: a DASHED
           accent edge means "no price yet" — on the dialog's waiting room types, the grid's cells, the legend and the note. */
        .vy-rnr-msg { margin: 0 0 14px; padding: 10px 13px; border-radius: var(--vy-r-md); font-size: 12.5px; font-weight: 600; line-height: 1.45; }
        .vy-rnr-msg.is-ok { background: rgba(52, 140, 90, 0.10); color: rgb(34, 110, 68); border: 1px solid rgba(52, 140, 90, 0.22); }
        .vy-rnr-msg.is-err { background: rgba(194, 65, 12, 0.08); color: #8F3110; border: 1px solid var(--vy-accent-border); }
        .vy-rnr-season { display: flex; flex-direction: column; align-items: flex-start; gap: 9px; margin-top: 8px; padding: 11px 12px; border-radius: 10px; background: rgba(255, 255, 255, 0.72); border: 1px solid rgba(232, 112, 58, 0.22); }
        .vy-rnr-season-why { margin: 0; font-size: 12.5px; line-height: 1.45; color: var(--vy-muted); }
        .vy-rnr-waiting .vy-rnr-season-why strong { font-size: 12.5px; }
        .vy-rnr-season-go { height: 38px; }
        /* the Apply view, in the dialog's place: the Rates page's disclosure, then what it does for THIS stay, said last and plainly */
        .vy-rnr-confirm > p:last-child { margin-bottom: 0; font-weight: 600; color: var(--vy-ink); }
        .vy-rnr-setprice { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(232, 112, 58, 0.32); }
        .vy-rnr-setprice-row { display: flex; align-items: center; gap: 8px; }
        .vy-rnr-money { position: relative; flex: 1 1 140px; min-width: 0; max-width: 210px; }
        .vy-rnr-money-sym { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: var(--vy-muted); pointer-events: none; }
        .vy-rnr-money .vy-input { height: 40px; padding-left: 27px; }
        .vy-rnr-setprice .vy-btn { flex: none; height: 40px; }
        .vy-rnr-setprice .vy-btn:disabled { opacity: .55; cursor: default; }
        .vy-rnr-problem--tight, .vy-rnr-fine--tight { margin: 0; }
        .vy-rnr-others { display: flex; flex-direction: column; gap: 8px; margin-top: 14px; }
        .vy-rnr-others-h { margin: 0 0 2px; font-size: 12.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-rnr-other { display: flex; align-items: center; gap: 11px; width: 100%; padding: 12px 14px; border-radius: var(--vy-r-md); border: 1px solid rgba(120, 70, 40, 0.14); background: rgba(255, 255, 255, 0.72); font: inherit; color: var(--vy-ink); text-align: left; cursor: pointer; transition: border-color .14s, background .14s; }
        .vy-rnr-other:hover { border-color: var(--vy-accent-border); background: #fff; }
        .vy-rnr-other:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-rnr-other-ico { flex: none; color: var(--vy-accent-text); }
        .vy-rnr-other-name { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-rnr-other-rooms { font-size: 12px; color: var(--vy-muted); white-space: nowrap; }
        .vy-rnr-other-go { flex: none; color: var(--vy-faint); }
        .vy-rack-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 36px 20px 10px; text-align: center; }
        .vy-rack-empty strong { font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); }
        .vy-rack-empty span { font-size: 13px; color: var(--vy-muted); }
        .vy-rack-empty .vy-btn { margin-top: 8px; }
        .vy-rack-others { max-width: 460px; margin: 18px auto 28px; padding: 0 16px; }
        .vy-rack-norooms { margin-bottom: 14px; }   /* the same card above a grid of room types with no unit that can take a booking */
        .vy-rack-pricenote { display: flex; align-items: flex-start; gap: 10px; margin: 0 0 14px; padding: 11px 14px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.62); border: 1px solid rgba(232, 112, 58, 0.28); font-size: 12.5px; line-height: 1.5; color: var(--vy-muted); }
        .vy-rack-pricenote-mark { flex: none; width: 16px; height: 0; margin-top: 10px; border-bottom: 2px dashed rgba(232, 112, 58, 0.75); }
        /* the number underlined dashed — light at any row height (a box drawn round the cell grew with the room type's label) */
        .vy-avail.is-noprice { color: var(--vy-muted); text-decoration: underline dashed rgba(232, 112, 58, 0.75); text-decoration-thickness: 1.5px; text-underline-offset: 5px; }
        .vy-legend-swatch.swatch-noprice { height: 0; margin-top: 2px; border: 0; border-bottom: 2px dashed rgba(232, 112, 58, 0.75); border-radius: 0; background: none; }
        /* the nights already gone — never offered for a new booking */
        .vy-avail.is-past, .vy-cal .vy-day.is-past { opacity: .45; }
        @media (max-width: 560px) {
            .vy-rnr-when { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vy-rnr-when .vy-field:last-child { grid-column: 1 / -1; }
            .vy-rnr-money { max-width: none; }
        }

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
        .vy-toast svg { color: var(--vy-accent-text); flex: none; }

        /* ⚠️⚠️ A REFUSED OUTCOME (audit D4). The base `.vy-toast` is the SUCCESS skin and every view rendered
           refusals in it, tick and all — so invariant #27 refusing an over-refund arrived looking like a
           receipt. ⚠️ COLOUR IS THE LEAST OF THE DIFFERENCE HERE, deliberately: the component swaps the tick
           for an alert triangle and adds a visually-hidden "Refused:" prefix, so the outcome survives a
           monochrome screen, a colour-blind reader and a screen reader. This rule is the reinforcement, not
           the signal. ⚠️ `#8F3110` is the palette's existing danger ink (`.vy-acct-link--danger`), reused
           rather than a new one invented — a second red is how two surfaces come to disagree. */
        .vy-toast--refused {
            background: rgba(143, 49, 16, 0.09);
            border-color: rgba(143, 49, 16, 0.28);
            border-left-color: #8F3110;
        }
        .vy-toast--refused svg { color: #8F3110; }
        /* Focus lands here programmatically (the live-region fix); never show a focus ring for that. */
        .vy-toast--refused:focus { outline: none; }
        .vy-toast--refused:focus-visible { outline: 2px solid #8F3110; outline-offset: 2px; }

        .vy-summary { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .vy-summary-chip {
            display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-radius: var(--vy-r-md);
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm);
        }
        .vy-summary-chip .vy-sc-num { font-family: var(--vy-display); font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
        .vy-summary-chip .vy-sc-label { font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-on-grad); }
        .vy-summary-chip.is-due { border-color: var(--vy-accent-border); }
        .vy-summary-chip.is-due .vy-sc-num { color: var(--vy-accent-text); }

        .vy-fd-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 18px; align-items: start; }
        .vy-fd-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 20px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-fd-head .vy-fd-title { font-family: var(--vy-display); font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .vy-fd-count { font-size: 11px; font-weight: 700; color: var(--vy-accent); background: rgba(232, 112, 58, 0.12); border-radius: var(--vy-r-pill); padding: 2px 9px; }

        .vy-fd-row { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--vy-hairline); transition: background .15s ease; }
        .vy-fd-row:last-child { border-bottom: none; }
        .vy-fd-row:hover { background: rgba(255, 255, 255, 0.45); }
        .vy-fd-main { flex: 1; min-width: 0; }
        .vy-fd-guest { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-fd-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; color: var(--vy-on-grad)   /* the "3 nights"/"2 guests" spans INHERIT this — measured 4.48 on the row plate */; margin-top: 4px; }
        .vy-fd-meta .vy-sep { opacity: .5; }
        .vy-fd-right { display: flex; flex-direction: column; align-items: flex-end; gap: 7px; flex: none; }
        .vy-fd-actions { display: flex; align-items: center; gap: 7px; }
        .vy-fd-actions form { margin: 0; }

        .vy-balance { font-family: var(--vy-display); font-weight: 600; font-size: 13.5px; font-variant-numeric: tabular-nums; }
        .vy-balance.is-due { color: var(--vy-accent-text); }
        .vy-balance.is-settled { color: var(--vy-muted); font-weight: 500; }

        /* Warm status tags (no green/red) — keyed to the real reservation enum */
        .vy-tag { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: var(--vy-r-pill); font-size: 11px; font-weight: 600; white-space: nowrap; }
        .vy-tag .vy-dot { width: 6px; height: 6px; border-radius: 50%; flex: none; }
        /* ⚠️ --vy-accent-text, not --vy-accent: at the tag's 11px this measured 4.23 on its own 14% wash,
           under the 4.5 bar. This is the SHARED tag, so the one fix reaches all five views that use it. */
        .vy-tag.tone-confirmed { background: rgba(232, 112, 58, 0.14); color: var(--vy-accent-text); }
        .vy-tag.tone-confirmed .vy-dot { background: var(--vy-accent); }
        .vy-tag.tone-in-house { background: rgba(194, 65, 12, 0.14); color: #8f2f08; }
        .vy-tag.tone-in-house .vy-dot { background: #8f2f08; }
        .vy-tag.tone-departed { background: rgba(138, 128, 121, 0.16); color: var(--vy-muted); }
        .vy-tag.tone-departed .vy-dot { background: var(--vy-muted); }
        .vy-tag.tone-pending { background: rgba(224, 146, 30, 0.15); color: var(--vy-amber-text); }
        .vy-tag.tone-pending .vy-dot { background: var(--vy-amber-2); }
        .vy-tag.tone-no-show { background: rgba(178, 106, 8, 0.15); color: var(--vy-amber-text); }
        .vy-tag.tone-no-show .vy-dot { background: var(--vy-amber); }
        .vy-tag.tone-cancelled { background: rgba(166, 154, 146, 0.18); color: var(--vy-faint); }
        .vy-tag.tone-cancelled .vy-dot { background: var(--vy-faint); }
        /* Guest CRM flags (Wave 2) — the real `guests.is_vip` / `risk_flag` columns + the tag catalog.
           Warm only, per the design rule: at-risk borrows the established amber WARNING tone (the same one
           `.vy-unassigned` uses) rather than introducing a red. */
        .vy-tag.tone-vip { background: rgba(180, 130, 44, 0.16); color: #6f4f0c; }
        .vy-tag.tone-vip .vy-dot { background: #b4822c; }
        .vy-tag.tone-risk { background: rgba(178, 106, 8, 0.15); color: var(--vy-amber-text); }
        .vy-tag.tone-risk .vy-dot { background: var(--vy-amber); }
        .vy-tag.tone-tag { background: rgba(120, 70, 40, 0.08); color: var(--vy-on-grad); }
        .vy-tag.tone-tag .vy-dot { background: var(--vy-faint); }

        /* Unit assignment chip / unassigned + assign link */
        .vy-unit-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: var(--vy-ink); background: rgba(120, 70, 40, 0.07); border-radius: 6px; padding: 2px 7px; }
        .vy-unassigned { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: var(--vy-amber); background: rgba(224, 146, 30, 0.12); border: 1px dashed rgba(224, 146, 30, 0.4); border-radius: 6px; padding: 1px 7px; }
        .vy-assign-link { font-size: 11px; font-weight: 600; color: var(--vy-accent-text); }
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
        .vy-prio { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; letter-spacing: .03em; border-radius: var(--vy-r-pill); padding: 2px 8px; }
        .vy-prio.tone-high { color: var(--vy-accent); background: rgba(194, 65, 12, 0.12); }
        .vy-prio.tone-normal { color: var(--vy-muted); background: rgba(120, 70, 40, 0.08); }
        .vy-prio.tone-low { color: var(--vy-faint); background: rgba(120, 70, 40, 0.05); }

        /* ---- Housekeeping (2026-10-05 — his "text is not visible completely … search box … its symbol not well-aligned … Board and
           list buttons design is not good"): rebuilt on the shared list kit. The old .vy-col / .vy-hk-top/-unit/-date/-meta/-actions
           rules styled a board this page no longer had; they are gone, and every rule below is used. --------------------------- */
        .vy-hk-notice { margin-bottom: 14px; background: rgba(224, 146, 30, .10); border: 1px solid rgba(224, 146, 30, .32); border-left: 3px solid var(--vy-amber); border-radius: 12px; padding: 11px 15px; font-size: 13px; font-weight: 500; color: var(--vy-ink); }
        .vy-hk-notice.is-info { background: rgba(255, 252, 248, .72); border-color: rgba(120, 70, 40, .14); border-left-color: var(--vy-accent); }
        .vy-hk-flash { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .vy-hk-undo { margin: 0; flex: none; }
        .vy-hk-undo .vy-btn { padding: 3px 14px; font-size: 12px; }
        .vy-hk-bar .vy-gl-tools { flex-wrap: wrap; justify-content: flex-end; }
        .vy-hk-bar .vy-gl-chips { flex: 0 1 auto; max-width: 100%; }
        /* Board | List — one segmented control, never the browser's own button */
        .vy-hk-views { display: inline-flex; align-items: center; gap: 3px; height: 40px; padding: 3px; border-radius: var(--vy-r-pill); background: rgba(120, 70, 40, .07); border: 1px solid rgba(120, 70, 40, .10); box-sizing: border-box; flex: none; }
        .vy-hk-view { -webkit-appearance: none; appearance: none; display: inline-flex; align-items: center; gap: 7px; height: 100%; padding: 0 14px; border: 0; border-radius: var(--vy-r-pill); background: transparent; color: var(--vy-on-grad); font: inherit; font-size: 13px; font-weight: 600; line-height: 1; cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; }
        .vy-hk-view:hover { color: var(--vy-ink); }
        .vy-hk-view.is-on { background: rgba(255, 255, 255, .95); color: var(--vy-accent-text); box-shadow: 0 2px 8px rgba(150, 90, 50, .16); }
        .vy-hk-view:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-hk-view svg { flex: none; }
        /* the board */
        .vy-hk-crew { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
        .vy-hk-crew-who { display: flex; align-items: center; gap: 8px; min-width: 0; }
        .vy-hk-crew-name { font-size: 13px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vy-hk-crew-load { font-size: 11.5px; color: var(--vy-on-grad); }
        .vy-hk-crew-none { font-size: 12.5px; color: var(--vy-on-grad); margin: 0; }
        .vy-hk-crew-end { margin-left: auto; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .vy-hk-avatar { width: 30px; height: 30px; font-size: 12px; flex: none; }
        .vy-hk-avatar.is-sm { width: 22px; height: 22px; font-size: 10px; }
        .vy-hk-lane { padding: 12px; min-width: 0; }
        .vy-hk-lane-head { -webkit-appearance: none; appearance: none; width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: 0 0 10px; padding: 2px; border: 0; border-radius: 8px; background: none; font: inherit; color: var(--vy-ink); cursor: pointer; user-select: none; }
        .vy-hk-lane-head:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-hk-lane-end { display: inline-flex; align-items: center; gap: 7px; }
        .vy-hk-lane-n { font-size: 12px; font-weight: 600; color: var(--vy-on-grad); font-variant-numeric: tabular-nums; }
        .vy-hk-chev { color: var(--vy-on-grad); transition: transform .15s; }
        .vy-hk-chev.is-open { transform: rotate(180deg); }
        .vy-hk-lane-empty { padding: 14px 6px; text-align: center; font-size: 12px; color: var(--vy-on-grad); }
        .vy-hk-card { padding: 11px 12px; margin-bottom: 10px; border-left: 3px solid var(--vy-accent); min-width: 0; }
        .vy-hk-card-top { display: flex; align-items: baseline; justify-content: space-between; gap: 4px 8px; flex-wrap: wrap; }
        .vy-hk-room { -webkit-appearance: none; appearance: none; border: 0; background: none; padding: 0; font: inherit; font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-ink); text-align: left; cursor: pointer; overflow-wrap: anywhere; }
        .vy-hk-room:hover { color: var(--vy-accent-text); }
        .vy-hk-room:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; border-radius: 4px; }
        .vy-hk-sub { display: block; font-size: 12px; color: var(--vy-on-grad); margin-top: 2px; overflow-wrap: anywhere; }
        .vy-hk-tags { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-top: 6px; min-width: 0; }
        .vy-hk-card-who { margin-top: 8px; }
        .vy-hk-who { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--vy-ink); min-width: 0; overflow-wrap: anywhere; }
        .vy-hk-none { font-size: 12px; color: var(--vy-on-grad); }
        .vy-hk-small { padding: 4px 12px; font-size: 12px; }
        .vy-hk-elapsed { margin-top: 8px; }
        .vy-hk-elapsed-row { display: flex; justify-content: space-between; font-size: 11px; color: var(--vy-on-grad); }
        .vy-hk-elapsed.is-over .vy-hk-elapsed-row { color: var(--vy-accent-text); }
        .vy-hk-elapsed-bar { height: 4px; border-radius: 2px; background: var(--vy-hairline); margin-top: 3px; overflow: hidden; }
        .vy-hk-elapsed-bar span { display: block; height: 100%; background: #E0A63A; }
        .vy-hk-elapsed.is-over .vy-hk-elapsed-bar span { background: var(--vy-accent); }
        .vy-hk-card-foot { margin-top: 10px; padding-top: 9px; border-top: 1px solid var(--vy-hairline); display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .vy-hk-inline { display: inline; margin: 0; }
        /* the day a room is for — the page lists every day's cleaning */
        .vy-hk-day { display: inline-flex; align-items: center; font-size: 11.5px; font-weight: 600; white-space: nowrap; color: var(--vy-on-grad); }
        .vy-hk-day.is-today { color: var(--vy-ink); }
        .vy-hk-day.is-overdue { color: var(--vy-accent-text); }
        /* the list — a SIZE CONTAINER, as Guests: a narrow card stacks each room, every fact named, nothing running past the card */
        .vy-hk .vy-gl-card { container: vy-hk-list / inline-size; }
        .vy-hk .vy-gl-table { min-width: 0; }
        .vy-hk .vy-hk-c-day, .vy-hk .vy-hk-c-stage, .vy-hk .vy-hk-c-ready { white-space: nowrap; }
        .vy-hk .vy-hk-c-act { text-align: right; }
        .vy-hk-c-room .vy-hk-tags, .vy-hk-c-room .vy-hk-sub { margin-top: 4px; }
        @container vy-hk-list (max-width: 820px) {
            .vy-hk .vy-gl-table, .vy-hk .vy-gl-table tbody { display: block; }
            .vy-hk .vy-gl-table thead { display: none; }
            .vy-hk .vy-gl-table tbody tr { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 18px; padding: 14px 16px; border-top: 1px solid var(--vy-hairline); }
            .vy-hk .vy-gl-table tbody tr:first-child { border-top: none; }
            .vy-hk .vy-gl-table tbody td { display: block; padding: 0; border: 0; text-align: left; }
            .vy-hk .vy-gl-table tbody td.vy-hk-c-room { flex: 1 1 100%; min-width: 0; }
            .vy-hk .vy-gl-table tbody td[data-label] { display: inline-flex; align-items: baseline; gap: 6px; font-size: 12.5px; }
            .vy-hk .vy-gl-table tbody td[data-label]::before { content: attr(data-label); font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-on-grad); }
            .vy-hk .vy-gl-table tbody td.vy-hk-c-act { margin-left: auto; }
            .vy-hk .vy-gl-table tbody td[colspan] { flex: 1 1 100%; }
        }
        /* the dialogs — the shared solid card */
        .vy-hk-dlg { max-width: 500px; }
        .vy-hk-head-text { flex: 1; min-width: 0; }
        .vy-hk-head-text .vy-modal-title { overflow-wrap: anywhere; }
        .vy-hk-pick { border: 0; margin: 0 0 12px; padding: 0; min-width: 0; }
        .vy-hk-pick legend { padding: 0; margin-bottom: 8px; }
        .vy-hk-options { display: flex; flex-direction: column; gap: 6px; max-height: 320px; overflow-y: auto; }
        .vy-hk-option { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border: 1px solid var(--vy-hairline); border-radius: 12px; cursor: pointer; font-size: 13px; }
        .vy-hk-option:has(input:checked) { border-color: rgba(217, 102, 46, .35); background: rgba(217, 102, 46, .07); }
        .vy-hk-option:has(input:focus-visible) { outline: 2px solid var(--vy-accent); outline-offset: 1px; }
        .vy-hk-option input { accent-color: var(--vy-accent); margin: 0; flex: none; }
        .vy-hk-option-name { flex: 1; min-width: 0; font-weight: 600; overflow-wrap: anywhere; }
        .vy-hk-option-name .vy-tag { margin-left: 6px; }
        .vy-hk-option-load { flex: none; font-size: 12px; color: var(--vy-on-grad); }
        .vy-hk-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink); cursor: pointer; }
        .vy-hk-check input { accent-color: var(--vy-accent); }
        .vy-hk-hint { font-size: 12px; color: var(--vy-on-grad); margin: 8px 0 0; }
        .vy-hk-lead { font-size: 13.5px; color: var(--vy-ink); margin: 0; }
        .vy-hk-dlg .vy-modal-foot .vy-btn { height: 42px; justify-content: center; }
        .vy-hk-dlg .vy-modal-foot .vy-btn--ghost { flex: 0 0 auto; min-width: 104px; }
        .vy-hk-foot-form { flex: 1; display: flex; margin: 0; }
        .vy-hk-foot-form .vy-btn { flex: 1; }
        .vy-hk-steps { list-style: none; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin: 0 0 16px; padding: 0; }
        .vy-hk-step { display: flex; flex-direction: column; gap: 5px; font-size: 11px; color: var(--vy-on-grad); text-align: center; }
        .vy-hk-step-bar { height: 4px; border-radius: 2px; background: var(--vy-hairline); }
        .vy-hk-step.is-done { color: var(--vy-ink); font-weight: 600; }
        .vy-hk-step.is-done .vy-hk-step-bar { background: var(--vy-accent); }
        .vy-hk-facts { display: grid; grid-template-columns: auto 1fr; gap: 6px 14px; margin: 0 0 14px; font-size: 13px; }
        .vy-hk-facts dt { color: var(--vy-on-grad); }
        .vy-hk-facts dd { margin: 0; color: var(--vy-ink); font-weight: 600; }
        .vy-hk-urgent { margin: 0 0 12px; padding: 8px 12px; border-radius: 10px; background: rgba(194, 65, 12, .08); color: var(--vy-accent-text); font-size: 12.5px; font-weight: 600; }
        .vy-hk-wide { width: 100%; justify-content: center; margin-bottom: 14px; }
        .vy-hk-h3 { font-size: 13px; font-weight: 700; color: var(--vy-ink); margin: 6px 0 8px; }
        .vy-hk-photos { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
        .vy-hk-photo { position: relative; }
        .vy-hk-photo img { display: block; width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid var(--vy-hairline); }
        .vy-hk-photo-x { position: absolute; top: 4px; right: 4px; margin: 0; }
        .vy-hk-photo-x button { width: 20px; height: 20px; border-radius: 50%; border: 0; background: rgba(43, 35, 32, .6); color: #fff; font-size: 12px; line-height: 1; cursor: pointer; display: grid; place-items: center; }
        .vy-hk-upload { cursor: pointer; display: inline-flex; }
        .vy-hk-upload:focus-within { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* Maintenance board — reuses the housekeeping card/kanban; a few work-order-specific bits. */
        .vy-prio.tone-urgent { color: var(--vy-amber); background: rgba(224, 146, 30, 0.16); }
        .vy-mt-title { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; line-height: 1.3; color: var(--vy-ink); text-decoration: none; display: block; }
        .vy-mt-title:hover { color: var(--vy-accent-text); }
        .vy-mt-sub { display: flex; align-items: center; gap: 7px; font-size: 11px; color: var(--vy-muted); flex-wrap: wrap; }
        .vy-mt-cat { color: var(--vy-faint); }
        .vy-mt-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 11.5px; padding-top: 6px; border-top: 1px solid var(--vy-hairline); }
        .vy-mt-cost { font-family: var(--vy-display); font-weight: 700; color: var(--vy-ink); }
        .vy-mt-when { color: var(--vy-faint); }
        /* Per-card assign disclosure (native <details> — no shared JS state across N cards). */
        .vy-assign { position: relative; }
        .vy-assign > summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: var(--vy-accent); padding: 3px 9px; border-radius: var(--vy-r-pill); border: 1px solid var(--vy-accent-border); background: rgba(232, 112, 58, 0.06); }
        .vy-assign > summary::-webkit-details-marker { display: none; }
        .vy-assign[open] > summary { background: rgba(232, 112, 58, 0.14); }
        .vy-assign-pop { margin-top: 8px; padding: 10px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.85); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); display: flex; flex-direction: column; gap: 8px; }
        .vy-assign-pop select { width: 100%; padding: 8px 10px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: #fff; font-family: inherit; font-size: 12.5px; color: var(--vy-ink); }
        /* "Units out of service" rail — a real attention strip above the board. */
        .vy-oos-rail { display: flex; align-items: flex-start; gap: 12px; padding: 14px 18px; border-radius: var(--vy-r-lg); background: rgba(66, 52, 44, 0.06); border: 1px solid rgba(66, 52, 44, 0.14); margin-bottom: 18px; }
        .vy-oos-rail .vy-oos-ico { flex: none; width: 34px; height: 34px; border-radius: var(--vy-r-md); background: rgba(66, 52, 44, 0.12); color: var(--vy-dark); display: grid; place-items: center; }
        .vy-oos-rail-body { flex: 1; }
        .vy-oos-rail-title { font-family: var(--vy-display); font-size: 13px; font-weight: 600; color: var(--vy-dark); }
        .vy-oos-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .vy-oos-item { display: inline-flex; align-items: center; gap: 7px; padding: 5px 11px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.7); border: 1px solid var(--vy-glass-border); font-size: 12px; color: var(--vy-ink); text-decoration: none; }
        .vy-oos-item:hover { border-color: var(--vy-dark); }
        .vy-oos-item .vy-oos-code { font-weight: 600; }
        /* Work-order detail — activity timeline (real audit events only). */
        .vy-timeline { display: flex; flex-direction: column; gap: 0; margin-top: 4px; }
        .vy-tl-item { display: flex; gap: 12px; padding: 9px 0; position: relative; }
        .vy-tl-rail { flex: none; display: flex; flex-direction: column; align-items: center; }
        .vy-tl-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--vy-grad); margin-top: 3px; flex: none; box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.12); }
        .vy-tl-item:not(:last-child) .vy-tl-line { width: 2px; flex: 1; background: var(--vy-hairline); margin-top: 3px; }
        .vy-tl-body { flex: 1; min-width: 0; }
        .vy-tl-label { font-size: 13px; font-weight: 600; color: var(--vy-ink); }
        .vy-tl-label .vy-tl-who { font-weight: 500; color: var(--vy-accent-text); }
        .vy-tl-meta { font-size: 11px; color: var(--vy-faint); margin-top: 2px; }
        /* Work-order detail — an in-card callout (warm-dark for OOS impact, accent for the cost card). */
        .vy-callout { border-radius: var(--vy-r-md); padding: 13px 15px; font-size: 12.5px; line-height: 1.5; }
        .vy-callout.is-oos { background: rgba(66, 52, 44, 0.07); border: 1px solid rgba(66, 52, 44, 0.16); color: var(--vy-dark); }
        .vy-callout.is-cost { background: rgba(232, 112, 58, 0.09); border: 1px solid var(--vy-accent-border); color: var(--vy-ink); }
        .vy-callout-title { font-weight: 700; display: flex; align-items: center; gap: 7px; }
        .vy-callout-move { display: inline-flex; align-items: center; gap: 6px; margin-top: 9px; font-weight: 600; color: var(--vy-dark); text-decoration: none; padding: 6px 11px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(66, 52, 44, 0.2); }
        .vy-callout-move:hover { border-color: var(--vy-dark); }
        .vy-cost-amt { font-family: var(--vy-display); font-size: 22px; font-weight: 700; letter-spacing: -0.02em; color: var(--vy-ink); }
        .vy-resolve-form { margin-top: 12px; }
        .vy-resolve-form .vy-input-group { margin-top: 7px; }

        /* ---- Unified Inbox --------------------------------------------------------------------- */
        /* Channel badge — WARM tints only (no brand green/blue), distinguished by a glyph letter. */
        .vy-chan { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px 2px 3px; border-radius: var(--vy-r-pill); font-size: 10.5px; font-weight: 600; white-space: nowrap; }
        .vy-chan .vy-chan-glyph { width: 15px; height: 15px; border-radius: 50%; display: grid; place-items: center; font-size: 8.5px; font-weight: 700; color: #fff; flex: none; }
        .vy-chan.tone-manual { background: rgba(232, 112, 58, 0.14); color: var(--vy-accent); } .vy-chan.tone-manual .vy-chan-glyph { background: var(--vy-grad); }
        .vy-chan.tone-email { background: rgba(224, 146, 30, 0.16); color: var(--vy-amber); } .vy-chan.tone-email .vy-chan-glyph { background: linear-gradient(140deg, #E0921E, #B26A08); }
        .vy-chan.tone-whatsapp { background: rgba(240, 165, 122, 0.24); color: #a8480f; } .vy-chan.tone-whatsapp .vy-chan-glyph { background: linear-gradient(140deg, #F0A57A, #D9662E); }
        .vy-chan.tone-bookingcom { background: rgba(194, 65, 12, 0.13); color: #8f2f08; } .vy-chan.tone-bookingcom .vy-chan-glyph { background: linear-gradient(160deg, #C2410C, #8f2f08); }
        .vy-chan.tone-airbnb { background: rgba(217, 102, 46, 0.15); color: var(--vy-accent-2); } .vy-chan.tone-airbnb .vy-chan-glyph { background: linear-gradient(140deg, #D9662E, #A8480F); }

        /* Thread list */
        .vy-inbox { display: flex; flex-direction: column; margin: 0 -8px; }
        .vy-thread-row { display: flex; align-items: center; gap: 13px; padding: 13px 12px; border-radius: var(--vy-r-md); text-decoration: none; color: inherit; transition: background .15s ease, transform .15s ease; border-bottom: 1px solid var(--vy-hairline); }
        .vy-thread-row:last-child { border-bottom: none; }
        .vy-thread-row:hover { background: rgba(255, 255, 255, 0.5); transform: translateX(2px); }
        .vy-thread-row.is-unread { background: rgba(232, 112, 58, 0.05); }
        .vy-thread-av { position: relative; flex: none; width: 40px; height: 40px; border-radius: 50%; background: var(--vy-avatar-bg); color: var(--vy-on-peach); display: grid; place-items: center; font-family: var(--vy-display); font-weight: 600; font-size: 14px; }
        .vy-thread-av .vy-unread-dot { position: absolute; top: -1px; right: -1px; width: 11px; height: 11px; border-radius: 50%; background: var(--vy-accent); border: 2px solid rgba(255, 252, 248, 0.95); }
        .vy-thread-main { flex: 1; min-width: 0; }
        .vy-thread-top { display: flex; align-items: center; gap: 8px; }
        .vy-thread-name { font-family: var(--vy-display); font-size: 14px; font-weight: 500; color: var(--vy-ink); }
        .vy-thread-row.is-unread .vy-thread-name { font-weight: 600; }
        .vy-thread-preview { font-size: 12.5px; color: var(--vy-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-thread-row.is-unread .vy-thread-preview { color: var(--vy-ink); font-weight: 500; }
        .vy-thread-res { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; color: var(--vy-faint); margin-top: 5px; }
        .vy-thread-res svg { opacity: .7; }
        .vy-thread-right { flex: none; display: flex; flex-direction: column; align-items: flex-end; gap: 6px; min-width: 66px; }
        .vy-thread-time { font-size: 11px; color: var(--vy-faint); }
        .vy-unread-count { min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: var(--vy-grad); color: #fff; font-size: 10.5px; font-weight: 700; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-thread-assignee { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; color: var(--vy-muted); }
        .vy-thread-assignee .vy-mini-avatar { width: 17px; height: 17px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 8px; font-weight: 600; display: grid; place-items: center; }
        .vy-assignee-none { font-size: 9.5px; letter-spacing: .04em; text-transform: uppercase; color: var(--vy-faint); background: rgba(120, 70, 40, 0.06); padding: 2px 7px; border-radius: var(--vy-r-pill); }
        .vy-thread-closed { opacity: .62; }

        /* Conversation (thread view) */
        .vy-convo-head { display: flex; align-items: center; gap: 14px; margin-bottom: 6px; flex-wrap: wrap; }
        .vy-convo-back { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: var(--vy-on-grad); text-decoration: none; }
        .vy-convo-back:hover { color: var(--vy-accent-text); }
        .vy-convo-who { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
        .vy-convo-name { font-family: var(--vy-display); font-size: 18px; font-weight: 600; letter-spacing: -0.01em; }
        .vy-convo-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .vy-msgs { display: flex; flex-direction: column; gap: 10px; padding: 4px 2px; }
        .vy-day { text-align: center; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-faint); margin: 12px 0 4px; }
        .vy-msg { display: flex; max-width: 76%; }
        .vy-msg.is-out { align-self: flex-end; flex-direction: column; align-items: flex-end; }
        .vy-msg.is-in { align-self: flex-start; flex-direction: column; align-items: flex-start; }
        .vy-bubble { padding: 10px 14px; border-radius: 16px; font-size: 13.5px; line-height: 1.5; white-space: pre-line; }
        .vy-msg.is-in .vy-bubble { background: rgba(255, 252, 248, 0.72); border: 1px solid var(--vy-glass-border); color: var(--vy-ink); border-bottom-left-radius: 5px; }
        .vy-msg.is-out .vy-bubble { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); border-bottom-right-radius: 5px; }
        .vy-msg-meta { display: flex; align-items: center; gap: 6px; font-size: 10.5px; color: var(--vy-faint); margin-top: 4px; padding: 0 4px; }
        .vy-deliv { display: inline-flex; align-items: center; gap: 3px; }
        .vy-deliv.is-fail { color: #b23a0a; font-weight: 600; }
        /* Composer */
        .vy-composer { margin-top: 16px; border-top: 1px solid var(--vy-hairline); padding-top: 14px; }
        .vy-composer textarea { width: 100%; padding: 12px 14px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.72); border: 1px solid var(--vy-glass-border); font-family: inherit; font-size: 13.5px; color: var(--vy-ink); resize: vertical; min-height: 74px; }
        .vy-composer-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px; }
        .vy-composer-via { font-size: 11.5px; color: var(--vy-muted); display: inline-flex; align-items: center; gap: 6px; }
        .vy-composer.is-blocked { opacity: .96; }
        .vy-composer-blocked { display: flex; align-items: center; gap: 9px; padding: 12px 15px; border-radius: var(--vy-r-md); background: rgba(66, 52, 44, 0.06); border: 1px solid rgba(66, 52, 44, 0.14); color: var(--vy-dark); font-size: 12.5px; }
        /* Context sidebar cards */
        .vy-ctx-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-faint); }
        .vy-ctx-guest { display: flex; align-items: center; gap: 11px; margin-top: 10px; }
        .vy-ctx-guest .vy-thread-av { width: 44px; height: 44px; font-size: 15px; }
        .vy-ctx-name { font-family: var(--vy-display); font-size: 15px; font-weight: 600; }
        .vy-ctx-sub { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; }
        .vy-ctx-line { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; padding: 5px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-ctx-line:last-child { border-bottom: none; }
        .vy-ctx-line .k { color: var(--vy-faint); }
        .vy-ctx-line .v { color: var(--vy-ink); font-weight: 500; text-align: right; }
        .vy-ctx-link { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: var(--vy-accent-text); text-decoration: none; margin-top: 10px; font-weight: 600; }

        /* ---- Reservation detail (folio ledger) ------------------------------------------------- */
        .vy-detail-cols { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 22px; align-items: start; }
        .vy-stack { display: flex; flex-direction: column; gap: 22px; }

        .vy-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 22px; }
        .vy-fact-label { font-size: 9.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-fact-val { font-size: 13.5px; font-weight: 500; margin-top: 3px; }
        /* #105 Work Order detail — the wireframe's 4-col fact grid + responsive 2-col stack */
        /* ---- the work order page (2026-10-05 — his "note progress part ordered buttons design is not cool") ------------------------ */
        .vy-wo-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 18px 16px; }
        .vy-wo-facts > div { min-width: 0; }
        .vy-wo-facts .vy-wo-fval { overflow-wrap: anywhere; }
        .vy-wo-composer { display: flex; gap: 11px; margin-top: 4px; padding-top: 16px; border-top: 1px solid rgba(120, 70, 40, .10); }
        .vy-wo-me { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(140deg, #F0A57A, #D9662E); display: grid; place-items: center; color: #fff; font-size: 11px; font-weight: 600; flex: none; }
        .vy-wo-compose { flex: 1; min-width: 0; }
        .vy-wo-kinds { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; border-radius: 14px; background: rgba(120, 70, 40, .06); border: 1px solid rgba(120, 70, 40, .10); margin-bottom: 10px; max-width: 100%; box-sizing: border-box; }
        .vy-wo-kind { -webkit-appearance: none; appearance: none; display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px; border: 0; border-radius: 10px; background: transparent; color: var(--vy-on-grad); font: inherit; font-size: 13px; font-weight: 500; line-height: 1; cursor: pointer; white-space: nowrap; transition: background .15s, color .15s, box-shadow .15s; }
        .vy-wo-kind:hover { color: var(--vy-ink); }
        .vy-wo-kind.is-on { background: rgba(255, 255, 255, .95); color: var(--vy-accent-text); font-weight: 600; box-shadow: 0 2px 8px rgba(150, 90, 50, .16); }
        .vy-wo-kind:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-wo-kind-tick { display: none; flex: none; }
        .vy-wo-kind.is-on .vy-wo-kind-tick { display: block; }
        .vy-wo-partrow { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 10px; margin-bottom: 10px; }
        .vy-wo-partrow .vy-field, .vy-wo-manage-form .vy-field { margin: 0; }
        .vy-wo-textarea { width: 100%; min-height: 64px; resize: vertical; box-sizing: border-box; }
        .vy-wo-compose-foot { display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
        .vy-wo-compose-note { font-size: 12px; color: var(--vy-on-grad); }
        .vy-wo-compose-foot .vy-btn:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }
        .vy-wo-manage-form { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
        @media (max-width: 560px) { .vy-wo-partrow { grid-template-columns: minmax(0, 1fr); } }
        .vy-wo-flabel { font-size: 10px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 5px; }
        .vy-wo-fval { font-size: 13px; font-weight: 500; }
        .vy-wo-fsub { font-size: 11px; color: var(--vy-faint); margin-top: 1px; }
        @media (max-width: 1080px) { .vy-wo-grid { grid-template-columns: minmax(0, 1fr) !important; } }
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
        .vy-ledger-amt.tone-paid { color: var(--vy-accent-text); }
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
        .vy-total-row.is-balance .vy-t-val { font-size: 20px; color: var(--vy-accent-text); }
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
        .vy-recon svg { color: var(--vy-accent-text); }

        /* ---- Operator dashboard ---------------------------------------------------------------- */
        /* KPI band. Default 4-up (wraps for the 6-tile Insights → 4+2). Per-count modifiers let a page pick a
           full-width row: --5 (Dashboard), --3 (Reservations). The design shows each KPI row spanning full width. */
        .vy-dash-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
        .vy-dash-kpis--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .vy-dash-kpis--5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        /* #104 GATE 2 — the KPI stat tiles read as full glass cards (radius 20px + the full warm shadow +
           a translateY(-2px) hover), matching the decoded wireframe's stat cards. */
        .vy-dash-kpi {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 18px 20px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .vy-dash-kpi:hover { transform: translateY(-2px); }
        .vy-dash-kpi.is-feature { background: var(--vy-grad-dark); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: var(--vy-shadow-dark); color: rgba(255, 255, 255, 0.92); }
        .vy-dash-kpi.is-feature .vy-dk-label { color: rgba(255, 255, 255, 0.55); }
        .vy-dash-kpi.is-feature .vy-dk-sub { color: rgba(255, 255, 255, 0.6); }
        /* The prototype's own #9C9089 is NOT copied literally: measured 2.44:1 on the composited card
           background, below AA. --vy-muted carries the darkened value the polish pass computed (5.48:1). */
        .vy-dk-label { font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-dk-val { font-family: var(--vy-display); font-size: 30px; font-weight: 600; letter-spacing: -0.02em; margin-top: 8px; font-variant-numeric: tabular-nums; }
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
        .vy-arr-link { font-size: 11.5px; font-weight: 600; color: var(--vy-accent-text); }

        .vy-hk-snapshot { display: flex; flex-direction: column; gap: 10px; }
        .vy-hk-line { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 13px; }
        .vy-hk-line .vy-hk-k { display: inline-flex; align-items: center; gap: 8px; color: var(--vy-muted); }
        .vy-hk-line .vy-hk-v { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-hk-swatch { width: 9px; height: 9px; border-radius: 50%; }

        /* Housekeeping progress (real: cleaned+inspected of total) — the design's HK progress bar */
        .vy-hk-progress { height: 6px; border-radius: 6px; background: var(--vy-hairline); overflow: hidden; margin-top: 14px; }
        .vy-hk-progress > span { display: block; height: 100%; border-radius: 6px; background: var(--vy-grad); }
        .vy-hk-progress-label { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; font-size: 11px; color: var(--vy-muted); margin-top: 8px; }

        /* Right-rail dark "Tonight" panel (the design's TONIGHT block) — REAL occupancy + rooms sold/available */
        .vy-tonight { background: var(--vy-grad-dark); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: var(--vy-shadow-dark); border-radius: var(--vy-r-lg); padding: 18px 20px; color: rgba(255, 255, 255, 0.92); }
        .vy-tonight .vy-eyebrow { color: rgba(255, 255, 255, 0.5); }
        .vy-tonight-val { font-family: var(--vy-display); font-size: 42px; font-weight: 700; letter-spacing: -0.02em; line-height: 1; margin-top: 12px; }
        .vy-tonight-val .vy-dk-unit { font-size: 18px; font-weight: 600; color: rgba(255, 255, 255, 0.55); margin-left: 3px; }
        .vy-tonight-sub { font-size: 12px; color: rgba(255, 255, 255, 0.6); margin-top: 6px; }
        .vy-tonight-row { display: flex; gap: 22px; margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.12); }
        .vy-tonight-cell .vy-tc-k { font-size: 9.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: rgba(255, 255, 255, 0.5); }
        .vy-tonight-cell .vy-tc-v { font-family: var(--vy-display); font-size: 17px; font-weight: 600; margin-top: 4px; font-variant-numeric: tabular-nums; }

        /* ── THE SHARED WIZARD STEPPER (New Reservation + Group Booking) ─────────────────────────────
           ONE definition of the progress stepper: circular numbered nodes joined by connector bars,
           clickable to jump, gradient fill + check when done, ring when active. Both operator wizard
           prototypes draw the identical control.

           ⚠️ PROMOTED RATHER THAN COPIED. New Reservation already had this (prototype-exact) as
           `.vy-nr-*`; Group Booking had a compact `.vy-gb-*` variant that did not match its own design.
           Restyling the group one would have MANUFACTURED a second copy of ~15 rules for a single
           concept — precisely the drift this project keeps consolidating — so the rule moved here once
           and both wizards point at it. Values are the design-matching New Reservation ones, unchanged. */
        .vy-wiz-stepper { display: flex; background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: 20px; box-shadow: 0 14px 40px rgba(150, 90, 50, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.65); padding: 18px 26px; margin-bottom: 20px; }
        .vy-wiz-node { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 9px; min-width: 0; }
        .vy-wiz-node-row { display: flex; align-items: center; width: 100%; }
        .vy-wiz-bar { flex: 1; height: 2px; border-radius: 2px; background: var(--vy-hairline); }
        .vy-wiz-node.is-done .vy-wiz-bar { background: var(--vy-grad); }
        .vy-wiz-node.is-on .vy-wiz-bar:first-child { background: var(--vy-grad); }
        .vy-wiz-circle { position: relative; width: 34px; height: 34px; border-radius: 50%; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); color: var(--vy-faint); cursor: pointer; flex: none; display: grid; place-items: center; font-family: var(--vy-display); font-weight: 600; font-size: 13px; transition: background 0.2s, box-shadow 0.2s, transform 0.12s; }
        .vy-wiz-circle:hover { transform: translateY(-1px); }
        .vy-wiz-node.is-on .vy-wiz-circle { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: 0 0 0 4px rgba(232, 112, 58, 0.18); }
        .vy-wiz-node.is-done .vy-wiz-circle { background: var(--vy-grad); color: #fff; border-color: transparent; }
        .vy-wiz-circle-num { transition: opacity 0.15s; }
        .vy-wiz-circle-check { position: absolute; opacity: 0; transition: opacity 0.15s; }
        .vy-wiz-node.is-done .vy-wiz-circle-num { opacity: 0; }
        .vy-wiz-node.is-done .vy-wiz-circle-check { opacity: 1; }
        .vy-wiz-node-label { font-size: 12px; font-weight: 500; color: var(--vy-faint); white-space: nowrap; }
        .vy-wiz-node.is-on .vy-wiz-node-label, .vy-wiz-node.is-done .vy-wiz-node-label { color: var(--vy-ink); }
        @media (max-width: 760px) { .vy-wiz-node-label { display: none; } }
        /* the connector before the first step and after the last — drawn invisible so every node keeps its width */
        .vy-wiz-bar.is-edge { visibility: hidden; }
        .vy-wiz-circle:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 3px; }

        /* ---- New reservation wizard (2026-10-04) ----------------------------------------------------------------
           The focused booking screen and its done card. ⚠️ Its own `vy-nr-` namespace: the older `vyn-` rules further
           down are shared with other screens (notifications, preferences) and must not be restyled from here. */
        .vy-nr-standalone { position: relative; z-index: 2; max-width: 1180px; margin: 0 auto; padding: 24px 30px 60px; box-sizing: border-box; }
        .vy-nr-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .vy-nr-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .vy-nr-brand-id { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .vy-nr-logo { width: 36px; height: 36px; border-radius: 10px; background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-weight: 600; font-size: 18px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); flex: none; }
        .vy-nr-brand-title { margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 18px; letter-spacing: -0.02em; line-height: 1.2; color: var(--vy-ink); }
        .vy-nr-brand-sub { font-size: 12.5px; color: var(--vy-muted); line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-nr-propform { display: flex; align-items: center; gap: 7px; min-width: 0; }
        .vy-nr-proplabel { font-size: 12.5px; color: var(--vy-muted); white-space: nowrap; }
        .vy-nr-propselect { min-width: 0; max-width: 280px; padding: 4px 8px; border-radius: 8px; border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.62); font: 600 12.5px/1.3 var(--vy-sans); color: var(--vy-ink); cursor: pointer; }
        .vy-nr-propselect:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-topright { display: flex; align-items: center; gap: 12px; flex: none; }
        .vy-nr-stepcount { font-size: 12.5px; font-weight: 500; color: var(--vy-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-nr-close { width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); color: var(--vy-muted); display: grid; place-items: center; text-decoration: none; transition: background 0.14s; flex: none; }
        .vy-nr-close:hover { background: rgba(255, 255, 255, 0.9); color: var(--vy-ink); }
        .vy-nr-close:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-flash { margin-bottom: 16px; }
        .vy-nr-ro { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 14px; background: rgba(217, 154, 61, 0.16); border: 1px solid rgba(217, 154, 61, 0.4); color: var(--vy-amber-text); font-size: 13px; margin-bottom: 16px; }
        .vy-nr-draft { display: flex; align-items: center; gap: 8px; margin: -6px 0 12px; font-size: 13px; color: var(--vy-muted); }
        .vy-nr-body { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
        .vy-nr-main { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
        .vy-nr-card { background: rgba(255, 252, 248, 0.72); backdrop-filter: blur(26px) saturate(1.2); -webkit-backdrop-filter: blur(26px) saturate(1.2); border: 1px solid var(--vy-glass-border); border-radius: 20px; padding: 24px 26px; box-shadow: var(--vy-shadow); min-width: 0; }
        .vy-nr-h { margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 19px; letter-spacing: -0.02em; line-height: 1.25; color: var(--vy-ink); }
        .vy-nr-lede { margin: 5px 0 18px; font-size: 13px; line-height: 1.5; color: var(--vy-muted); }
        .vy-nr-h3 { margin: 0; font-size: 13.5px; font-weight: 600; line-height: 1.35; color: var(--vy-ink); }
        .vy-nr-label { display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-nr-opt { font-weight: 400; color: var(--vy-faint); }
        .vy-nr-field { margin-bottom: 14px; min-width: 0; }
        .vy-nr-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 14px; }
        .vy-nr-input { width: 100%; box-sizing: border-box; padding: 10px 13px; border-radius: 11px; border: 1px solid rgba(120, 70, 40, 0.18); background: rgba(255, 255, 255, 0.7); font: 13.5px/1.4 var(--vy-sans); color: var(--vy-ink); }
        .vy-nr-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.18); background: #fff; }
        .vy-nr-input:disabled { opacity: 0.55; }
        .vy-nr-textarea { resize: vertical; min-height: 66px; }
        .vy-nr-link { font-size: 12.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none; white-space: nowrap; }
        .vy-nr-link:hover { text-decoration: underline; }
        .vy-nr-link:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; border-radius: 4px; }
        .vy-nr-textbtn { border: 0; background: none; padding: 2px 4px; font: 600 12.5px/1.3 var(--vy-sans); color: var(--vy-accent-text); cursor: pointer; border-radius: 6px; white-space: nowrap; }
        .vy-nr-textbtn:hover { text-decoration: underline; }
        .vy-nr-textbtn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-fine { margin: 10px 0 0; font-size: 12px; line-height: 1.5; color: var(--vy-faint); }
        .vy-nr-note { margin: 2px 0 14px; padding: 10px 13px; border-radius: 10px; font-size: 12.5px; line-height: 1.5; background: rgba(217, 154, 61, 0.13); border: 1px solid rgba(217, 154, 61, 0.32); color: var(--vy-amber-text); }
        .vy-nr-note.is-calm { margin-top: 14px; background: rgba(31, 110, 75, 0.07); border-color: rgba(31, 110, 75, 0.16); color: var(--vy-muted); }
        /* the "what's missing" line — the same warm red as every refusal in the operator app (the group wizard's line) */
        .vy-nr-miss { margin: 12px 0 0; font-size: 12.5px; font-weight: 600; line-height: 1.45; color: #8F3110; }
        .vy-nr-miss--tight { margin: 0 0 10px; }
        .vy-nr-empty { margin: 6px 0 0; font-size: 13px; line-height: 1.5; color: var(--vy-muted); }
        .vy-nr-empty.is-block { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; margin-top: 14px; padding: 18px; border-radius: 14px; border: 1px dashed rgba(120, 70, 40, 0.22); background: rgba(255, 255, 255, 0.45); }
        .vy-nr-empty.is-block strong { font-size: 14.5px; color: var(--vy-ink); }
        /* the two-month calendar */
        .vy-nr-cal { border: 1px solid var(--vy-hairline); border-radius: 16px; background: rgba(255, 255, 255, 0.45); padding: 14px 16px; }
        .vy-nr-cal-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .vy-nr-cal-nav { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.75); color: var(--vy-accent-text); display: grid; place-items: center; cursor: pointer; flex: none; transition: background 0.14s; }
        .vy-nr-cal-nav:hover { background: rgba(232, 112, 58, 0.1); }
        .vy-nr-cal-nav:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-cal-labels { flex: 1; display: grid; grid-template-columns: 1fr 1fr; }
        .vy-nr-cal-labels span { text-align: center; font-family: var(--vy-display); font-weight: 600; font-size: 14px; color: var(--vy-ink); }
        .vy-nr-cal-grids { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .vy-nr-cal-month { min-width: 0; }
        .vy-nr-cal-mlabel { display: none; margin-bottom: 6px; text-align: center; font-family: var(--vy-display); font-weight: 600; font-size: 14px; color: var(--vy-ink); }
        .vy-nr-cal-dow { display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 4px; }
        .vy-nr-cal-dow span { text-align: center; font-size: 11px; font-weight: 600; color: var(--vy-faint); padding: 4px 0; }
        .vy-nr-cal-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px 0; }
        .vy-nr-cal-day { height: 46px; border: 0; background: transparent; font: 13px/1 var(--vy-sans); color: var(--vy-ink); cursor: pointer; border-radius: 9px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; padding: 0; transition: background 0.12s; font-variant-numeric: tabular-nums; }
        .vy-nr-cal-day:hover:not(:disabled):not(.is-start):not(.is-end) { background: rgba(232, 112, 58, 0.12); }
        .vy-nr-cal-day:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 1px; position: relative; z-index: 1; }
        .vy-nr-cal-day.is-pad { visibility: hidden; cursor: default; }
        .vy-nr-cal-day.is-past { color: var(--vy-faint); opacity: 0.4; cursor: default; }
        .vy-nr-cal-day.is-weekend:not(.is-start):not(.is-end):not(.is-in) { color: var(--vy-accent-text); }
        .vy-nr-cal-day.is-in { background: rgba(232, 112, 58, 0.14); border-radius: 0; }
        .vy-nr-cal-day.is-start, .vy-nr-cal-day.is-end { background: var(--vy-grad); color: #fff; font-weight: 600; }
        .vy-nr-cal-day.is-start { border-radius: 9px 0 0 9px; }
        .vy-nr-cal-day.is-end { border-radius: 0 9px 9px 0; }
        .vy-nr-cal-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--vy-hairline); }
        .vy-nr-cal-sel { font-size: 13px; font-weight: 500; color: var(--vy-ink); }
        /* party size */
        .vy-nr-party { display: flex; flex-wrap: wrap; gap: 14px 30px; margin-top: 18px; }
        .vy-nr-count { display: flex; flex-direction: column; }
        .vy-nr-counter { display: inline-flex; align-items: center; gap: 14px; padding: 5px 8px; border: 1px solid var(--vy-hairline); border-radius: 12px; background: rgba(255, 255, 255, 0.55); }
        .vy-nr-counter button { width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.8); color: var(--vy-accent-text); font-size: 18px; line-height: 1; cursor: pointer; display: grid; place-items: center; transition: background 0.14s; }
        .vy-nr-counter button:hover:not(:disabled) { background: rgba(232, 112, 58, 0.1); }
        .vy-nr-counter button:disabled { opacity: 0.4; cursor: default; }
        .vy-nr-counter button:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-counter output { min-width: 22px; text-align: center; font-size: 15px; font-weight: 600; font-variant-numeric: tabular-nums; }
        /* what's free */
        .vy-nr-availhead { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin: 24px 0 6px; }
        .vy-nr-rt { margin-top: 10px; padding: 14px 16px; border: 1px solid var(--vy-hairline); border-radius: 14px; background: rgba(255, 255, 255, 0.42); }
        .vy-nr-rt-head { display: flex; align-items: baseline; justify-content: space-between; gap: 6px 12px; flex-wrap: wrap; margin-bottom: 10px; }
        .vy-nr-rt-name { font-family: var(--vy-display); font-weight: 600; font-size: 14.5px; color: var(--vy-ink); }
        .vy-nr-rt-price { font-size: 12.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vy-nr-rt-price strong { font-size: 13.5px; font-weight: 600; color: var(--vy-accent-text); }
        .vy-nr-rt-free { font-size: 12.5px; color: var(--vy-faint); }
        .vy-nr-units { display: flex; flex-wrap: wrap; gap: 8px; }
        .vy-nr-unit { position: relative; display: flex; flex-direction: column; gap: 2px; min-width: 104px; padding: 10px 30px 10px 12px; border-radius: 12px; border: 1px solid rgba(120, 70, 40, 0.16); background: rgba(255, 255, 255, 0.7); cursor: pointer; transition: background 0.14s, border-color 0.14s, box-shadow 0.14s; }
        .vy-nr-unit:hover { background: #fff; border-color: var(--vy-accent-border); }
        .vy-nr-unit:has(input:focus-visible) { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-unit.is-on { background: var(--vy-grad); border-color: transparent; color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vy-nr-unit-code { font-family: var(--vy-display); font-weight: 600; font-size: 14px; }
        .vy-nr-unit-meta { font-size: 11.5px; color: var(--vy-muted); }
        .vy-nr-unit.is-on .vy-nr-unit-meta { color: rgba(255, 255, 255, 0.9); }
        .vy-nr-unit-tick { position: absolute; top: 10px; right: 10px; opacity: 0; transition: opacity 0.14s; }
        .vy-nr-unit.is-on .vy-nr-unit-tick { opacity: 1; }
        .vy-nr-noprice { margin: 0; font-size: 12.5px; line-height: 1.5; color: var(--vy-muted); }
        /* a missing price, set from here */
        .vy-nr-setprice { padding: 14px 15px; border-radius: 12px; background: rgba(232, 112, 58, 0.07); border: 1px dashed rgba(232, 112, 58, 0.4); }
        .vy-nr-setprice-why { margin: 0 0 10px; font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vy-nr-setprice-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .vy-nr-setprice-row .vy-nr-label { margin: 0; }
        .vy-nr-money { position: relative; display: inline-flex; align-items: center; min-width: 0; }
        .vy-nr-money-sym { position: absolute; left: 12px; font-size: 13.5px; color: var(--vy-muted); pointer-events: none; }
        .vy-nr-money .vy-nr-input { padding-left: 27px; }
        .vy-nr-setprice .vy-nr-money .vy-nr-input { width: 140px; }
        .vy-nr-priceform { display: none; }
        /* who's staying */
        .vy-nr-seg { display: inline-flex; gap: 4px; padding: 4px; margin-bottom: 16px; border-radius: 12px; background: rgba(255, 255, 255, 0.45); border: 1px solid var(--vy-glass-border); }
        .vy-nr-seg-i { padding: 7px 14px; border: 0; border-radius: 9px; background: none; font: 600 12.5px/1.2 var(--vy-sans); color: var(--vy-muted); cursor: pointer; }
        .vy-nr-seg-i.is-on { background: #fff; color: var(--vy-accent-text); box-shadow: var(--vy-shadow-sm); }
        .vy-nr-seg-i:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-search { display: flex; gap: 8px; margin-bottom: 12px; }
        .vy-nr-chosen { margin: 0 0 10px; font-size: 13px; color: var(--vy-muted); }
        .vy-nr-guests { display: grid; gap: 6px; }
        .vy-nr-guest { display: flex; align-items: center; gap: 11px; padding: 9px 12px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.62); cursor: pointer; }
        .vy-nr-guest:hover { background: #fff; }
        .vy-nr-guest.is-on { background: #fff; border-color: var(--vy-accent-border); box-shadow: 0 0 0 1px var(--vy-accent-border); }
        .vy-nr-guest:has(input:focus-visible) { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-guest-av { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; background: var(--vy-grad); color: #fff; font-size: 11px; font-weight: 700; flex: none; }
        .vy-nr-guest-id { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
        .vy-nr-guest-name { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-nr-guest-meta { font-size: 12px; color: var(--vy-muted); }
        .vy-nr-checks { display: flex; flex-wrap: wrap; gap: 10px 24px; }
        .vy-nr-check { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vy-ink); cursor: pointer; }
        .vy-nr-check input { width: 16px; height: 16px; margin: 0; accent-color: var(--vy-accent); }
        .vy-nr-check--gap { margin-top: 16px; }
        /* rate & extras */
        .vy-nr-plans { display: flex; flex-wrap: wrap; gap: 10px; }
        .vy-nr-plan { flex: 1; min-width: 150px; display: flex; flex-direction: column; gap: 3px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.55); cursor: pointer; }
        .vy-nr-plan.is-on { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.08); box-shadow: 0 0 0 1px var(--vy-accent-border); }
        .vy-nr-plan:has(input:focus-visible) { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-plan-name { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-nr-plan-price { font-size: 12px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vy-nr-extra { display: flex; align-items: center; gap: 12px; margin-top: 8px; padding: 11px 13px; border: 1px solid var(--vy-hairline); border-radius: 12px; background: rgba(255, 255, 255, 0.5); font-size: 13px; cursor: pointer; }
        .vy-nr-extra-text { flex: 1; min-width: 0; color: var(--vy-ink); }
        .vy-nr-extra-basis { color: var(--vy-muted); }
        .vy-nr-extra-price { color: var(--vy-ink); white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-nr-switch { appearance: none; -webkit-appearance: none; position: relative; width: 38px; height: 22px; margin: 0; border: 0; border-radius: 22px; background: rgba(120, 70, 40, 0.2); cursor: pointer; flex: none; transition: background 0.18s; }
        .vy-nr-switch::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.28); transition: left 0.18s; }
        .vy-nr-switch:checked { background: var(--vy-grad); }
        .vy-nr-switch:checked::after { left: 19px; }
        .vy-nr-switch:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-trip { display: flex; gap: 18px; margin: 8px 0 2px 14px; }
        /* house rules + review */
        .vy-nr-rules > div { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vy-nr-rules > div span { color: var(--vy-muted); }
        .vy-nr-rules > div strong { font-weight: 600; color: var(--vy-ink); text-align: right; }
        .vy-nr-review { margin: 0 0 16px; }
        .vy-nr-review > div { display: grid; grid-template-columns: 84px minmax(0, 1fr) auto; align-items: baseline; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vy-nr-review dt { color: var(--vy-muted); }
        .vy-nr-review dd { margin: 0; font-weight: 600; color: var(--vy-ink); min-width: 0; overflow-wrap: anywhere; }
        /* payment */
        .vy-nr-deposit { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-bottom: 14px; }
        .vy-nr-dep { display: flex; flex-direction: column; gap: 3px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.55); font-size: 13.5px; color: var(--vy-ink); cursor: pointer; }
        .vy-nr-dep span { font-size: 12px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vy-nr-dep.is-on { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.08); box-shadow: 0 0 0 1px var(--vy-accent-border); }
        .vy-nr-dep:has(input:focus-visible) { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        /* a refused restriction */
        .vy-nr-restrict { padding: 16px 18px 6px; border-radius: 16px; background: rgba(255, 252, 248, 0.86); border: 1px solid rgba(217, 154, 61, 0.42); border-left: 4px solid rgb(217, 154, 61); box-shadow: var(--vy-shadow-sm); }
        .vy-nr-restrict-h { margin: 0 0 6px; font-size: 14px; font-weight: 700; color: var(--vy-amber-text); }
        .vy-nr-restrict-list { margin: 0 0 12px; padding-left: 18px; font-size: 13px; line-height: 1.6; color: var(--vy-ink); }
        .vy-nr-restrict .vy-nr-fine { margin: 0 0 12px; color: var(--vy-muted); }
        .vy-nr-nav { display: flex; align-items: center; gap: 10px; padding-top: 2px; }
        .vy-nr-nav-gap { flex: 1; }
        /* the summary */
        .vy-nr-summary { position: sticky; top: 16px; padding: 20px 20px 18px; border-radius: 20px; background: rgba(255, 252, 248, 0.72); backdrop-filter: blur(26px) saturate(1.2); -webkit-backdrop-filter: blur(26px) saturate(1.2); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow); }
        .vy-nr-sum-h { margin: 0 0 10px; font-family: var(--vy-display); font-weight: 600; font-size: 15px; color: var(--vy-ink); }
        .vy-nr-facts { margin: 0 0 12px; }
        .vy-nr-facts > div { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; font-size: 12.5px; }
        .vy-nr-facts dt { color: var(--vy-muted); flex: none; }
        .vy-nr-facts dd { margin: 0; text-align: right; font-weight: 500; color: var(--vy-ink); min-width: 0; overflow-wrap: anywhere; }
        .vy-nr-lines { padding-top: 8px; border-top: 1px solid var(--vy-hairline); }
        .vy-nr-line { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: 13px; font-variant-numeric: tabular-nums; }
        .vy-nr-line.is-total { margin-top: 6px; padding-top: 10px; border-top: 2px solid var(--vy-ink); font-size: 14px; font-weight: 700; }
        .vy-nr-line-note { font-size: 12px; color: var(--vy-green-text); }
        /* which property */
        .vy-nr-choose, .vy-nr-blank { max-width: 620px; margin: 10px auto 0; }
        .vy-nr-blank-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
        .vy-nr-blank-actions .vy-nr-fine { margin: 0; }
        .vy-nr-choose-list { display: grid; gap: 8px; }
        .vy-nr-choose-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 14px 16px; border-radius: 14px; border: 1px solid rgba(120, 70, 40, 0.14); background: rgba(255, 255, 255, 0.66); font: 600 14px/1.3 var(--vy-sans); color: var(--vy-ink); text-align: left; cursor: pointer; transition: background 0.14s, border-color 0.14s; }
        .vy-nr-choose-item:hover { background: #fff; border-color: var(--vy-accent-border); }
        .vy-nr-choose-item:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-choose-ico { color: var(--vy-accent-text); flex: none; }
        .vy-nr-choose-name { flex: 1; min-width: 0; overflow-wrap: anywhere; }
        .vy-nr-choose-rooms { font-size: 12.5px; font-weight: 500; color: var(--vy-muted); white-space: nowrap; }
        .vy-nr-choose-go { color: var(--vy-faint); flex: none; }
        /* the done card */
        .vy-nr-donewrap { max-width: 460px; margin: 22px auto 0; padding: 40px 34px 34px; text-align: center; border-radius: 22px; background: rgba(255, 252, 248, 0.72); backdrop-filter: blur(26px) saturate(1.2); -webkit-backdrop-filter: blur(26px) saturate(1.2); border: 1px solid var(--vy-glass-border); box-shadow: 0 22px 60px -24px rgba(60, 40, 24, 0.32); }
        .vy-nr-donecheck { width: 62px; height: 62px; margin: 0 auto 16px; border-radius: 50%; background: var(--vy-grad); color: #fff; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-nr-donetitle { font-family: var(--vy-display); font-weight: 600; font-size: 22px; letter-spacing: -0.02em; color: var(--vy-ink); }
        .vy-nr-doneref { margin-top: 6px; font-size: 13.5px; color: var(--vy-muted); }
        .vy-nr-doneref .vy-u-code { font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
        .vy-nr-qr { width: 190px; height: 190px; margin: 26px auto 12px; padding: 12px; background: #fff; border-radius: 16px; border: 1px solid var(--vy-hairline); box-shadow: 0 8px 24px -12px rgba(60, 40, 24, 0.28); }
        .vy-nr-qr svg { width: 100%; height: 100%; display: block; }
        .vy-nr-qrlabel { font-size: 13px; font-weight: 600; color: var(--vy-ink); }
        .vy-nr-qrsub { margin: 5px auto 0; max-width: 340px; font-size: 12px; line-height: 1.5; color: var(--vy-faint); }
        .vy-nr-doneactions { display: flex; gap: 10px; margin-top: 26px; }
        .vy-nr-donebtn { flex: 1; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.6); color: var(--vy-ink); font: 600 13.5px/1.3 var(--vy-sans); text-decoration: none; text-align: center; transition: background 0.14s; }
        .vy-nr-donebtn:hover { background: rgba(255, 255, 255, 0.9); }
        .vy-nr-donebtn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-donebtn.is-primary { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent); }
        /* ── 2026-10-04, the second pass: each night's price on the calendar, the way out of a property with no rooms, a saved
              season applied from here, and four steps with the confirmation in one place ───────────────────────────────── */
        .vy-nr-cal-num { font-size: 13px; line-height: 1; }
        .vy-nr-cal-price { min-height: 10px; max-width: 100%; overflow: hidden; white-space: nowrap; font-size: 10px; line-height: 1; font-weight: 500; color: var(--vy-muted); letter-spacing: -0.01em; }
        /* the day cells are narrowest with two months side by side in a narrow card — "€120.50" spilled out (the review) */
        @media (min-width: 561px) and (max-width: 1100px) { .vy-nr-cal-price { font-size: 9px; letter-spacing: -0.03em; } }
        .vy-nr-h[tabindex="-1"]:focus { outline: none; }
        .vy-nr-cal-day.is-noprice .vy-nr-cal-price { color: var(--vy-faint); }
        .vy-nr-cal-day.is-full { color: var(--vy-faint); }
        .vy-nr-cal-day.is-full .vy-nr-cal-price { color: #8F3110; font-weight: 600; }
        .vy-nr-cal-day.is-today:not(.is-start):not(.is-end) { box-shadow: inset 0 0 0 1.5px rgba(232, 112, 58, 0.55); }
        .vy-nr-cal-day.is-start .vy-nr-cal-price, .vy-nr-cal-day.is-end .vy-nr-cal-price { color: rgba(255, 255, 255, 0.92); }
        .vy-nr-cal-legend { margin: 10px 0 0; font-size: 11.5px; line-height: 1.5; color: var(--vy-muted); }
        .vy-nr-cal-key { font-weight: 600; color: var(--vy-ink); }
        .vy-nr-flash--in { margin: -4px 0 16px; }
        .vy-nr-others { display: grid; gap: 8px; margin: 18px 0 16px; }
        .vy-nr-others .vy-nr-h3 { margin-bottom: 2px; }
        .vy-nr-other { display: flex; align-items: center; gap: 12px; width: 100%; padding: 14px 16px; border-radius: 14px; border: 1px solid rgba(120, 70, 40, 0.14); background: rgba(255, 255, 255, 0.66); font: 600 14px/1.3 var(--vy-sans); color: var(--vy-ink); text-align: left; cursor: pointer; transition: background 0.14s, border-color 0.14s; }
        .vy-nr-other:hover { background: #fff; border-color: var(--vy-accent-border); }
        .vy-nr-other:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-nr-rt.is-picked { border-color: var(--vy-accent-border); background: rgba(255, 255, 255, 0.62); }
        .vy-nr-plans--inline { margin-top: 12px; }
        .vy-nr-season { margin-bottom: 10px; padding: 14px 15px; border-radius: 12px; background: rgba(232, 112, 58, 0.09); border: 1px solid rgba(232, 112, 58, 0.35); }
        .vy-nr-season-why { margin: 0 0 10px; font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vy-nr-season .vy-nr-fine { margin-top: 8px; }
        .vy-nr-block { margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--vy-hairline); }
        .vy-nr-block > .vy-nr-h3 { margin-bottom: 8px; }
        .vy-nr-lede--tight { margin: 0 0 12px; }
        .vy-nr-fine--tight { margin-top: 6px; }
        .vy-nr-field--gap { margin-top: 14px; }
        .vy-nr-line.is-sub { padding: 4px 0; font-size: 12.5px; color: var(--vy-muted); }
        .vy-nr-nav-total { display: none; }
        .vy-nr-doneno { max-width: 340px; margin: 24px auto 8px; font-size: 13px; line-height: 1.55; color: var(--vy-muted); }
        @media (max-width: 900px) {
            .vy-nr-body { grid-template-columns: minmax(0, 1fr); }
            .vy-nr-summary { position: static; }
        }
        @media (max-width: 760px) {
            .vy-nr-standalone { padding: 16px 16px 48px; }
            .vy-nr-topbar { margin-bottom: 14px; }
            .vy-nr-stepcount { display: none; }
            .vy-nr-propselect { max-width: 190px; }
            .vy-nr-card { padding: 18px 16px; border-radius: 18px; }
            .vy-nr-grid2, .vy-nr-deposit { grid-template-columns: minmax(0, 1fr); }
            .vy-nr-nav .vy-btn { flex: 1 1 auto; justify-content: center; }
            .vy-nr-nav-gap { display: none; }
            /* a bar at the bottom on a phone: the total and the next move, where the thumb is */
            .vy-nr-nav { position: sticky; bottom: 10px; z-index: 5; flex-wrap: wrap; padding: 10px 12px; border-radius: 16px; background: rgba(255, 252, 248, 0.94); backdrop-filter: blur(18px) saturate(1.2); -webkit-backdrop-filter: blur(18px) saturate(1.2); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow); }
            .vy-nr-nav-total { display: inline-flex; align-items: baseline; gap: 6px; flex: 1 1 100%; font-size: 12.5px; color: var(--vy-muted); }
            .vy-nr-nav-total strong { font-size: 15px; font-weight: 700; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
            .vy-nr-review > div { grid-template-columns: minmax(0, 1fr) auto; }
            .vy-nr-review dt { grid-column: 1 / -1; }
            .vy-nr-search .vy-nr-input { min-width: 0; }
            .vy-nr-donewrap { padding: 32px 20px 24px; }
        }
        @media (max-width: 560px) {
            .vy-nr-cal { padding: 12px; }
            .vy-nr-cal-grids { grid-template-columns: minmax(0, 1fr); gap: 16px; }
            .vy-nr-cal-labels { visibility: hidden; }
            .vy-nr-cal-mlabel { display: block; }
            .vy-nr-unit { flex: 1 1 calc(50% - 4px); min-width: 0; }
        }

        /* Operator Billing — the design's plan-card header (name + status pill left, price right), its
           next-invoice strip, and the subdued rail "Cancel subscription" card. */
        .vy-bill-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .vy-bill-plan { font-family: var(--vy-display); font-size: 22px; font-weight: 700; letter-spacing: -0.02em; color: var(--vy-ink); }
        .vy-bill-price { text-align: right; flex: none; }
        .vy-bill-amt { font-family: var(--vy-display); font-size: 32px; font-weight: 700; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
        .vy-bill-per { font-size: 13px; color: var(--vy-muted); margin-left: 2px; }
        .vy-bill-vat { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; }
        .vy-bill-next { display: flex; align-items: center; gap: 10px; margin-top: 14px; padding: 12px 14px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.42); border: 1px solid var(--vy-hairline); font-size: 12.5px; color: var(--vy-ink); }
        .vy-bill-next-ico { flex: none; width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; background: rgba(232, 112, 58, 0.12); color: var(--vy-accent-text); }
        .vy-bill-cancel .vy-card-title { color: var(--vy-muted); }

        /* Payout-run transfer row — the design's initial disc beside the owner name. */
        .vy-po-who { display: flex; align-items: flex-start; gap: 10px; }
        .vy-po-who .vy-mini-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 11px; font-weight: 600; display: grid; place-items: center; flex: none; font-family: var(--vy-display); }

        /* Right-rail dark "Run summary" panel (Operator Payout Run exact-match — the design's one dark
           surface sits at the TOP of the rail, not in the stat strip). Deliberately mirrors .vy-tonight's
           tokens and shape rather than defining a second dark-panel treatment: two dark rail panels styled
           independently is exactly how the drift this project keeps consolidating starts. */
        .vy-runsum { background: var(--vy-grad-dark); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: var(--vy-shadow-dark); color: rgba(255, 255, 255, 0.92); }
        /* ⚠️ .58 not .50 — MEASURED, not copied. Light text on this gradient composites against the SAME
           stop it sits on, so the worst case is the LIGHTEST stop rgb(58,46,39): at alpha .50 (the value
           .vy-tonight uses) a 9.5px uppercase label lands on exactly 4.50 — the AA boundary, with nothing
           in hand. At .58 it measures 5.51. These labels are 9.5px, i.e. NORMAL text for WCAG (large is
           ≥24px, or ≥18.66px bold), so 4.5 is the real bar. See the realm-wide note in MISSING-BACKEND. */
        .vy-runsum-label { font-size: 9.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: rgba(255, 255, 255, 0.58); }
        .vy-runsum-head { font-family: var(--vy-display); font-size: 30px; font-weight: 700; letter-spacing: -0.02em; line-height: 1.1; margin-top: 12px; }
        .vy-runsum-sub { font-size: 12px; color: rgba(255, 255, 255, 0.62); margin-top: 7px; }
        .vy-runsum-note { font-size: 11.5px; color: rgba(255, 255, 255, 0.5); margin-top: 8px; }
        .vy-runsum-grid { display: flex; gap: 22px; margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.12); }
        .vy-runsum-k { font-size: 9.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: rgba(255, 255, 255, 0.58); }
        .vy-runsum-v { font-family: var(--vy-display); font-size: 17px; font-weight: 600; margin-top: 4px; font-variant-numeric: tabular-nums; }
        .vy-runsum .vy-sep { color: rgba(255, 255, 255, 0.35); }

        /* Cross-cutting READ-ONLY "workspace paused" banner (Operator Dashboard/Calendar exact-match) */
        .vy-readonly-banner { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; padding: 14px 18px; border-radius: var(--vy-r-lg); background: rgba(224, 146, 30, 0.10); border: 1px solid rgba(224, 146, 30, 0.30); }
        .vy-ro-body { display: flex; align-items: flex-start; gap: 12px; }
        .vy-ro-ico { color: var(--vy-amber); flex: none; margin-top: 1px; }
        .vy-ro-title { font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-ink); }
        .vy-ro-sub { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; line-height: 1.5; max-width: 640px; }
        .vy-ro-cta { flex: none; font-size: 13px; font-weight: 600; color: #fff; background: var(--vy-amber); border-radius: var(--vy-r-pill); padding: 8px 16px; text-decoration: none; white-space: nowrap; }
        .vy-ro-arrow { margin-left: 2px; }

        /* Dashboard — urgent count on the work-orders KPI */
        /* The design sets both of these as plain text on the value's baseline, not as chips. */
        .vy-dk-urgent { display: inline; font-size: 13px; font-weight: 500; color: var(--vy-accent-text); margin-left: 8px; }
        /* Size/weight follow the design; the up/down colour pair is kept — the design has only one
           styling for this slot, and collapsing them would drop the direction signal. */
        .vy-dk-delta { font-size: 13px; font-weight: 500; color: #3d6b3d; margin-left: 8px; }
        .vy-dk-delta.is-down { color: var(--vy-accent-text); }

        /* Dashboard — Today's tasks */
        .vy-task-groups { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        .vy-task-group { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--vy-muted); background: var(--vy-wash, rgba(255,255,255,.5)); border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-pill); padding: 4px 10px; }
        .vy-task-group b { font-family: var(--vy-display); color: var(--vy-ink); }
        .vy-task-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-task-row:last-child { border-bottom: none; }
        /* D1-1 — the personal tick + your own progress through the list. ⚠️ A ticked row is DIMMED and
           struck, never removed, and its real count stays legible: the tick is a note to yourself, not a
           claim that the work is finished. Tokens only (--vy-muted / --vy-accent are the AA-corrected ones). */
        .vy-task-line { display: flex; align-items: center; gap: 10px; }
        .vy-task-line .vy-task-row { flex: 1; min-width: 0; }
        .vy-task-tick { flex: none; width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid var(--vy-hairline-strong, rgba(120,70,40,.28)); background: rgba(255,255,255,.6); color: #fff; display: grid; place-items: center; cursor: pointer; padding: 0; transition: background .15s, border-color .15s; }
        .vy-task-tick:hover { border-color: var(--vy-accent); }
        .vy-task-tick:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-task-line.is-ticked .vy-task-tick { background: var(--vy-accent); border-color: var(--vy-accent); }
        .vy-task-line.is-ticked .vy-task-label { text-decoration: line-through; color: var(--vy-muted); }
        .vy-task-prog { margin: 2px 0 12px; }
        .vy-task-prog-bar { height: 4px; border-radius: 999px; background: rgba(120,70,40,.12); overflow: hidden; }
        .vy-task-prog-bar span { display: block; height: 100%; border-radius: 999px; background: var(--vy-accent); transition: width .25s ease; }
        .vy-task-prog-label { display: block; margin-top: 6px; font-size: 11.5px; color: var(--vy-muted); }
        /* D1-2 — the grouping switch + the urgent signal that must survive re-grouping. Tokens only. */
        .vy-task-group-sw { display: inline-flex; align-items: center; gap: 4px; margin: 0 0 12px; padding: 3px; border-radius: var(--vy-r-pill); background: rgba(120,70,40,.06); border: 1px solid var(--vy-glass-border); }
        .vy-task-group-lbl { font-size: 10.5px; font-weight: 500; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-muted); padding: 0 6px 0 8px; }
        .vy-task-group-opt { border: none; background: none; padding: 5px 11px; border-radius: var(--vy-r-pill); font-family: var(--vy-sans); font-size: 12px; font-weight: 500; color: var(--vy-muted); cursor: pointer; transition: background .15s, color .15s; }
        .vy-task-group-opt:hover { color: var(--vy-ink); }
        .vy-task-group-opt.is-on { background: #fff; color: var(--vy-ink); font-weight: 600; box-shadow: var(--vy-shadow-sm); }
        .vy-task-group-opt:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        /* ⚠️ The folded-away urgency, brought forward: a category heading says nothing about priority, so the
           real urgent count rides the heading and each row keeps its own priority dot. */
        .vy-task-sec-urgent { margin-left: auto; font-size: 10.5px; font-weight: 600; color: var(--vy-accent-text); background: rgba(194,65,12,.10); padding: 2px 8px; border-radius: var(--vy-r-pill); white-space: nowrap; }
        .vy-task-row-dot { flex: none; }
        .vy-task-pri { width: 7px; height: 7px; border-radius: 50%; flex: none; }
        .vy-task-pri.is-urgent { background: var(--vy-accent); }
        .vy-task-pri.is-high { background: var(--vy-amber-2); }
        .vy-task-pri.is-normal { background: var(--vy-muted); }
        .vy-task-label { font-size: 13px; color: var(--vy-ink); }
        .vy-task-meta { font-size: 11.5px; color: var(--vy-muted); margin-left: auto; }
        /* GATE 7 — an ACTIONABLE task row (links to where it's resolved). */
        .vy-task-row.is-actionable { text-decoration: none; color: inherit; cursor: pointer; border-radius: 8px; padding: 10px 8px; margin: 0 -8px; border-bottom: 1px solid var(--vy-hairline); transition: background .14s; }
        .vy-task-row.is-actionable:hover { background: rgba(232, 112, 58, 0.06); }
        .vy-task-go { margin-left: 10px; color: var(--vy-accent-text); font-weight: 700; opacity: 0; transition: opacity .14s, transform .14s; }
        .vy-task-row.is-actionable:hover .vy-task-go { opacity: 1; transform: translateX(2px); }
        /* GATE 7 — Housekeeping / Maintenance per-status rail cards (real status counts). */
        .vy-stat-list { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .vy-stat { display: flex; align-items: center; gap: 8px; padding: 8px 11px; border-radius: 10px; background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); }
        .vy-stat-dot { width: 8px; height: 8px; border-radius: 50%; flex: none; }
        .vy-stat-k { font-size: 12px; color: var(--vy-muted); }
        .vy-stat-v { margin-left: auto; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--vy-ink); }
        /* GATE 8.1 — housekeeping drag-lane-move (forward-only). */
        .vy-hk-drag { cursor: grab; }
        .vy-hk-drag:active { cursor: grabbing; }
        .vy-hk-lane { transition: background .12s, outline-color .12s; }
        .vy-hk-lane.is-drop { outline: 2px dashed var(--vy-accent-border); outline-offset: -2px; background: rgba(232, 112, 58, 0.05); }

        /* Dashboard — Occupancy next 7 days (real per-day bars) */
        .vy-occ7 { display: flex; align-items: flex-end; gap: 8px; height: 92px; margin: 6px 0 4px; }
        .vy-occ7-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end; }
        .vy-occ7-bar { width: 100%; max-width: 30px; border-radius: 6px 6px 0 0; background: var(--vy-grad); min-height: 3px; }
        .vy-occ7-pct { font-size: 10.5px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
        .vy-occ7-day { font-size: 10px; color: var(--vy-muted); }

        /* ---- Operator Dashboard — wireframe parity ----------------------------------------------
           Scoped to .vy-dash on purpose: .vy-dash-cols, .vy-tonight and .vy-stat-list are shared with
           Channels, Billing, Reservation Detail, Maintenance and Owners, which keep their own sizing.
           Colours resolve through the --vy-* tokens; the prototype's lighter greys are NOT copied
           literally because --vy-muted carries the WCAG-AA value the polish pass computed. */
        .vy-dash .vy-dash-cols { grid-template-columns: minmax(0, 1fr) 250px; gap: 20px; }
        .vy-dash .vy-dash-cols > .vy-stack:first-child .vy-card-title { font-size: 16px; font-weight: 500; }

        /* Today's tasks — grouped by priority (the prototype's default grouping) */
        .vy-dash .vy-task-sec-head { display: flex; align-items: center; gap: 8px; padding: 15px 2px 5px; }
        .vy-dash .vy-task-sec:first-child .vy-task-sec-head { padding-top: 4px; }
        .vy-dash .vy-task-sec-dot { width: 7px; height: 7px; border-radius: 50%; flex: none; }
        .vy-dash .vy-task-sec-label { font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; }
        .vy-dash .vy-task-pri-ink.is-urgent { color: var(--vy-accent-text); }
        .vy-dash .vy-task-pri-ink.is-high { color: var(--vy-amber-2); }
        .vy-dash .vy-task-pri-ink.is-normal { color: var(--vy-muted); }
        .vy-dash .vy-task-sec-count { font-size: 11px; color: var(--vy-faint); font-variant-numeric: tabular-nums; }
        .vy-dash .vy-task-sec .vy-task-row.is-actionable { border-bottom: none; border-top: 1px solid var(--vy-hairline); }
        .vy-dash .vy-task-clear { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: var(--vy-r-pill); background: rgba(217, 102, 46, 0.13); color: #B4400F; font-size: 11px; font-weight: 500; }

        /* Arrivals — the prototype's initials avatar */
        .vy-dash .vy-arr-who { display: flex; align-items: center; gap: 11px; min-width: 0; }
        .vy-dash .vy-arr-av { width: 32px; height: 32px; border-radius: 50%; background: var(--vy-avatar-bg); display: flex; align-items: center; justify-content: center; color: var(--vy-on-peach); font-size: 11px; font-weight: 500; flex: none; }

        /* Occupancy — the prototype's 130px chart, wider gutters, today emphasised */
        .vy-dash .vy-occ7 { height: 130px; gap: 14px; }
        .vy-dash .vy-occ7-col { gap: 8px; }
        .vy-dash .vy-occ7-bar { max-width: none; }
        .vy-dash .vy-occ7-day.is-today { color: var(--vy-ink); font-weight: 600; }
        .vy-dash .vy-week-chip { display: inline-flex; align-items: center; padding: 6px 14px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.55); border: 1px solid var(--vy-glass-border); font-size: 12px; font-weight: 500; color: var(--vy-muted); }

        /* Tonight — 34px figure with its label on the same baseline, plus the prototype's glow */
        .vy-dash .vy-tonight-wrap { position: relative; }
        .vy-dash .vy-tonight-glow { position: absolute; top: -28px; left: -30px; right: -30px; bottom: -34px; background: radial-gradient(ellipse at 50% 55%, rgba(232, 112, 58, 0.38), transparent 70%); filter: blur(26px); pointer-events: none; }
        .vy-dash .vy-tonight { position: relative; padding: 20px; }
        .vy-dash .vy-tonight-head { display: flex; align-items: baseline; gap: 8px; margin-top: 14px; }
        .vy-dash .vy-tonight-val { font-size: 34px; font-weight: 600; margin-top: 0; }
        .vy-dash .vy-tonight-val .vy-dk-unit { font-size: 16px; }
        .vy-dash .vy-tonight-sub { margin-top: 0; }
        .vy-dash .vy-tonight-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .vy-dash .vy-tonight-cell .vy-tc-k { font-size: 10.5px; font-weight: 400; letter-spacing: normal; text-transform: none; color: rgba(255, 255, 255, 0.62); }
        .vy-dash .vy-tonight-cell .vy-tc-v { font-weight: 600; margin-top: 3px; }

        /* Right rail — the prototype heads these cards with an eyebrow and lists status as plain rows */
        .vy-dash .vy-rail-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
        .vy-dash .vy-stat-list { grid-template-columns: 1fr; gap: 10px; }
        .vy-dash .vy-stat { padding: 0; background: none; border: none; border-radius: 0; }
        .vy-dash .vy-stat-k { font-size: 13px; }
        .vy-dash .vy-stat-v { font-size: 13px; font-weight: 500; }

        /* Maintenance — the prototype's highlighted out-of-order callout */
        .vy-dash .vy-oo-cta { display: flex; align-items: center; gap: 10px; margin-top: 14px; padding: 10px 12px; border-radius: 12px; background: rgba(217, 102, 46, 0.08); border: 1px solid rgba(232, 112, 58, 0.22); text-decoration: none; transition: background .14s; }
        .vy-dash .vy-oo-cta:hover { background: rgba(217, 102, 46, 0.13); }
        .vy-dash .vy-oo-ico { width: 28px; height: 28px; border-radius: 8px; background: rgba(217, 102, 46, 0.14); color: #B4400F; display: flex; align-items: center; justify-content: center; flex: none; }
        .vy-dash .vy-oo-t { display: block; font-size: 12px; font-weight: 500; color: var(--vy-ink); }
        .vy-dash .vy-oo-s { display: block; font-size: 11px; color: var(--vy-muted); margin-top: 1px; font-variant-numeric: tabular-nums; }

        /* Dashboard — Maintenance + Channels rows */
        .vy-mini-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vy-mini-row:last-child { border-bottom: none; }
        .vy-mini-k { color: var(--vy-muted); display: inline-flex; align-items: center; gap: 8px; }
        .vy-mini-v { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-chan-status { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; }
        .vy-chan-status .vy-dot { width: 7px; height: 7px; border-radius: 50%; }
        .vy-chan-status.is-synced { color: #3d6b3d; } .vy-chan-status.is-synced .vy-dot { background: var(--vy-accent-3); }
        .vy-chan-status.is-off { color: var(--vy-muted); } .vy-chan-status.is-off .vy-dot { background: var(--vy-muted); }
        .vy-chan-status.is-pending { color: var(--vy-amber); } .vy-chan-status.is-pending .vy-dot { background: var(--vy-amber-2); }
        .vy-chan-status.is-error { color: var(--vy-accent-text); } .vy-chan-status.is-error .vy-dot { background: var(--vy-accent); }

        /* Operator Channels exact-match — channel rows + connect grid */
        .vy-chan-item { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-chan-item:last-child { border-bottom: none; }
        .vy-chan-badge { width: 34px; height: 34px; border-radius: 9px; background: var(--vy-dark); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-weight: 700; font-size: 14px; flex: none; }
        .vy-chan-name { font-family: var(--vy-display); font-size: 14px; font-weight: 600; display: flex; align-items: center; }
        .vy-chan-sub { font-size: 12px; color: var(--vy-muted); margin-top: 2px; }
        .vy-chan-count { font-family: var(--vy-display); font-size: 16px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .vy-chan-count-sub { font-size: 11px; color: var(--vy-muted); }
        .vy-connect-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; }
        .vy-connect-opt { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--vy-hairline); border-radius: 12px; }

        /* ---- Units table ----------------------------------------------------------------------- */
        .vy-utable-wrap { overflow-x: auto; }
        .vy-utable { width: 100%; border-collapse: collapse; min-width: 720px; }
        .vy-utable thead th { text-align: left; font-size: 9.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted); padding: 0 14px 12px; white-space: nowrap; }
        .vy-utable tbody tr { transition: background .15s ease; }
        .vy-utable tbody tr:hover { background: rgba(255, 255, 255, 0.5); }
        .vy-utable td { padding: 13px 14px; border-top: 1px solid var(--vy-hairline); font-size: 13px; vertical-align: middle; }
        .vy-utable .vy-u-code { font-family: var(--vy-display); font-weight: 600; }
        .vy-utable .vy-u-sub { font-size: 11px; color: var(--vy-on-grad); margin-top: 2px; }   /* muted measured 4.48 on the row tint */
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
        /* ⚠️ 2026-10-05 — "In progress" (housekeeping) had no tone at all: the tag and its dot were blank */
        .vy-tag.tone-in-progress { background: rgba(224, 166, 58, 0.16); color: var(--vy-amber-text); }
        .vy-tag.tone-in-progress .vy-dot { background: var(--vy-hk-progress); }
        .vy-tag.tone-in-service { background: rgba(138, 128, 121, 0.14); color: var(--vy-muted); }
        .vy-tag.tone-in-service .vy-dot { background: var(--vy-muted); }
        .vy-tag.tone-ooo { background: rgba(194, 65, 12, 0.14); color: var(--vy-accent); }
        .vy-tag.tone-ooo .vy-dot { background: var(--vy-accent); }
        .vy-tag.tone-oos { background: rgba(66, 52, 44, 0.12); color: var(--vy-dark); }
        .vy-tag.tone-oos .vy-dot { background: var(--vy-dark); }

        /* ---- Owners list + invite modal -------------------------------------------------------- */
        [x-cloak] { display: none !important; }
        /* Polish Batch 1 — the base carries a neutral glass notice tone so a bare `.vy-flash` (billing / billing-checkout /
           insights multi-currency / portfolio notices) is styled, not untinted; `.is-ok` / `.is-err` override it. */
        .vy-flash { display: flex; align-items: center; gap: 9px; padding: 12px 16px; border-radius: var(--vy-r-md); font-size: 13px; font-weight: 500; margin-bottom: 16px; background: var(--vy-glass); border: 1px solid var(--vy-glass-border); color: var(--vy-ink); }
        .vy-flash.is-ok { background: rgba(52, 140, 90, 0.12); color: rgb(34, 110, 68); border: 1px solid rgba(52, 140, 90, 0.24); }
        .vy-flash.is-err { background: rgba(194, 65, 12, 0.10); color: var(--vy-accent); border: 1px solid var(--vy-accent-border); }

        .vy-owner-row { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-owner-row:last-child { border-bottom: none; }
        /* Polish Batch 2 — hover ONLY on the clickable (anchor) owner rows; the static <div> variant (pending invites) is untouched. */
        a.vy-owner-row { transition: background .15s ease; }
        a.vy-owner-row:hover { background: rgba(255, 255, 255, 0.5); }
        .vy-owner-main { flex: 1; min-width: 0; }
        .vy-owner-name { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; }
        .vy-owner-sub { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; }
        .vy-owner-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--vy-avatar-bg); color: var(--vy-on-peach); font-size: 13px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vy-pending-badge { font-size: 11px; font-weight: 600; color: var(--vy-amber); background: rgba(224, 146, 30, 0.14); border-radius: var(--vy-r-pill); padding: 3px 11px; }

        /* Modal */
        .vy-modal-backdrop { position: fixed; inset: 0; z-index: 40; background: rgba(66, 40, 22, 0.34); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); display: grid; place-items: center; padding: 20px; }
        .vy-modal { width: 100%; max-width: 480px; max-height: 92vh; overflow-y: auto; background: var(--vy-glass); backdrop-filter: blur(30px) saturate(1.2); -webkit-backdrop-filter: blur(30px) saturate(1.2); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); }
        .vy-modal-head { display: flex; align-items: flex-start; gap: 14px; padding: 22px 24px 14px; }
        .vy-modal-ico { width: 42px; height: 42px; border-radius: var(--vy-r-md); background: var(--vy-grad); color: #fff; display: grid; place-items: center; flex: none; box-shadow: var(--vy-shadow-accent); }
        .vy-modal-title { font-family: var(--vy-display); font-size: 19px; font-weight: 600; margin: 0; letter-spacing: -0.01em; }
        .vy-modal-sub { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; line-height: 1.4; }
        .vy-modal-x { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); color: var(--vy-muted); cursor: pointer; flex: none; }
        .vy-modal-body { padding: 6px 24px 22px; }
        .vy-modal-foot { display: flex; gap: 10px; justify-content: flex-end; padding: 4px 24px 22px; }
        .vy-modal-foot .vy-btn--accent { flex: 1; justify-content: center; padding: 12px 16px; }
        /* A modal panel that is also a modal CARD is near-opaque (DO-STATE-1): over the scrim the see-through glass took
           muted labels to 3.38:1. Two classes, so it wins over the glass above whatever the order of the rules. */
        .vy-modal.vy-modal-card { background: rgba(255, 252, 248, 0.94); }
        .vy-modal--wide { max-width: 560px; }

        /* ── Guests (2026-09-29) — the CRM list rebuilt: one toolbar, a sortable table with a real footer, a menu on every
           row, and centred dialogs. Secondary text INSIDE rows is --vy-on-grad, never --vy-muted, which read 4.48:1 on
           the row plate (DO-A11Y-5). ⚠️ Every display below is in a class, never inline beside x-show — x-show removes
           an inline display when it shows the element (XShowInlineDisplayContractTest). ────────────────────────────── */
        .vy-gl-bar { position: relative; z-index: 3; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 22px 0 16px; padding: 8px; border-radius: var(--vy-r-lg); background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        /* ⚠️ 2026-10-05 (his report on Guests: "search box design is not good and its symbol is not well-aligned") — ONE field for every
           list that uses this kit (Guests, Owners, Units, Maintenance): the toolbar's own height, the icon drawn as a 16 px block centred
           in the row, the text and its placeholder in readable ink, and the browser's own search styling switched off (Safari draws its
           own field — padding, decoration — under `type="search"` unless told not to). */
        .vy-gl-search { position: relative; display: flex; align-items: center; gap: 9px; flex: 1 1 300px; min-width: 0; height: 40px; padding: 0 6px 0 15px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.82); border: 1px solid rgba(120, 70, 40, 0.14); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7); transition: border-color .15s ease, box-shadow .15s ease, background .15s ease; }
        .vy-gl-search:focus-within { background: #fff; border-color: var(--vy-accent-border); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.12); }
        .vy-gl-search-ico { flex: none; display: block; width: 16px; height: 16px; color: var(--vy-muted); }
        .vy-gl-spinner { flex: none; display: inline-block; width: 15px; height: 15px; border-radius: 50%; border: 2px solid rgba(194, 65, 12, 0.18); border-top-color: var(--vy-accent); animation: vy-gl-spin .7s linear infinite; }
        @keyframes vy-gl-spin { to { transform: rotate(360deg); } }
        .vy-gl-search-in { flex: 1; min-width: 0; height: 100%; margin: 0; padding: 0; border: none; background: transparent; font: inherit; font-size: 13.5px; line-height: normal; color: var(--vy-ink); outline: none; -webkit-appearance: none; appearance: none; border-radius: 0; }
        .vy-gl-search-in::placeholder { color: var(--vy-muted); opacity: 1; }
        .vy-gl-search-in::-webkit-search-cancel-button, .vy-gl-search-in::-webkit-search-decoration, .vy-gl-search-in::-webkit-search-results-button, .vy-gl-search-in::-webkit-search-results-decoration { -webkit-appearance: none; display: none; }
        .vy-gl-bar .vy-btn { height: 40px; }   /* one height for the toolbar row — the buttons were 36 px beside a 40 px field */
        .vy-gl-clear { flex: none; width: 28px; height: 28px; padding: 0; border-radius: 50%; display: grid; place-items: center; border: none; background: transparent; color: var(--vy-on-grad); cursor: pointer; transition: background .15s ease, color .15s ease; }
        .vy-gl-clear:hover { background: rgba(120, 70, 40, 0.08); color: var(--vy-ink); }
        .vy-gl-chips { display: inline-flex; align-items: center; flex-wrap: wrap; gap: 2px; padding: 3px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.45); border: 1px solid var(--vy-glass-border); }
        .vy-gl-chip { display: inline-flex; align-items: center; gap: 7px; height: 32px; padding: 0 12px; border-radius: var(--vy-r-pill); font-size: 12.5px; font-weight: 600; color: var(--vy-on-grad); white-space: nowrap; transition: background .15s ease, color .15s ease, box-shadow .15s ease; }
        .vy-gl-chip:hover { color: var(--vy-ink); background: rgba(255, 255, 255, 0.6); }
        .vy-gl-chip.is-on { background: #fff; color: var(--vy-accent-text); box-shadow: var(--vy-shadow-sm); }
        .vy-gl-count { min-width: 20px; padding: 1px 7px; border-radius: 999px; font-size: 11px; font-weight: 600; text-align: center; font-variant-numeric: tabular-nums; background: rgba(120, 70, 40, 0.08); color: var(--vy-on-grad); }
        .vy-gl-chip.is-on .vy-gl-count { background: rgba(232, 112, 58, 0.14); color: var(--vy-accent-text); }
        .vy-gl-tools { display: flex; align-items: center; gap: 8px; margin-left: auto; }
        .vy-gl-select.is-on { background: #fff; color: var(--vy-accent-text); border-color: var(--vy-accent-border); }
        /* ── Guests, second pass (2026-10-05, his report: "text is not visible completely") ────────────────────────────────────
           The toolbar like the newer lists — search and the buttons on top, the filters below. The table fits its card at EVERY
           width: at 960 px of card and below each guest is a stacked row (🔬 before: a 760 px table in a 732 px card at 1,024 px, Status
           cut to "No booki…"; on a phone the Nationality column under the row menu). A size container, so the card's own width decides —
           the sidebar and the window both change it. ⚠️ 960, not 880: the review predicted, and the walk measured, a full table of 939 px
           (969 with Select on) in cards of 890–940 px — sideways scrolling again at 1,180–1,230 px windows. With a long country name
           allowed to wrap up to 1,080 px of card (below), 🖥 the table then measured inside its card at every width from 961 px,
           Select on and off (1,251–1,259 px windows included — the second review's question). */
        .vy-gs-bar .vy-gl-tools { flex-wrap: wrap; justify-content: flex-end; }
        .vy-gs-filters { flex: 1 1 100%; min-width: 0; display: flex; }
        /* what cannot be used looks it (2026-10-05: Message's Send, refused while email is not sent, looked ready) */
        .vy-gst .vy-btn--accent:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; transform: none; }
        .vy-gst .vy-input:disabled { opacity: .55; cursor: not-allowed; }
        .vy-gst .vy-gl-switch input:disabled ~ .vy-gl-track, .vy-gst .vy-gl-switch input:disabled ~ .vy-gl-switch-text { opacity: .5; }
        @media (max-width: 1100px) {
            /* the search has the whole first line here (the kit's rule) — so the filters and the buttons share the second */
            .vy-gs-bar .vy-gs-filters { flex: 1 1 auto; order: 2; }
            .vy-gs-bar .vy-gl-tools { order: 3; }
        }
        .vy-gs-bar .vy-gl-chips { flex: 0 1 auto; max-width: 100%; }
        .vy-gst .vy-gl-card { container: vy-gst-list / inline-size; }
        .vy-gst .vy-gl-table { min-width: 0; }
        .vy-gst .vy-gl-c-nat, .vy-gst .vy-gl-c-status, .vy-gst .vy-gl-c-stay { white-space: nowrap; }
        /* …except a country name over 16 characters ("Bosnia & Herzegovina", "Central African Republic"), which may wrap at its spaces
           where the card is too narrow to hold it beside the rest — "United Kingdom" and every shorter name keep one line. Only up to
           1,080 px of card: wider, the table measured fits with it on one line, and a wrap allowed there was taken anyway (🔬 at 1,440 px
           — the table shares its shrinking out across every column that may wrap). */
        @container vy-gst-list (max-width: 1080px) {
            .vy-gst .vy-gl-c-nat.is-long { white-space: normal; }
        }
        /* ⚠️ scoped to this page: Units' sub-line is a .vy-gl-contact of spans joined by "·", and stacking it read "Name / · Door code set" */
        .vy-gst .vy-gl-contact { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
        .vy-gst .vy-gl-mail { overflow-wrap: anywhere; }   /* a long address breaks only when it must — it no longer shares a line with the phone */
        @container vy-gst-list (max-width: 960px) {
            /* the header keeps only what sorts — Guest · Stays · Stay — as a row of links above the cards */
            .vy-gst .vy-gl-table, .vy-gst .vy-gl-table tbody, .vy-gst .vy-gl-table thead { display: block; }
            .vy-gst .vy-gl-table thead tr { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 20px; padding: 11px 16px; }
            .vy-gst .vy-gl-table thead th { padding: 0; border: 0; background: none; backdrop-filter: none; -webkit-backdrop-filter: none; }
            .vy-gst .vy-gl-table thead tr::before { content: "Sort by"; font-size: 11px; color: var(--vy-on-grad); }
            .vy-gst .vy-gl-table thead th.vy-gl-c-nat, .vy-gst .vy-gl-table thead th.vy-gl-c-life, .vy-gst .vy-gl-table thead th.vy-gl-c-status,
            .vy-gst .vy-gl-table thead th.vy-gl-actcol { display: none; }
            /* each guest a wrapping row: the guest, its status and the menu on the first line, the facts on a quiet line beneath */
            .vy-gst .vy-gl-table tbody tr { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; padding: 14px 10px 14px 16px; border-top: 1px solid var(--vy-hairline); }
            .vy-gst .vy-gl-table tbody tr:first-child { border-top: none; }
            .vy-gst .vy-gl-table tbody td { display: block; padding: 0; border: 0; text-align: left; }
            .vy-gst .vy-gl-table tbody td.vy-gl-check { flex: none; order: 0; }
            .vy-gst .vy-gl-table tbody td.vy-gl-guestcell { flex: 1 1 0; min-width: 0; order: 1; }
            .vy-gst .vy-gl-table tbody td.vy-gl-c-status { flex: none; order: 2; }
            .vy-gst .vy-gl-table tbody td.vy-gl-actcol { flex: none; order: 3; width: auto; padding: 0; }
            /* a stacked row keeps its menu in view by itself — no sticky edge (the phone rule for wide tables) */
            .vy-gst .vy-gl-table tbody td.vy-gl-actcol { position: static; background: none; backdrop-filter: none; -webkit-backdrop-filter: none; box-shadow: none; }
            .vy-gst .vy-gl-table tbody tr::after { content: ""; order: 4; flex-basis: 100%; height: 0; margin-top: -8px; }
            .vy-gst .vy-gl-table tbody td.vy-gl-c-nat, .vy-gst .vy-gl-table tbody td.vy-gl-c-stays, .vy-gst .vy-gl-table tbody td.vy-gl-c-stay,
            .vy-gst .vy-gl-table tbody td.vy-gl-c-life { flex: none; order: 5; display: inline-flex; align-items: baseline; gap: 5px; font-size: 12.5px; }
            .vy-gst .vy-gl-table tbody td[data-label]::before { content: attr(data-label); font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-on-grad); }   /* on the row: --vy-muted is 4.48:1 there */
            .vy-gst .vy-gl-table tbody td.vy-gl-c-status::before { content: none; }
        }
        @container vy-gst-list (max-width: 520px) {
            /* a phone: the status joins the facts, so the name and its contact have the whole first line */
            .vy-gst .vy-gl-table tbody td.vy-gl-c-status { order: 5; }
            /* an empty Lifetime ("—") goes; one with a figure or a balance due stays */
            .vy-gst .vy-gl-table tbody td.vy-gl-c-life:not(:has(.vy-gl-fig, .vy-gl-due)) { display: none; }
        }

        .vy-gl-results { transition: opacity .18s ease; }
        .vy-gl-results.is-loading { opacity: .55; }
        .vy-gl-root.is-selecting .vy-gl-results { padding-bottom: 76px; }
        .vy-gl-card { position: relative; padding: 0; overflow: clip; }
        .vy-gl-progress { position: absolute; top: 0; left: 0; right: 0; z-index: 4; height: 2px; opacity: 0; background: linear-gradient(90deg, transparent, var(--vy-accent-3), transparent) no-repeat; background-size: 40% 100%; transition: opacity .15s ease; }
        .vy-gl-results.is-loading .vy-gl-progress { opacity: 1; animation: vy-gl-load 1s ease-in-out infinite; }
        @keyframes vy-gl-load { from { background-position: -40% 0; } to { background-position: 140% 0; } }
        .vy-gl-scroll { overflow-x: auto; }
        .vy-gl-table { width: 100%; min-width: 760px; border-collapse: separate; border-spacing: 0; }
        .vy-gl-table thead th { z-index: 2; padding: 13px 14px 11px; text-align: left; white-space: nowrap; font-size: 10px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); background: rgba(255, 251, 247, 0.97); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--vy-hairline); }
        .vy-gl-table th.is-num, .vy-gl-table td.is-num { text-align: right; }
        .vy-gl-table tbody td { padding: 12px 14px; border-top: 1px solid var(--vy-hairline); font-size: 13px; color: var(--vy-ink); vertical-align: middle; }
        .vy-gl-table tbody tr:first-child td { border-top: none; }
        .vy-gl-table tbody tr { transition: background .15s ease; }
        .vy-gl-table tbody tr:hover { background: rgba(255, 255, 255, 0.5); }
        .vy-gl-table tbody tr.is-selected { background: rgba(232, 112, 58, 0.09); }
        /* The header stays in view while the rows scroll — only where the table fits without scrolling sideways (a
           sideways scroller would become the sticky header's frame, and it would not stick to the page). ⚠️ "Fits" is
           MEASURED, not assumed from the window: the shared list script marks the frame `is-roomy` when the table's own
           width fits it. A width breakpoint alone clipped the Maintenance table's last two columns at 1180 px (🖥 995 px
           of table in an 877 px card, sideways scrolling off, the card clipping) — so sideways scrolling is the default
           and the sticky header is what has to earn its place. */
        @media (min-width: 1101px) {
            .vy-gl-scroll.is-roomy { overflow: visible; }
            .vy-gl-scroll.is-roomy .vy-gl-table thead th { position: sticky; top: 0; }
        }
        .vy-gl-sort { display: inline-flex; align-items: center; gap: 7px; color: inherit; border-radius: 5px; }
        .vy-gl-sort:hover, .vy-gl-sort.is-on { color: var(--vy-ink); }
        .vy-gl-arrow { width: 6px; height: 6px; margin-top: -3px; border-right: 1.7px solid currentColor; border-bottom: 1.7px solid currentColor; transform: rotate(45deg); opacity: 0; transition: opacity .15s ease, transform .15s ease; }
        .vy-gl-sort:hover .vy-gl-arrow, .vy-gl-sort:focus-visible .vy-gl-arrow { opacity: .45; }
        .vy-gl-sort.is-on .vy-gl-arrow { opacity: 1; color: var(--vy-accent-text); }
        .vy-gl-sort.is-asc .vy-gl-arrow { margin-top: 3px; transform: rotate(-135deg); }
        /* a right-aligned column keeps its label on the numbers' edge — the arrow goes on the left */
        .vy-gl-table th.is-num .vy-gl-sort { flex-direction: row-reverse; }
        .vy-gl-check { width: 42px; }
        .vy-gl-table th.vy-gl-check, .vy-gl-table td.vy-gl-check { padding-right: 0; }
        .vy-gl-check input { width: 16px; height: 16px; margin: 0; accent-color: var(--vy-accent); cursor: pointer; vertical-align: middle; }
        .vy-gl-guest { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .vy-gl-table td.vy-gl-guestcell { min-width: 250px; }
        .vy-gl-avatar { flex: none; width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; font-size: 12px; font-weight: 600; letter-spacing: .02em; color: var(--vy-ink); background: #F3C9AE; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6); }
        .vy-gl-avatar.tone-1 { background: #EBD3B5; }
        .vy-gl-avatar.tone-2 { background: #EFC6C0; }
        .vy-gl-avatar.tone-3 { background: #E4C3A7; }
        .vy-gl-avatar.tone-4 { background: #DCD4CD; }
        .vy-gl-avatar.tone-erased { background: transparent; color: var(--vy-on-grad); box-shadow: inset 0 0 0 1px rgba(120, 70, 40, 0.28); }
        .vy-gl-who { min-width: 0; }
        .vy-gl-nameline { display: flex; align-items: center; flex-wrap: wrap; gap: 5px 7px; }
        .vy-gl-name { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; color: var(--vy-ink); border-radius: 4px; overflow-wrap: anywhere; }
        .vy-gl-name:hover { color: var(--vy-accent-text); text-decoration: underline; text-underline-offset: 3px; text-decoration-thickness: 1px; }
        tr.is-erased .vy-gl-name { font-style: italic; font-weight: 500; color: var(--vy-on-grad); }
        .vy-gl-contact { margin-top: 3px; font-size: 11.5px; color: var(--vy-on-grad); overflow-wrap: anywhere; }
        .vy-gl-contact a:hover { color: var(--vy-accent-text); text-decoration: underline; text-underline-offset: 2px; }
        .vy-gl-fig { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-gl-none { color: var(--vy-on-grad); }
        .vy-gl-when { white-space: nowrap; }
        .vy-gl-when.is-live { font-weight: 600; color: var(--vy-accent-text); }
        .vy-gl-phone { white-space: nowrap; }
        .vy-gl-due { margin-top: 2px; font-size: 11px; font-weight: 600; color: var(--vy-accent-text); }
        .vy-gl-actcol { width: 56px; text-align: right; }
        .vy-gl-table td.vy-gl-actcol { padding-top: 6px; padding-bottom: 6px; }
        .vy-gl-more { width: 32px; height: 32px; padding: 0; border-radius: 50%; display: inline-grid; place-items: center; border: 1px solid transparent; background: transparent; color: var(--vy-on-grad); cursor: pointer; transition: background .15s ease, border-color .15s ease, color .15s ease; }
        .vy-gl-more:hover, .vy-gl-more[aria-expanded="true"] { background: #fff; border-color: var(--vy-glass-border); color: var(--vy-ink); box-shadow: var(--vy-shadow-sm); }
        .vy-gl-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px 18px; padding: 12px 16px; border-top: 1px solid var(--vy-hairline); }
        .vy-gl-summary { margin: 0; font-size: 12.5px; color: var(--vy-on-grad); font-variant-numeric: tabular-nums; }
        .vy-gl-summary strong { font-weight: 600; color: var(--vy-ink); }
        .vy-gl-summary:focus { outline: none; }
        .vy-gl-paging { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
        .vy-gl-rows-label, .vy-gl-pagepos { font-size: 12px; color: var(--vy-on-grad); font-variant-numeric: tabular-nums; }
        .vy-gl-sizes { display: inline-flex; gap: 2px; padding: 2px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); }
        .vy-gl-size { min-width: 34px; height: 26px; padding: 0 8px; display: inline-grid; place-items: center; border-radius: var(--vy-r-pill); font-size: 12px; font-weight: 600; color: var(--vy-on-grad); font-variant-numeric: tabular-nums; }
        .vy-gl-size:hover { color: var(--vy-ink); }
        .vy-gl-size.is-on { background: #fff; color: var(--vy-accent-text); box-shadow: var(--vy-shadow-sm); }
        .vy-gl-pages { display: inline-flex; align-items: center; gap: 8px; }
        .vy-gl-page { width: 30px; height: 30px; border-radius: 50%; display: inline-grid; place-items: center; background: rgba(255, 255, 255, 0.65); border: 1px solid var(--vy-glass-border); color: var(--vy-ink); transition: background .15s ease; }
        .vy-gl-page:hover { background: #fff; }
        .vy-gl-page.is-off { opacity: .4; }
        .vy-gl-empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 48px 24px; text-align: center; }
        .vy-gl-empty-ico { width: 52px; height: 52px; margin-bottom: 4px; border-radius: 50%; display: grid; place-items: center; background: rgba(232, 112, 58, 0.12); color: var(--vy-accent-text); }
        .vy-gl-empty-title { margin: 0; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vy-gl-empty-text { max-width: 460px; margin: 0 0 8px; font-size: 13px; line-height: 1.55; color: var(--vy-on-grad); }

        .vy-gl-menu { position: fixed; z-index: 38; width: 224px; display: flex; flex-direction: column; gap: 2px; padding: 6px; border-radius: 14px; background: rgba(255, 252, 248, 0.97); backdrop-filter: blur(24px) saturate(1.2); -webkit-backdrop-filter: blur(24px) saturate(1.2); border: 1px solid var(--vy-glass-border); box-shadow: 0 18px 44px rgba(90, 50, 25, 0.22), 0 2px 6px rgba(90, 50, 25, 0.08); animation: vy-gl-pop .14s ease-out; }
        .vy-gl-menu.is-up { transform: translateY(-100%); }
        @keyframes vy-gl-pop { from { opacity: 0; } to { opacity: 1; } }
        .vy-gl-menu-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px; border: none; border-radius: 9px; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: var(--vy-ink); text-align: left; cursor: pointer; }
        .vy-gl-menu-item svg { flex: none; color: var(--vy-on-grad); }
        .vy-gl-menu-item:hover, .vy-gl-menu-item:focus-visible { background: rgba(232, 112, 58, 0.09); }
        .vy-gl-menu-item.is-danger, .vy-gl-menu-item.is-danger svg { color: #8F3110; }
        .vy-gl-menu-item.is-danger:hover, .vy-gl-menu-item.is-danger:focus-visible { background: rgba(143, 49, 16, 0.07); }
        .vy-gl-menu-note { margin: 2px 0 0; padding: 8px 11px 5px; border-top: 1px solid var(--vy-hairline); font-size: 11.5px; line-height: 1.45; color: var(--vy-on-grad); }

        .vy-gl-bulk { position: fixed; z-index: 35; left: 0; right: 0; bottom: 24px; width: max-content; max-width: calc(100vw - 32px); margin: 0 auto; display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 6px; padding: 8px 8px 8px 16px; border-radius: 16px; background: rgba(38, 29, 24, 0.95); color: #fff; box-shadow: 0 18px 50px rgba(20, 10, 5, 0.35); }
        .vy-gl-bulk form { display: contents; }
        .vy-gl-bulk-count { margin-right: 2px; font-size: 13px; }
        .vy-gl-bulk-count strong { font-family: var(--vy-display); font-weight: 600; }
        .vy-gl-bulk-clear, .vy-gl-bulk-act { padding: 8px 12px; border: none; border-radius: 10px; font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: background .15s ease; }
        .vy-gl-bulk-clear { padding: 8px 9px; background: none; color: #F0A57A; }
        .vy-gl-bulk-act { background: rgba(255, 255, 255, 0.09); color: #fff; }
        .vy-gl-bulk-act:hover, .vy-gl-bulk-clear:hover { background: rgba(255, 255, 255, 0.17); }
        .vy-gl-bulk-act.is-danger { color: #F6C6B4; }
        .vy-gl-bulk-sep { width: 1px; height: 20px; margin: 0 4px; background: rgba(255, 255, 255, 0.18); }
        .vy-gl-bulk-act:focus-visible, .vy-gl-bulk-clear:focus-visible { outline: 2px solid #F0A57A; outline-offset: 2px; }

        .vy-gl-head-text { flex: 1; min-width: 0; }
        .vy-gl-head-text .vy-modal-title { overflow-wrap: anywhere; }
        .vy-gl-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 14px; }
        .vy-gl-code { text-transform: uppercase; }
        .vy-gl-textarea { min-height: 112px; resize: vertical; }
        .vy-gl-switches { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .vy-gl-switches > .vy-gl-switch:last-child:nth-child(odd) { grid-column: 1 / -1; }
        .vy-gl-switch { position: relative; display: flex; align-items: flex-start; gap: 11px; padding: 11px 12px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.62); border: 1px solid var(--vy-hairline); cursor: pointer; transition: border-color .15s ease; }
        .vy-gl-switch:hover { border-color: var(--vy-accent-border); }
        .vy-gl-switch input { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; }
        .vy-gl-track { position: relative; flex: none; width: 34px; height: 20px; margin-top: 1px; border-radius: 999px; background: rgb(150, 128, 114); transition: background .18s ease; }
        .vy-gl-track::after { content: ""; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(60, 30, 10, 0.3); transition: transform .18s ease; }
        .vy-gl-switch input:checked + .vy-gl-track { background: var(--vy-accent); }
        .vy-gl-switch input:checked + .vy-gl-track::after { transform: translateX(14px); }
        .vy-gl-switch input:focus-visible + .vy-gl-track { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-gl-switch-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .vy-gl-switch-text strong { font-size: 13px; font-weight: 600; color: var(--vy-ink); }
        .vy-gl-switch-text span { font-size: 11.5px; line-height: 1.4; color: var(--vy-muted); }
        .vy-gl-note { margin: 14px 0 0; font-size: 12px; line-height: 1.5; color: var(--vy-muted); }
        .vy-gl-errors { margin: 0 0 14px; padding: 10px 12px; border-radius: var(--vy-r-sm); background: rgba(143, 49, 16, 0.07); border: 1px solid rgba(143, 49, 16, 0.2); font-size: 12.5px; line-height: 1.5; color: #8F3110; }
        .vy-gl-link { color: var(--vy-accent-text); text-decoration: underline; text-underline-offset: 2px; }
        .vy-gl-fieldset { min-width: 0; margin: 0 0 15px; padding: 0; border: none; }
        .vy-gl-fieldset legend { padding: 0; }
        .vy-gl-prefs { display: flex; flex-wrap: wrap; gap: 6px; }
        .vy-gl-pref { position: relative; cursor: pointer; }
        .vy-gl-pref input { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; }
        .vy-gl-pref span { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 12px; border-radius: var(--vy-r-pill); border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.62); font-size: 12.5px; font-weight: 600; color: var(--vy-on-grad); transition: background .15s ease, border-color .15s ease, color .15s ease; }
        .vy-gl-pref:hover span { border-color: var(--vy-accent-border); }
        .vy-gl-pref input:checked + span { background: rgba(232, 112, 58, 0.12); border-color: var(--vy-accent-border); color: var(--vy-accent-text); }
        .vy-gl-pref input:checked + span::before { content: "✓"; font-size: 11px; }
        .vy-gl-pref input:focus-visible + span { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        /* the row menu, shared: a line between reading and changing, and a form whose button is a menu item */
        .vy-gl-menu-sep { display: block; height: 1px; margin: 4px 6px; background: var(--vy-hairline); }
        .vy-gl-menu form { display: contents; }

        /* ── Maintenance (2026-09-29) — the board rebuilt on the SAME list system as Guests: the toolbar, chips, table,
           footer, row menu and bulk bar above are shared, never copied (partials/operator-list-script is the one script).
           What follows is only what a work order needs on top: the rail, the priority marker, the technician picker and
           the drawer on the right. ⚠️ Every display is in a class — never inline beside x-show. ─────────────────── */
        .vy-mnt .vy-dash-kpi, .vy-units .vy-dash-kpi { padding: 15px 18px; }
        .vy-mnt .vy-dk-val, .vy-units .vy-dk-val { font-size: 26px; }
        .vy-mt-bar .vy-gl-tools, .vy-ul-bar .vy-gl-tools { flex-wrap: wrap; justify-content: flex-end; }
        /* a link and a button side by side, one height (🖥 the links inherited 1.45 and stood 38.8 px beside 35.5 px) */
        .vy-mt-bar .vy-gl-tools a.vy-btn, .vy-ul-bar .vy-gl-tools a.vy-btn { line-height: normal; }
        .vy-mt-cols { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 22px; align-items: start; }
        .vy-mt-rail { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
        .vy-mt-table { min-width: 940px; }
        /* ⚠️ 2026-10-05 (his "text is not visible completely") — a SIZE CONTAINER, as Guests: 🔬 the 945 px table ran 213 px past its 734 px
           card at a 1,024 px window (589 px at 390), the right columns behind a hidden scrollbar. A narrow card now stacks each work
           order — its title and status, then the facts, each named; the sortable headers become a "Sort by" row. */
        .vy-mnt .vy-gl-card { container: vy-mt-list / inline-size; }
        .vy-mnt .vy-mt-table { min-width: 0; }
        @container vy-mt-list (max-width: 980px) {
            .vy-mnt .vy-gl-table, .vy-mnt .vy-gl-table tbody, .vy-mnt .vy-gl-table thead { display: block; }
            .vy-mnt .vy-gl-table thead tr { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 20px; padding: 11px 16px; }
            .vy-mnt .vy-gl-table thead tr::before { content: "Sort by"; font-size: 11px; color: var(--vy-on-grad); }
            .vy-mnt .vy-gl-table thead th { padding: 0; border: 0; background: none; backdrop-filter: none; -webkit-backdrop-filter: none; }
            .vy-mnt .vy-gl-table thead th:not([aria-sort]):not(.vy-gl-check) { display: none; }
            .vy-mnt .vy-gl-table tbody tr { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; padding: 14px 10px 14px 16px; border-top: 1px solid var(--vy-hairline); }
            .vy-mnt .vy-gl-table tbody tr:first-child { border-top: none; }
            .vy-mnt .vy-gl-table tbody td { display: block; padding: 0; border: 0; text-align: left; }
            .vy-mnt .vy-gl-table tbody td.vy-gl-check { flex: none; order: 0; }
            .vy-mnt .vy-gl-table tbody td.vy-mt-ordercell { flex: 1 1 0; min-width: 0; order: 1; }
            .vy-mnt .vy-gl-table tbody td.vy-mt-c-status { flex: none; order: 2; }
            .vy-mnt .vy-gl-table tbody td.vy-gl-actcol { flex: none; order: 3; width: auto; position: static; background: none; backdrop-filter: none; -webkit-backdrop-filter: none; box-shadow: none; }
            .vy-mnt .vy-gl-table tbody tr::after { content: ""; order: 4; flex-basis: 100%; height: 0; margin-top: -8px; }
            .vy-mnt .vy-gl-table tbody td[data-label]:not(.vy-mt-c-status) { flex: none; order: 5; display: inline-flex; align-items: baseline; gap: 5px; font-size: 12.5px; }
            .vy-mnt .vy-gl-table tbody td[data-label]:not(.vy-mt-c-status)::before { content: attr(data-label); font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-on-grad); }
        }
        .vy-mt-table td.vy-mt-ordercell { min-width: 240px; }
        .vy-mt-unit { font-family: var(--vy-display); font-weight: 600; white-space: nowrap; }
        /* priority: the colour is on the marker, the text stays readable (AA) at every level */
        .vy-mt-prio { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 600; color: var(--vy-on-grad); white-space: nowrap; }
        .vy-mt-prio::before { content: ""; flex: none; width: 9px; height: 9px; border-radius: 3px; background: rgb(150, 128, 114); }
        .vy-mt-prio.is-urgent { color: #8F3110; }
        .vy-mt-prio.is-urgent::before { background: #C2410C; box-shadow: 0 0 0 3px rgba(194, 65, 12, 0.16); }
        .vy-mt-prio.is-high { color: var(--vy-accent-text); }
        .vy-mt-prio.is-high::before { background: #E8703A; }
        .vy-mt-prio.is-low::before { background: transparent; box-shadow: inset 0 0 0 1.5px rgb(150, 128, 114); }
        .vy-mt-due { font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-mt-tech { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
        .vy-mt-tech .vy-gl-avatar { width: 26px; height: 26px; font-size: 10px; }
        .vy-mt-window { flex-basis: 100%; margin: 0; font-size: 12px; line-height: 1.5; color: var(--vy-on-grad); }

        .vy-mt-oos { padding: 20px; }
        .vy-mt-oos-label { color: rgba(255, 255, 255, 0.7); }
        .vy-mt-oos-count { margin: 6px 0 0; font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: rgba(255, 255, 255, 0.75); }
        .vy-mt-oos-n { margin-right: 2px; font-size: 30px; font-weight: 700; color: #fff; }
        .vy-mt-oos-units { margin: 4px 0 0; font-size: 12.5px; color: rgba(255, 255, 255, 0.72); overflow-wrap: anywhere; }
        .vy-mt-oos-hold { display: flex; flex-direction: column; gap: 2px; margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255, 255, 255, 0.14); }
        .vy-mt-oos-hold strong { font-weight: 700; color: #F6C6B4; }
        .vy-mt-oos-hold span { font-size: 11.5px; color: rgba(255, 255, 255, 0.66); }
        .vy-mt-panel .vy-card-title { margin-bottom: 6px; }
        .vy-mt-panel-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; border-top: 1px solid var(--vy-hairline); }
        .vy-mt-panel-row:first-of-type { border-top: none; }
        .vy-mt-panel-name { display: block; font-size: 13px; color: var(--vy-ink); white-space: normal; }
        .vy-mt-panel-sub { display: block; margin-top: 1px; font-size: 11.5px; color: var(--vy-on-grad); white-space: normal; }
        .vy-mt-panel-empty { margin: 0; padding: 6px 0 2px; font-size: 12.5px; color: var(--vy-on-grad); }

        /* the dialogs */
        /* five categories across: icon over label, centred, and a label is never split mid-word (🖥 "Plumbin|g" at 82 px) */
        .vy-mt-cats .vy-choice-row { grid-auto-flow: row; grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .vy-mt-cats .vy-choice-face, .vy-ul-kinds .vy-choice-face { align-items: center; text-align: center; padding: 11px 6px; }
        .vy-mt-cats .vy-choice-text, .vy-mnt .vy-choices--segmented .vy-choice-text, .vy-ul-kinds .vy-choice-text { overflow-wrap: normal; hyphens: manual; }
        .vy-mnt .vy-gl-textarea, .vy-units .vy-gl-textarea { min-height: 84px; }
        .vy-mt-warn { margin: 8px 0 0; font-size: 12px; line-height: 1.45; color: #8F3110; }
        .vy-mt-first { margin-top: 0; }
        .vy-mt-sale { margin-top: 14px; }
        .vy-mt-facts-list { display: grid; gap: 8px; margin: 0; padding: 0 0 0 18px; font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vy-mt-people { display: flex; flex-direction: column; gap: 6px; max-height: min(340px, 44vh); overflow-y: auto; padding: 2px; }
        .vy-mt-person { position: relative; display: block; cursor: pointer; }
        .vy-mt-person input { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; }
        .vy-mt-person-face { display: flex; align-items: center; gap: 11px; padding: 9px 12px; border-radius: var(--vy-r-md); border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.62); transition: border-color .15s ease, background .15s ease; }
        .vy-mt-person-face::after { content: ""; flex: none; width: 18px; height: 18px; border-radius: 50%; box-shadow: inset 0 0 0 1.5px rgba(120, 70, 40, 0.3); }
        .vy-mt-person:hover .vy-mt-person-face { border-color: var(--vy-accent-border); }
        .vy-mt-person input:checked + .vy-mt-person-face { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.1); }
        .vy-mt-person input:checked + .vy-mt-person-face::after { box-shadow: none; background: var(--vy-accent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center / 12px no-repeat; }
        .vy-mt-person input:focus-visible + .vy-mt-person-face { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-mt-person .vy-gl-avatar { width: 30px; height: 30px; font-size: 11px; }
        .vy-mt-person-name { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 500; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vy-mt-person-now { flex: none; padding: 2px 8px; border-radius: 999px; background: rgba(120, 70, 40, 0.09); font-size: 11px; font-weight: 600; color: var(--vy-on-grad); }
        /* the work order page's two dialogs: the panel is the card and pads the form — a second 24 px inside it pushed the
           Parts cost field past the edge (🖥 484 px of content in a 478 px panel) */
        .vy-mt-dform { padding: 0; }

        /* the drawer — a dialog on the RIGHT: the shared backdrop, aligned to the end, full height */
        .vy-modal-backdrop.vy-drawer-backdrop { padding: 0; place-items: stretch; justify-items: end; }
        .vy-modal.vy-drawer { display: flex; flex-direction: column; width: min(460px, 100vw); max-width: none; height: 100%; max-height: none; overflow: hidden; border-right: none; border-radius: var(--vy-r-lg) 0 0 var(--vy-r-lg); }
        .vy-drawer-in { flex: 1; min-height: 0; display: flex; flex-direction: column; }
        .vy-drawer-in .vy-modal-head { flex: none; }
        .vy-drawer-in .vy-modal-body { flex: 1; min-height: 0; overflow-y: auto; }
        .vy-drawer-move { transition: transform .24s cubic-bezier(.2, .8, .2, 1), opacity .2s ease; }
        .vy-drawer-off { transform: translateX(32px); opacity: 0; }
        .vy-mt-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin: 0 0 16px; padding: 0; list-style: none; }
        .vy-mt-step { display: flex; flex-direction: column; gap: 6px; font-size: 11px; font-weight: 600; text-align: center; color: var(--vy-on-grad); }
        .vy-mt-step-bar { height: 4px; border-radius: 2px; background: var(--vy-hairline); }
        .vy-mt-step.is-done { color: var(--vy-ink); }
        .vy-mt-step.is-done .vy-mt-step-bar { background: var(--vy-grad); }
        .vy-mt-tags { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; margin-bottom: 14px; }
        .vy-mt-facts { margin: 0 0 14px; border-top: 1px solid var(--vy-hairline); }
        .vy-mt-facts > div { display: grid; grid-template-columns: 116px minmax(0, 1fr); gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-mt-facts dt { font-size: 12px; color: var(--vy-on-grad); }
        .vy-mt-facts dd { margin: 0; font-size: 13px; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vy-mt-facts dd.is-breached { font-weight: 600; color: var(--vy-accent-text); }
        .vy-mt-facts dd.is-soon { font-weight: 600; color: var(--vy-amber-text); }
        .vy-mt-muted { font-size: 12px; color: var(--vy-on-grad); }
        .vy-mt-desc { margin: 0 0 14px; font-size: 13px; line-height: 1.55; color: var(--vy-ink); white-space: pre-line; overflow-wrap: anywhere; }
        .vy-mt-part { margin: 0 0 14px; padding: 9px 12px; border-radius: var(--vy-r-sm); background: rgba(51, 160, 172, 0.1); font-size: 12.5px; color: var(--vy-ink); }
        .vy-mt-h { margin: 4px 0 8px; font-size: 12px; font-weight: 700; color: var(--vy-ink); }
        .vy-mt-photos { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .vy-mt-photo { width: 76px; height: 76px; object-fit: cover; border-radius: 10px; border: 1px solid var(--vy-hairline); }
        .vy-mt-activity { display: grid; gap: 5px; margin: 0; padding: 0; list-style: none; font-size: 12.5px; color: var(--vy-ink); }
        .vy-mt-drawer-foot { flex: none; display: flex; flex-direction: column; gap: 8px; padding: 14px 24px 20px; border-top: 1px solid var(--vy-hairline); }
        .vy-mt-drawer-foot .vy-btn { justify-content: center; }
        .vy-mt-drawer-acts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .vy-mt-drawer-acts form { display: contents; }
        .vy-mt-drawer-acts .vy-mt-wide { grid-column: 1 / -1; }
        .vy-mt-danger { color: #8F3110; }
        .vy-mt-photo-form { display: contents; }
        .vy-mt-photo-btn { position: relative; overflow: hidden; }
        .vy-mt-file { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .vy-mt-photo-btn:focus-within { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        @media (prefers-reduced-motion: reduce) {
            .vy-drawer-move { transition: opacity .12s ease; }
            .vy-drawer-off { transform: none; }
        }
        /* The rail beside the table only where both fit; below that it follows the table, three across, then one. */
        @media (max-width: 1599px) {
            .vy-mt-cols { grid-template-columns: minmax(0, 1fr); }
            .vy-mt-rail { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: start; }
        }
        @media (max-width: 1100px) {
            /* five figures two across: the fifth takes the whole row rather than sitting alone in half of it */
            .vy-mnt .vy-dash-kpis--5 > :last-child:nth-child(odd), .vy-units .vy-dash-kpis--5 > :last-child:nth-child(odd) { grid-column: 1 / -1; }
        }
        @media (max-width: 900px) {
            .vy-mt-rail { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 560px) {
            /* each button as wide as its words (🖥 "Costs report" wrapped onto two lines at an equal third of 390 px) */
            .vy-mt-bar .vy-gl-tools .vy-btn, .vy-ul-bar .vy-gl-tools .vy-btn { flex: 1 1 auto; white-space: nowrap; }
            /* these dialogs pad their own head, body and foot, so on a phone the panel adds almost nothing around them
               (🖥 its 26 px on top of theirs left 250 px of a 350 px dialog, and "Normal" broke mid-word) */
            .vy-mnt .vy-modal-backdrop > .vy-modal, .vy-units .vy-modal-backdrop > .vy-modal { padding: 6px; }
            .vy-mt-drawer-acts { grid-template-columns: minmax(0, 1fr); }
            .vy-mt-cats .vy-choice-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .vy-modal.vy-drawer { width: 100vw; border-left: none; border-radius: 0; }
            .vy-mt-window { text-align: center; }
        }

        /* ── Units (2026-09-30) — rebuilt on the SAME list system as Guests and Maintenance (shared `.vy-gl-*` and the one
           list script); what follows is only what units need on top: the setup guide, the unit cell, room types as cards,
           the Pause tag and the dialogs' bits. ⚠️ Every display is in a class — never inline beside x-show. ─────────── */
        .vy-ul-bar .vy-btn:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; transform: none; }
        .vy-ul-setup { margin: 22px 0 0; padding: 22px 24px; }
        .vy-ul-setup-title { margin: 0; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink); }
        .vy-ul-setup-sub { margin: 4px 0 16px; font-size: 13px; color: var(--vy-on-grad); }
        .vy-ul-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 0; padding: 0; list-style: none; }
        .vy-ul-step { display: flex; flex-direction: column; align-items: flex-start; gap: 10px; padding: 14px 16px; border-radius: var(--vy-r-md); border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.55); }
        .vy-ul-step.is-current { border-color: var(--vy-accent-border); background: #fff; box-shadow: var(--vy-shadow-sm); }
        .vy-ul-step-n { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font-family: var(--vy-display); font-size: 13px; font-weight: 700; color: var(--vy-on-grad); background: rgba(120, 70, 40, 0.08); }
        .vy-ul-step.is-current .vy-ul-step-n { color: #fff; background: var(--vy-grad); }
        .vy-ul-step.is-done .vy-ul-step-n { color: #fff; background: rgb(52, 140, 90); }
        .vy-ul-step-text { display: flex; flex-direction: column; gap: 3px; }
        .vy-ul-step-text strong { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-ul-step-text span { font-size: 12px; line-height: 1.45; color: var(--vy-on-grad); }
        .vy-ul-step .vy-btn { margin-top: auto; }

        /* the two sections — room types, then units — each named, so the page reads top-down: what is sold, then the rooms
           (⚠️ 2026-10-05, his "It is so confusing": the room types sat under the whole table, with no section name) */
        .vy-ul-section { margin-top: 26px; }
        .vy-ul-sechead { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 10px 16px; margin-bottom: 12px; }
        .vy-ul-sechead-text { min-width: 0; }
        .vy-ul-sectitle { margin: 0; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); }
        .vy-ul-secsub { margin: 3px 0 0; font-size: 12.5px; color: var(--vy-on-grad); }

        .vy-ul-table td.vy-ul-unitcell { min-width: 220px; }
        .vy-ul-unit { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .vy-ul-thumb { flex: none; width: 46px; height: 34px; border-radius: 8px; object-fit: cover; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4); }
        /* no photo: a quiet tile with the kind's mark (🖥 the old orange block on every row read as an image that failed) */
        .vy-ul-thumb.is-empty { display: grid; place-items: center; background: rgba(120, 70, 40, 0.07); color: var(--vy-on-grad); box-shadow: none; }
        .vy-ul-who { min-width: 0; }
        .vy-ul-code { font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-ink); }
        .vy-ul-table .vy-gl-contact > span + span::before { content: "·"; margin: 0 6px; }
        /* a long unit name wraps within its own line rather than widening the column (🖥 it pushed "Garden Studio" and
           "Operator-owned" onto two lines at 1,280 px) */
        .vy-ul-table .vy-gl-contact { max-width: 240px; }
        .vy-ul-table .vy-gl-none { white-space: nowrap; }
        .vy-ul-table tr.is-paused .vy-ul-thumb { opacity: .45; }
        .vy-tag.tone-paused { background: rgba(120, 70, 40, 0.1); color: var(--vy-on-grad); }
        .vy-ul-type { display: block; color: var(--vy-ink); }
        .vy-ul-kind { display: block; margin-top: 2px; font-size: 11.5px; color: var(--vy-on-grad); }
        .vy-ul-table .vy-gl-actcol { width: 88px; }
        .vy-ul-rowacts { display: inline-flex; align-items: center; justify-content: flex-end; gap: 2px; }
        /* Edit, in plain sight on every row and room type (📖 before, only inside the ⋯ menu) — the row menu's own look */
        .vy-ul-iconbtn { width: 32px; height: 32px; padding: 0; border-radius: 50%; display: inline-grid; place-items: center; border: 1px solid transparent; background: transparent; color: var(--vy-on-grad); cursor: pointer; transition: background .15s ease, border-color .15s ease, color .15s ease; }
        .vy-ul-iconbtn:hover { background: #fff; border-color: var(--vy-glass-border); color: var(--vy-ink); box-shadow: var(--vy-shadow-sm); }
        .vy-ul-iconbtn.is-danger:hover { color: #8F3110; }
        .vy-ul-iconbtn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        /* ⚠️ 2026-10-05 (his "text is not visible completely") — a SIZE CONTAINER, as Guests and Maintenance: 🔬 the 900 px table ran
           168 px past its 734 px card at a 1,024 px window. A narrow card now stacks each unit — its code and actions, then the
           facts, each named; the sortable headers become a "Sort by" row. */
        .vy-units .vy-gl-card { container: vy-ul-list / inline-size; }
        .vy-units .vy-ul-table { min-width: 0; }
        @container vy-ul-list (max-width: 860px) {
            .vy-units .vy-gl-table, .vy-units .vy-gl-table tbody, .vy-units .vy-gl-table thead { display: block; }
            .vy-units .vy-gl-table thead tr { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 20px; padding: 11px 16px; }
            .vy-units .vy-gl-table thead tr::before { content: "Sort by"; font-size: 11px; color: var(--vy-on-grad); }
            .vy-units .vy-gl-table thead th { padding: 0; border: 0; background: none; backdrop-filter: none; -webkit-backdrop-filter: none; }
            .vy-units .vy-gl-table thead th:not([aria-sort]):not(.vy-gl-check) { display: none; }
            .vy-units .vy-gl-table tbody tr { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; padding: 14px 10px 14px 16px; border-top: 1px solid var(--vy-hairline); }
            .vy-units .vy-gl-table tbody tr:first-child { border-top: none; }
            .vy-units .vy-gl-table tbody td { display: block; padding: 0; border: 0; text-align: left; }
            .vy-units .vy-gl-table tbody td.vy-gl-check { flex: none; order: 0; }
            .vy-units .vy-gl-table tbody td.vy-ul-unitcell { flex: 1 1 0; min-width: 0; order: 1; }
            .vy-units .vy-gl-table tbody td.vy-gl-actcol { flex: none; order: 2; width: auto; position: static; background: none; backdrop-filter: none; -webkit-backdrop-filter: none; box-shadow: none; }
            .vy-units .vy-gl-table tbody tr::after { content: ""; order: 3; flex-basis: 100%; height: 0; margin-top: -8px; }
            .vy-units .vy-gl-table tbody td[data-label] { flex: none; order: 4; display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; }
            .vy-units .vy-gl-table tbody td[data-label]::before { content: attr(data-label); font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-on-grad); }
            .vy-units .vy-ul-type, .vy-units .vy-ul-kind { display: inline; margin: 0; font-size: 12.5px; }
            .vy-units .vy-ul-kind::before { content: "· "; }
        }

        /* room types — compact cards: what it is, what keeps it from being booked, and its actions (📖 before, a 128 px banner
           and every photo control on each card) */
        .vy-ul-types-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .vy-card.vy-ul-rt { display: flex; flex-direction: column; gap: 8px; padding: 16px 16px 14px; }
        .vy-ul-rt-top { display: flex; align-items: flex-start; gap: 12px; }
        .vy-ul-rt-thumb { flex: none; width: 52px; height: 52px; border-radius: 12px; object-fit: cover; }
        .vy-ul-rt-thumb.is-empty { display: grid; place-items: center; background: rgba(232, 112, 58, 0.1); color: var(--vy-accent-text); }
        .vy-ul-rt-id { flex: 1; min-width: 0; }
        .vy-ul-rt-name { margin: 2px 0 0; font-family: var(--vy-display); font-size: 15.5px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vy-ul-rt-meta { margin: 3px 0 0; font-size: 12.5px; line-height: 1.45; color: var(--vy-on-grad); }
        .vy-ul-rt-acts { display: flex; gap: 2px; margin: -4px -6px 0 0; }
        .vy-ul-rt-count { margin: 4px 0 0; font-size: 12.5px; color: var(--vy-on-grad); }
        .vy-ul-rt-count strong { font-weight: 600; color: var(--vy-ink); }
        .vy-ul-rt-state { display: flex; align-items: center; flex-wrap: wrap; gap: 4px 7px; margin: 0; font-size: 12.5px; font-weight: 600; }
        .vy-ul-rt-state.is-ok { color: rgb(38, 110, 70); }
        .vy-ul-rt-state.is-warn { color: var(--vy-amber-text); }
        .vy-ul-rt-dot { flex: none; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .vy-ul-rt-state a { margin-left: 4px; color: var(--vy-accent-text); text-decoration: underline; text-underline-offset: 2px; }
        .vy-ul-rt-foot { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--vy-hairline); }
        .vy-ul-rt-btn { padding: 7px 12px; font-size: 12.5px; }
        .vy-ul-rt-n { margin-left: 2px; padding: 0 6px; border-radius: var(--vy-r-pill); background: rgba(120, 70, 40, 0.08); font-size: 11px; font-variant-numeric: tabular-nums; }
        .vy-pm-file:focus-visible + .vy-pm-add { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* the photos dialog — the controls always shown (they appeared on hover only, so a phone never saw them) */
        .vy-ul-photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 14px; }
        .vy-ul-photo { position: relative; margin: 0; border-radius: var(--vy-r-md); overflow: hidden; border: 1px solid var(--vy-glass-border); background: #fff; }
        .vy-ul-photo.is-cover { border-color: var(--vy-accent); }
        .vy-ul-photo img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; }
        .vy-ul-photo-ctrls { display: flex; justify-content: center; gap: 4px; padding: 6px; }
        .vy-ul-photo-ctrls form { margin: 0; }
        .vy-ul-photo-ctrls .vy-pm-btn { border: 1px solid var(--vy-glass-border); }
        .vy-ul-rt-upload { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
        .vy-ul-rt-upload .vy-gl-note { margin: 0; }

        /* the dialogs' own bits */
        .vy-ul-addsto { margin: 0 0 14px; font-size: 13px; color: var(--vy-on-grad); }
        .vy-ul-addsto strong { font-weight: 600; color: var(--vy-ink); }
        .vy-gl-note + .vy-ul-codesfield { margin-top: 16px; }
        .vy-ul-codes-note { margin: 7px 0 0; font-size: 12px; line-height: 1.5; color: var(--vy-on-grad); }
        .vy-ul-codes-note.is-ok { color: rgb(38, 110, 70); }
        .vy-ul-codes-note.is-err { color: #8F3110; }
        .vy-ul-kindlock { display: flex; flex-direction: column; margin: 0 0 15px; font-size: 13px; color: var(--vy-ink); }
        .vy-ul-why p { margin: 0 0 8px; font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vy-ul-why .vy-gl-note { margin: 0; }
        .vy-ul-paused { margin: 0 0 14px; padding: 10px 12px; border-radius: var(--vy-r-sm); background: rgba(224, 166, 58, 0.14); font-size: 12.5px; line-height: 1.5; color: var(--vy-amber-text); }
        .vy-modal-foot .vy-ul-foot-del { margin-right: auto; color: #8F3110; }

        .vy-ul-kinds .vy-choice-row { grid-auto-flow: row; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .vy-ul-narrow { max-width: 150px; }
        .vy-ul-last { margin-bottom: 0; }
        .vy-ul-first { margin-top: 0; }
        .vy-ul-facts { display: grid; gap: 8px; margin: 0; padding: 0 0 0 18px; font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vy-ul-revealed { margin-top: 12px; padding: 10px 12px; border-radius: var(--vy-r-sm); background: rgba(194, 65, 12, 0.08); font-size: 12.5px; line-height: 1.6; color: var(--vy-ink); }
        .vy-ul-reveal { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin: 0 24px 20px; padding-top: 14px; border-top: 1px solid var(--vy-hairline); }
        .vy-ul-reveal .vy-gl-note { margin: 0; }
        .vy-ul-price .vy-btn--ghost { padding: 9px 16px; }
        @media (max-width: 900px) {
            .vy-ul-steps { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 560px) {
            .vy-ul-kinds .vy-choice-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vy-ul-reveal { margin: 0 18px 16px; }
        }

        /* ── Security (2026-10-05, his "this page design is not good") — one header, then two named groups of equal cards
           (Signing in · Devices and access) and your data. 🖥 Before: a 560 px column beside full-width cards, the right half
           of the page empty, 101 inline styles. The shared two-factor and passkey cards keep their own inner styles (the owner
           page renders them too); here they are grid items like the rest. ⚠️ Every display is in a class. ─────────────── */
        .vy-sec-hero { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 18px 28px; padding: 22px 24px; }
        .vy-sec-hero-id { display: flex; align-items: center; gap: 16px; flex: 1 1 300px; min-width: 0; }
        .vy-sec-shield { flex: none; width: 54px; height: 54px; border-radius: 17px; display: grid; place-items: center; color: #fff; }
        .vy-sec-shield.is-ok { background: linear-gradient(140deg, #3F8F5E, #23683F); box-shadow: 0 10px 22px rgba(35, 104, 63, 0.3); }
        .vy-sec-shield.is-warn { background: linear-gradient(140deg, #E8703A, #C2410C); box-shadow: 0 10px 22px rgba(194, 65, 12, 0.32); }
        .vy-sec-hero-text { min-width: 0; }
        .vy-sec-state { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 7px; padding: 3px 11px 3px 9px; border-radius: var(--vy-r-pill); font-size: 11.5px; font-weight: 600; }
        /* (the inks measured against their own washes: green 5.75, amber 5.39 — the badge's first colours were under AA) */
        .vy-sec-state.is-ok { background: rgba(26, 127, 75, 0.1); color: rgb(18, 99, 58); }
        .vy-sec-state.is-warn { background: rgba(178, 106, 8, 0.12); color: rgb(133, 76, 4); }
        .vy-sec-state-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .vy-sec-title { margin: 0; font-family: var(--vy-display); font-size: 20px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); }
        .vy-sec-sub { margin: 3px 0 0; font-size: 12.5px; line-height: 1.45; color: var(--vy-on-grad); }
        .vy-sec-tiles { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; flex: 1.4 1 420px; }
        .vy-sec-tile { display: flex; flex-direction: column; align-items: flex-start; padding: 13px 14px; border-radius: 15px; background: rgba(255, 255, 255, 0.55); border: 1px solid var(--vy-hairline); color: inherit; text-decoration: none; transition: background .15s ease, box-shadow .15s ease, transform .15s ease; }
        .vy-sec-tile:hover { background: rgba(255, 255, 255, 0.85); box-shadow: var(--vy-shadow-sm); transform: translateY(-2px); }
        .vy-sec-tile:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-sec-tile-ico { width: 28px; height: 28px; margin-bottom: 9px; border-radius: 9px; display: grid; place-items: center; background: rgba(217, 102, 46, 0.12); color: var(--vy-accent-text); }
        .vy-sec-tile-n { font-family: var(--vy-display); font-size: 22px; font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums; color: var(--vy-ink); }
        .vy-sec-tile-l { margin-top: 4px; font-size: 11.5px; color: var(--vy-on-grad); }
        .vy-sec-group { margin-top: 26px; }
        .vy-sec-group-title { margin: 0 0 12px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); }
        .vy-sec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px; align-items: stretch; }
        .vy-sec-grid > .vy-card { min-width: 0; margin: 0; }
        /* the tall password card beside two-factor and passkeys, the sign-in accounts across below — no stretched empty card */
        .vy-sec-grid--signin > #sec-password { grid-row: span 2; }
        .vy-sec-grid--signin > #sec-connections { grid-column: 1 / -1; }
        .vy-sec-card { display: flex; flex-direction: column; }
        .vy-sec-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .vy-sec-card-title { margin: 0 0 4px; font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); }
        .vy-sec-card-sub { margin: 0 0 12px; font-size: 12.5px; line-height: 1.5; color: var(--vy-on-grad); }
        /* a label that wraps (🖥 "Confirm new password" at 1,024 px) keeps the two boxes level */
        .vy-sec-form .vy-gl-fields { margin-bottom: 0; align-items: end; }
        .vy-sec-hint { margin: 0 0 14px; }
        .vy-sec-list { margin: 0; padding: 0; list-style: none; }
        .vy-sec-row, .vy-sec-empty { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-top: 1px solid var(--vy-hairline); }
        .vy-sec-empty { flex-direction: column; align-items: flex-start; gap: 2px; }
        .vy-sec-row-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .vy-sec-row-title { display: inline-flex; align-items: center; flex-wrap: wrap; gap: 6px; font-size: 13.5px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vy-sec-empty .vy-sec-row-title { font-weight: 500; }
        .vy-sec-row-meta { font-size: 12px; line-height: 1.45; color: var(--vy-on-grad); overflow-wrap: anywhere; }
        .vy-sec-mono { font-variant-numeric: tabular-nums; letter-spacing: .01em; }
        .vy-sec-this { font-size: 10px; }
        .vy-sec-small { padding: 7px 13px; font-size: 12.5px; white-space: nowrap; }
        .vy-sec-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; padding-top: 12px; }
        .vy-sec-why { max-width: 260px; text-align: right; }
        .vy-sec-why p { margin: 6px 0 0; font-size: 11.5px; line-height: 1.45; color: var(--vy-on-grad); }
        .vy-sec-inline { margin: 0 0 10px; font-size: 12.5px; font-weight: 500; }
        .vy-sec-inline.is-ok { color: rgb(34, 110, 68); }
        .vy-sec-inline.is-err { color: #8F3110; }
        .vy-sec-data { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px 20px; margin-top: 26px; }
        .vy-sec-data .vy-sec-card-sub { margin-bottom: 0; }
        .vy-sec-dlg { max-width: 460px; }
        .vy-sec-dlg .vy-modal-body > .vy-gl-note:first-child { margin-top: 0; }
        @media (max-width: 900px) {
            .vy-sec-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); flex-basis: 100%; }
        }
        @media (max-width: 560px) {
            .vy-sec-hero { padding: 18px; }
            .vy-sec-grid { grid-template-columns: minmax(0, 1fr); }
            .vy-sec-row { flex-wrap: wrap; }
            .vy-sec-why { max-width: none; text-align: left; }
        }

        @media (max-width: 1100px) {
            .vy-gl-search { flex-basis: 100%; }
        }
        @media (max-width: 560px) {
            .vy-gl-chips, .vy-gl-tools { width: 100%; }
            /* four chips with their counts do not fit one row on a phone — two even rows, never a lone stretched chip */
            .vy-gl-chips { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); border-radius: var(--vy-r-md); }
            .vy-gl-chip { justify-content: center; padding: 0 10px; border-radius: var(--vy-r-sm); }
            .vy-gl-tools .vy-btn { flex: 1; justify-content: center; }
            .vy-gl-fields, .vy-gl-switches { grid-template-columns: minmax(0, 1fr); }
            .vy-gl-foot { justify-content: center; }
        }

        .vy-field { margin-bottom: 15px; }
        .vy-field-label { display: block; font-size: 10px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 7px; }
        .vy-field-label .vy-fl-note { font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--vy-faint); }
        .vy-field-label .vy-fl-right { float: right; font-weight: 600; text-transform: none; letter-spacing: 0; color: var(--vy-muted); }

        /* Text input with a leading icon */
        .vy-inwrap { position: relative; }
        .vy-inwrap .vy-in-ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--vy-faint); pointer-events: none; }
        .vy-input { width: 100%; padding: 11px 14px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.7); border: 1px solid var(--vy-glass-border); font-size: 14px; color: var(--vy-ink); font-family: inherit; transition: border-color .15s ease, box-shadow .15s ease; }
        .vy-inwrap .vy-input { padding-left: 38px; }
        .vy-input::placeholder { color: var(--vy-faint); }
        .vy-input:focus { outline: none; border-color: var(--vy-accent-border); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.12); background: #fff; }

        /* Segmented control (role + commission) */
        .vy-seg { display: flex; gap: 4px; padding: 4px; background: rgba(255, 255, 255, 0.45); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md); }
        .vy-seg-opt { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 8px; border-radius: var(--vy-r-sm); font-size: 12.5px; font-weight: 600; color: var(--vy-on-grad); cursor: pointer; text-align: center; transition: all .14s ease; white-space: nowrap; }
        .vy-seg-opt input { position: absolute; opacity: 0; width: 0; height: 0; }
        .vy-seg-opt:hover:not(.is-disabled) { color: var(--vy-ink); }
        .vy-seg-opt.is-checked { background: #fff; color: var(--vy-accent); box-shadow: var(--vy-shadow-sm); }
        .vy-seg-opt.is-disabled { opacity: .5; cursor: not-allowed; }
        .vy-help { font-size: 11.5px; color: var(--vy-faint); margin-top: 8px; line-height: 1.45; }

        /* ---- Unit thumbnails + room-type photo manager ---------------------------------------- */
        .vy-u-cell { display: flex; align-items: center; gap: 11px; }
        .vy-u-thumb { width: 44px; height: 34px; border-radius: var(--vy-r-sm); object-fit: cover; flex: none; border: 1px solid var(--vy-glass-border); }
        .vy-u-thumb--empty { background: var(--vy-grad); opacity: .85; }
        .vy-pm-room { padding: 14px 0; border-top: 1px solid var(--vy-hairline); }
        .vy-pm-room:first-of-type { border-top: none; }
        .vy-pm-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .vy-pm-name { font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; }
        .vy-pm-file { position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
        .vy-pm-add { padding: 7px 13px; font-size: 12.5px; cursor: pointer; }
        .vy-pm-thumbs { display: flex; flex-wrap: wrap; gap: 12px; }
        .vy-pm-thumb { position: relative; width: 132px; height: 92px; border-radius: var(--vy-r-md); overflow: hidden; border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-pm-thumb.is-cover { border-color: var(--vy-accent); box-shadow: var(--vy-shadow-accent); }
        .vy-pm-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vy-pm-cover-badge { position: absolute; top: 6px; left: 6px; font-size: 9px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #fff; background: var(--vy-grad); border-radius: var(--vy-r-pill); padding: 2px 8px; }
        .vy-pm-ctrls { position: absolute; bottom: 0; left: 0; right: 0; display: flex; gap: 3px; justify-content: center; padding: 5px; background: linear-gradient(to top, rgba(30,18,12,.55), transparent); opacity: 0; transition: opacity .15s ease; }
        .vy-pm-thumb:hover .vy-pm-ctrls, .vy-pm-thumb:focus-within .vy-pm-ctrls { opacity: 1; }
        .vy-pm-ctrls form { display: inline; margin: 0; }
        .vy-pm-btn { width: 24px; height: 24px; border-radius: 6px; border: none; background: rgba(255,255,255,.92); color: var(--vy-ink); font-size: 13px; line-height: 1; cursor: pointer; display: inline-grid; place-items: center; }
        .vy-pm-btn:hover:not(:disabled) { background: #fff; }
        .vy-pm-btn:disabled { opacity: .4; cursor: default; }
        .vy-pm-btn--danger { color: var(--vy-accent-text); }
        .vy-pm-empty { font-size: 12.5px; color: var(--vy-faint); padding: 6px 2px; }

        /* ---- Rates & availability (rebuilt 2026-10-01) ------------------------------------------------------------------
           The founder: "table design must be better, texts must look good, all texts and boxes well aligned." Measured before
           (1440×900): every dialog appeared in the top-left corner, the grid was 1320 px wide and hid its last two days, the
           controls in one row were 34, 36 and 43 px tall, and the season and add-plan inputs had no visible box.
           ⚠️ Every display here is a CLASS — x-show erases an inline display. ⚠️ `.vyr-section` belongs to the Roles page (its
           own inline style); this page's panel parts are `.vyr-panel-*`, so neither page restyles the other.
           ⚠️ ONE LOOK PER CELL STATE, and the legend swatches read the SAME tokens as the cells, so the key cannot drift from
           the grid it explains. */
        .vy-rates {
            --vyr-h: 40px;
            --vyr-open: rgba(255, 255, 255, 0.66);
            --vyr-last: rgba(224, 146, 30, 0.16);
            --vyr-last-edge: rgba(178, 106, 8, 0.30);
            --vyr-full: rgba(194, 65, 12, 0.12);
            --vyr-full-edge: rgba(194, 65, 12, 0.26);
            --vyr-closed: repeating-linear-gradient(135deg, rgba(120, 70, 40, 0.10) 0 5px, rgba(255, 255, 255, 0.5) 5px 10px);
            --vyr-weekend: rgba(232, 112, 58, 0.07);
            display: flex; flex-direction: column; gap: 18px;
        }
        .vyr-flash { padding: 12px 16px; margin-bottom: 14px; font-size: 13px; line-height: 1.5; color: var(--vy-ink); border-color: var(--vy-accent-border); }
        .vyr-flash.is-error { color: #8F3110; background: rgba(255, 244, 238, 0.86); border-color: rgba(143, 49, 16, 0.26); }
        .vyr-unitpreview { margin-bottom: 18px; }
        /* ⚠️ a scroll box is the containing block of what it scrolls — otherwise an absolutely placed label (a `vy-sr-only`
           header) takes <main> as its block, escapes the box and widens the page (🖥 production, 390 px: 660 px wide) */
        .vyr-tablewrap { position: relative; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .vyr-actions-end { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }

        /* toolbar — every control in the row is the same height */
        .vyr-bar { display: flex; align-items: center; flex-wrap: wrap; gap: 10px 12px; }
        .vyr-winnav { display: inline-flex; align-items: center; gap: 6px; }
        .vyr-winlabel { font-family: var(--vy-display); font-weight: 600; font-size: 15px; color: var(--vy-ink); min-width: 168px; padding: 0 4px; text-align: center; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .vyr-navbtn { flex: none; width: var(--vyr-h); height: var(--vyr-h); display: inline-grid; place-items: center; border-radius: 50%; border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.62); color: var(--vy-ink); box-shadow: var(--vy-shadow-sm); text-decoration: none; transition: background .15s ease; }
        .vyr-navbtn:hover { background: rgba(255, 255, 255, 0.92); }
        .vyr-bar .vy-btn.vyr-tool { height: var(--vyr-h); padding: 0 16px; white-space: nowrap; }
        .vyr-bar .vy-input.vyr-filter { flex: 0 1 230px; height: var(--vyr-h); padding-top: 0; padding-bottom: 0; }
        .vyr-jump { font-size: 12.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: underline; text-underline-offset: 3px; }
        .vyr-bar-tools { display: flex; align-items: center; gap: 8px; margin-left: auto; }
        .vyr-dk-val-2 { font-size: 20px; margin-top: 2px; }

        /* the grid card */
        .vyr-gridhead { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px 18px; padding: 14px 18px; border-bottom: 1px solid var(--vy-hairline); }
        .vyr-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 12px; color: var(--vy-on-grad); }
        .vyr-legend > span { display: inline-flex; align-items: center; gap: 7px; }
        .vyr-sw { flex: none; width: 16px; height: 12px; border-radius: 4px; border: 1px solid var(--vy-hairline); }
        .vyr-sw.is-open { background: var(--vyr-open); border-color: rgba(120, 70, 40, 0.18); }
        .vyr-sw.is-last { background: var(--vyr-last); border-color: var(--vyr-last-edge); }
        .vyr-sw.is-full { background: var(--vyr-full); border-color: var(--vyr-full-edge); }
        .vyr-sw.is-closed { background: var(--vyr-closed); border-color: rgba(120, 70, 40, 0.18); }
        .vyr-sw.is-weekend { background: var(--vyr-weekend); border-color: rgba(232, 112, 58, 0.22); }
        .vyr-hint { font-size: 12px; color: var(--vy-muted); }
        .vyr-empty { display: grid; gap: 6px; justify-items: center; text-align: center; padding: 42px 20px; font-size: 13px; color: var(--vy-muted); }
        .vyr-empty strong { font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); }

        /* the table — all fourteen nights fit a laptop; narrower screens scroll it under a sticky first column and header */
        .vyr-scroll { position: relative; overflow: auto; max-height: min(74vh, 820px); -webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; }
        .vyr-grid { width: 100%; min-width: 1040px; table-layout: fixed; border-collapse: separate; border-spacing: 0; }
        .vyr-grid col.vyr-col-rt { width: 172px; }
        .vyr-grid th, .vyr-grid td { border-bottom: 1px solid var(--vy-hairline); }
        .vyr-grid thead th { position: sticky; top: 0; z-index: 3; background: rgba(255, 250, 246, 0.97); }
        .vyr-corner { left: 0; z-index: 4 !important; }
        .vyr-dhead { padding: 9px 2px 8px; text-align: center; line-height: 1.15; }
        .vyr-dhead > span { display: block; }
        .vyr-dweekday { font-size: 11px; font-weight: 600; color: var(--vy-muted); }
        .vyr-dday { font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink); margin: 2px auto 1px; width: 28px; height: 28px; line-height: 28px; border-radius: 50%; font-variant-numeric: tabular-nums; }
        .vyr-dmonth { font-size: 10.5px; color: var(--vy-muted); }
        .vyr-grid thead th.vyr-dhead.is-weekend { background: linear-gradient(var(--vyr-weekend), var(--vyr-weekend)), rgba(255, 250, 246, 0.97); }
        .vyr-dhead.is-weekend .vyr-dweekday { color: var(--vy-accent-text); }
        .vyr-dhead.is-today .vyr-dday { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vyr-dhead.is-today .vyr-dweekday { color: var(--vy-accent-text); }
        .vyr-rt { position: sticky; left: 0; z-index: 2; background: rgba(255, 250, 246, 0.97); text-align: left; padding: 10px 14px; vertical-align: middle; border-right: 1px solid var(--vy-hairline); }
        .vyr-rt > span { display: block; }
        .vyr-rt-name { font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vyr-rt-sub { font-size: 11.5px; line-height: 1.4; color: var(--vy-on-grad); margin-top: 2px; }
        .vyr-rt-sub.is-warn { color: var(--vy-amber-text); }
        .vyr-linkbtn { padding: 0; border: 0; background: none; font: inherit; font-weight: 600; color: var(--vy-accent-text); text-decoration: underline; text-underline-offset: 2px; cursor: pointer; }
        .vyr-cell { padding: 4px 2px; vertical-align: middle; }
        .vyr-cell.is-weekend { background: var(--vyr-weekend); }
        .vyr-cellbtn, .vyr-cell-static { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; width: 100%; min-height: 66px; padding: 7px 2px 6px; border-radius: 10px; border: 1px solid transparent; font-family: inherit; color: var(--vy-ink); text-align: center; }
        .vyr-cellbtn { background: var(--vyr-open); border-color: rgba(120, 70, 40, 0.10); cursor: pointer; user-select: none; -webkit-user-select: none; touch-action: pan-x pan-y; transition: border-color .12s ease, background-color .12s ease, box-shadow .12s ease; }
        .vyr-cellbtn:hover { border-color: var(--vy-accent-border); background-color: #fff; box-shadow: 0 4px 12px rgba(150, 90, 50, 0.12); }
        .vyr-cellbtn.is-last, .vyr-cell-static.is-last { background: var(--vyr-last); border-color: var(--vyr-last-edge); }
        .vyr-cellbtn.is-full, .vyr-cell-static.is-full { background: var(--vyr-full); border-color: var(--vyr-full-edge); }
        .vyr-cellbtn.is-closed, .vyr-cell-static.is-closed { background: var(--vyr-closed); border-color: rgba(120, 70, 40, 0.16); }
        .vyr-cellbtn.is-selecting { background: rgba(232, 112, 58, 0.22); border-color: var(--vy-accent-3); box-shadow: inset 0 0 0 1px var(--vy-accent-3); }
        .vyr-rate { font-family: var(--vy-display); font-size: 12.5px; font-weight: 600; font-variant-numeric: tabular-nums; letter-spacing: -0.01em; white-space: nowrap; }
        /* a long price steps down a size so it stays inside its night (RatesGrid::rateFit) */
        .vyr-rate.is-long { font-size: 11px; } .vyr-rate.is-longer { font-size: 10px; } .vyr-rate.is-longest { font-size: 9px; }
        .vyr-urate { white-space: nowrap; } .vyr-urate.is-long { font-size: 10.5px; } .vyr-urate.is-longer { font-size: 9.5px; } .vyr-urate.is-longest { font-size: 8.5px; }
        .vyr-rate.is-empty { color: var(--vy-faint); font-weight: 500; }
        .is-closed > .vyr-rate { color: var(--vy-muted); }
        .vyr-avail { font-size: 10.5px; line-height: 1.2; color: var(--vy-on-grad); white-space: nowrap; }
        .vyr-avail.is-out { color: var(--vy-accent-text); font-weight: 600; }
        .vyr-flags { display: flex; flex-wrap: wrap; justify-content: center; gap: 2px 3px; }
        .vyr-flag { font-size: 9.5px; font-weight: 600; line-height: 1.5; padding: 0 5px; border-radius: var(--vy-r-pill); white-space: nowrap; }
        .vyr-flag--min { background: rgba(178, 106, 8, 0.14); color: var(--vy-amber-text); }
        .vyr-flag--closed { background: rgba(143, 49, 16, 0.12); color: #8F3110; }
        .vyr-booked { display: block; width: 72%; height: 3px; margin-top: 2px; border-radius: 3px; background: rgba(120, 70, 40, 0.13); overflow: hidden; }
        .vyr-booked > i { display: block; height: 100%; border-radius: 3px; background: var(--vy-grad-peach); }
        .vyr-typerow + .vyr-unitrow > *, .vyr-unitrow + .vyr-unitrow > * { border-top: 0; }
        .vyr-unitrow > .vyr-rt { padding: 6px 12px 6px 26px; }
        .vyr-unitrow .vyr-rt-name { font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-on-grad); }
        .vyr-unitrow .vyr-cell { padding: 2px; }
        .vyr-cell-static.is-unit { flex-direction: row; gap: 4px; min-height: 30px; padding: 4px 2px; font-size: 11.5px; color: var(--vy-on-grad); font-variant-numeric: tabular-nums; }
        .vyr-own { flex: none; width: 7px; height: 7px; border-radius: 50%; background: var(--vy-accent); }
        .vyr-priceind { display: inline-flex; align-items: center; gap: 4px; margin-top: 2px; padding: 0; border: 0; background: none; font: inherit; font-size: 11.5px; font-weight: 600; color: var(--vy-accent-text); cursor: pointer; white-space: nowrap; }
        .vyr-priceind:hover { text-decoration: underline; text-underline-offset: 2px; }

        /* the panels under the grid */
        .vyr-panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px 18px; flex-wrap: wrap; margin-bottom: 14px; }
        .vyr-panel-head > div { min-width: 0; flex: 1 1 320px; }
        .vyr-panel-sub { margin: 5px 0 0; max-width: 80ch; font-size: 12.5px; line-height: 1.55; color: var(--vy-muted); }
        .vyr-muted { font-size: 12px; line-height: 1.45; color: var(--vy-muted); }
        .vyr-accent { font-size: 12px; line-height: 1.45; color: var(--vy-accent-text); }
        .vyr-nowrap { white-space: nowrap; }
        .vyr-cell-title { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vyr-seasons { min-width: 820px; }
        .vyr-seasons td { vertical-align: top; }
        .vyr-row-actions { text-align: right; white-space: nowrap; }
        .vyr-row-actions .vy-btn + .vy-btn { margin-left: 6px; }
        .vy-btn.vyr-sm { height: 30px; padding: 0 12px; font-size: 12px; box-shadow: none; }
        .vy-btn.vyr-sm.is-danger { color: #8F3110; }
        .vy-btn.vyr-sm.is-danger:hover { background: rgba(143, 49, 16, 0.07); border-color: rgba(143, 49, 16, 0.26); }
        .vyr-empty-line { margin: 0; font-size: 12.5px; color: var(--vy-muted); }
        .vyr-season-form { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1.1fr) minmax(0, 0.95fr) minmax(0, 0.95fr) minmax(0, 0.8fr) auto; gap: 12px; align-items: end; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--vy-hairline); }
        .vyr-season-form .vy-field { margin-bottom: 0; min-width: 0; }
        /* one height for every box on the page; the height sets it, so no vertical padding (🖥 on a phone the shared 11 px
           padding left 16 px for 17 px of text and clipped a select's words) */
        .vyr-season-form .vy-input, .vyr-dlg .vy-input, .vyr-upf .vy-input { width: 100%; height: var(--vyr-h, 40px); padding-top: 0; padding-bottom: 0; }
        .vyr-season-form .vy-btn.vyr-season-save { height: 40px; padding: 0 18px; white-space: nowrap; }
        .vyr-form-errors { margin: 12px 0 0; padding: 10px 12px; border-radius: var(--vy-r-sm); background: rgba(143, 49, 16, 0.07); border: 1px solid rgba(143, 49, 16, 0.2); font-size: 12.5px; line-height: 1.5; color: #8F3110; }
        .vyr-plan { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 14px; padding: 12px 0; border-top: 1px solid var(--vy-hairline); }
        .vyr-plan-main { flex: 1 1 260px; min-width: 0; }
        .vyr-plan-name { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vyr-plan-sub { margin-top: 2px; font-size: 12px; line-height: 1.45; color: var(--vy-muted); }
        .vyr-plan-priced { margin-top: 3px; font-size: 12px; line-height: 1.45; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
        .vyr-planpick { grid-column: 1 / -1; }
        .vyr-fieldhint { display: block; margin-top: 6px; font-size: 11.5px; line-height: 1.4; color: var(--vy-muted); }
        .vyr-channel { flex: none; }
        .vyr-plan-actions { display: flex; gap: 6px; }
        .vyr-channel-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid var(--vy-hairline); }
        .vyr-channel-ico { flex: none; width: 30px; height: 30px; display: grid; place-items: center; border-radius: 50%; background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-size: 12.5px; font-weight: 600; }
        .vyr-channel-name { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 500; color: var(--vy-ink); }
        .vyr-note { margin: 12px 0 0; font-size: 12px; line-height: 1.5; color: var(--vy-muted); }
        .vy-tag.tone-ok { background: rgba(20, 84, 62, 0.11); color: var(--vy-green-text); }
        .vy-tag.tone-muted { background: rgba(120, 70, 40, 0.09); color: var(--vy-on-grad); }

        /* the dialogs — the head, body and foot pad themselves, so the panel adds nothing around them */
        .vy-modal.vyr-dlg { padding: 0; }
        /* a tall dialog scrolls inside itself; its Cancel / Apply bar stays in view (🖥 the editor with a plan picker is 870 px) */
        .vyr-dlg .vy-modal-foot { position: sticky; bottom: 0; z-index: 1; padding-top: 14px; background: rgba(255, 252, 248, 0.97); border-top: 1px solid var(--vy-hairline); }
        .vy-modal.vyr-dlg.is-padded { padding: 24px 26px 22px; }
        .vy-modal.vyr-dlg.vy-modal--wide { max-width: 620px; }
        .vyr-dlg-head { flex: 1; min-width: 0; }
        .vyr-dlg-sub { margin: 4px 0 0; font-size: 12.5px; line-height: 1.45; color: var(--vy-muted); }
        /* the room type on its own row (🖥 in a third of the width its longest choice was cut off), the two nights under it */
        .vyr-dlg-range { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 12px; margin-bottom: 16px; }
        .vyr-dlg-range > :first-child { grid-column: 1 / -1; }
        .vyr-dlg-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .vyr-dlg-range .vy-field, .vyr-dlg-grid2 .vy-field { margin-bottom: 0; min-width: 0; }
        .vy-modal-body.vyr-dlg-grid2 { padding-bottom: 18px; }
        .vyr-rules { display: grid; gap: 6px; margin-bottom: 12px; }
        .vyr-rule { display: grid; grid-template-columns: minmax(0, 1fr) 216px; align-items: center; gap: 12px; padding: 8px 12px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.62); border: 1px solid var(--vy-hairline); }
        .vyr-rule > .vy-gl-switch { padding: 0; border: 0; background: none; }
        .vyr-money, .vyr-nights { position: relative; display: flex; align-items: center; gap: 8px; min-width: 0; transition: opacity .15s ease; }
        .vyr-money > span { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13.5px; color: var(--vy-muted); pointer-events: none; }
        .vyr-money > .vy-input { padding-left: 28px; }
        .vyr-money.is-pct > span { left: auto; right: 12px; }
        .vyr-money.is-pct > .vy-input { padding-left: 12px; padding-right: 30px; }
        .vyr-nights > .vy-input { flex: 0 0 96px; }
        .vyr-nights > span { font-size: 12.5px; color: var(--vy-muted); }
        .vyr-money.is-off, .vyr-nights.is-off, .vyr-seg:disabled { opacity: 0.42; }
        .vyr-seg { margin: 0; }
        .vyr-rule .vy-choices--segmented .vy-choice-face { padding: 7px 6px; font-size: 12.5px; }
        .vyr-rule .vy-choice-text { overflow-wrap: normal; hyphens: manual; white-space: nowrap; }
        .vyr-derived { margin: 0 0 10px; }
        .vyr-tiles3 { margin-bottom: 14px; }
        .vyr-tiles3 .vy-choice-face { flex-direction: row; align-items: center; justify-content: center; text-align: center; gap: 8px; }
        .vyr-summary { margin: 4px 0 0; padding: 10px 12px; border-radius: var(--vy-r-sm); background: rgba(232, 112, 58, 0.08); font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vyr-prose p { margin: 0 0 10px; font-size: 13px; line-height: 1.55; color: var(--vy-ink); }
        .vyr-prose p.vyr-muted { font-size: 12px; color: var(--vy-muted); }
        .vyr-callout { padding: 10px 12px; border-radius: var(--vy-r-sm); background: rgba(224, 146, 30, 0.12); border: 1px solid rgba(178, 106, 8, 0.22); }

        /* the ONE per-unit price form (shared with the Units page) */
        .vyr-upf-title { margin: 0; font-family: var(--vy-display); font-size: 19px; font-weight: 600; letter-spacing: -0.01em; color: var(--vy-ink); }
        .vyr-upf-sub { margin: 4px 0 18px; font-size: 12.5px; line-height: 1.45; color: var(--vy-muted); }
        .vyr-upf-dates { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .vyr-upf-dates .vy-field { min-width: 0; }
        .vyr-upf-unit { font-size: 13.5px; color: var(--vy-ink); }
        .vyr-upf-unit span { color: var(--vy-muted); }
        .vyr-upf-units-field { min-width: 0; margin: 0 0 15px; padding: 0; border: 0; }
        .vyr-upf-units { display: flex; flex-wrap: wrap; gap: 6px; }
        .vyr-upf-chip { display: inline-flex; align-items: center; gap: 7px; padding: 6px 11px; border-radius: var(--vy-r-pill); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.66); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; }
        .vyr-upf-chip input { margin: 0; accent-color: var(--vy-accent); }
        .vyr-upf-chip:has(input:checked) { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.10); }
        .vyr-upf-hint { margin: 0 0 12px; font-size: 12px; line-height: 1.45; color: var(--vy-muted); }
        .vyr-upf-mode { margin-bottom: 14px; }
        .vyr-upf-foot { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .vyr-upf-foot .vy-btn { height: 40px; padding: 0 18px; }

        .vyr-navbtn:focus-visible, .vyr-cellbtn:focus-visible, .vyr-linkbtn:focus-visible, .vyr-priceind:focus-visible, .vyr-jump:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        @media (prefers-reduced-motion: reduce) { .vyr-cellbtn, .vyr-navbtn, .vyr-money, .vyr-nights { transition: none; } }
        @media (max-width: 1100px) {
            .vyr-season-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .vyr-season-form .vy-btn.vyr-season-save { justify-self: start; }
        }
        @media (max-width: 760px) {
            .vyr-bar-tools { width: 100%; margin-left: 0; flex-wrap: wrap; }
            .vyr-bar-tools .vy-btn.vyr-tool { flex: 1 1 auto; justify-content: center; }
            .vyr-bar .vy-input.vyr-filter { flex: 1 1 100%; }
            .vyr-winnav { width: 100%; }
            .vyr-winlabel { flex: 1; min-width: 0; }
            .vyr-gridhead { padding: 12px 14px; }
            .vyr-grid col.vyr-col-rt { width: 140px; }
            .vyr-rt { padding: 8px 10px; }
            .vyr-unitrow > .vyr-rt { padding-left: 12px; }
            /* 🖥 at 390 px a four-digit average at 30 px ran past its half-width card */
            .vy-rates .vy-dk-val { font-size: 22px; }
            .vy-rates .vyr-dk-val-2 { font-size: 17px; }
            .vyr-season-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vyr-season-form > .vy-field:first-of-type, .vyr-season-form > .vy-field:nth-of-type(2) { grid-column: 1 / -1; }
            .vyr-rule { grid-template-columns: minmax(0, 1fr); }
            .vyr-plan-actions { width: 100%; }
            .vyr-plan-actions .vy-btn { flex: 1 1 auto; justify-content: center; }
            .vy-rates .vy-modal-backdrop { padding: 10px; }
            .vy-modal.vyr-dlg.is-padded { padding: 20px 18px 18px; }
        }
        @media (max-width: 420px) {
            .vyr-dlg-grid2, .vyr-upf-dates { grid-template-columns: minmax(0, 1fr); }
            .vyr-tiles3 .vy-choice-face { flex-direction: column; gap: 5px; padding: 10px 6px; }
        }

        /* ---- A three-up form grid — Settings and Notification preferences. (The rest of this block styled the old
           New reservation page; it moved to its own `vy-nr-` rules on 2026-10-04 and nothing else used it.) ---- */
        .vyn-grid3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; }

        /* ---- Responsive ------------------------------------------------------------------------ */
        @media (max-width: 1100px) {
            .vy-kanban { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vy-detail-cols { grid-template-columns: minmax(0, 1fr); }
            .vy-dash-kpis, .vy-dash-kpis--3, .vy-dash-kpis--5 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vy-dash-cols { grid-template-columns: minmax(0, 1fr); }
            /* the .vy-dash-scoped rule above out-specifies the line above it, so restate it here */
            .vy-dash .vy-dash-cols { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 860px) {
            /* ⚠️⚠️ `minmax(0, …)` AND `min-width: 0` — BOTH, AND THEY FIX DIFFERENT THINGS.
               A bare `1fr` is `minmax(auto, 1fr)`, and that `auto` is a MIN-CONTENT FLOOR the track
               cannot shrink below. ⚠️ But MEASURED at 375px the track was already 375 and it was the
               ITEMS that overflowed it: `.vy-side` and `.vy-main` computed `min-width: auto`, so they
               refused to shrink below their own content and rendered 985px wide inside a 375px track.
               Fixing only the track leaves the items overflowing; fixing only the items leaves the track
               with a min-content floor. Both, or the page stays wide.
               ⚠️ THE DESKTOP RULE AT :129 ALREADY USES `minmax(0, 1fr)` — this only brings the phone rule
               into line with it. Nothing above this breakpoint changes.
               ⚠️ SAME DEFECT CLASS AS THE MARKETING FOOTER GRID (fixed 2026-09-02) — third instance of a
               bare `1fr` min-content floor in this codebase, second in a shell. */
            .vy-shell { grid-template-columns: minmax(0, 1fr); }
            .vy-side { position: static; height: auto; flex-direction: row; flex-wrap: wrap; align-items: center; overflow-x: auto; min-width: 0; }
            .vy-main { min-width: 0; }

            /* ⚠️⚠️ THE TOPBAR — 629px of content in 343px of room, so something has to give, and WHAT
               gives is a design decision rather than a CSS one. MEASURED at 375px, it holds four things:
                 · `.vy-search-pill`  219px — a real CAPABILITY (a link to /app/search)
                 · `.vy-date-chip`    176px — DISPLAY ONLY: "Today · <date>"
                 · the bell            38px — a real capability
                 · `.vy-acct`         159px — the account menu (avatar + the viewer's own name)
               ⚠️ THE TWO THINGS HIDDEN ON A PHONE ARE NAMED, because a capability that exists on desktop
               and vanishes on a phone is a difference this project does not ship undisclosed:
                 1. THE DATE CHIP is hidden. It is not a control, it performs no action, and today's date
                    is a thing a phone already shows in its own status bar.
                 2. THE ACCOUNT NAME is hidden — the TEXT only. The button, the avatar, the menu and every
                    item in it (Profile / Security / Sign out) remain, so no capability is lost; the
                    viewer's own name is the one label they do not need to be told.
               ⚠️ NOTHING ELSE IS HIDDEN. The search pill keeps its label and merely truncates, so its
               accessible name is unchanged and it does not join the unnamed-control backlog; the bell and
               the account menu are untouched.
               ⚠️ AND THE HEADING WRAPS. `.vy-topbar h1` is `white-space: nowrap` (deliberate, :173) and
               measured 318px alone — on a 343px phone line that is the whole row. Wrapping is the only
               option that keeps the page title readable without truncating it. */
            .vy-topbar { flex-wrap: wrap; }
            .vy-topbar h1 { white-space: normal; }
            .vy-topbar-actions { min-width: 0; flex-wrap: wrap; }
            .vy-search-pill { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .vy-date-chip { display: none; }
            .vy-acct-name { display: none; }

            /* ⚠️ THE LAST 14px, AND IT IS DECORATION. `.vy-tonight-glow` is an absolutely-positioned,
               `pointer-events: none` radial blur that deliberately bleeds 30px past its wrapper on each
               side. Inside a wide desktop column that bleed is invisible; at 375px it is 14px of the page
               a thumb can push sideways. Contained rather than removed — the glow still renders, it just
               stops painting outside the viewport. */
            .vy-dash .vy-tonight-glow { left: 0; right: 0; }

            /* ⚠️ FOUR MORE INSTANCES OF THE SAME TWO SHAPES, found by re-measuring every screen rather
               than by stopping at the shell. Each was a page still overflowing after the shell was fixed:
                 · `.vy-ins-grid` (:289) is the OTHER bare `1fr` in a phone breakpoint — the same defect as
                   `.vy-shell`, one screen over. `/app/maintenance/costs` was 363px because of it.
                 · `.vy-stack` and `.vy-left` are flex/grid children computing `min-width: auto`, so they
                   refuse to shrink inside a track that can. Same mechanism as `.vy-main`.
                 · `.vy-table` on `/app/portfolio` is a 434px table with no scroll wrapper, while this
                   codebase already has three (`.vy-utable-wrap`, `.vyr-scroll`, `.vy-rack-scroll`). Its
                   card gets the same treatment rather than a fourth pattern (§N.8f). */
            .vy-ins-grid { grid-template-columns: minmax(0, 1fr); }
            .vy-stack, .vy-left { min-width: 0; }
            /* ⚠️⚠️ WITHDRAWN, DELIBERATELY, AND THE REASON IS THE POINT. A `.vy-card:has(> .vy-table)
               { overflow-x: auto }` rule cleared `/app/portfolio`'s 98px — but turning a card into a
               horizontal scroller can push a card's ACTION BUTTON off-screen inside it, and on this realm
               those buttons are how work gets done. I could not establish within this pass that no card
               action moves out of reach. ⚠️ A fix that hides a control is worse than the overflow it
               removes, so the 98px stays and the table keeps a page a thumb can push 98px sideways.
               ⚠️ THE DURABLE FIX IS A VIEW CHANGE: wrap that table in the house `.vy-utable-wrap`, as
               `/app/units` already does. Reported, not done — it is outside this pass's scope. */

            /* ⚠️ A SCROLL CONTAINER MUST NOT IMPOSE ITS CONTENT'S WIDTH ON THE PAGE. `/app/schedule`
               wraps its 864px roster grid in `overflow-x: auto` — correctly — but the wrapper itself is a
               grid child computing `min-width: auto`, so it sized to 864 instead of scrolling inside 375.
               The wrapper is written as an INLINE STYLE in the view, so this matches the attribute.
               ⚠️ REPORTED, NOT DONE: the durable fix is a `.vy-scroll-x` utility the views use instead of
               an inline style — a view change, outside this pass's scope. This rule is the shell-level
               equivalent and is deliberately narrow. */
            [style*="overflow-x:auto"], [style*="overflow-x: auto"] { min-width: 0; }
            .vy-nav-group { flex-direction: row; flex-wrap: wrap; }
            .vy-nav-label { display: none; }
            .vy-main { padding: 18px 16px 48px; }
        }

        /* ⚠️⚠️ D10 — ONE KANBAN COLUMN ON A PHONE. The rule above already narrows `.vy-kanban` to two
           columns at 1100px; two lanes at 375px is ~180px each, which is the same defect one step smaller.
           ⚠️ LANES STACK RATHER THAN SCROLL SIDEWAYS, and that is the whole argument: a board scrolled
           horizontally can never show two states at once, which is the only reason to draw a board at all.
           Stacking keeps every lane full-width and legible, and vertical scrolling is the gesture a
           housekeeper already uses one-handed in a corridor. Horizontal scroll (`overflow-x`) is this
           project's house answer for wide TABLES — where a row carries the meaning — and a kanban is not
           a table. ⚠️ 700px is an EXISTING shell breakpoint (see :401), not a new one for one screen. */
        @media (max-width: 700px) {
            .vy-kanban { grid-template-columns: minmax(0, 1fr); }

            /* ⚠️⚠️ THE PRIMARY ACTION STAYS ON SCREEN WHILE THE ROW SCROLLS UNDER IT.
               ⚠️ THE SCROLLER IS NOT THE DEFECT AND IS NOT REMOVED. `.vy-utable-wrap` is the house answer
               for a wide table and it is the RIGHT answer here: Room · Housekeeper · Stage · Ready is one
               unit of meaning and horizontal scrolling preserves it. What was wrong is that the column
               carrying the ONLY control that advances a task scrolled away with the data — MEASURED at
               375px the "Start" button sat at x=669, 294px outside a 375px viewport.
               ⚠️ SO NOTHING IS HIDDEN AND NOTHING MOVES. Every column a desk shows, a phone still shows;
               the action is pinned to the right edge instead of being 294px past it. A wrap-or-stack
               answer would have reordered the row; a "hide the middle columns" answer would have removed
               data. This removes neither.
               ⚠️ The surface is the CARD'S OWN `--vy-glass` plus its blur, so the pinned cell reads as
               part of the card rather than introducing a second colour (§N.8f).
               ⚠️ 700px is an EXISTING shell breakpoint (:1650, the same one the kanban collapses at), not
               a new set invented for one screen. */
            .vy-utable--stick-action th:last-child,
            .vy-utable--stick-action td:last-child {
                position: sticky; right: 0; z-index: 1;
                background: var(--vy-glass);
                backdrop-filter: blur(22px) saturate(1.15);
                -webkit-backdrop-filter: blur(22px) saturate(1.15);
                box-shadow: -10px 0 12px -10px rgba(120, 70, 40, .30);
            }
        }

        /* ---- Owners (rebuilt 2026-10-01) --------------------------------------------------------------------------------
           The founder's report on the invite dialog (2026-10-01). Read in the old source: ONE flex row held both of its
           buttons, the password's label, its box and its error side by side, in a panel of its own outside the shared
           dialog. Now the password has its own full-width row and the two buttons share the foot at one height. The list is
           the shared one (Guests, Units, Maintenance); what follows is only what an owner row, the invites and the dialogs
           need on top. ⚠️ Every display is a CLASS — x-show erases an inline display. ⚠️ Secondary text inside rows is
           --vy-on-grad, never --vy-muted (DO-A11Y-5). ⚠️ `.vy-modal` carries its own 26 px padding, and the head, body and
           foot pad themselves: these dialogs set the panel's to 0. */
        .vyo-root { position: relative; }
        .vyo-root .vy-dash-kpi { padding: 15px 18px; }
        .vyo-root .vy-dk-val { font-size: 26px; }
        .vyo-root .vy-gl-sorted { color: var(--vy-on-grad); }
        .vyo-bar .vy-gl-tools { flex-wrap: wrap; justify-content: flex-end; }
        .vyo-bar .vy-gl-tools a.vy-btn { line-height: normal; }
        .vyo-chips { max-width: 100%; }
        .vy-flash.vyo-flash { flex-direction: column; align-items: flex-start; gap: 4px; }
        .vyo-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 22px; align-items: start; }
        .vyo-main, .vyo-rail { display: flex; flex-direction: column; gap: 18px; min-width: 0; }

        /* the table: who, what they own, what they are owed, and why not yet */
        .vyo-table { min-width: 740px; }
        .vyo-table td.vyo-ownercell { min-width: 220px; }
        .vyo-owner { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .vyo-avatar { width: 34px; height: 34px; font-size: 12px; letter-spacing: .02em; }
        .vyo-table tr.is-invited .vyo-avatar { background: transparent; color: var(--vy-on-grad); box-shadow: inset 0 0 0 1.5px rgba(120, 70, 40, 0.3); }
        .vyo-who { min-width: 0; }
        .vyo-units { max-width: 180px; margin: 3px 0 0 auto; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-size: 11.5px; color: var(--vy-on-grad); }
        .vyo-net { font-size: 14px; white-space: nowrap; }
        .vyo-period { margin-top: 3px; font-size: 11.5px; color: var(--vy-on-grad); white-space: nowrap; }
        .vyo-table td.vyo-statuscell { min-width: 190px; }
        .vyo-reason { max-width: 230px; margin-top: 5px; font-size: 11.5px; line-height: 1.4; color: var(--vy-on-grad); }

        /* the invite list */
        .vyo-invites { padding: 20px 22px 8px; }
        .vyo-section-head { margin-bottom: 6px; }
        .vyo-section-sub { margin: 5px 0 0; max-width: 620px; font-size: 12.5px; line-height: 1.5; color: var(--vy-on-grad); }
        .vyo-count { display: inline-grid; place-items: center; min-width: 22px; height: 20px; margin-left: 6px; padding: 0 7px; border-radius: 999px; vertical-align: 2px; font-family: var(--vy-sans); font-size: 11px; font-weight: 700; background: rgba(232, 112, 58, 0.14); color: var(--vy-accent-text); }
        .vyo-invite { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-top: 1px solid var(--vy-hairline); }
        .vyo-invite .vyo-avatar { background: transparent; color: var(--vy-on-grad); box-shadow: inset 0 0 0 1.5px rgba(120, 70, 40, 0.3); }
        .vyo-invite-main { flex: 1; min-width: 0; }
        .vyo-invite-name { display: flex; align-items: center; flex-wrap: wrap; gap: 4px 8px; font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vyo-role { padding: 1px 8px; border-radius: 999px; font-family: var(--vy-sans); font-size: 10.5px; font-weight: 700; background: rgba(120, 70, 40, 0.08); color: var(--vy-on-grad); }
        .vyo-invite-meta { display: flex; flex-wrap: wrap; gap: 3px 14px; margin-top: 4px; font-size: 11.5px; color: var(--vy-on-grad); }
        .vyo-invite-meta span { overflow-wrap: anywhere; }
        .vyo-invite-meta .is-late { font-weight: 600; color: var(--vy-accent-text); }
        .vyo-invite-actions { display: flex; align-items: center; flex-wrap: wrap; justify-content: flex-end; gap: 6px; flex: none; margin: 0; }
        .vyo-confirmcancel { gap: 8px; }
        .vyo-ask { margin-right: 2px; font-size: 12.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-btn.vyo-sm { height: 32px; padding: 0 13px; font-size: 12.5px; line-height: normal; }
        .vy-btn--ghost.vyo-sm.is-danger { color: #8F3110; }

        /* the rail */
        .vyo-payout { position: relative; overflow: hidden; }
        .vyo-payout::after { content: ""; position: absolute; right: -40px; top: -50px; width: 170px; height: 170px; border-radius: 50%; background: radial-gradient(circle, rgba(232, 112, 58, 0.35), transparent 70%); pointer-events: none; }
        .vy-dk-label.vyo-payout-label { color: rgba(255, 255, 255, 0.6); }
        .vyo-payout-val { margin-top: 12px; font-family: var(--vy-display); font-size: 34px; font-weight: 600; letter-spacing: -0.02em; line-height: 1.05; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .vyo-payout-sub { margin-top: 6px; font-size: 12px; color: rgba(255, 255, 255, 0.68); }
        .vyo-payout-link { position: relative; z-index: 1; display: inline-flex; align-items: center; height: 34px; margin-top: 16px; padding: 0 14px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.18); font-size: 12.5px; font-weight: 600; color: #fff; transition: background .15s ease; }
        .vyo-payout-link:hover { background: rgba(255, 255, 255, 0.2); }
        .vyo-payout-link:focus-visible, .vyo-portal-link:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vyo-howpaid { padding: 18px 20px; }
        .vyo-facts { margin: 12px 0 0; }
        .vyo-facts > div { padding: 10px 0; border-top: 1px solid var(--vy-hairline); }
        .vyo-facts > div:first-child { border-top: none; padding-top: 0; }
        .vyo-facts dt { font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); }
        .vyo-facts dd { margin: 4px 0 0; font-size: 12.5px; line-height: 1.5; color: var(--vy-ink); }
        .vyo-portal { padding: 18px 20px; }
        .vyo-portal .vy-dk-label { margin: 0; }
        .vyo-portal-figure { display: flex; align-items: baseline; gap: 7px; margin-top: 10px; }
        .vyo-portal-figure strong { font-family: var(--vy-display); font-size: 26px; font-weight: 700; letter-spacing: -0.02em; color: var(--vy-ink); }
        .vyo-portal-figure span { font-size: 12.5px; color: var(--vy-muted); }
        .vyo-portal-track { height: 6px; margin-top: 12px; overflow: hidden; border-radius: 6px; background: var(--vy-hairline); }
        .vyo-portal-fill { display: block; height: 100%; border-radius: 6px; background: var(--vy-grad); }
        .vyo-portal-sub { margin: 9px 0 0; font-size: 11.5px; line-height: 1.45; color: var(--vy-muted); }
        .vyo-portal-link { display: inline-block; margin-top: 10px; border-radius: 4px; font-size: 12.5px; font-weight: 600; color: var(--vy-accent-text); }
        .vyo-portal-link:hover { text-decoration: underline; text-underline-offset: 2px; }
        .vyo-copied { position: fixed; z-index: 45; left: 50%; bottom: 24px; transform: translateX(-50%); max-width: calc(100vw - 32px); margin: 0; padding: 10px 16px; border-radius: 12px; background: rgba(38, 29, 24, 0.95); color: #fff; font-size: 12.5px; font-weight: 500; box-shadow: 0 14px 34px rgba(40, 20, 8, 0.28); overflow-wrap: anywhere; }

        /* the dialogs — the head, body and foot pad themselves, so the panel adds nothing around them */
        .vy-modal.vyo-dlg { padding: 0; }
        .vy-modal.vyo-dlg.vy-modal--wide { max-width: 620px; }
        .vyo-dlg-head { flex: 1; min-width: 0; }
        .vyo-dlg-head .vy-modal-title { overflow-wrap: anywhere; }
        .vyo-dlg-sub { margin: 4px 0 0; font-size: 12.5px; line-height: 1.45; color: var(--vy-muted); }
        .vy-modal-ico.is-danger { background: linear-gradient(140deg, #A63A12, #7A2A0C); box-shadow: 0 4px 12px rgba(143, 49, 16, 0.28); }
        /* the two foot buttons: one height, one baseline, the action the wider of the two; the bar stays in view while a
           long dialog scrolls */
        .vyo-dlg .vy-modal-foot { position: sticky; bottom: 0; z-index: 1; align-items: center; padding: 14px 24px 20px; background: rgba(255, 252, 248, 0.97); border-top: 1px solid var(--vy-hairline); }
        .vyo-dlg .vy-modal-foot .vy-btn { height: 42px; padding: 0 18px; justify-content: center; line-height: normal; }
        .vyo-dlg .vy-modal-foot .vy-btn--ghost { flex: 0 0 auto; min-width: 116px; }
        .vyo-dlg .vy-modal-foot .vy-btn--accent, .vyo-dlg .vy-modal-foot .vy-btn--danger { flex: 1; padding: 0 18px; }
        .vyo-errors { margin: 0 0 16px; padding: 10px 12px; border-radius: var(--vy-r-sm); background: rgba(143, 49, 16, 0.07); border: 1px solid rgba(143, 49, 16, 0.2); font-size: 12.5px; line-height: 1.5; color: #8F3110; }
        .vyo-errors div + div { margin-top: 4px; }
        .vyo-roles .vy-choice-face { height: 100%; gap: 5px; }
        .vyo-roles .vy-choice-text { font-weight: 600; }
        .vyo-choice-sub { font-size: 11.5px; font-weight: 500; line-height: 1.35; color: var(--vy-on-grad); }
        .vy-choice input:checked + .vy-choice-face .vyo-choice-sub { color: rgba(255, 255, 255, 0.88); }
        .vyo-pair { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 12px; }
        .vyo-pair .vy-field { min-width: 0; }
        .vyo-dlg .vy-input { width: 100%; height: 42px; padding-top: 0; padding-bottom: 0; }
        .vyo-unitfield { min-width: 0; margin: 0 0 15px; padding: 0; border: none; }
        .vyo-unitfield legend { padding: 0; }
        .vyo-optional { margin-left: 4px; font-weight: 500; letter-spacing: 0; text-transform: none; color: var(--vy-faint); }
        .vyo-units-pick { display: flex; flex-wrap: wrap; gap: 6px; max-height: 150px; overflow-y: auto; padding: 2px; }
        .vyo-unitchip { position: relative; display: inline-flex; align-items: center; gap: 7px; min-height: 32px; padding: 5px 12px; border-radius: var(--vy-r-pill); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.66); font-size: 12.5px; font-weight: 600; color: var(--vy-on-grad); cursor: pointer; transition: background .15s ease, border-color .15s ease, color .15s ease; }
        .vyo-unitchip input { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; }
        .vyo-unitchip:hover { border-color: var(--vy-accent-border); }
        .vyo-unitchip:has(input:checked) { background: rgba(232, 112, 58, 0.12); border-color: var(--vy-accent-border); color: var(--vy-accent-text); }
        .vyo-unitchip:has(input:checked) .vyo-unitchip-label::before { content: "✓ "; }
        .vyo-unitchip:has(input:focus-visible) { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vyo-unitchip.is-taken { cursor: not-allowed; border-style: dashed; background: transparent; }
        .vyo-unitchip.is-taken:hover { border-color: var(--vy-glass-border); }
        .vyo-unitchip-label { color: inherit; }
        .vyo-unitchip-owner { font-size: 11px; font-weight: 500; color: var(--vy-on-grad); }
        .vyo-commission { margin-bottom: 8px; }
        .vyo-commission .vy-choice-text { white-space: nowrap; }
        .vyo-other { display: flex; align-items: center; gap: 8px; max-width: 160px; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--vy-on-grad); }
        .vyo-hint { margin: 6px 0 0; font-size: 11.5px; line-height: 1.45; color: var(--vy-muted); }
        .vyo-fielderr { margin-top: 6px; font-size: 12px; font-weight: 600; line-height: 1.4; color: #8F3110; }
        .vyo-access { display: flex; flex-direction: column; gap: 3px; margin: 0 0 15px; padding: 11px 13px; border-radius: var(--vy-r-sm); background: rgba(120, 70, 40, 0.06); border: 1px solid rgba(120, 70, 40, 0.1); font-size: 12.5px; line-height: 1.45; color: var(--vy-on-grad); }
        .vyo-access strong { font-size: 13px; color: var(--vy-ink); }
        /* the password: its own row, the full width, with what it is for under it */
        .vyo-confirm { margin-top: 18px; padding: 14px 14px 13px; border-radius: var(--vy-r-md); background: rgba(232, 112, 58, 0.06); border: 1px solid rgba(232, 112, 58, 0.18); }
        .vy-modal-body > .vyo-confirm:first-child { margin-top: 4px; }
        .vyo-confirm .vy-input { background: #fff; }
        .vyo-confirm .vyo-hint { margin-top: 8px; }
        .vyo-sent { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 30px 28px 16px; text-align: center; }
        .vyo-sent-ico { width: 56px; height: 56px; margin-bottom: 6px; border-radius: 50%; display: grid; place-items: center; background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vyo-sent-sub { max-width: 380px; margin: 0; font-size: 13px; line-height: 1.55; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vyo-sent-note { max-width: 380px; margin: 0; font-size: 12px; line-height: 1.5; color: var(--vy-muted); }
        .vyo-factlist { margin: 0; padding: 0 0 0 18px; font-size: 12.5px; line-height: 1.6; color: var(--vy-ink); }

        @media (max-width: 1180px) {
            .vyo-layout { grid-template-columns: minmax(0, 1fr); }
            .vyo-rail { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); align-items: start; }
        }
        @media (max-width: 760px) {
            .vyo-root .vy-dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            /* the row menu stays at the right edge while the table scrolls sideways — the work-order table's treatment */
            .vyo-table th.vy-gl-actcol, .vyo-table td.vy-gl-actcol { position: sticky; right: 0; z-index: 1; background: rgba(255, 251, 247, 0.96); box-shadow: -10px 0 12px -10px rgba(120, 70, 40, 0.3); }
            .vyo-table thead th.vy-gl-actcol { z-index: 3; }
            .vyo-bar .vy-gl-tools .vy-btn { flex: 1 1 auto; justify-content: center; white-space: nowrap; }
            .vyo-invite { flex-wrap: wrap; }
            .vyo-invite-actions { width: 100%; justify-content: flex-start; padding-left: 46px; }
            .vyo-pair { grid-template-columns: minmax(0, 1fr); }
            .vyo-roles .vy-choice-row { grid-auto-flow: row; grid-template-columns: minmax(0, 1fr); }
            .vyo-roles .vy-choice-face { flex-direction: row; align-items: center; flex-wrap: wrap; column-gap: 10px; }
            .vyo-roles .vyo-choice-sub { flex-basis: 100%; padding-left: 28px; }
            .vyo-dlg .vy-modal-foot { padding: 12px 16px 16px; }
            .vyo-dlg .vy-modal-foot .vy-btn--ghost { min-width: 96px; }
        }

        /* ---- The owner's page (rebuilt 2026-10-03) ----------------------------------------------------------------------
           The founder's report on its dialogs: the password box sat under a shouted label with no border, and Keep and
           Remove were two different sizes, in panels of the page's own. They are now the Owners list's dialogs above (the
           password on its own row, both buttons in the foot at one height); what follows is the page around them — the owner
           and every action on them in one bar, the cards, and the rail. ⚠️ Every display is a CLASS — x-show erases an inline
           display. ⚠️ Secondary text on cards is --vy-on-grad, never --vy-muted on a row (DO-A11Y-5). */
        .vyo-back { margin: 0 0 12px; border-radius: 4px; }
        .vyo-back:focus-visible, .vyo-hero-link:focus-visible, .vyo-card-link:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vyo-hero { position: relative; overflow: hidden; overflow: clip; display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 18px 24px; margin-bottom: 18px; padding: 22px 24px; }
        .vyo-hero::after { content: ""; position: absolute; right: -70px; top: -90px; width: 240px; height: 240px; border-radius: 50%; background: radial-gradient(circle, rgba(232, 112, 58, 0.16), transparent 70%); pointer-events: none; }
        .vyo-hero-id { position: relative; z-index: 1; display: flex; align-items: flex-start; gap: 16px; flex: 1 1 360px; min-width: 0; }
        .vyo-hero-avatar { width: 56px; height: 56px; font-size: 18px; font-weight: 700; letter-spacing: .02em; background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vyo-hero-who { min-width: 0; }
        .vyo-hero-name { margin: 2px 0 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; line-height: 1.15; letter-spacing: -0.015em; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vyo-hero-legal { margin-top: 3px; font-size: 12.5px; color: var(--vy-on-grad); overflow-wrap: anywhere; }
        .vyo-hero-tags { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 12px; margin-top: 10px; }
        .vyo-hero-fact { font-size: 12px; font-weight: 600; color: var(--vy-on-grad); }
        .vyo-tag-paused { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; line-height: 1.5; background: rgba(224, 146, 30, 0.16); color: var(--vy-amber-text); }
        .vyo-hero-contact { display: flex; flex-wrap: wrap; gap: 6px 20px; margin-top: 12px; }
        .vyo-hero-link { display: inline-flex; align-items: center; gap: 7px; min-width: 0; border-radius: 4px; font-size: 13px; font-weight: 500; color: var(--vy-ink); }
        .vyo-hero-link svg { flex: none; color: var(--vy-accent-text); }
        .vyo-hero-link span { overflow-wrap: anywhere; }
        .vyo-hero-link:hover span { text-decoration: underline; text-underline-offset: 2px; }
        .vyo-hero-none { font-size: 12.5px; color: var(--vy-on-grad); }
        .vyo-hero-actions { position: relative; z-index: 1; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; max-width: 100%; }
        .vyo-hero-actions .vy-btn { gap: 7px; }
        .vyo-kpis { margin-bottom: 22px; }
        .vyo-root .vy-dk-val.vyo-kpi-extra { margin-top: 2px; font-size: 19px; }
        .vyo-ownerpage .vyo-layout { grid-template-columns: minmax(0, 1fr) 320px; }
        .vyo-card { padding: 20px 22px; }
        .vyo-card-head { flex-wrap: wrap; margin-bottom: 12px; }
        .vyo-card-head .vy-card-title { display: inline-flex; align-items: center; }
        .vyo-card-link { border-radius: 4px; font-size: 12.5px; font-weight: 600; color: var(--vy-accent-text); }
        .vyo-card-link:hover { text-decoration: underline; text-underline-offset: 2px; }
        .vyo-card-sub { margin: 8px 0 0; font-size: 12.5px; line-height: 1.5; color: var(--vy-on-grad); overflow-wrap: anywhere; }
        .vyo-empty { padding: 26px 16px; }
        .vyo-empty-sub { margin: 6px 0 0; font-size: 12.5px; }
        .vyo-sublabel { margin: 0 0 12px; }
        .vyo-sublabel.is-spaced { margin: 22px 0 8px; }
        .vyo-flags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
        .vyo-num { font-variant-numeric: tabular-nums; }
        .vyo-fact-note { margin-top: 2px; font-size: 11.5px; color: var(--vy-on-grad); }
        .vyo-terms-table { min-width: 0; }
        .vyo-inline-form { flex: none; margin: 0; }
        .vyo-inline-note { margin: 0 0 12px; padding: 10px 12px; border-radius: var(--vy-r-sm); font-size: 12.5px; line-height: 1.5; }
        .vyo-inline-note.is-ok { background: rgba(31, 110, 75, 0.08); border: 1px solid rgba(31, 110, 75, 0.2); color: #1F6E4B; }
        .vyo-inline-note.is-err { background: rgba(143, 49, 16, 0.07); border: 1px solid rgba(143, 49, 16, 0.2); color: #8F3110; }
        /* net to owner — credited beside deducted, the net beneath both */
        .vyo-net-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 28px; }
        .vyo-net-label { margin-bottom: 6px; font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); }
        .vyo-net-note { margin-left: 4px; font-weight: 500; letter-spacing: 0; text-transform: none; }
        .vyo-net-row { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; font-size: 12.5px; color: var(--vy-ink); }
        .vyo-net-row.is-sum { margin-top: 2px; border-top: 1px solid var(--vy-hairline); font-weight: 700; }
        .vyo-net-total { display: flex; justify-content: space-between; gap: 10px; margin-top: 14px; padding-top: 12px; border-top: 2px solid var(--vy-ink); font-family: var(--vy-display); font-size: 15px; font-weight: 700; }
        /* the rail — how they're paid, the portal, the team's notes */
        .vyo-terms, .vyo-portalcard, .vyo-notes { padding: 18px 20px; }
        .vyo-terms-big { font-family: var(--vy-display); font-size: 22px; font-weight: 700; letter-spacing: -0.01em; color: var(--vy-ink); }
        .vyo-terms-next { display: block; margin-top: 4px; font-size: 12px; font-weight: 600; color: var(--vy-accent-text); }
        .vyo-paused-word { color: var(--vy-amber-text); }
        .vyo-dd-note { margin-left: 4px; color: var(--vy-on-grad); }
        .vyo-portal-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .vyo-note { padding: 10px 0; border-top: 1px solid var(--vy-hairline); font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vyo-notes .vy-card-title + .vyo-note { margin-top: 8px; }
        .vyo-note-legacy, .vyo-note-body { white-space: pre-line; overflow-wrap: anywhere; }
        .vyo-note-meta { margin-top: 3px; font-size: 11px; color: var(--vy-on-grad); }
        .vyo-note-form { margin-top: 12px; }
        .vyo-note-form textarea.vy-input { width: 100%; min-height: 64px; resize: vertical; }
        .vyo-note-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 8px; }
        .vyo-note-foot .vyo-hint { margin: 0; }
        /* the dialogs' own pieces — the rate box, the period line, three fields in a row */
        .vyo-rate { display: flex; align-items: center; gap: 8px; max-width: 180px; }
        .vyo-rate-unit { font-size: 15px; font-weight: 600; color: var(--vy-on-grad); }
        .vyo-factlist--spaced { margin-bottom: 18px; }
        .vyo-closed-upto { margin: 0 0 14px; padding: 9px 12px; border-radius: var(--vy-r-sm); background: rgba(120, 70, 40, 0.06); border: 1px solid rgba(120, 70, 40, 0.1); font-size: 12.5px; line-height: 1.45; color: var(--vy-on-grad); }
        .vyo-span { min-height: 18px; margin: -6px 0 14px; font-size: 12.5px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
        .vyo-trio { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 12px; }
        .vyo-trio .vy-field { min-width: 0; }
        .vyo-field-last { margin-bottom: 4px; }

        /* the payout run page (2026-10-03) — its breadcrumb, the bar, and the notes that sat in inline styles */
        .vyo-pr { position: relative; }
        .vyo-pr-crumbs { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; font-size: 12.5px; }
        .vyo-pr-crumb { display: inline-flex; align-items: center; gap: 4px; border-radius: 4px; font-weight: 500; color: var(--vy-on-grad); }
        .vyo-pr-crumb:hover { text-decoration: underline; text-underline-offset: 2px; }
        .vyo-pr-crumb:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vyo-pr-sep, .vyo-pr-here { color: var(--vy-on-grad); }
        .vyo-pr-bar { margin-bottom: 12px; }
        .vyo-pr-actions { gap: 8px; }
        .vyo-pr-actions .vy-btn:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; transform: none; }
        .vyo-pr-off { display: flex; align-items: flex-start; gap: 8px; margin: 0 0 16px; padding: 10px 14px; border-radius: var(--vy-r-md); background: rgba(255, 255, 255, 0.55); border: 1px solid var(--vy-glass-border); font-size: 12.5px; line-height: 1.5; color: var(--vy-ink); }
        .vyo-pr-off svg { flex: none; margin-top: 2px; color: var(--vy-accent-text); }
        .vyo-pr-cols { margin-top: 22px; }
        .vyo-pr-note { margin: 0 0 12px; }
        .vyo-pr-note--top { margin-top: 14px; }
        .vyo-pr-note--tight { margin-top: 10px; }
        .vyo-pr-soon { display: flex; align-items: center; gap: 8px; margin-top: 14px; }
        .vyo-pr-end { text-align: right; }
        .vyo-pr-holdnote { padding: 8px 0 0; font-size: 11.5px; line-height: 1.45; color: var(--vy-on-grad); }

        /* the unit requests page (2026-10-03) — a request is a card with its photos; Approve and Decline open the dialogs */
        .vyo-ur { position: relative; }
        .vyo-ur-intro { max-width: 720px; margin: 0 0 14px; font-size: 13px; line-height: 1.55; color: var(--vy-on-grad); }
        .vyo-ur-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .vyo-ur-chip { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 14px; border-radius: var(--vy-r-pill); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.6); font-size: 12.5px; font-weight: 600; color: var(--vy-on-grad); transition: background .15s ease, border-color .15s ease; }
        .vyo-ur-chip:hover { border-color: var(--vy-accent-border); }
        .vyo-ur-chip:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vyo-ur-chip.is-on { background: var(--vy-grad); border-color: transparent; color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vyo-ur-chipcount { display: inline-grid; place-items: center; min-width: 20px; height: 18px; padding: 0 6px; border-radius: 999px; font-size: 11px; font-weight: 700; background: rgba(120, 70, 40, 0.1); color: var(--vy-ink); }
        .vyo-ur-chip.is-on .vyo-ur-chipcount { background: rgba(255, 255, 255, 0.25); color: #fff; }
        .vyo-ur-list { padding: 6px 22px; }
        .vyo-ur-item { display: grid; grid-template-columns: 96px minmax(0, 1fr) auto; gap: 16px; align-items: start; padding: 18px 0; border-top: 1px solid var(--vy-hairline); }
        .vyo-ur-item:first-child { border-top: none; }
        .vyo-ur-media { width: 96px; height: 72px; overflow: hidden; border-radius: var(--vy-r-md); background: rgba(120, 70, 40, 0.06); border: 1px solid var(--vy-hairline); }
        .vyo-ur-cover { display: block; width: 100%; height: 100%; object-fit: cover; }
        .vyo-ur-noimg { display: grid; place-items: center; width: 100%; height: 100%; color: var(--vy-on-grad); }
        .vyo-ur-body { min-width: 0; }
        .vyo-ur-top { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; }
        .vyo-ur-name { margin: 0; font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vyo-ur-status { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; line-height: 1.5; background: var(--vy-hairline); color: var(--vy-on-grad); }
        .vyo-ur-status.is-pending { background: rgba(224, 146, 30, 0.16); color: var(--vy-amber-text); }
        .vyo-ur-status.is-approved { background: rgba(31, 110, 75, 0.14); color: #1F6E4B; }
        .vyo-ur-meta { margin-top: 4px; font-size: 12px; font-weight: 600; color: var(--vy-on-grad); overflow-wrap: anywhere; }
        .vyo-ur-details { margin: 6px 0 0; font-size: 12.5px; line-height: 1.55; color: var(--vy-ink); overflow-wrap: anywhere; }
        .vyo-ur-strip { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 10px; }
        .vyo-ur-thumb { display: block; width: 48px; height: 36px; object-fit: cover; border-radius: 8px; border: 1px solid var(--vy-hairline); }
        .vyo-ur-more { font-size: 12px; font-weight: 600; color: var(--vy-on-grad); }
        .vyo-ur-outcome { margin: 8px 0 0; font-size: 12px; font-weight: 600; color: #1F6E4B; }
        .vyo-ur-outcome.is-quiet { color: var(--vy-on-grad); }
        .vyo-ur-note { margin: 6px 0 0; font-size: 12.5px; font-style: italic; color: var(--vy-on-grad); overflow-wrap: anywhere; }
        .vyo-ur-removed { margin: 8px 0 0; font-size: 12px; font-weight: 600; color: var(--vy-amber-text); }
        .vyo-ur-actions { display: flex; gap: 8px; flex: none; }
        .vyo-ur-empty { padding: 34px 16px; }
        .vyo-ur-empty-sub { margin: 6px 0 0; font-size: 12.5px; }

        @media (max-width: 1180px) {
            .vyo-ownerpage .vyo-layout { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 760px) {
            .vyo-hero { padding: 18px 16px; }
            .vyo-hero-name { font-size: 21px; }
            .vyo-hero-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
            .vyo-hero-actions .vy-btn { justify-content: center; white-space: nowrap; }
            .vyo-hero-actions .vy-btn svg { display: none; }
            .vyo-card { padding: 16px; }
            .vyo-net-grid, .vyo-trio { grid-template-columns: minmax(0, 1fr); }
            .vyo-ur-list { padding: 4px 16px; }
            .vyo-ur-item { grid-template-columns: 72px minmax(0, 1fr); gap: 12px; }
            .vyo-ur-media { width: 72px; height: 56px; }
            .vyo-ur-actions { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vyo-ur-actions .vy-btn { justify-content: center; }
            .vyo-note-foot { flex-wrap: wrap; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .vy-fade { opacity: 1; }
        }
        /* #96 — the STORED interface-motion preference (Profile) overrides the OS default: with it off, kill
           animations/transitions just like the media query; with it on, ambient motion runs. */
        body[data-reduce-motion="1"] *, body[data-reduce-motion="1"] *::before, body[data-reduce-motion="1"] *::after { animation: none !important; transition: none !important; }
        body[data-reduce-motion="1"] .vy-fade { opacity: 1; }
        /* #96 — the STORED compact-density preference tightens the workspace's spacing. */
        body[data-density="compact"] .vy-main { padding-top: 12px; padding-bottom: 32px; }
        body[data-density="compact"] .vy-card { padding: 14px; }
        body[data-density="compact"] .vy-dash-kpi { padding: 12px 14px; }


    </style>
    <x-vy-scrollbar />
</head>
@php $vyPrefs = auth()->user(); @endphp
<body class="vy-body" @if ($vyPrefs && $vyPrefs->reduce_motion) data-reduce-motion="1" @endif @if ($vyPrefs && $vyPrefs->compact_density) data-density="compact" @endif>
{{-- ADMIN 01 · PHASE 5 — Vyrobel announcements to this organisation (live, aimed here, not dismissed). --}}
<x-platform-announcements />
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
    @include('partials.submit-guard')   {{-- Polish Batch 4 — double-submit guard for the operator money/inventory writes --}}
    @include('partials.count-up')       {{-- Polish Batch 5 — KPI/hero count-up (lands on the exact real figure) --}}
    @include('partials.live-pulse')     {{-- Team chat (2026-10-06) — the live bell, Messages badge and chime on every page --}}
</body>
</html>
