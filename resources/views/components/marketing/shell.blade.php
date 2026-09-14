{{-- THE MARKETING SHELL (front-website Gate 1) — Vyrobel's SIXTH shell, alongside public/guest/owner/
     operator/admin. ⚠️ DELIBERATELY SEPARATE from components/public/shell.blade.php: that is the sealed,
     money-verified STOREFRONT's chrome (deliberately no nav, no large footer) and is never edited for a
     cosmetic reason (E3). This shell serves the marketing site only.

     ⚠️ ONE PALETTE (D6/E4, §N.8f): <x-design.tokens /> is the colour source — the marketing CSS below binds
     var(--vy-*) and copies NO design hex. The design's divergent tones are NOT adopted: #4A403A → var(--vy-muted),
     #A8380B → var(--vy-accent-text), the icon-stroke #B4400F → var(--vy-accent-text), the logo cream
     #FFF7F0 → #fff (the app's own brand-mark precedent). Structural recipe values (glass alphas, radii,
     shadow geometry) are shell-scoped, exactly as every other shell does.

     ⚠️ SELF-HOSTED FONTS ONLY (/fonts/fonts.css — the F6-6 no-third-party-request GDPR posture). The
     design's Google-Fonts CDN link and its React/unpkg runtime do NOT survive the port (E9); this is
     server-rendered Blade, CSS-native interactivity, Alpine later if ever needed.

     Props: the SEO head is REQUIRED — a marketing page without title/description/canonical is an SEO
     regression by construction, so the shell takes them rather than hoping each page remembers. --}}
