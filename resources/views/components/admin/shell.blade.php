{{-- SUPER ADMIN · GATE 0 — the authenticated PLATFORM console shell (sidebar + topbar + content). The cohesive
     "control-room" design (deep slate + indigo), distinct from the warm tenant portals, ready for the feature
     gates to fill. Gate 0 ships the shell + an honest empty landing — NO features yet. The viewer is resolved
     from the `admin` guard only. noindex. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@props(['active' => 'overview', 'title' => 'Overview'])
@php
    $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
    $adminName = $admin?->name ?? 'Platform admin';
    $initials = \Illuminate\Support\Str::of($adminName)->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('');
    // The nav — Overview is live in Gate 0; the feature areas arrive in later gates (honest "Soon", not fake links).
    $nav = [
        ['overview', 'Overview', route('admin.dashboard'), true],
        ['customers', 'Customers', route('admin.customers.index'), true],
        ['plans', 'Plans', route('admin.plans.index'), true],
        ['activity', 'Security & activity', route('admin.activity.index'), true],
        ['integrations', 'Integrations', route('admin.integrations.index'), true],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>{{ $title }} · Vyrobel Platform</title>
    {{-- Audit fix P3 — an admin favicon (control-room indigo "V"). --}}
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%236366F1'/%3E%3Ctext x='16' y='23' font-family='sans-serif' font-size='19' font-weight='700' fill='white' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        :root {
            --ad-bg-0: #0C1322; --ad-bg-1: #111B2E; --ad-bg-2: #16223A;
            --ad-ink: #E8EDF6; --ad-muted: #94A3B8; --ad-faint: #64748B;
            --ad-accent: #818CF8; --ad-accent-2: #6366F1; --ad-accent-soft: rgba(129, 140, 248, 0.16);
            --ad-glass: rgba(255, 255, 255, 0.045); --ad-glass-2: rgba(255, 255, 255, 0.07);
            --ad-border: rgba(148, 163, 184, 0.18); --ad-border-strong: rgba(148, 163, 184, 0.30);
            --ad-grad: linear-gradient(135deg, #818CF8, #6366F1);
            --ad-r-lg: 18px; --ad-r-md: 12px; --ad-r-sm: 9px;
            --ad-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --ad-display: 'Sora', 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--ad-sans); color: var(--ad-ink); font-size: 15px; line-height: 1.5; min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            background:
                radial-gradient(circle at 88% 2%, rgba(99, 102, 241, 0.12), transparent 46%),
                linear-gradient(160deg, var(--ad-bg-0) 0%, var(--ad-bg-1) 62%, var(--ad-bg-2) 100%);
            background-attachment: fixed;
        }
        @keyframes adFadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; } }
        .ad-layout { display: grid; grid-template-columns: 236px 1fr; min-height: 100vh; }
        .ad-side { border-right: 1px solid var(--ad-border); padding: 22px 16px; display: flex; flex-direction: column; gap: 22px; background: rgba(8, 12, 20, 0.4); backdrop-filter: blur(8px); }
        .ad-brand { display: flex; align-items: center; gap: 10px; padding: 0 6px; }
        .ad-brand-mark { width: 34px; height: 34px; border-radius: 10px; background: var(--ad-grad); color: #fff; font-family: var(--ad-display); font-weight: 700; font-size: 18px; display: grid; place-items: center; box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4); }
        .ad-brand-name { font-family: var(--ad-display); font-weight: 600; font-size: 15.5px; }
        .ad-brand-tag { font-size: 9.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--ad-accent); }
        .ad-nav { display: flex; flex-direction: column; gap: 3px; }
        .ad-nav-label { font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--ad-faint); padding: 0 10px; margin-bottom: 6px; }
        .ad-nav a, .ad-nav .ad-soon { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 13.5px; font-weight: 500; color: var(--ad-muted); text-decoration: none; padding: 9px 11px; border-radius: 10px; transition: background .14s ease, color .14s ease; }
        .ad-nav a:hover { color: var(--ad-ink); background: var(--ad-glass); }
        .ad-nav a.is-active { color: #fff; background: var(--ad-accent-soft); font-weight: 600; box-shadow: inset 0 0 0 1px rgba(129,140,248,.28); }
        .ad-nav a:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-nav .ad-soon { cursor: default; color: var(--ad-faint); }
        .ad-soon-tag { font-size: 8.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ad-faint); border: 1px dashed var(--ad-border-strong); border-radius: 6px; padding: 1px 6px; }
        .ad-main { display: flex; flex-direction: column; min-width: 0; }
        .ad-top { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 16px 30px; border-bottom: 1px solid var(--ad-border); }
        .ad-top-title { font-family: var(--ad-display); font-weight: 600; font-size: 15px; }
        .ad-user { display: flex; align-items: center; gap: 12px; }
        .ad-chip { display: flex; align-items: center; gap: 9px; padding: 6px 8px 6px 6px; border-radius: 30px; border: 1px solid var(--ad-border); background: var(--ad-glass); transition: border-color .14s ease, background .14s ease; }
        a.ad-chip:hover { border-color: var(--ad-border-strong); background: var(--ad-glass-2); }
        a.ad-chip.is-active { border-color: var(--ad-accent); }
        a.ad-chip:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-ava { width: 28px; height: 28px; border-radius: 50%; background: var(--ad-grad); color: #fff; font-size: 11px; font-weight: 600; display: grid; place-items: center; }
        .ad-chip-name { font-size: 12.5px; font-weight: 500; padding-right: 4px; }
        .ad-signout { background: none; border: 1px solid var(--ad-border); color: var(--ad-muted); font-family: var(--ad-sans); font-size: 12.5px; font-weight: 500; cursor: pointer; padding: 7px 13px; border-radius: 9px; transition: color .14s ease, border-color .14s ease; }
        .ad-signout:hover { color: var(--ad-ink); border-color: var(--ad-border-strong); }
        .ad-signout:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-content { padding: 30px; max-width: 1180px; width: 100%; animation: adFadeUp .5s cubic-bezier(.2,.7,.2,1) both; }
        .ad-h1 { font-family: var(--ad-display); font-weight: 700; font-size: 24px; letter-spacing: -0.02em; margin: 0 0 4px; }
        .ad-lead { color: var(--ad-muted); font-size: 14px; margin: 0 0 24px; }
        .ad-card { background: var(--ad-glass); border: 1px solid var(--ad-border); border-radius: var(--ad-r-lg); padding: 22px 24px; backdrop-filter: blur(10px); }
        .ad-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ad-accent); }
        .ad-stat { font-family: var(--ad-display); font-weight: 700; font-size: 40px; letter-spacing: -0.02em; margin: 8px 0 2px; }
        .ad-stat-sub { color: var(--ad-muted); font-size: 13px; }
        /* Metrics / KPIs (Build 1 · Gate 1) */
        .ad-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .ad-kpi { background: var(--ad-glass); border: 1px solid var(--ad-border); border-radius: 16px; padding: 18px 20px; backdrop-filter: blur(10px); }
        .ad-kpi-label { font-size: 10.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ad-muted); }
        .ad-kpi-val { font-family: var(--ad-display); font-weight: 700; font-size: 30px; letter-spacing: -0.02em; margin: 9px 0 2px; }
        .ad-kpi-sub { color: var(--ad-faint); font-size: 12.5px; }
        .ad-grid-2 { display: grid; grid-template-columns: 1.3fr 1fr; gap: 18px; margin-top: 18px; }
        @media (max-width: 900px) { .ad-grid-2 { grid-template-columns: 1fr; } }
        .ad-panel-title { font-family: var(--ad-display); font-weight: 600; font-size: 14.5px; margin: 0 0 16px; }
        /* Mini bar chart — thin indigo bars, the peak in the accent gradient (the control-room take on a chart) */
        .ad-chart { display: flex; align-items: flex-end; gap: 10px; height: 130px; }
        .ad-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; min-width: 0; }
        .ad-bar { width: 100%; max-width: 34px; border-radius: 6px 6px 3px 3px; background: rgba(129,140,248,.32); transition: background .2s ease; min-height: 4px; }
        .ad-bar.is-peak { background: var(--ad-grad); }
        .ad-bar-label { font-size: 10.5px; color: var(--ad-faint); }
        .ad-bar-val { font-size: 11px; font-weight: 600; color: var(--ad-ink); }
        /* Distribution / breakdown rows */
        .ad-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-top: 1px solid var(--ad-border); font-size: 13.5px; }
        .ad-row:first-child { border-top: none; }
        .ad-row-k { color: var(--ad-muted); }
        .ad-row-v { font-weight: 600; }
        /* Status pills */
        .ad-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; }
        .ad-pill--active { background: rgba(110,231,183,.14); color: #6EE7B7; }
        .ad-pill--trialing { background: rgba(129,140,248,.16); color: var(--ad-accent); }
        .ad-pill--suspended { background: rgba(245,158,158,.14); color: var(--ad-danger); }
        .ad-pill--cancelled { background: rgba(148,163,184,.16); color: var(--ad-muted); }
        .ad-empty { color: var(--ad-faint); font-size: 13px; padding: 20px 2px; }
        /* Tables (Build 1 · Gates 2/3) */
        .ad-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ad-table th { text-align: left; font-size: 10.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--ad-faint); padding: 0 14px 12px; }
        .ad-table td { padding: 13px 14px; border-top: 1px solid var(--ad-border); }
        .ad-table tbody tr { transition: background .14s ease; }
        .ad-table tbody tr:hover { background: var(--ad-glass); }
        .ad-table a.ad-cell-link { color: var(--ad-ink); font-weight: 600; text-decoration: none; }
        .ad-table a.ad-cell-link:hover { color: var(--ad-accent); }
        .ad-table .ad-num { text-align: right; font-variant-numeric: tabular-nums; color: var(--ad-muted); }
        /* Filters bar */
        .ad-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .ad-filters input, .ad-filters select { padding: 9px 12px; border-radius: 9px; border: 1px solid var(--ad-border); background: rgba(9,14,24,.55); color: var(--ad-ink); font-family: var(--ad-sans); font-size: 13.5px; }
        .ad-filters input:focus, .ad-filters select:focus { outline: none; border-color: var(--ad-accent); box-shadow: 0 0 0 3px var(--ad-accent-soft); }
        .ad-filters input:focus-visible, .ad-filters select:focus-visible, .ad-table a:focus-visible, .ad-btn:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        /* Buttons (control-room) */
        .ad-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 16px; border-radius: 10px; font-family: var(--ad-sans); font-size: 13px; font-weight: 600; color: #fff; background: var(--ad-grad); border: none; cursor: pointer; box-shadow: 0 6px 16px rgba(99,102,241,.3); transition: transform .14s ease; text-decoration: none; }
        .ad-btn:hover { transform: translateY(-1px); }
        .ad-btn--ghost { background: rgba(255,255,255,.06); color: var(--ad-ink); border: 1px solid var(--ad-border); box-shadow: none; }
        .ad-btn--danger { background: rgba(245,158,158,.14); color: var(--ad-danger); border: 1px solid rgba(245,158,158,.34); box-shadow: none; }
        .ad-btn--sm { padding: 7px 13px; font-size: 12.5px; }
        /* Flash notice */
        .ad-flash { padding: 11px 15px; border-radius: 10px; background: rgba(110,231,183,.10); border: 1px solid rgba(110,231,183,.32); color: #A7F3D0; font-size: 13px; font-weight: 500; margin-bottom: 18px; }
        /* Form fields (control-room) */
        .ad-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--ad-muted); margin-bottom: 7px; }
        .ad-input { width: 100%; padding: 11px 13px; border-radius: 9px; border: 1px solid var(--ad-border); background: rgba(9,14,24,.55); color: var(--ad-ink); font-family: var(--ad-sans); font-size: 14px; transition: border-color .15s ease, box-shadow .15s ease; }
        .ad-input:focus { outline: none; border-color: var(--ad-accent); box-shadow: 0 0 0 3px var(--ad-accent-soft); }
        .ad-input:focus-visible, input[type=checkbox]:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        input[type=checkbox] { accent-color: var(--ad-accent-2); width: 16px; height: 16px; }
        /* Detail (definition list) */
        .ad-back { display: inline-flex; align-items: center; gap: 6px; color: var(--ad-muted); text-decoration: none; font-size: 13px; margin-bottom: 14px; }
        .ad-back:hover { color: var(--ad-ink); }
        .ad-dl { display: grid; grid-template-columns: 160px 1fr; gap: 12px 18px; font-size: 13.5px; }
        .ad-dl dt { color: var(--ad-muted); }
        .ad-dl dd { margin: 0; font-weight: 500; }
        @media (max-width: 820px) { .ad-layout { grid-template-columns: 1fr; } .ad-side { flex-direction: row; overflow-x: auto; align-items: center; } .ad-nav { flex-direction: row; } .ad-nav-label { display: none; } }
    </style>
