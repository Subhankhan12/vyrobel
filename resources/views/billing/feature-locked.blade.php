{{-- Plan feature-gate — the clean, honest "not in your plan" state (Build 1 · Gate 3). Rendered when a plan
     does NOT grant a feature. A real, tidy page (never a broken screen). Self-contained so it renders without
     any tenant layout context. --}}
@php
    $labels = ['channels' => 'Channel manager', 'analytics' => 'Insights & reports', 'team' => 'Extra team members'];
    $label = $labels[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Not in your plan · Vyrobel</title>
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            font-family: 'Inter', system-ui, sans-serif; color: #2B2320;
            background: radial-gradient(circle at 80% 6%, rgba(255,255,255,.4), transparent 62%), linear-gradient(180deg, #FBF3EC 0%, #F6EBE2 100%); }
        .card { max-width: 440px; width: 100%; background: rgba(255,252,248,.62); backdrop-filter: blur(22px);
            border: 1px solid rgba(255,255,255,.75); border-radius: 22px; box-shadow: rgba(150,90,50,.18) 0 22px 60px; padding: 34px 32px; text-align: center; }
        .badge { width: 52px; height: 52px; margin: 0 auto 16px; border-radius: 15px; display: grid; place-items: center;
            background: rgba(217,102,46,.13); color: #B4400F; }
        h1 { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 22px; margin: 0 0 8px; }
        p { color: #5F5853; font-size: 14px; line-height: 1.55; margin: 0 0 22px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 30px;
            font-weight: 600; font-size: 14px; color: #fff; text-decoration: none; background: #C2410C;   /* DO-EMAIL-6: white 14px on the accent GRADIENT measured 3.08 at its lighter stop (#E8703A). The repo's settled fix for this exact construct is the SOLID accent — 5.18, the published anchor. */ box-shadow: rgba(194,65,12,.3) 0 4px 12px; }
    </style>
    <x-vy-scrollbar />
    <x-vy-selects />
</head>
<body>
    <main class="card">
        <div class="badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1>{{ $label }} isn't in your plan</h1>
        <p>Your current plan doesn't include {{ \Illuminate\Support\Str::lower($label) }}. Upgrade your plan to unlock it — your data and settings stay exactly as they are.</p>
        <a href="{{ route('app.billing') }}" class="btn">View plans &amp; upgrade</a>
    </main>
</body>
</html>
