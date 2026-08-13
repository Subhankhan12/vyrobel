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
               --vy-muted only reaches 4.03:1 there and fails AA. These two are computed against the
               gradient's darkest sampled stop, rgb(241,185,150): 4.97:1 and 4.67:1. Warm stone, not
               cold grey, so the shell still reads warm. */
            --vy-on-grad: rgb(84, 74, 68);
            --vy-on-grad-soft: rgb(88, 78, 72);

            /* Accent used as TEXT (links, accent labels) on a glass/card surface. --vy-accent itself is
               the BRAND fill — gradients, buttons, chips, borders, icons — and is deliberately unchanged.
               As text it measured 4.09–4.46:1 across real sampled card backgrounds, under AA; the worst
               was rgb(252,223,204). This darker terracotta clears it at 4.85:1 there. Same family, ~10%
               deeper. Only applied where accent is text WITHOUT its own background (a tinted chip carries
               its own surface and already measured 4.61). */
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
            animation: vyFadeUp .45s cubic-bezier(.22, .61, .36, 1) both;
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
            background: rgba(255, 255, 255, 0.72); color: var(--vy-accent); font-weight: 500;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 14px rgba(150, 90, 50, 0.14); border: 1px solid rgba(255, 255, 255, 0.8);
        }
        .vy-nav-item[aria-disabled="true"] { cursor: default; opacity: .55; }
        .vy-nav-item[aria-disabled="true"]:hover { background: transparent; color: var(--vy-on-grad); }

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
        .vy-topbar-sub { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; }
        .vy-topbar-actions { display: flex; align-items: center; gap: 12px; }

        .vy-bell { position: relative; width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); color: var(--vy-muted); }
        .vy-chip { display: flex; align-items: center; gap: 9px; padding: 5px 12px 5px 6px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-avatar { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(140deg, #F0A57A, #D9662E); color: #fff; font-size: 11px; font-weight: 500; display: grid; place-items: center; flex: none; }
        .vy-avatar--lg { width: 38px; height: 38px; font-size: 13px; font-weight: 600; }
        .vy-chip-name { font-size: 13px; font-weight: 500; }

        /* GATE 0 — bespoke-chrome topbar (prototype-exact): read-only pill · search pill · date chip · account dropdown */
        [x-cloak] { display: none !important; }
        .vy-topbar-lead { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1 1 auto; }
        .vy-ro-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 13px; border-radius: var(--vy-r-pill); background: rgba(217, 154, 61, 0.18); color: #8F6414; font-size: 11.5px; font-weight: 500; white-space: nowrap; flex: none; }
        .vy-glass-pill { display: inline-flex; align-items: center; gap: 8px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); }
        .vy-search-pill { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); color: var(--vy-faint); font-size: 13px; min-width: 210px; text-decoration: none; transition: color .15s; }
        .vy-search-pill:hover { color: var(--vy-muted); }
        .vy-date-chip { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); font-size: 13px; font-weight: 500; white-space: nowrap; }
        .vy-acct { position: relative; }
        .vy-acct-btn { display: flex; align-items: center; gap: 9px; padding: 5px 9px 5px 14px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); white-space: nowrap; cursor: pointer; font-family: var(--vy-sans); transition: background .15s; }
        .vy-acct-btn:hover { background: rgba(255, 255, 255, 0.72); }
        .vy-acct-id { text-align: right; line-height: 1.2; }
        .vy-acct-name { display: block; font-size: 12.5px; font-weight: 500; color: var(--vy-ink); line-height: 1.25; }
        .vy-acct-role { display: block; font-size: 10.5px; color: var(--vy-faint); line-height: 1.2; }
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

        /* Analytics — Insights + Reports (Phase 6b). */
        .vy-ins-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; margin-top: 14px; }
        @media (max-width: 900px) { .vy-ins-grid { grid-template-columns: 1fr; } }

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
        @media (max-width: 1100px) {
            .vy-sch-cols { display: block; }
            .vy-sch-rail { width: auto; margin-top: 18px; }
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
        .vy-table th { text-align: left; font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-faint); padding: 6px 8px; border-bottom: 1px solid var(--vy-glass-border); }
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
        .vy-plan-tag { margin-left: auto; font-size: 8.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #fff; background: var(--vy-grad); border-radius: 5px; padding: 1px 5px; }

        /* ---- Reusable surfaces ----------------------------------------------------------------- */
        .vy-card {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg);
            box-shadow: var(--vy-shadow); padding: 22px;
        }
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
        .vy-btn:focus-visible, .vy-nav-item:focus-visible, .vy-icobtn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* A control with no backend yet: visible, clearly non-functional, never a fake action. */
        .vy-btn.is-soon, .vy-icobtn.is-soon, .vy-pill.is-soon { opacity: .5; cursor: not-allowed; }
        .vy-soon-tag { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 2px 6px; }

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
        .vy-deferred-note { font-size: 11.5px; color: var(--vy-muted); display: inline-flex; align-items: center; gap: 6px; }

        /* ---- Reservation rack (timeline grid) -------------------------------------------------- */
        .vy-rack-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
        .vy-rack-toolbar .vy-left, .vy-rack-toolbar .vy-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        /* Operator Calendar exact-match — the inline New-reservation panel */
        .vy-newres-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
        .vy-newres .vy-field { margin-bottom: 0; display: flex; flex-direction: column; gap: 6px; }
        .vy-newres .vy-field > span { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-newres-rt { border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-md, 12px); padding: 12px 14px; margin-bottom: 10px; }
        .vy-newres-rt-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .vy-newres-rt-name { font-family: var(--vy-display); font-size: 14px; font-weight: 600; }
        .vy-newres-rt-rate { font-family: var(--vy-display); font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .vy-newres-units { display: flex; flex-wrap: wrap; gap: 8px; }
        .vy-newres-unit { margin: 0; }
        .vy-newres-confirm { cursor: pointer; border: 1px solid var(--vy-accent-border); color: var(--vy-accent); background: rgba(194, 65, 12, 0.06); font-weight: 600; }
        .vy-newres-confirm:hover { background: rgba(194, 65, 12, 0.12); }
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

        /* New-reservation modal — the design's round glyph on each unit row. */
        .vy-cal .vy-newres-room-ico { width: 34px; height: 34px; border-radius: 50%; flex: none; display: flex; align-items: center; justify-content: center; background: rgba(217, 102, 46, 0.12); color: var(--vy-accent); }
        .vy-cal .vy-newres-room.is-sel .vy-newres-room-ico { background: var(--vy-grad); color: #fff; }
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

        /* GATE 5.2 — the New-reservation MODAL (two-phase: Check rack → pick a unit → Confirm). Reuses the
           shared .vy-modal chrome; these are the modal-specific controls. */
        .vy-newres-modal { max-width: 480px; }
        .vy-newres-p1 { display: block; }
        .vy-newres-guest { display: grid; grid-template-columns: 1fr 1fr 116px; gap: 9px; }
        .vy-stepper { display: flex; align-items: center; border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.7); border-radius: var(--vy-r-md); overflow: hidden; }
        .vy-stepper button { width: 30px; padding: 10px 0; border: none; background: transparent; color: var(--vy-muted); font-size: 15px; line-height: 1; cursor: pointer; transition: background .14s; }
        .vy-stepper button:hover { background: rgba(232,112,58,.08); color: var(--vy-accent); }
        .vy-stepper span { flex: 1; text-align: center; font-size: 12px; font-weight: 500; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-newres-dates { display: grid; grid-template-columns: 1fr 1fr auto; gap: 9px; align-items: center; }
        .vy-newres-nights { font-size: 12px; font-weight: 600; color: var(--vy-muted); white-space: nowrap; padding-right: 2px; }
        .vy-newres-check { width: 100%; justify-content: center; margin-top: 14px; padding: 11px 16px; }

        .vy-newres-rooms { display: flex; flex-direction: column; gap: 8px; }
        .vy-newres-room { display: flex; align-items: center; gap: 12px; text-align: left; padding: 11px 13px; border-radius: var(--vy-r-md); background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); cursor: pointer; transition: transform .12s ease, border-color .14s, background .14s; }
        .vy-newres-room:hover { transform: translateY(-1px); }
        .vy-newres-room.is-sel { border-color: var(--vy-accent-border); background: rgba(232,112,58,.09); }
        .vy-newres-room-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
        .vy-newres-room-name { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-newres-room-sub { font-size: 11px; color: var(--vy-muted); }
        .vy-newres-room-rate { font-size: 13px; font-weight: 600; color: var(--vy-ink); white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-newres-radio { width: 18px; height: 18px; border-radius: 50%; border: 1.5px solid var(--vy-faint); flex: none; position: relative; transition: all .14s; }
        .vy-newres-radio.on { border-color: transparent; background: var(--vy-grad); box-shadow: var(--vy-shadow-accent); }
        .vy-newres-radio.on::after { content: ''; position: absolute; inset: 4px; border-radius: 50%; background: #fff; }
        .vy-newres-hidden { display: none; }

        .vy-newres-sources { display: flex; flex-wrap: wrap; gap: 7px; }
        .vy-source-pill { padding: 8px 15px; border-radius: var(--vy-r-pill); font-size: 12.5px; font-weight: 500; color: var(--vy-muted); background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); cursor: pointer; transition: transform .12s ease, all .14s; }
        .vy-source-pill:hover { transform: translateY(-1px); }
        .vy-source-pill.on { color: var(--vy-accent); background: rgba(232,112,58,.10); border-color: var(--vy-accent-border); font-weight: 600; }
        .vy-newres-total { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; padding: 13px 15px; border-radius: var(--vy-r-md); background: rgba(232,112,58,.07); border: 1px solid var(--vy-accent-border); }
        .vy-newres-total-lbl { font-size: 12.5px; font-weight: 500; color: var(--vy-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-newres-total-amt { font-family: var(--vy-display); font-size: 18px; font-weight: 700; color: var(--vy-accent-text); white-space: nowrap; font-variant-numeric: tabular-nums; }
        .vy-newres-foot .vy-btn--accent[disabled] { opacity: .5; cursor: not-allowed; }
        .vy-newres-cancel { background: rgba(255,255,255,.6); border: 1px solid var(--vy-glass-border); color: var(--vy-muted); }
        .vy-newres-note { padding: 0 24px 22px; font-size: 11.5px; color: var(--vy-faint); line-height: 1.5; }
        .vy-newres-note a { color: var(--vy-accent-text); font-weight: 600; text-decoration: none; }

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
        .vy-fd-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; color: var(--vy-muted); margin-top: 4px; }
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
        .vy-tag.tone-pending { background: rgba(224, 146, 30, 0.15); color: var(--vy-amber); }
        .vy-tag.tone-pending .vy-dot { background: var(--vy-amber-2); }
        .vy-tag.tone-no-show { background: rgba(178, 106, 8, 0.15); color: var(--vy-amber); }
        .vy-tag.tone-no-show .vy-dot { background: var(--vy-amber); }
        .vy-tag.tone-cancelled { background: rgba(166, 154, 146, 0.18); color: var(--vy-faint); }
        .vy-tag.tone-cancelled .vy-dot { background: var(--vy-faint); }
        /* Guest CRM flags (Wave 2) — the real `guests.is_vip` / `risk_flag` columns + the tag catalog.
           Warm only, per the design rule: at-risk borrows the established amber WARNING tone (the same one
           `.vy-unassigned` uses) rather than introducing a red. */
        .vy-tag.tone-vip { background: rgba(180, 130, 44, 0.16); color: #8a6410; }
        .vy-tag.tone-vip .vy-dot { background: #b4822c; }
        .vy-tag.tone-risk { background: rgba(178, 106, 8, 0.15); color: var(--vy-amber); }
        .vy-tag.tone-risk .vy-dot { background: var(--vy-amber); }
        .vy-tag.tone-tag { background: rgba(120, 70, 40, 0.08); color: var(--vy-muted); }
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
        .vy-thread-av { position: relative; flex: none; width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(140deg, #F0A57A, #D9662E); color: #fff; display: grid; place-items: center; font-family: var(--vy-display); font-weight: 600; font-size: 14px; }
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
        .vy-convo-back { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: var(--vy-muted); text-decoration: none; }
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
        .vy-fact-label { font-size: 9.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-faint); }
        .vy-fact-val { font-size: 13.5px; font-weight: 500; margin-top: 3px; }
        /* #105 Work Order detail — the wireframe's 4-col fact grid + responsive 2-col stack */
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
        .vy-dash .vy-arr-av { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(140deg, #F0A57A, #D9662E); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 500; flex: none; }

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
        .vy-owner-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 13px; font-weight: 600; display: grid; place-items: center; flex: none; }
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
        .vy-seg-opt { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 8px; border-radius: var(--vy-r-sm); font-size: 12.5px; font-weight: 600; color: var(--vy-muted); cursor: pointer; text-align: center; transition: all .14s ease; white-space: nowrap; }
        .vy-seg-opt input { position: absolute; opacity: 0; width: 0; height: 0; }
        .vy-seg-opt:hover:not(.is-disabled) { color: var(--vy-ink); }
        .vy-seg-opt.is-checked { background: #fff; color: var(--vy-accent); box-shadow: var(--vy-shadow-sm); }
        .vy-seg-opt.is-disabled { opacity: .5; cursor: not-allowed; }
        .vy-help { font-size: 11.5px; color: var(--vy-faint); margin-top: 8px; line-height: 1.45; }

        /* Unit assignment — horizontal checkbox pills */
        .vy-units { display: flex; flex-wrap: wrap; gap: 8px; }
        .vy-unitpill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px 8px 10px; border-radius: var(--vy-r-pill); font-size: 13px; font-weight: 500; color: var(--vy-ink); background: rgba(255, 255, 255, 0.55); border: 1px solid var(--vy-glass-border); cursor: pointer; transition: all .14s ease; }
        .vy-unitpill input { position: absolute; opacity: 0; width: 0; height: 0; }
        .vy-unitpill .vy-ubox { width: 18px; height: 18px; border-radius: 6px; border: 1.5px solid var(--vy-faint); display: grid; place-items: center; flex: none; color: transparent; transition: all .14s ease; }
        .vy-unitpill.is-checked { border-color: var(--vy-accent-border); color: var(--vy-accent); background: rgba(232, 112, 58, 0.08); font-weight: 600; }
        .vy-unitpill.is-checked .vy-ubox { background: var(--vy-grad); border-color: transparent; color: #fff; }

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

        /* ---- Rates & availability grid (Wave 4) ------------------------------------------------ */
        .vyr-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
        .vyr-winnav { display: inline-flex; align-items: center; gap: 6px; }
        .vyr-winlabel { font-family: var(--vy-display); font-weight: 600; font-size: 14px; padding: 0 8px; min-width: 156px; text-align: center; }
        .vyr-navbtn { width: 34px; height: 34px; display: inline-grid; place-items: center; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.6); color: var(--vy-ink); box-shadow: var(--vy-shadow-sm); text-decoration: none; }
        .vyr-navbtn:hover { background: rgba(255,255,255,.9); }
        .vyr-jump { font-size: 12.5px; color: var(--vy-accent-text); text-decoration: none; font-weight: 600; }
        /* Rates legend + bulk action — the design carries both in the CONTROL ROW, not on a separate line
           below the stat tiles. `margin-left: auto` pushes the pair to the right of the window nav and the
           room-type filter. Each swatch is followed by its WORD in the markup, so a grid state is never
           conveyed by colour alone (WCAG 1.4.1). */
        .vyr-legend { display: flex; gap: 16px; font-size: 12px; color: var(--vy-muted); margin-left: auto; }
        .vyr-legend > span { display: inline-flex; align-items: center; gap: 6px; }
        .vyr-legend-sw { width: 10px; height: 10px; border-radius: 3px; flex: none; }
        .vyr-bulk { flex: none; }
        .vyr-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .vyr-grid { border-collapse: separate; border-spacing: 0; width: 100%; min-width: 760px; }
        .vyr-grid th, .vyr-grid td { border-bottom: 1px solid var(--vy-hairline); }
        .vyr-corner, .vyr-rt { position: sticky; left: 0; z-index: 2; background: var(--vy-glass); backdrop-filter: blur(6px); text-align: left; min-width: 188px; }
        .vyr-dhead { padding: 8px 6px; text-align: center; min-width: 62px; }
        .vyr-dhead.is-weekend { background: rgba(232,112,58,.06); }
        .vyr-dweekday { font-size: 10px; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-faint); }
        .vyr-dday { font-family: var(--vy-display); font-weight: 700; font-size: 15px; color: var(--vy-ink); }
        .vyr-dmonth { font-size: 10px; color: var(--vy-muted); }
        .vyr-rt { padding: 12px 14px; }
        .vyr-rt-name { font-family: var(--vy-display); font-weight: 600; font-size: 14px; }
        .vyr-rt-sub { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; }
        .vyr-cell { padding: 6px; text-align: center; vertical-align: top; min-width: 62px; }
        .vyr-cell.is-weekend { background: rgba(232,112,58,.04); }
        .vyr-cellbtn { display: block; width: 100%; border: 1px solid transparent; border-radius: var(--vy-r-sm); background: rgba(255,255,255,.5); padding: 7px 4px; cursor: pointer; font-family: inherit; transition: border-color .12s ease, background .12s ease; }
        .vyr-cellbtn:hover { border-color: var(--vy-accent-border); background: #fff; }
        .vyr-cellbtn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 1px; }
        .vyr-cell-static { padding: 7px 4px; }
        .vyr-rate { font-family: var(--vy-display); font-weight: 600; font-size: 13px; color: var(--vy-ink); }
        .vyr-rate.is-empty { color: var(--vy-faint); font-weight: 500; }
        .vyr-avail { font-size: 10.5px; color: var(--vy-muted); margin-top: 3px; }
        .vyr-avail.is-out { color: var(--vy-accent-text); font-weight: 600; }
        .vyr-flags { display: flex; gap: 3px; justify-content: center; margin-top: 4px; min-height: 13px; }
        .vyr-flag { font-size: 9px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: 1px 5px; border-radius: var(--vy-r-pill); }
        .vyr-flag--min { background: rgba(178,106,8,.12); color: var(--vy-amber); }
        .vyr-flag--closed { background: rgba(194,65,12,.12); color: var(--vy-accent); }
        .vyr-noplan { font-size: 11.5px; color: var(--vy-faint); padding: 10px 6px; text-align: center; }
        .vyr-editor { display: grid; grid-template-columns: repeat(auto-fit, minmax(158px, 1fr)); gap: 14px; align-items: end; }
        .vyr-check { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--vy-ink); font-weight: 500; margin-bottom: 7px; }
        .vyr-soon-note { font-size: 12px; color: var(--vy-faint); margin-top: 14px; display: flex; align-items: flex-start; gap: 7px; line-height: 1.5; }

        /* ---- New reservation (Wave 4) ---------------------------------------------------------- */
        .vyn-rt { padding: 13px 0; border-top: 1px solid var(--vy-hairline); }
        .vyn-rt:first-child { border-top: none; padding-top: 2px; }
        .vyn-rt-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 9px; }
        .vyn-rt-name { font-family: var(--vy-display); font-weight: 600; font-size: 14px; }
        .vyn-rt-price { font-family: var(--vy-display); font-weight: 600; font-size: 14px; color: var(--vy-accent-text); white-space: nowrap; }
        .vyn-units { display: flex; flex-wrap: wrap; gap: 8px; }
        .vyn-unit { display: inline-flex; align-items: center; gap: 7px; padding: 8px 13px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.6); font-size: 13px; cursor: pointer; transition: border-color .12s ease, background .12s ease; }
        .vyn-unit:has(input:checked) { border-color: var(--vy-accent); box-shadow: var(--vy-shadow-accent); background: #fff; }
        .vyn-unit input { accent-color: var(--vy-accent); }
        .vyn-unpriced { font-size: 12px; color: var(--vy-faint); font-style: italic; }
        .vyn-guest { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.6); cursor: pointer; margin-bottom: 6px; }
        .vyn-guest:has(input:checked) { border-color: var(--vy-accent); background: #fff; }
        .vyn-guest input { accent-color: var(--vy-accent); }
        .vyn-guest-av { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; background: var(--vy-grad); color: #fff; font-size: 11px; font-weight: 700; flex: none; }
        .vyn-empty { font-size: 12.5px; color: var(--vy-muted); padding: 8px 0; }
        .vyn-tabs { display: inline-flex; gap: 4px; padding: 4px; background: rgba(255,255,255,.45); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md); margin-bottom: 12px; }
        .vyn-tab { padding: 7px 14px; border-radius: var(--vy-r-sm); font-size: 12.5px; font-weight: 600; color: var(--vy-muted); cursor: pointer; border: none; background: none; }
        .vyn-tab.is-on { background: #fff; color: var(--vy-accent); box-shadow: var(--vy-shadow-sm); }
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
</body>
</html>
