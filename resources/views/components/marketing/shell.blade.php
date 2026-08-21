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
        @media (max-width: 960px) { .vy-mk-footgrid { grid-template-columns: 1fr 1fr; } }
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
    </style>
    {{ $head ?? '' }}
</head>
<body>
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
</body>
</html>
