{{-- Guest portal shell (Phase 4b) — the page chrome every guest-portal screen inherits, on the 4a guest brand
     tokens. A light, mobile-first top header (brand · Trips · Account · sign out) + a centered content column.
     The viewer is resolved from the GUEST guard (never passed in) so it can only ever be the authed guest.
     Props: active (nav key to highlight), title (the DOCUMENT title). RAW-BLADE RULE: every `@` follows a

     ⚠️⚠️ `title` IS NOT A PAGE HEADING, AND THIS LINE USED TO SAY IT WAS. It feeds `<title>` and nothing
     else. That false description is very likely how twelve signed-in screens ended up with NO heading
     element at all while every signed-out one had exactly one: the prop looked like it supplied the
     heading, and nothing rendered it. ⚠️ A page's `<h1>` belongs in the page, where its real subject
     text already is — see the promotions in the guest views.
     space, newline or tag. --}}
@props(['active' => '', 'title' => '', 'accent' => null, 'head' => null])
@php
    // #98 — a property-scoped guest surface (e.g. the review page) may pass its brand accent; validated hex only,
    // never an arbitrary value into inline CSS. Unset/invalid ⇒ the default Vyrobel terracotta (fail-open).
    $brandAccent = is_string($accent) && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $accent) === 1 ? $accent : null;