@props([
    'title',
    'description',
    'canonical',
    'ogType' => 'website',
    'ogImage' => null,
    'index' => true,
    'alternates' => [],
])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-marketing.seo :title="$title" :description="$description" :canonical="$canonical"
                     :og-type="$ogType" :og-image="$ogImage" :index="$index" :alternates="$alternates" />

    {{-- Self-hosted Inter + Sora — no third-party font request (F6-6). --}}
    <link href="/fonts/fonts.css" rel="stylesheet">

    {{-- ⚠️ THE CANONICAL PALETTE FIRST — the shared tokens declared once (§N.8f); the shell's own
         structural recipe follows and may deliberately override, same as every other shell. --}}
    <x-design.tokens />
    <style>
        html { scroll-behavior: smooth; }
        /* Visually hidden, still announced — the cookie table's caption uses it (Gate 6). */
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: var(--vy-sans); color: var(--vy-ink); background: var(--vy-bg); background-attachment: fixed; min-height: 100vh; }
        a { color: var(--vy-accent-text); }
        :focus-visible { outline: 2.5px solid var(--vy-accent); outline-offset: 3px; border-radius: 8px; }

        /* ⚠️⚠️ F-11 — THE SKIP LINK WAS PERMANENTLY INVISIBLE, AND THAT IS WORSE THAN ABSENT.
           The design parks it at `left: -9999px` and never brings it back: MEASURED in a browser, its
           bounding box was left=-9999 before focus AND left=-9999 after focus. A keyboard user could tab
           to it, saw nothing (the focus ring is off-screen too), and had no way to know it existed. It
           still WORKED if activated blind, which is why no test and no reading caught it.
           ⚠️ The link itself now lives in this shell, above the nav — see the body below for why. */
        .vy-mk-skip { position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px;
            border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600;
            text-decoration: none; }
        .vy-mk-skip:focus { left: 12px; }

        /* ⚠️⚠️ F-9 — THE SELECTED TAB WAS INDISTINGUISHABLE FROM THE UNSELECTED ONES.
           The audit reported the hour rail's BORDER at 1.02:1 and asked whether that mattered on what was
           then a decorative strip. Re-measured now that it is a working tab group, the answer is worse
           than the finding: selected vs unselected measured **1.00:1** — identical background, identical
           border, identical colour, identical weight. `marketing.js` moves `aria-selected` and the roving
           tabindex correctly and shows the right panel, so a screen-reader user is served properly; a
           SIGHTED user could see the panel change but not which control was active.
           ⚠️ WHY IT HAPPENED: the design set the selected styling IMPERATIVELY (`b.style.background = on
           ? '<a two-stop accent gradient>' : …`). The converter strips the runtime and keeps
           the markup, so the page shipped permanently in the RESTING style — which is exactly what the
           measurement found (`rgba(255,252,248,.55)`, weight 500, no shadow).
           ⚠️ THE INTENT IS RECOVERED FROM THE MASTER; THE COLOURS ARE THE PROJECT'S TOKENS, AND THE
           DIFFERENCE IS DELIBERATE. The design's handler painted a two-stop accent gradient with an
           off-white label — but its DARK STOP and that OFF-WHITE are both on the REFUSED-TONE list (D6/E4),
           and `MarketingCorePagesTest` reds on either. ⚠️ It caught my first attempt, which pasted them.
           ⚠️⚠️ AND THEN IT CAUGHT ME AGAIN, FOR A BETTER REASON: my first version of THIS COMMENT quoted
           the two refused hex values while explaining why they are not used — and a CSS comment SHIPS, so
           the page carried them and the guard was right to red. A refused value may not appear on the page
           even inside an apology for not using it (the canon's "never negate a claim by quoting it").
           ⚠️ NOR IS `var(--vy-grad)` THE ANSWER, although it is the sanctioned gradient token: 🔬 white on
           its LIGHT stop (`#E8703A`) measures **3.08:1**, under the 4.5:1 AA floor for 13px text. A
           token-bound rule that fails the contrast it exists to fix would be the worse trade.
           ⚠️ SO: the SOLID accent token, measured — 🔬 fill 5.06:1 against the page, label 5.18:1 on the
           fill, plus weight 600 and a shadow, so the state has three cues and not one.
           ⚠️ `!important` because the design's styles are INLINE on each control; the shell's own
           responsive rules already override them the same way. */
        [data-hourbtn][aria-selected="true"], [data-stagebtn][aria-selected="true"],
        [data-lensbtn][aria-selected="true"], [data-modchip][aria-pressed="true"],
        [data-cyclebtn][aria-checked="true"] {
            background: var(--vy-accent) !important;
            color: #fff !important;
            border-color: var(--vy-accent) !important;
            font-weight: 600 !important;
            box-shadow: 0 8px 20px rgba(194, 65, 12, .28) !important;
        }
        /* ⚠️ AND THE RESTING BORDER, which is the finding as originally written. `rgba(255,255,255,.8)`
           over the cream page measured 1.02:1 — the control's edge cannot be seen at all. WCAG 1.4.11
           asks 3:1 for the boundary of a control, and these are controls a user must find to operate.
           ⚠️ NOT applied to `[data-lensbtn]`: those ship `border: none` by design and are not in this set. */
        [data-hourbtn], [data-stagebtn], [data-modchip], [data-cyclebtn] {
            border-color: rgba(140, 82, 45, .7) !important;   /* 🔬 3.22:1 — measured, not chosen by eye */
        }
        @keyframes vyShimmer { 0% { opacity: .2; transform: translateX(-120%) skewX(-18deg); } 55% { opacity: .5; } 100% { opacity: .2; transform: translateX(220%) skewX(-18deg); } }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } .vy-mk-shimmer { animation: none !important; } }

        /* ── Header — the design's fixed glass bar (74px) ─────────────────────────────────────── */
        .vy-mk-header { position: fixed; top: 0; left: 0; right: 0; z-index: 400; background: rgba(255, 252, 248, 0.34); backdrop-filter: blur(14px) saturate(1.1); -webkit-backdrop-filter: blur(14px) saturate(1.1); border-bottom: 1px solid rgba(255, 255, 255, 0.4); }
        .vy-mk-nav { max-width: 1240px; margin: 0 auto; padding: 0 28px; height: 74px; display: flex; align-items: center; gap: 26px; position: relative; }
        .vy-mk-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; color: var(--vy-ink); flex-shrink: 0; }
        .vy-mk-logo-mark { position: relative; width: 34px; height: 34px; border-radius: 11px; background: var(--vy-grad); box-shadow: var(--vy-shadow-accent); display: flex; align-items: center; justify-content: center; overflow: hidden; font-family: var(--vy-display); font-weight: 700; font-size: 17px; color: #fff; letter-spacing: -.02em; }
        .vy-mk-shimmer { position: absolute; top: 0; left: 0; width: 40%; height: 100%; background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.75), rgba(255,255,255,0)); animation: vyShimmer 4.5s ease-in-out infinite; }
        .vy-mk-logo-name { font-family: var(--vy-display); font-weight: 600; font-size: 19px; letter-spacing: -.02em; }

        /* ── Top-level links + dropdowns ──────────────────────────────────────────────────────── */
        .vy-mk-links { display: flex; align-items: center; gap: 4px; margin-left: 10px; flex: 1; }
        .vy-mk-navlink { display: inline-flex; align-items: center; gap: 5px; padding: 9px 13px; border-radius: 11px; font-size: 14px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: background .2s ease; }
        a.vy-mk-navlink:hover, .vy-mk-drop:hover > .vy-mk-navlink, .vy-mk-drop:focus-within > .vy-mk-navlink { background: rgba(255,255,255,.62); box-shadow: var(--vy-shadow-sm); }
        .vy-mk-caret { opacity: .55; }
        .vy-mk-drop { position: relative; }
        .vy-mk-mega { position: absolute; top: calc(100% + 10px); left: -14px; min-width: 720px; padding: 22px; border-radius: 20px; background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow); opacity: 0; visibility: hidden; transform: translateY(6px); transition: opacity .22s ease, transform .22s ease, visibility 0s linear .22s; }
        .vy-mk-drop:hover .vy-mk-mega, .vy-mk-drop:focus-within .vy-mk-mega { opacity: 1; visibility: visible; transform: none; transition-delay: 0s; }
        .vy-mk-mega--solutions { min-width: 560px; }
        .vy-mk-megagrid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .vy-mk-megagrid--2 { grid-template-columns: 1fr 1fr; }
        .vy-mk-megalabel { font-size: 10.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 9px; }
        .vy-mk-megarow { display: flex; align-items: flex-start; gap: 10px; padding: 8px 9px; border-radius: 11px; text-decoration: none; color: var(--vy-ink); transition: background .18s ease, transform .18s ease; }
        a.vy-mk-megarow:hover { background: rgba(217,102,46,.1); transform: translateX(2px); }
        .vy-mk-megaicon { width: 28px; height: 28px; flex-shrink: 0; border-radius: 9px; background: rgba(217,102,46,.12); display: grid; place-items: center; color: var(--vy-accent-text); }
        .vy-mk-megatext { display: flex; flex-direction: column; gap: 1px; }
        .vy-mk-megatext span:first-child { font-size: 13.5px; font-weight: 600; }
        .vy-mk-megatext span:last-child { font-size: 12px; color: var(--vy-muted); }
        .vy-mk-megasmall { display: flex; flex-direction: column; gap: 1px; padding: 7px 9px; border-radius: 10px; text-decoration: none; color: var(--vy-ink); }
        a.vy-mk-megasmall:hover { background: rgba(217,102,46,.1); }
        .vy-mk-megasmall span:first-child { font-size: 13px; font-weight: 600; }
        .vy-mk-megasmall span:last-child { font-size: 11.5px; color: var(--vy-muted); }
        .vy-mk-megafeature { border-radius: 14px; padding: 14px; background: rgba(255,255,255,.5); border: 1px solid var(--vy-glass-border); }
        .vy-mk-megafeature-title { font-family: var(--vy-display); font-weight: 600; font-size: 14.5px; margin-top: 2px; }
        .vy-mk-megafeature-desc { font-size: 12px; color: var(--vy-muted); line-height: 1.5; margin: 6px 0 10px; }
        .vy-mk-megafeature-cta { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none; }
        .vy-mk-megastrip { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--vy-hairline); }
        .vy-mk-megastrip-head { display: flex; align-items: center; justify-content: space-between; font-size: 11px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 8px; }
        .vy-mk-megastrip-head a, .vy-mk-megastrip-head span[data-vy-pending] { font-size: 12px; letter-spacing: 0; text-transform: none; color: var(--vy-accent-text); text-decoration: none; }
        .vy-mk-megaguides { display: flex; flex-wrap: wrap; gap: 7px; }
        .vy-mk-megaguide { font-size: 12.5px; padding: 6px 11px; border-radius: var(--vy-r-pill); background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); color: var(--vy-ink); text-decoration: none; }
        a.vy-mk-megaguide:hover { background: rgba(217,102,46,.1); }
        .vy-mk-megastrip--cta { display: flex; align-items: center; justify-content: space-between; gap: 16px; font-size: 12.5px; color: var(--vy-muted); }
        .vy-mk-megacta { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-size: 12.5px; font-weight: 600; text-decoration: none; box-shadow: var(--vy-shadow-accent); white-space: nowrap; }

        /* ── CTAs ─────────────────────────────────────────────────────────────────────────────── */
        .vy-mk-ctas { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .vy-mk-login { padding: 9px 15px; border-radius: var(--vy-r-pill); background: rgba(255,255,255,.6); border: 1px solid var(--vy-glass-border); font-size: 13.5px; font-weight: 600; color: var(--vy-ink); text-decoration: none; transition: transform .18s ease, box-shadow .18s ease; }
        .vy-mk-login:hover { transform: translateY(-1px); box-shadow: var(--vy-shadow-sm); }
        .vy-mk-signup { padding: 9px 17px; border-radius: var(--vy-r-pill); background: var(--vy-grad); font-size: 13.5px; font-weight: 600; color: #fff; text-decoration: none; box-shadow: var(--vy-shadow-accent); transition: transform .18s ease, box-shadow .18s ease, filter .18s ease; }
        .vy-mk-signup:hover { transform: translateY(-2px); filter: saturate(1.08); }

        /* ── Pending items (Gate 2+ lights them up) ───────────────────────────────────────────── */
        [data-vy-pending] { cursor: default; opacity: .55; }

        /* ── Mobile — checkbox toggle, no JS ──────────────────────────────────────────────────── */
        .vy-mk-menu-box { display: none; }
        .vy-mk-burger { display: none; margin-left: auto; width: 40px; height: 40px; border-radius: 11px; background: rgba(255,255,255,.6); border: 1px solid var(--vy-glass-border); place-items: center; color: var(--vy-ink); cursor: pointer; }
        .vy-mk-mobile { display: none; }
        @media (max-width: 960px) {
            .vy-mk-links, .vy-mk-ctas { display: none; }
            .vy-mk-burger { display: grid; }
            .vy-mk-mobile { position: fixed; top: 74px; left: 0; right: 0; bottom: 0; overflow-y: auto; padding: 18px 22px 40px; background: rgba(255,252,248,.96); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); flex-direction: column; gap: 18px; }
            .vy-mk-menu-box:checked ~ .vy-mk-mobile { display: flex; }
            .vy-mk-mgroup { display: flex; flex-direction: column; gap: 2px; padding-bottom: 12px; border-bottom: 1px solid var(--vy-hairline); }
            .vy-mk-mlabel { font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint); padding: 6px 12px 4px; }
            .vy-mk-mrow, .vy-mk-msub { padding: 11px 12px; border-radius: 11px; font-size: 15px; font-weight: 600; color: var(--vy-ink); text-decoration: none; }
            .vy-mk-msub { font-weight: 500; font-size: 14px; color: var(--vy-muted); padding-left: 22px; }
            a.vy-mk-mrow:hover, a.vy-mk-msub:hover { background: rgba(217,102,46,.09); }
            .vy-mk-mobile-ctas { display: flex; gap: 10px; }
        }

        /* ── Image placeholders (D8 open — photography pending, never a stock photo) ─────────── */
        .vy-mk-slot { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 7px; width: 100%; height: 100%; min-height: 130px; padding: 18px; box-sizing: border-box; text-align: center; background: repeating-linear-gradient(135deg, rgba(255,252,248,.72), rgba(255,252,248,.72) 11px, rgba(240,165,122,.16) 11px, rgba(240,165,122,.16) 22px); border: 1px dashed rgba(194,65,12,.34); }
        .vy-mk-slot-mark { color: var(--vy-accent-text); opacity: .8; }
        .vy-mk-slot-brief { font-size: 12.5px; font-weight: 600; color: var(--vy-ink); max-width: 240px; line-height: 1.45; }
        .vy-mk-slot-note { font-size: 10.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-faint); }

        /* ── The deferred commission calculator (honest, not a fake number) ───────────────────── */
        .vy-mk-roi { display: flex; gap: 16px; align-items: flex-start; padding: 24px 26px; border-radius: 22px; background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); }
        .vy-mk-roi-mark { width: 38px; height: 38px; flex-shrink: 0; border-radius: 12px; background: rgba(217,102,46,.12); display: grid; place-items: center; color: var(--vy-accent-text); }
        .vy-mk-roi-title { font-family: var(--vy-display); font-weight: 600; font-size: 17px; letter-spacing: -.02em; color: var(--vy-ink); }
        .vy-mk-roi-body { margin: 8px 0 12px; font-size: 13.5px; line-height: 1.6; color: var(--vy-muted); max-width: 560px; }
        .vy-mk-roi-cta { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none; }

        /* ── Page body ────────────────────────────────────────────────────────────────────────── */
        .vy-mk-main { position: relative; padding-top: 74px; min-height: 60vh; }

        /* ── Footer ───────────────────────────────────────────────────────────────────────────── */
        .vy-mk-footer { font-family: var(--vy-sans); color: var(--vy-ink); padding: 0 24px 40px; max-width: 1240px; margin: 40px auto 0; }
        .vy-mk-whatsnew { margin-bottom: 26px; }
        .vy-mk-whatsnew-link { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; padding: 13px 18px; border-radius: 16px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); text-decoration: none; color: var(--vy-ink); font-size: 13px; }
        .vy-mk-whatsnew-pill { display: inline-flex; align-items: center; gap: 7px; padding: 4px 11px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-size: 11px; font-weight: 700; letter-spacing: .04em; }
        .vy-mk-whatsnew-dot { width: 6px; height: 6px; border-radius: 50%; background: #fff; }
        .vy-mk-whatsnew-cta { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--vy-accent-text); }
        .vy-mk-footgrid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr; gap: 28px; padding: 26px 0; border-top: 1px solid var(--vy-hairline); }
        /* ⚠️⚠️ F-5 — THE 13px OF HORIZONTAL OVERFLOW ON ALL 19 PAGES WAS THIS ONE LINE.
           A `1fr` grid track is `minmax(auto, 1fr)`, and that `auto` MINIMUM is the track's min-content
           size — a floor `1fr` cannot go below. The first footer column's min-content is ~243px, so at
           375px the two tracks plus the 28px gap plus the footer's padding measured 388px against a 375px
           viewport: exactly the 13px. ⚠️ MEASURED, NOT REASONED: neutralising this grid in a live browser
           took document.scrollWidth from 388 to 375; hiding the header and hiding the cookie banner each
           left it at 388.
           ⚠️ THE HEADER AND THE COOKIE BANNER WERE FOLLOWERS, NOT CAUSES. Both are `position: fixed` with
           `left:0; right:0`, so once something else widened the scrollable area they stretched to it and
           measured 388 too — which is why a reader looking at the rendered page blames the header.
           ⚠️ AND THE 560px COOKIE TABLE IS NOT THE CAUSE EITHER, despite extending 225px past the viewport:
           it sits in an `overflow-x: auto` wrapper, and a scroll container does not widen its page. */
        @media (max-width: 960px) { .vy-mk-footgrid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
        .vy-mk-footbrand p { font-size: 13px; line-height: 1.6; color: var(--vy-muted); max-width: 300px; margin: 14px 0 18px; }
        .vy-mk-footcol { display: flex; flex-direction: column; gap: 8px; }
        .vy-mk-footcol a, .vy-mk-footcol span[data-vy-pending] { font-size: 13px; color: var(--vy-muted); text-decoration: none; }
        .vy-mk-footcol a:hover { color: var(--vy-accent-text); }
        .vy-mk-footlabel { font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 4px; }
        .vy-mk-guestlink { font-size: 12.5px !important; }
        .vy-mk-notes-label { font-size: 11px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 8px; }
        .vy-mk-notes-row { display: flex; gap: 8px; }
        .vy-mk-notes-row input { flex: 1; min-width: 0; font-family: var(--vy-sans); font-size: 13px; padding: 10px 13px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.7); color: var(--vy-ink); }
        .vy-mk-notes-row button { padding: 10px 16px; border-radius: 12px; border: none; background: var(--vy-grad); color: #fff; font-family: var(--vy-sans); font-size: 13px; font-weight: 600; }
        .vy-mk-notes-row input:disabled, .vy-mk-notes-row button:disabled { opacity: .55; cursor: not-allowed; }
        .vy-mk-notes-soon { font-size: 11.5px; color: var(--vy-faint); margin-top: 7px; }
        .vy-mk-legal { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-top: 18px; border-top: 1px solid var(--vy-hairline); font-size: 12px; color: var(--vy-faint); }
        .vy-mk-legal-links { display: inline-flex; gap: 16px; }
        .vy-mk-legal-links a, .vy-mk-legal-links span[data-vy-pending] { color: var(--vy-faint); text-decoration: none; }
        .vy-mk-legal-links a:hover { color: var(--vy-accent-text); }

        /* ── The contact form's chips (Gate 7) ─────────────────────────────────────────────────
           ⚠️ RADIOS STYLED AS CHIPS, not buttons. The design drew these as <button>s whose selection lived
           in React state — which posts nothing. A real radio posts its value, is keyboard-navigable with
           the arrow keys and announces its group to a screen reader, all of which a styled button would
           have to reimplement badly. The input is hidden visually but never with display:none, which would
           take it out of the focus order. */
        /* ══ ⚠️⚠️ THE DESIGN'S OWN RESPONSIVE RULES, RESTORED FOR EVERY PAGE (Gate 7) ═══════════════
           ⚠️⚠️ ALL NINETEEN BUILT MARKETING PAGES HAD LOST THESE, AND NOBODY NOTICED FOR SIX GATES. The
           prototypes are inline-styled throughout — but their RESPONSIVE behaviour lives in a stylesheet
           block, and the converter strips stylesheet and script blocks alike. So every page kept the
           design's hooks (`data-h1` and `data-sec` are on all 19; `data-two` on 9; `data-bandinner` on 8)
           and nothing read a single one of them. A two-column layout drawn as `1.35fr .85fr` stayed two
           columns at 390px, with the contact card squeezed to 241px and the right rail beside it.

           ⚠️ THE TAG NAMES ABOVE ARE DELIBERATELY SPELLED OUT AS WORDS. A CSS comment SHIPS to the browser,
           unlike a Blade one — so writing them in angle brackets put literal markup-looking text into every
           marketing page, and an HTML-scanning test then opened a match on it and swallowed the page body.
           §N.27.5, in a stylesheet: a guard reads whatever the page says, including prose about markup.

           ⚠️ THESE ARE THE DESIGN'S OWN RULES, extracted from the 19 masters' stylesheets and deduplicated
           — 114 declarations across 10 breakpoints. Not one is invented here. The `!important` markers are
           the design's too, and they are load-bearing: the columns they override are declared in an inline
           style, which no class or attribute selector can outrank.

           ⚠️ ORDERED WIDEST-FIRST so a narrower breakpoint wins, and every condition is `max-width`, so
           nothing here can change a desktop rendering.

           ⚠️ FOUND BY TAKING A SCREENSHOT AT A PHONE WIDTH — not by any assertion, and not by the earlier
           check that measured `document.scrollWidth` and reported no overflow. It reported none because a
           grid does not overflow: it dutifully squeezes its columns to fit. ⚠️ LAYOUT DOES NOT FAIL LOUDLY,
           which is exactly why the render step of this gate exists. */
        @media (max-width: 1040px) {
            [data-fgrid] { grid-template-columns: 1fr 1fr !important; }
            [data-jump] { position: static !important; }
            [data-mgrid] { grid-template-columns: 1fr 1fr !important; }
            [data-plans] { grid-template-columns: 1fr 1fr !important; }
        }
        @media (max-width: 1020px) {
            [data-fwrap] { grid-template-columns: 1fr !important; gap: 26px !important; }
            [data-fcol] { position: relative !important; top: auto !important; height: 500px !important; }
            [data-fstep] { min-height: 0 !important; padding: 22px 0 !important; }
            [data-day-card] { left: 0 !important; bottom: -20px !important; }
            [data-hero] { grid-template-columns: 1fr !important; gap: 46px !important; }
            [data-split] { grid-template-columns: 1fr !important; }
            [data-split-rev] { grid-template-columns: 1fr !important; }
            [data-trio] { grid-template-columns: 1fr !important; }
            [data-plans] { grid-template-columns: 1fr !important; }
            [data-gallery] { grid-template-columns: 1fr 1fr !important; grid-template-rows: 300px 300px !important; }
            [data-gallery] > div:first-child { grid-column: span 2 !important; height: 100% !important; min-height: 0 !important; }
            [data-gallery] > div:not(:first-child) { height: 100% !important; min-height: 0 !important; display: grid !important; grid-template-columns: 1fr 1fr !important; grid-template-rows: 1fr !important; gap: 14px !important; }
            [data-gallery] > div > div { height: auto !important; min-height: 0 !important; }
            [data-morphwrap] { grid-template-columns: 1fr !important; gap: 24px !important; }
            [data-morphtrack] { position: static !important; }
            [data-morphcol] { position: relative !important; top: auto !important; height: 580px !important; }
            [data-morphstep] { min-height: auto !important; padding: 26px 0 !important; }
            [data-split-rev] > * { order: 0 !important; }
        }
        @media (max-width: 1000px) {
            [data-story] { grid-template-columns: 1fr !important; }
            [data-story] [data-side] { order: 2; }
            [data-three] { grid-template-columns: 1fr !important; }
            [data-quad] { grid-template-columns: 1fr !important; }
            [data-two] { grid-template-columns: 1fr !important; }
            [data-shifts] { grid-template-columns: 1fr !important; }
            [data-ownernotes] { grid-template-columns: 1fr !important; }
            [data-yeargrid] { grid-template-columns: repeat(6, 1fr) !important; }
            [data-book] { grid-template-columns: 1fr !important; }
            [data-summary] { position: static !important; }
        }
        @media (max-width: 980px) {
            [data-two] { grid-template-columns: 1fr !important; }
            [data-trio] { grid-template-columns: 1fr !important; }
            [data-team] { grid-template-columns: 1fr 1fr !important; }
            [data-clayout] { grid-template-columns: 1fr !important; }
            [data-frow] { grid-template-columns: 1fr !important; }
            [data-steprow] { grid-template-columns: 1fr !important; gap: 24px !important; }
            [data-steprow] [data-stepart] { order: 2; }
            [data-igrid] { grid-template-columns: 1fr 1fr !important; }
            [data-sgrid] { grid-template-columns: 1fr 1fr !important; }
            {{-- ⚠️ `[data-estwrap]` REMOVED with the /switching migration estimator it stacked. A deletion
                 is not finished until the rules that depended on it go too — the same residue class as the
                 currency copy that outlived its switcher (D8), and the second time this shell has needed
                 the follow-up read. Nothing else in the site carries that attribute. --}}
            [data-split] { grid-template-columns: 1fr !important; }
            [data-timeline] { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 860px) {
            [data-stats] { grid-template-columns: 1fr 1fr !important; }
            [data-steps] { grid-template-columns: 1fr !important; }
            [data-quotes] { grid-template-columns: 1fr !important; }
            [data-flowrow] { flex-direction: column !important; gap: 0 !important; }
            [data-flowarrow] { width: 100% !important; padding: 14px 0 !important; flex-direction: row !important; gap: 12px !important; justify-content: center !important; }
            [data-flowarrow] svg { transform: rotate(90deg); width: 46px !important; }
            [data-gallery] { grid-template-columns: 1fr 1fr !important; grid-template-rows: 200px 340px !important; }
            [data-gallery] > div { height: 100% !important; min-height: 0 !important; }
            [data-gallery] > div > div { height: auto !important; min-height: 0 !important; }
            [data-gallery] > div:not(:first-child) { display: grid !important; grid-template-columns: 1fr !important; grid-template-rows: 1fr 1fr !important; gap: 12px !important; }
            [data-gallery] > div > div { grid-row: auto !important; }
            [data-gallery] > div:first-child { grid-row: span 1 !important; grid-column: span 2 !important; }
            [data-mockrail] { display: none !important; }
            [data-mockstats] { grid-template-columns: repeat(3, 1fr) !important; }
            [data-mockstats] > div:nth-child(n+4) { display: none !important; }
            [data-mockbody] { grid-template-columns: 1fr !important; }
            [data-h1] { font-size: 44px !important; }
            [data-sec] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-h1] { font-size: 42px !important; }
            [data-sec]:has([data-bandinner]) { padding-left: 0 !important; padding-right: 0 !important; }
            [data-bandinner] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-probs] { grid-template-columns: 1fr !important; }
            [data-facts] { grid-template-columns: 1fr 1fr !important; }
            [data-two] { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 760px) {
            /* ⚠️ THE CARD GRID COLLAPSES HERE. `[data-plans]` — the design's own grid container — no
               longer exists on the page: the converter replaces that whole element with
               <x-marketing.plan-cards />, so the rules below it target nothing. The component now owns
               the grid and this collapses it to one column on a phone, at an EXISTING breakpoint. */
            .vy-plan-grid { grid-template-columns: minmax(0, 1fr) !important; }
            [data-plans] { grid-template-columns: 1fr !important; }
            [data-cmpscroll] { overflow-x: auto; }
            [data-cmp] thead th { position: static !important; }
            [data-h1] { font-size: 40px !important; }
            [data-sec] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-sec]:has([data-bandinner]) { padding-left: 0 !important; padding-right: 0 !important; }
            [data-bandinner] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-cmp] { font-size: 12px !important; }
        }
        @media (max-width: 720px) {
            [data-fgrid] { grid-template-columns: 1fr !important; }
            [data-h1] { font-size: 40px !important; }
            [data-sec] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-sec]:has([data-bandinner]) { padding-left: 0 !important; padding-right: 0 !important; }
            [data-bandinner] { padding-left: 20px !important; padding-right: 20px !important; }
        }
        @media (max-width: 700px) {
            [data-h1] { font-size: 40px !important; }
            [data-sec] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-sec]:has([data-bandinner]) { padding-left: 0 !important; padding-right: 0 !important; }
            [data-bandinner] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-mgrid] { grid-template-columns: 1fr !important; }
            [data-incl] { grid-template-columns: 1fr !important; }
            [data-stagerail] { gap: 6px !important; }
            [data-stagebtn] { padding: 9px 13px !important; }
            [data-h1] { font-size: 34px !important; }
            [data-pn] { flex-direction: column !important; }
            [data-pull] { font-size: 20px !important; padding-left: 20px !important; }
            [data-facts] { grid-template-columns: 1fr !important; }
            [data-h1] { font-size: 38px !important; }
            [data-step] { flex-direction: column !important; gap: 14px !important; }
            [data-stepnum] { margin-bottom: 0 !important; }
        }
        @media (max-width: 640px) {
            [data-team] { grid-template-columns: 1fr !important; }
            [data-h1] { font-size: 40px !important; }
            [data-sec] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-h1] { font-size: 38px !important; }
            [data-nums] { grid-template-columns: 1fr 1fr !important; }
            [data-sec]:has([data-bandinner]) { padding-left: 0 !important; padding-right: 0 !important; }
            [data-bandinner] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-nums] { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 620px) {
            [data-fcol] { height: 540px !important; }
            [data-morphcol] { top: auto !important; }
            [data-modgrid] { grid-template-columns: 1fr 1fr !important; }
            [data-h1] { font-size: 36px !important; }
            [data-igrid] { grid-template-columns: 1fr !important; }
            [data-h1] { font-size: 38px !important; }
            [data-sec] { padding-left: 20px !important; padding-right: 20px !important; }
            [data-sgrid] { grid-template-columns: 1fr !important; }
            [data-h1] { font-size: 34px !important; }
            [data-sec] { padding-left: 18px !important; padding-right: 18px !important; }
        }

        /* ══ ⚠️⚠️ THE PRINT ARTEFACT — RESTORED HERE, BECAUSE THE CONVERTER CANNOT CARRY IT ═══════════
           ⚠️ THE DESIGN SHIPS A `<style media="print">` BLOCK, and `helmet()` consumes every <style> in
           the master before the body is even extracted — so these rules have never reached a generated
           page. The button worked and the ARTEFACT was broken: "Print this story" produced a document
           with the navigation bar, the footer, the decorative orbs and the cookie notice on it, and
           WITHOUT the letterhead — because `[data-printhead]` and `[data-printfoot]` ship
           `display: none` inline and the only rule that reveals them was in that stripped block.
           ⚠️ THEY ARE TWO MORE HIDDEN PANES, and they belong on the hidden-pane census beside the eight
           the hour rail once had: served, paid for in bytes, unreachable by anyone.
           ⚠️ THE SHELL IS THE RIGHT HOME. There is nothing to "preserve through conversion" — the block
           is gone before the converter sees the body — and print rules are global: four pages carry
           print elements today and any page can be printed with Cmd-P, so a per-page copy would be four
           copies of one rule (§N.8f). The design's page-specific print cosmetics are NOT reproduced;
           what is here is the artefact contract, which is what makes a printed page a document. */
        @media print {
            @page { size: A4; margin: 15mm 14mm; }
            html, body, [data-screen-label] { background: #fff !important; }

            /* ⚠️ WHAT A PRINTED PAGE MUST NOT CONTAIN: chrome, decoration and anything clickable. A story
               printed with a nav bar and a consent notice is not a document anyone keeps. */
            .vy-mk-header, .vy-mk-footer, footer, .vy-mk-skip, .vy-mk-burger, .vy-mk-menu-box,
            .vy-mk-mobile, [data-orb], [data-inpagenav], [data-noprint],
            section[aria-labelledby^="vy-cookie-h-"] { display: none !important; }
            /* ⚠️ `[data-vy-rail]` WAS IN THIS LIST AND HID THE WHOLE DOCUMENT. The design's block hides
               `#vy-rail` — an id, a scroll-progress element that does not exist in these pages — and I
               transcribed it as the ATTRIBUTE, which sits on each page's ROOT WRAPPER div. In print the
               letterhead stayed hidden not because its own rule failed (it computed `display: block`
               correctly) but because an ancestor was gone. ⚠️ Only emulating print media showed it;
               reading the stylesheet showed a rule that looked right. */

            /* ⚠️ AND WHAT IT MUST CONTAIN: the letterhead and the footer line, which exist on the page
               already and are revealed only here. */
            [data-printhead], [data-printfoot] { display: block !important; }

            /* ⚠️ Effects that cost ink and mean nothing on paper; and `[data-reveal]` must be settled,
               since its entrance animation may never have run before the print dialog opened. */
            * { backdrop-filter: none !important; -webkit-backdrop-filter: none !important;
                box-shadow: none !important; animation: none !important; transition: none !important; }
            [data-reveal] { opacity: 1 !important; transform: none !important; }

            main, .vy-mk-main { padding-top: 0 !important; }
            [data-sec] { padding: 0 0 10pt !important; max-width: none !important; margin: 0 !important; }
            h1 { font-size: 20pt !important; margin-bottom: 4pt !important; }
            h2 { font-size: 12.5pt !important; page-break-after: avoid; margin-bottom: 4pt !important; }
            h3 { font-size: 10.5pt !important; }
            p, li { font-size: 9pt !important; line-height: 1.45 !important; }
        }

        .vy-chip { display: inline-flex; cursor: pointer; }
        .vy-chip input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .vy-chip span { display: inline-block; padding: 10px 15px; border-radius: 11px; border: 1px solid rgba(150,90,50,.2); background: rgba(255,255,255,.6); font-family: var(--vy-sans); font-size: 13px; color: var(--vy-ink); transition: all .2s ease; }
        .vy-chip input:checked + span { border-color: var(--vy-accent); background: rgba(217,102,46,.12); color: var(--vy-accent-text); font-weight: 600; }
        .vy-chip input:focus-visible + span { outline: 2.5px solid var(--vy-accent); outline-offset: 3px; }
        @media (prefers-reduced-motion: reduce) { .vy-chip span { transition: none; } }
            /* ⚠️⚠️ DEFINED, NOT ASSUMED. A visually-hidden label whose class is undefined does not hide
           anything — it renders as stray text on the page. This shell had no `.vy-sr-only` while the
           operator, owner and guest shells each define it, so a label placed here would have SHOWN.
           Copied verbatim from those three: separate documents, one rule. */
        .vy-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>
    {{ $head ?? '' }}
</head>
<body>
    {{-- ⚠️⚠️ F-11 — THE SKIP LINK BELONGS HERE, ABOVE THE NAV, AND IT DID NOT USED TO BE.
         It was emitted INSIDE each page body, which the shell renders inside `<main>` — after the whole
         navigation. MEASURED in a browser: it was the THIRD tab stop on every page checked (logo → the
         burger → skip), which defeats the one thing a skip link is for. Shell-wide, not page-specific:
         the same index on `/` and on a scenario page.
         ⚠️ The burger ahead of it is NOT a defect and was not moved — the audit called it "an empty
         `<label>`", but its text content is empty only because it wraps an SVG: it carries
         `role="button"`, `aria-label="Open menu"` and `tabindex="0"`, and is correctly built.
         ⚠️ It is REMOVED from the 19 generated pages in the same change (see convert.py), because two
         skip links would be worse than one in the wrong place. --}}
    <a class="vy-mk-skip" href="#vy-main">Skip to content</a>
    <x-marketing.nav />
    <main class="vy-mk-main">
        {{ $slot }}
    </main>
    <x-marketing.footer />
    {{-- ⚠️ THE COOKIE NOTICE (Gate 6, D11) — rendered LAST in the document so a keyboard or screen-reader
         user reaches the page's own content first and meets the notice at the end, rather than being
         intercepted by it. It is fixed to the bottom visually but late in the DOM deliberately, and it
         traps nothing: the page stays fully operable with the notice ignored.
         ⚠️ Shown only until the visitor answers THIS version of the disclosure. --}}
    @if (\App\Support\Privacy\CookieConsent::shouldPrompt(request()))
        <x-marketing.cookie-notice mode="banner" />
    @endif

    {{-- ⚠️⚠️ THE MARKETING SITE'S ONLY SCRIPT (D1/D6). It ENHANCES; it never renders content.
         Every pane is present and visible in the HTML above — the converter strips the design's inline
         `display:none` (`_unhide`) precisely so this file is not load-bearing. If it 404s, is blocked, or
         never parses, the pages degrade to a long scrolling document with all content intact and indexable.

         ⚠️ `defer`, so it never blocks the render it is not required for.
         ⚠️ A PLAIN STATIC FILE, DELIBERATELY NOT `@vite`: the Vite helper THROWS when the build manifest is
         missing, which would turn a forgotten `npm run build` into a 500 on the public marketing site —
         strictly worse than the degraded-but-readable failure this design is built around.
         ⚠️ `filemtime` cache-busts without a manifest; it is guarded because the file's absence must never
         be able to break a page render. --}}
    @php($vyMkJs = public_path('js/marketing.js'))
    <script defer src="{{ asset('js/marketing.js') }}{{ is_file($vyMkJs) ? '?v='.filemtime($vyMkJs) : '' }}"></script>
</body>
</html>
