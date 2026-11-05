{{-- Public booking storefront shell (design pass · Booking Screen 1). A full-page, PUBLIC
     (unauthenticated) guest-facing chrome — property header + centered content + footer, NO operator
     sidebar/nav and NO logged-in user. It reuses the SAME formalized Vyrobel tokens as the owner/
     operator systems (frosted glass, terracotta, Sora/Inter, real keyframes) so the storefront is
     visually one product, but is clearly a guest surface. Tokens are copied (self-contained), the
     same "dedicated shell per surface" decision the owner/operator layouts record.

     No Livewire on this public page (lighter + rate-limit-friendly; the page is server-rendered).

     Props:
       property — ['name' => string, 'type' => string]
       title    — the browser <title> / page heading context --}}
@props([
    'property' => ['name' => __('components/public/shell.default_property'), 'type' => ''],
    'title' => '',
    'head' => null,
    'nav' => null,
    // STOREFRONT SEO (D10) — the per-page SEO payload from App\Support\Storefront\StorefrontSeo.
    // ⚠️ ADDITIVE AND BACKWARD-COMPATIBLE: null (the default) leaves this shell's output byte-identical to
    // what it emitted before D10, which is what lets the change be proven inert for any page not passing it.
    // ⚠️ When present it supplies the page's ONLY <title> (see below) plus description/canonical/robots/
    // OG/Twitter and the JSON-LD graph.
    'seo' => null,
])
<!DOCTYPE html>
<html {!! \App\Support\I18n\Locales::htmlAttributes() !!}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- ⚠️ EXACTLY ONE <title> PER PAGE. With a :seo payload the SEO component emits it (so the title and
         og:title can never disagree); without one, the original bare composition is emitted unchanged. --}}
    @if ($seo !== null)
    <x-public.seo :seo="$seo" />
    @else
    <title>{{ $title !== '' ? __('components/public/shell.page_title', ['title' => $title, 'property' => $property['name']]) : $property['name'] }}</title>{!! \App\Support\I18n\LocalText::caselessCss() !!}
    @endif

    <x-favicon />

    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">

    {{-- ⚠️ THE CANONICAL PALETTE FIRST — the 31 shared tokens, declared ONCE (DO-TOKEN-1, §N.8f).
         This shell's own <style> follows, so its DELIBERATE overrides win by cascade order.
         ⚠️ Moving this below the shell's styles would silently undo every override. --}}
    <x-design.tokens />
    <style>
        :root {
            --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-shadow: rgba(150, 90, 50, 0.16) 0 14px 40px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;

            --vy-r-lg: 20px;
            --vy-r-sm: 10px;

            }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }

        body.vy-body {
            font-family: var(--vy-sans); color: var(--vy-ink); font-size: 16px; line-height: 1.45;
            -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; min-height: 100vh;
            /* Guest-portal peach → sand, matching the decoded wireframes. */
            background: linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%);
            background-attachment: fixed;
        }
        a { color: inherit; text-decoration: none; }

        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes vyDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(40px, 24px); } 100% { transform: translate(0, 0); } }
        @keyframes vyShimmer { 0%, 100% { opacity: .5; } 50% { opacity: .85; } }
        .vy-fade { opacity: 0; animation: vyFadeUp .6s cubic-bezier(.2, .7, .2, 1) forwards; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } .vy-fade { opacity: 1; } }

        /* Ambient glow blobs (matching the wireframes) — decorative, motion-gated. */
        body.vy-body::before {
            content: ""; position: fixed; inset: -160px -100px auto auto; width: 560px; height: 560px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.35), transparent 68%);
            filter: blur(10px); z-index: 0; pointer-events: none;
        }
        body.vy-body::after {
            content: ""; position: fixed; top: 150px; left: 20%; width: 380px; height: 380px; border-radius: 50%;
            background: rgba(232, 112, 58, 0.5); filter: blur(70px); z-index: 0; pointer-events: none;
            animation: vyDrift 14s ease-in-out infinite;
        }

        /* Storefront header */
        .vy-store-header {
            position: sticky; top: 0; z-index: 5; display: flex; align-items: center; justify-content: space-between;
            gap: 16px; padding: 16px 28px; border-bottom: 1px solid var(--vy-hairline);
            background: rgba(255, 253, 250, 0.72); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
        }
        .vy-brand { display: flex; align-items: center; gap: 11px; }
        .vy-brand-mark { width: 36px; height: 36px; border-radius: var(--vy-r-md); background: var(--vy-accent); color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 19px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-store-name { font-family: var(--vy-display); font-weight: 600; font-size: 17px; letter-spacing: -0.01em; }
        .vy-store-type { font-size: 11px; color: var(--vy-muted); text-transform: capitalize; }
        .vy-store-note { font-size: 11.5px; color: var(--vy-muted); }

        main { position: relative; z-index: 1; max-width: 1080px; margin: 0 auto; padding: 30px 24px 70px; }

        /* Shared surfaces */
        .vy-card {
            background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 22px;
        }
        .vy-eyebrow { font-size: 10.5px; font-weight: 600; letter-spacing: 0.11em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-muted { color: var(--vy-muted); }
        .vy-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 11px 20px; border-radius: var(--vy-r-pill); font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; border: 1px solid transparent; cursor: pointer; transition: transform .15s ease, background .15s ease; }
        .vy-btn--accent { background: var(--vy-accent); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vy-btn--accent:hover { transform: translateY(-1px); }
        .vy-btn.is-soon { opacity: .55; cursor: not-allowed; }
        .vy-soon-tag { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 2px 6px; }
        .vy-btn:focus-visible, .vy-input:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }

        /* Hero + search */
        .vy-hero { margin-bottom: 26px; }
        .vy-hero-title { font-family: var(--vy-display); font-weight: 700; font-size: 34px; letter-spacing: -0.02em; margin: 0 0 6px; }
        .vy-hero-sub { color: var(--vy-muted); font-size: 14px; }
        .vy-search { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr 0.7fr 0.7fr auto; gap: 12px; align-items: end; }
        .vy-field-label { font-size: 10px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); display: block; margin-bottom: 6px; }
        .vy-input { width: 100%; padding: 11px 13px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, 0.8); font-family: var(--vy-sans); font-size: 14px; color: var(--vy-ink); }

        /* Room results */
        .vy-results-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin: 6px 2px 16px; flex-wrap: wrap; }
        .vy-results-title { font-family: var(--vy-display); font-weight: 600; font-size: 18px; }
        .vy-rooms { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
        .vy-room-card { border-radius: var(--vy-r-lg); overflow: hidden; background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow); display: flex; flex-direction: column; }
        /* Polish Batch 2 — the shared hover for an interactive TAP-TARGET card (the room listing cards). Prototype-matched: lift + shadow. */
        .vy-card--tappable { cursor: pointer; transition: transform .16s ease, box-shadow .16s ease; }
        .vy-card--tappable:hover { transform: translateY(-2px); box-shadow: 0 18px 44px rgba(150, 90, 50, .18); }
        .vy-room-banner { height: 128px; background: var(--vy-grad); position: relative; display: flex; align-items: flex-end; padding: 12px 16px; overflow: hidden; }
        .vy-room-banner .vy-room-kind { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: rgba(255,255,255,.9); background: rgba(0,0,0,.16); border-radius: var(--vy-r-pill); padding: 3px 10px; }
        .vy-room-banner .vy-card-cover { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .vy-room-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
        .vy-room-name { font-family: var(--vy-display); font-weight: 600; font-size: 16px; }
        .vy-room-meta { font-size: 12px; color: var(--vy-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .vy-avail-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: var(--vy-accent); background: rgba(232, 112, 58, 0.12); border-radius: var(--vy-r-pill); padding: 2px 9px; }
        .vy-avail-chip.is-tight { color: var(--vy-amber); background: rgba(224, 146, 30, 0.13); }
        .vy-room-foot { margin-top: auto; display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; padding-top: 8px; border-top: 1px solid var(--vy-hairline); }
        .vy-price-night { font-family: var(--vy-display); font-weight: 700; font-size: 20px; letter-spacing: -0.01em; }
        .vy-price-unit { font-size: 12px; color: var(--vy-muted); font-weight: 500; }
        .vy-price-total { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; }

        /* Teaser (no dates yet) */
        .vy-teaser-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-teaser-row:last-child { border-bottom: none; }
        .vy-teaser-name { font-family: var(--vy-display); font-weight: 600; font-size: 14px; }
        .vy-teaser-sub { font-size: 12px; color: var(--vy-muted); margin-top: 2px; }

        .vy-empty { text-align: center; color: var(--vy-muted); padding: 40px 20px; font-size: 14px; }
        .vy-deferred-note { font-size: 11.5px; color: var(--vy-muted); display: inline-flex; align-items: center; gap: 6px; margin-top: 16px; }

        /* Room detail */
        .vy-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: var(--vy-muted); margin-bottom: 16px; }
        .vy-back:hover { color: var(--vy-accent); }
        .vy-detail-grid { display: grid; grid-template-columns: minmax(0, 1fr) 330px; gap: 22px; align-items: start; }
        .vy-detail-banner { height: 260px; border-radius: var(--vy-r-lg); background: var(--vy-grad); box-shadow: var(--vy-shadow); position: relative; display: flex; align-items: flex-end; padding: 20px 22px; overflow: hidden; }
        .vy-detail-banner::after { content: ""; position: absolute; inset: 0; background: radial-gradient(circle at 78% 12%, rgba(255,255,255,.28), transparent 55%); }
        .vy-detail-banner .vy-room-kind { position: relative; z-index: 1; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #fff; background: rgba(0,0,0,.18); border-radius: var(--vy-r-pill); padding: 4px 12px; }
        .vy-photos-note { position: absolute; top: 14px; right: 16px; z-index: 1; }
        .vy-detail-banner.has-photo { background: var(--vy-grad); }
        .vy-detail-banner .vy-banner-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; }
        .vy-thumbs { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .vy-thumb { width: 72px; height: 54px; object-fit: cover; border-radius: var(--vy-r-md); border: 1px solid var(--vy-glass-border); }

        .vy-detail-title { font-family: var(--vy-display); font-weight: 700; font-size: 28px; letter-spacing: -0.02em; margin: 18px 0 4px; }
        .vy-detail-kind { color: var(--vy-muted); font-size: 13.5px; }

        .vy-specs { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 14px; margin-top: 18px; }
        .vy-spec { background: rgba(255, 255, 255, 0.55); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md); padding: 13px 15px; box-shadow: var(--vy-shadow-sm); }
        .vy-spec-label { font-size: 9.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); }
        .vy-spec-val { font-family: var(--vy-display); font-weight: 600; font-size: 18px; margin-top: 5px; }

        .vy-section-title { font-family: var(--vy-display); font-weight: 600; font-size: 16px; margin: 26px 0 12px; }
        .vy-amenities { display: flex; flex-wrap: wrap; gap: 9px; }
        .vy-amenity { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--vy-ink); background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-pill); padding: 6px 13px; }
        .vy-amenity svg { color: var(--vy-accent); }

        /* Right-rail booking panel */
        .vy-book-panel { position: sticky; top: 88px; }
        .vy-book-price { font-family: var(--vy-display); font-weight: 700; font-size: 28px; letter-spacing: -0.01em; }
        .vy-book-price .vy-price-unit { font-size: 13px; color: var(--vy-muted); font-weight: 500; }
        .vy-book-total { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; }
        .vy-book-avail { margin-top: 12px; }
        .vy-book-cta { width: 100%; margin-top: 16px; }
        .vy-mini-form { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px; }
        .vy-mini-form .vy-full { grid-column: 1 / -1; }

        /* Checkout */
        .vy-checkout-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 22px; align-items: start; }
        .vy-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .vy-form-grid .vy-full { grid-column: 1 / -1; }
        .vy-summary-line { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vy-summary-line:last-of-type { border-bottom: none; }
        .vy-summary-line .vy-sl-label { color: var(--vy-muted); }
        .vy-summary-line .vy-sl-val { font-family: var(--vy-display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-summary-total { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: 12px; padding-top: 14px; border-top: 1.5px solid rgba(120, 70, 40, 0.16); }
        .vy-summary-total .vy-st-label { font-family: var(--vy-display); font-weight: 600; font-size: 15px; }
        .vy-summary-total .vy-st-val { font-family: var(--vy-display); font-weight: 700; font-size: 24px; color: var(--vy-accent); letter-spacing: -0.01em; }
        .vy-pay-note { display: flex; align-items: flex-start; gap: 8px; margin-top: 14px; font-size: 11.5px; color: var(--vy-muted); background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md); padding: 11px 13px; }
        .vy-credit-toggle { display: flex; align-items: center; gap: 10px; margin-top: 16px; font-size: 12.5px; color: var(--vy-ink); background: rgba(255, 255, 255, 0.5); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md); padding: 12px 14px; cursor: pointer; }
        .vy-credit-toggle input { width: 16px; height: 16px; accent-color: var(--vy-accent); flex: none; }
        .vy-credit-toggle strong { color: var(--vy-accent-ink, var(--vy-ink)); }

        .vy-error { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding: 12px 16px; border-radius: var(--vy-r-md); background: rgba(224, 146, 30, 0.12); border: 1px solid rgba(224, 146, 30, 0.4); border-left: 3px solid var(--vy-amber-2); font-size: 13px; font-weight: 500; color: var(--vy-ink); }
        .vy-error svg { color: var(--vy-amber); flex: none; }

        /* Confirmation */
        .vy-confirm { max-width: 560px; margin: 20px auto; text-align: center; }
        .vy-confirm-check { width: 66px; height: 66px; border-radius: 50%; background: var(--vy-grad); color: #fff; display: grid; place-items: center; margin: 0 auto 18px; box-shadow: var(--vy-shadow-accent); }
        .vy-confirm-title { font-family: var(--vy-display); font-weight: 700; font-size: 28px; letter-spacing: -0.02em; }
        .vy-confirm-sub { color: var(--vy-muted); font-size: 14px; margin-top: 6px; }
        .vy-ref { font-family: var(--vy-display); font-weight: 700; font-size: 22px; letter-spacing: 0.02em; margin-top: 4px; }
        .vy-confirm-rows { text-align: left; margin-top: 18px; }

        .vy-store-footer { text-align: center; color: var(--vy-muted); font-size: 11.5px; padding: 26px 20px 34px; position: relative; z-index: 1; }

        @media (max-width: 820px) {
            .vy-detail-grid { grid-template-columns: minmax(0, 1fr); }
            .vy-book-panel { position: static; }
            .vy-checkout-grid { grid-template-columns: minmax(0, 1fr); }
        }

        @media (max-width: 780px) {
            .vy-search { grid-template-columns: 1fr 1fr; }
            .vy-hero-title { font-size: 27px; }
            main { padding: 22px 16px 56px; }
        }
    </style>
    {{ $head }}
    <x-vy-scrollbar />
    <x-vy-selects />
</head>
<body class="vy-body">
    <header class="vy-store-header">
        <div class="vy-brand">
            <span class="vy-brand-mark">{{ mb_substr($property['name'], 0, 1) }}</span>
            <span>
                <span class="vy-store-name">{{ $property['name'] }}</span><br>
                @if (($property['type'] ?? '') !== '')<span class="vy-store-type">{{ $property['type'] }}</span>@endif
            </span>
        </div>
        @if ($nav !== null){{ $nav }}@else<span class="vy-store-note">{{ __('components/public/shell.book_direct') }}</span>@endif
        <x-language-menu />
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="vy-store-footer">{{ __('components/public/shell.powered_by') }}</footer>
    @include('partials.submit-guard')   {{-- Polish Batch 4 — double-submit guard (public storefront has no Alpine) --}}
</body>
</html>