@endphp
@php
    $viewer = \Illuminate\Support\Facades\Auth::guard('guest')->user();
    $viewerName = \App\Support\Guest\GuestName::display($viewer);
    $nav = [
        ['trips', 'Trips', route('guest.trips')],
        ['payments', 'Payments', route('guest.payments')],
        ['wallet', 'Wallet', route('guest.wallet')],
        ['messages', 'Messages', route('guest.messages')],
        ['saved', 'Saved', route('guest.saved')],
        ['account', 'Account', route('guest.account')],
        ['help', 'Help', route('guest.help')],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title !== '' ? $title.' · ' : '' }}Vyrobel</title>
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    {{-- ⚠️ THE CANONICAL PALETTE FIRST — the 31 shared tokens, declared ONCE (DO-TOKEN-1, §N.8f).
         This shell's own <style> follows, so its DELIBERATE overrides win by cascade order.
         ⚠️ Moving this below the shell's styles would silently undo every override. --}}
    <x-design.tokens />
    <style>
        :root {
            /* TEXT-ONLY variants of the two warm hues. The plain tokens fail AA at badge/label size:
               --vy-accent at 13px on its own 8% wash = 4.20, --vy-amber at 11px on the pending badge = 3.79.
               Same shape as the operator realm's --vy-accent-text.
               ⚠️ CORRECTION TO G1: --vy-amber-text shipped as #8F6414 — the PROTOTYPE's own amber-on-light —
               measured 4.70 and 5.06 ON THE TRIPS PAGE. G2 measured the SAME token at 4.37 on the Reservation
               hero's warmer plate (#F8E8D4) and it FAILS there. One page proving a token is not enough:
               these badges are translucent on a translucent card over the page gradient, so their contrast
               moves with whatever sits behind them. Darkened to the minimum that clears 4.5 on the WORST
               backdrop measured so far — 5.35 there, 5.76 on Trips, 6.20 on the balance figure.
               ⚠️ Word choice here is load-bearing: this is a CSS comment, so it RENDERS into every guest
               page (Blade comments do not). An earlier draft used a word the check-in honesty guard
               forbids (#39 — an ID is captured, never 'v-e-r-i-f-i-e-d'), and shipped it site-wide.
               Use these for TEXT; keep --vy-accent / --vy-amber for fills, borders and rails. */
            /* DO-EMAIL-4: the ONE amber-text value (was rgb(126,88,17)) */
            /* G2: the same green measured 4.75 on Trips and 4.26 HERE — a translucent badge on a
               translucent card over the page gradient has NO fixed contrast, it moves with whatever is
               behind it. rgb(70,120,60) is hand-written at ELEVEN guest call sites; this is the one
               definition, dark enough to clear AA on the worst backdrop measured (7.24). */
            --vy-card: rgba(255, 253, 251, 0.86); --vy-glass-border: rgba(120, 70, 40, 0.12);
            /* Polish Batch 1 — the faint warm-cream tint (incoming chat bubbles, arrival/checkin/review chips). Was undefined → transparent. */
            --vy-shadow: rgba(150, 90, 50, 0.10) 0 10px 30px; --vy-shadow-accent: rgba(194, 65, 12, 0.28) 0 4px 12px;
            --vy-r-lg: 18px; --vy-r-sm: 9px;
            }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--vy-sans); color: var(--vy-ink); font-size: 15px; line-height: 1.5;
            -webkit-font-smoothing: antialiased; min-height: 100vh;
            background:
                radial-gradient(circle at 82% 0%, rgba(255, 255, 255, 0.5), transparent 55%),
                radial-gradient(circle at 5% 100%, rgba(255, 224, 200, 0.4), transparent 55%),
                linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%);
            background-attachment: fixed;
        }
        @keyframes vyFadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; } html { scroll-behavior: auto; } }
        .vy-top { position: sticky; top: 0; z-index: 10; backdrop-filter: blur(14px); background: rgba(251, 243, 236, 0.72); border-bottom: 1px solid var(--vy-hairline); }
        .vy-top-in { max-width: 760px; margin: 0 auto; padding: 13px 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .vy-brand { display: flex; align-items: center; gap: 9px; text-decoration: none; }
        /* SOLID, not --vy-grad: white 17px/700 is NORMAL-size text (large starts at 18.66px bold), so it needs
           4.5. Measured on rendered pixels, the gradient's light end gave 3.50; solid --vy-accent gives 5.18.
           No warm orange lighter than #C2410C reaches 4.5 for white — the same finding as the invite email. */
        .vy-brand-mark { width: 32px; height: 32px; border-radius: 9px; background: var(--vy-accent); color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 17px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 16px; color: var(--vy-ink); }
        .vy-nav { display: flex; align-items: center; gap: 4px; }
        .vy-nav a { font-size: 13px; font-weight: 500; color: var(--vy-muted); text-decoration: none; padding: 6px 11px; border-radius: 20px; }
        .vy-nav a.is-active { color: var(--vy-accent-text); background: rgba(194, 65, 12, 0.08); font-weight: 600; }   /* 4.20 -> 5.01 on its own wash (G1, rendered pixels) */
        .vy-nav a:hover { color: var(--vy-ink); }
        /* ⚠️ VISUALLY-HIDDEN, DEFINED NOT ASSUMED. An undefined `.vy-sr-only` does not hide anything — it
           renders the label as stray visible text. It was defined only in the owner/operator layouts and
           LOCALLY inside guest/help.blade.php, which is a page that already hit this. Hoisted to the shell so
           every guest page inherits it, rather than each one rediscovering the trap. */
        .vy-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;
            clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* ⚠️ The bell — the owner/operator glass recipe at 36px, sized for this lighter header. The shared
           component owns the glyph + badge; this rule owns the surface, exactly as in the other two shells. */
        .vy-bell {
            position: relative; width: 36px; height: 36px; padding: 0; border-radius: 50%; flex: none;
            display: grid; place-items: center;
            background: rgba(255, 252, 248, 0.58); border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow: 0 10px 30px rgba(150, 90, 50, .10), inset 0 1px 0 rgba(255, 255, 255, .65);
            backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            color: var(--vy-ink); transition: transform .14s ease;
        }
        .vy-bell:hover { transform: translateY(-1px); }

        /* ⚠️ `white-space: nowrap` + `flex: none` added with the bell (GATE 3): the extra 36px header action
           squeezed this flex item below its single-line width and "Sign out" wrapped onto two lines. The row
           does not overflow — the item was simply being compressed — so holding it at its natural width is the
           whole fix. Caught by RENDER, not by any test. */
        .vy-signout { background: none; border: none; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-faint); cursor: pointer; padding: 6px 4px; white-space: nowrap; flex: none; }
        .vy-signout:hover { color: var(--vy-accent); }
        .vy-wrap { max-width: 760px; margin: 0 auto; padding: 26px 20px 60px; animation: vyFadeUp .5s cubic-bezier(.2, .7, .2, 1) both; }
        .vy-h1 { font-family: var(--vy-display); font-weight: 700; font-size: 25px; letter-spacing: -0.02em; margin: 0 0 4px; }
        .vy-lead { color: var(--vy-muted); font-size: 14px; margin: 0 0 22px; }
        .vy-card { background: var(--vy-card); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 18px 20px; }
        /* Polish Batch 2 — the portal no longer feels inert: interactive tap-targets respond on hover (prototype-matched
           card lift + shadow; a subtle warm tint for link rows). Opt-in — applied only to clickable cards/rows. */
        .vy-card--tappable { cursor: pointer; transition: transform .16s ease, box-shadow .16s ease; }
        .vy-card--tappable:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(150, 90, 50, .16); }
        .vy-row--tappable { transition: background .15s ease; border-radius: var(--vy-r-sm); }
        .vy-row--tappable:hover { background: rgba(120, 70, 40, .05); }
        .vy-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .vy-badge--confirmed { background: rgba(120,160,110,.16); color: var(--vy-green-text); }   /* 4.26 -> 7.24 (G2, rendered pixels) */
        .vy-badge--in-house { background: rgba(194,65,12,.12); color: var(--vy-accent); }
        .vy-badge--pending { background: rgba(224,146,30,.14); color: var(--vy-amber-text); }   /* 3.79 -> 4.70 (G1, rendered pixels) */
        .vy-badge--departed { background: rgba(120,110,100,.14); color: var(--vy-muted); }
        .vy-badge--cancelled { background: rgba(180,68,58,.12); color: rgb(150,50,42); }
        .vy-notice { padding: 12px 14px; border-radius: var(--vy-r-md); background: rgba(120, 160, 110, 0.12); border: 1px solid rgba(120, 160, 110, 0.3); border-left: 3px solid rgb(90, 140, 80); font-size: 13px; margin-bottom: 16px; }
        /* SOLID for the same reason as .vy-brand-mark: white 13.5px on --vy-grad is the identical construct G1
           measured at 3.39 on the Trips CTA. Trips renders no .vy-btn, so this one is corrected BY CONSTRUCTION,
           not by a render on this page — G2 is the first gate that draws it and must confirm 5.18. */
        .vy-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 10px 18px; border-radius: 24px; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; color: #fff; background: var(--vy-accent); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); text-decoration: none; }
        .vy-btn--ghost { background: none; color: var(--vy-accent-text); border: 1px solid var(--vy-glass-border); box-shadow: none; }
        /* G3: a ghost button carries ACCENT TEXT, so it takes the text token. --vy-accent measured 5.03 on
           the white card but 4.41 on a --vy-wash tile (the arrival door-code panel) — the same token on a
           different plate, the G2 lesson again. --vy-accent-text clears both (5.28 on the wash, 5.9 on the
           card) and lifts G2's ghosts from 4.99 too. */
        .vy-btn:hover { transform: translateY(-1px); }
        .vy-empty { text-align: center; color: var(--vy-muted); font-size: 14px; padding: 30px 10px; }
        /* Polish Batch 4 — shared portal form classes (so the Account page can render IN this shell, with the nav,
           instead of the narrow auth card). Reusable across the portal's forms; inputs get a real focus ring. */
        .vy-field { margin-bottom: 14px; }
        .vy-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; }
        .vy-input { width: 100%; padding: 11px 13px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, .8); font-family: var(--vy-sans); font-size: 14px; color: var(--vy-ink); transition: border-color .15s ease, box-shadow .15s ease; }
        .vy-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232, 112, 58, .16); }
        .vy-input:disabled { opacity: .6; }
        .vy-error { display: flex; align-items: center; gap: 9px; margin-bottom: 16px; padding: 11px 14px; border-radius: var(--vy-r-md); background: rgba(224, 146, 30, .12); border: 1px solid rgba(224, 146, 30, .4); border-left: 3px solid var(--vy-amber); font-size: 13px; font-weight: 500; }
        .vy-link { color: var(--vy-accent-text); text-decoration: none; font-weight: 500; }   /* --vy-accent is the FILL; as text it measured 4.45 on a guest card */
        .vy-link:hover { text-decoration: underline; }
        /* Polish P3 (M5) — a consistent KEYBOARD focus ring across the whole portal, matching the app's other
           realms (operator/owner/auth all ship the same `2px --vy-accent` outline; the prototype ships none, so
           this is an internal-consistency + a11y nicety, not a prototype-match). `:focus-visible` = keyboard only,
           never a mouse-click halo. Broad element selectors so the inline-styled inputs/textareas on
           manage/wallet/messages/thread/saved/review are covered too (they don't all use `.vy-input`). */
        .vy-wrap a:focus-visible, .vy-wrap button:focus-visible, .vy-wrap input:focus-visible,
        .vy-wrap select:focus-visible, .vy-wrap textarea:focus-visible,
        .vy-top a:focus-visible, .vy-top button:focus-visible {
            outline: 2px solid var(--vy-accent); outline-offset: 2px;
        }

        /* ── PHONE (audit 2026-08-07 · L3-06) ────────────────────────────────────────────────────────────
           This shell had NO viewport breakpoint at all — its only @media was prefers-reduced-motion — while
           still declaring <meta viewport width=device-width, which promises adaptation. At a 390px iPhone
           viewport every one of the 14 guest pages side-scrolled by 225px, because `.vy-top-in` is a
           space-between flex row and `.vy-nav` is a non-wrapping flex row of links (476px wide on its own).

           That mattered more here than anywhere else in the product: the guest portal is the surface most
           likely to be opened on a phone — a traveller mid-trip checking arrival info or a door code.

           Kept in the guest design language: same tokens, same glass, no layout rewrite. The nav becomes a
           horizontally-scrollable pill row (the standard mobile pattern) rather than wrapping into a tall
           stack that would push content below the fold. */
        @media (max-width: 640px) {
            .vy-top-in { flex-wrap: wrap; gap: 8px 12px; padding: 11px 16px; }

            /* Brand and sign-out hold the first row; the nav takes the full width beneath them. */
            .vy-brand { flex: 0 0 auto; }
            .vy-signout { margin-left: auto; }

            /* ⚠️ The bell stays on the FIRST row, pinned right — `.vy-nav` becomes a scrollable strip below,
               and a bell inside it could scroll out of view on the surface guests most use. */
            .vy-bell { order: 2; margin-left: auto; }

            .vy-nav {
                order: 3; flex: 1 0 100%;
                overflow-x: auto; -webkit-overflow-scrolling: touch;
                scrollbar-width: none;                    /* Firefox */
                margin: 0 -16px; padding: 2px 16px 4px;   /* bleed to the screen edge so it reads as scrollable */
            }
            .vy-nav::-webkit-scrollbar { display: none; }
            .vy-nav a {
                flex: 0 0 auto; white-space: nowrap;
                padding: 12px 14px;                       /* 44px tall — the iOS/WCAG touch-target guideline */
            }

            .vy-wrap { padding: 20px 16px 48px; }
            .vy-h1 { font-size: 22px; }
            .vy-card { padding: 16px; border-radius: var(--vy-r-md); }

            /* Safety net for CONTENT, not just chrome: a wide table or a fixed-width media block inside any
               of the 14 pages scrolls inside its own box rather than pushing the whole document sideways. */
            .vy-wrap img, .vy-wrap svg { max-width: 100%; height: auto; }
            .vy-wrap table { display: block; width: 100%; overflow-x: auto; }
            .vy-wrap pre { overflow-x: auto; }
        }

        /* Belt-and-braces: nothing in the portal may force the document wider than the screen. Catches a
           fixed width added to a page later, which is exactly how this regressed in the first place. */
        html, body { max-width: 100%; overflow-x: hidden; }


    </style>
    @if ($brandAccent !== null)
        <style>:root { --vy-accent: {{ $brandAccent }}; --vy-shadow-accent: {{ $brandAccent }}47 0 4px 12px; }</style>
    @endif
    {{ $head ?? '' }}
    <x-vy-scrollbar />
    <x-vy-selects />