</head>
<body>
    <div class="ad-layout">
        <aside class="ad-side">
            <div class="ad-brand">
                <span class="ad-brand-mark">V</span>
                <span>
                    <div class="ad-brand-name">Vyrobel</div>
                    <div class="ad-brand-tag">Platform</div>
                </span>
            </div>
            <nav class="ad-nav">
                <div class="ad-nav-label">Console</div>
                @foreach ($nav as [$key, $label, $url, $live])
                    @if ($live)
                        <a href="{{ $url }}" class="{{ $key === $active ? 'is-active' : '' }}">{{ $label }}</a>
                    @else
                        <span class="ad-soon">{{ $label }} <span class="ad-soon-tag">Soon</span></span>
                    @endif
                @endforeach
            </nav>
        </aside>
        <div class="ad-main">
            <header class="ad-top">
                <div class="ad-top-title">{{ $title }}</div>
                <div class="ad-user">
                    {{-- The user chip links to the admin's own account page (profile · email · password · 2FA). --}}
                    <a class="ad-chip {{ $active === 'account' ? 'is-active' : '' }}" href="{{ route('admin.account.edit') }}" style="text-decoration: none; color: inherit;">
                        <span class="ad-ava">{{ $initials }}</span>
                        <span class="ad-chip-name">{{ $adminName }}</span>
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="ad-signout">Sign out</button>
                    </form>
                </div>
            </header>
            <main class="ad-content">
                {{ $slot }}
            </main>
        </div>
    </div>
    {{-- Audit fix P3 — the shared framework-free double-submit guard on the admin state-changing forms. --}}
    @include('partials.submit-guard')
</body>
</html>
