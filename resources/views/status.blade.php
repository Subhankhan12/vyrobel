{{--
    ⚠️⚠️ ADMIN 01 · PHASE 5 — THE PUBLIC STATUS PAGE. MANUAL INCIDENTS ONLY.

    It renders what an admin POSTED and nothing else: no release hash, no queue depth, no config state, no health check,
    no uptime. With nothing posted it says "No incidents have been posted" — never "all systems operational", a claim
    about internals this page does not measure.

    ⚠️ SELF-CONTAINED, like the error pages: no @vite, no external request of any kind — a status page must render
    when the asset pipeline is the thing that is broken.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status · Vyrobel</title>
    <style>
        :root { --ink: rgb(43, 35, 32); --muted: rgb(110, 100, 94); --card: rgba(255, 252, 248, .78); --line: rgba(120, 70, 40, .14); --accent: #C2410C; }
        @media (prefers-color-scheme: dark) { :root { --ink: #E8EAF0; --muted: #A8AEC0; --card: rgba(20, 27, 41, .8); --line: rgba(255,255,255,.12); } body { background: #0B0F17 !important; } }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 48px 16px; color: var(--ink);
               font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               background: linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%); }
        main { max-width: 680px; margin: 0 auto; }
        h1 { font-size: 26px; letter-spacing: -.02em; margin: 0 0 6px; }
        .sub { color: var(--muted); margin: 0 0 24px; font-size: 14px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px 22px; margin-bottom: 14px; }
        .tag { display: inline-block; font-size: 11.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; padding: 3px 9px; border-radius: 999px; background: var(--line); }
        .tag--major { background: #B42318; color: #fff; }
        .when { font-size: 12.5px; color: var(--muted); margin-top: 6px; }
        p { line-height: 1.6; margin: 10px 0 0; white-space: pre-line; }
        a { color: inherit; }
    </style>
</head>
<body>
    <main>
        <h1>Vyrobel status</h1>
        <p class="sub">Incidents posted by the Vyrobel team. Times are UTC.</p>

        @forelse ($incidents as $i)
            <article class="card">
                <span class="tag {{ $i->impact === 'major' && $i->isOpen() ? 'tag--major' : '' }}">{{ \App\Models\StatusIncident::STATUSES[$i->status] ?? $i->status }}</span>
                <h2 style="font-size: 18px; margin: 10px 0 0;">{{ $i->title }}</h2>
                <div class="when">Started {{ $i->started_at->utc()->format('j M Y, H:i') }}{{ $i->resolved_at ? ' · resolved '.$i->resolved_at->utc()->format('j M Y, H:i') : ' · last update '.$i->updated_at?->utc()->format('j M Y, H:i') }}</div>
                <p>{{ $i->message }}</p>
            </article>
        @empty
            <div class="card">No incidents have been posted.</div>
        @endforelse

        <p class="sub" style="margin-top: 24px;"><a href="{{ url('/') }}">vyrobel.com</a></p>
    </main>
</body>
</html>
