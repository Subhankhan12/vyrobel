{{-- Guest portal shell (Phase 4b) — the page chrome every guest-portal screen inherits, on the 4a guest brand
     tokens. A light, mobile-first top header (brand · Trips · Account · sign out) + a centered content column.
     The viewer is resolved from the GUEST guard (never passed in) so it can only ever be the authed guest.
     Props: active (nav key to highlight), title (optional page heading). RAW-BLADE RULE: every `@` follows a
     space, newline or tag. --}}
@props(['active' => '', 'title' => ''])
@php
    $viewer = \Illuminate\Support\Facades\Auth::guard('guest')->user();
    $viewerName = \App\Support\Guest\GuestName::display($viewer);
    $nav = [
        ['trips', 'Trips', route('guest.trips')],
        ['payments', 'Payments', route('guest.payments')],
        ['wallet', 'Wallet', route('guest.wallet')],
        ['account', 'Account', route('guest.account')],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title !== '' ? $title.' · ' : '' }}Vyrobel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --vy-ink: rgb(43, 35, 32); --vy-muted: rgb(138, 128, 121); --vy-faint: rgb(166, 154, 146);
            --vy-accent: rgb(194, 65, 12); --vy-accent-3: rgb(232, 112, 58); --vy-amber: rgb(178, 106, 8);
            --vy-card: rgba(255, 253, 251, 0.86); --vy-glass-border: rgba(120, 70, 40, 0.12);
            --vy-hairline: rgba(120, 70, 40, 0.10); --vy-grad: linear-gradient(140deg, #E8703A, #C2410C);
            --vy-shadow: rgba(150, 90, 50, 0.10) 0 10px 30px; --vy-shadow-accent: rgba(194, 65, 12, 0.28) 0 4px 12px;
            --vy-r-lg: 18px; --vy-r-md: 12px; --vy-r-sm: 9px;
            --vy-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --vy-display: 'Sora', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
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
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; } }
        .vy-top { position: sticky; top: 0; z-index: 10; backdrop-filter: blur(14px); background: rgba(251, 243, 236, 0.72); border-bottom: 1px solid var(--vy-hairline); }
        .vy-top-in { max-width: 760px; margin: 0 auto; padding: 13px 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .vy-brand { display: flex; align-items: center; gap: 9px; text-decoration: none; }
        .vy-brand-mark { width: 32px; height: 32px; border-radius: 9px; background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-weight: 700; font-size: 17px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .vy-brand-name { font-family: var(--vy-display); font-weight: 600; font-size: 16px; color: var(--vy-ink); }
        .vy-nav { display: flex; align-items: center; gap: 4px; }
        .vy-nav a { font-size: 13px; font-weight: 500; color: var(--vy-muted); text-decoration: none; padding: 6px 11px; border-radius: 20px; }
        .vy-nav a.is-active { color: var(--vy-accent); background: rgba(194, 65, 12, 0.08); font-weight: 600; }
        .vy-nav a:hover { color: var(--vy-ink); }
        .vy-signout { background: none; border: none; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-faint); cursor: pointer; padding: 6px 4px; }
        .vy-signout:hover { color: var(--vy-accent); }
        .vy-wrap { max-width: 760px; margin: 0 auto; padding: 26px 20px 60px; animation: vyFadeUp .5s cubic-bezier(.2, .7, .2, 1) both; }
        .vy-h1 { font-family: var(--vy-display); font-weight: 700; font-size: 25px; letter-spacing: -0.02em; margin: 0 0 4px; }
        .vy-lead { color: var(--vy-muted); font-size: 14px; margin: 0 0 22px; }
        .vy-card { background: var(--vy-card); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 18px 20px; }
        .vy-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .vy-badge--confirmed { background: rgba(120,160,110,.16); color: rgb(70,120,60); }
        .vy-badge--in-house { background: rgba(194,65,12,.12); color: var(--vy-accent); }
        .vy-badge--pending { background: rgba(224,146,30,.14); color: var(--vy-amber); }
        .vy-badge--departed { background: rgba(120,110,100,.14); color: var(--vy-muted); }
        .vy-badge--cancelled { background: rgba(180,68,58,.12); color: rgb(150,50,42); }
        .vy-notice { padding: 12px 14px; border-radius: var(--vy-r-md); background: rgba(120, 160, 110, 0.12); border: 1px solid rgba(120, 160, 110, 0.3); border-left: 3px solid rgb(90, 140, 80); font-size: 13px; margin-bottom: 16px; }
        .vy-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 10px 18px; border-radius: 24px; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; color: #fff; background: var(--vy-grad); border: none; cursor: pointer; box-shadow: var(--vy-shadow-accent); text-decoration: none; }
        .vy-btn--ghost { background: none; color: var(--vy-accent); border: 1px solid var(--vy-glass-border); box-shadow: none; }
        .vy-btn:hover { transform: translateY(-1px); }
        .vy-empty { text-align: center; color: var(--vy-muted); font-size: 14px; padding: 30px 10px; }
    </style>
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
        </div>
    </header>

    <main class="vy-wrap">
        {{ $slot }}
    </main>
</body>
</html>