</head>
<body>
    <header class="vy-top">
        <div class="vy-top-in">
            <a class="vy-brand" href="{{ route('guest.trips') }}">
                <span class="vy-brand-mark">V</span>
                <span class="vy-brand-name">Vyrobel</span>
            </a>
            <nav class="vy-nav">
                @foreach ($nav as [$key, $label, $url])
                    <a href="{{ $url }}" class="{{ $key === $active ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('guest.logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="vy-signout">Sign out</button>
                </form>
            </nav>

            {{-- ⚠️ GUEST NOTIFICATIONS · GATE 3 — the bell. PLACEMENT IS A PROPOSAL (there is no guest
                 notifications wireframe): top-right of the header, after the nav. At desktop `space-between`
                 pins it right; at mobile it holds the FIRST row beside the brand while `.vy-nav` scrolls on
                 its own row below — so it can never scroll out of reach on a phone, which is the surface a
                 guest actually reads notifications on.
                 ⚠️ `:panel="false"` — LINK mode. This shell loads no Alpine, so the popover the operator and
                 owner shells use would be a dead control here. --}}
            <x-notifications.bell :open-all-url="route('guest.notifications')" :panel="false" />
        </div>
    </header>

    <main class="vy-wrap">
        {{ $slot }}
    </main>
    @include('partials.submit-guard')   {{-- Polish Batch 4 — double-submit guard (this portal has no Alpine) --}}
</body>
</html>
