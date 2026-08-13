{{--
    The ONE branded error page (audit 2026-08-07 · F6-8).

    `resources/views/errors/` did not exist, so every realm — operator, owner, guest, storefront, admin —
    served Laravel's stock unbranded error page. The six views beside this file are thin wrappers that pass a
    code, a title and a line of copy.

    ⚠️ IT NEVER PRINTS THE EXCEPTION. No message, no trace, no SQL, no file path — not even when APP_DEBUG is
    on, because the whole point is that this is what a real user sees. (With APP_DEBUG=true Laravel shows its
    own debug page for 500s instead, so developers lose nothing.)

    ⚠️ SELF-CONTAINED BY DESIGN: no @vite, no Google Fonts, no external request of any kind. An error page is
    exactly the moment the asset pipeline or the network may be the thing that is broken, and it must still
    render. That also keeps it trivially inside the CSP.

    The "back" link is realm-aware so the page returns the user to somewhere they can actually use.
--}}
@php
    // Realm detection from the path, so a guest is never sent to the operator app and vice versa.
    $path = request()->path();
    [$homeUrl, $homeLabel] = match (true) {
        str_starts_with($path, 'admin') => ['/admin', 'Back to the console'],
        str_starts_with($path, 'guest') => ['/guest/trips', 'Back to your trips'],
        str_starts_with($path, 'owner') => ['/owner', 'Back to your portal'],
        str_starts_with($path, 'app') => ['/app/dashboard', 'Back to the dashboard'],
        default => ['/', 'Back to safety'],
    };
    $isAdmin = str_starts_with($path, 'admin');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Vyrobel</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: {{ $isAdmin ? '#E8EAF0' : 'rgb(43, 35, 32)' }};
            background: {{ $isAdmin
                ? 'radial-gradient(1200px 600px at 50% -10%, #1B2436 0%, #0B0F17 60%)'
                : 'linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%)' }};
        }
        .card {
            width: 100%; max-width: 520px; padding: 40px 36px; text-align: center;
            background: {{ $isAdmin ? 'rgba(20, 27, 41, .72)' : 'rgba(255, 252, 248, .72)' }};
            border: 1px solid {{ $isAdmin ? 'rgba(255,255,255,.10)' : 'rgba(120, 70, 40, .12)' }};
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(90, 55, 30, .16);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
        }
        .mark {
            width: 44px; height: 44px; margin: 0 auto 20px; border-radius: 12px;
            display: grid; place-items: center; color: #fff; font-weight: 700; font-size: 22px;
            background: {{ $isAdmin ? 'linear-gradient(140deg,#6366F1,#4338CA)' : 'linear-gradient(140deg,#D14102,#C2410C)' }};
        }
        .code { font-size: 13px; font-weight: 700; letter-spacing: .10em; text-transform: uppercase;
                color: {{ $isAdmin ? '#8C93A6' : 'rgb(95, 88, 83)' }}; margin: 0 0 8px; }
        h1 { font-size: 25px; font-weight: 700; letter-spacing: -.02em; margin: 0 0 10px; }
        p  { font-size: 14.5px; line-height: 1.6; margin: 0 0 26px;
             color: {{ $isAdmin ? '#A8AEC0' : 'rgb(110, 100, 94)' }}; }
        a.btn {
            display: inline-block; padding: 11px 22px; border-radius: 12px; text-decoration: none;
            font-size: 14px; font-weight: 600; color: #fff;
            background: {{ $isAdmin ? 'linear-gradient(140deg,#6366F1,#4338CA)' : 'linear-gradient(140deg,#D14102,#C2410C)' }};
        }
        a.btn:focus-visible { outline: 3px solid rgba(232,112,58,.5); outline-offset: 2px; }
        .foot { margin-top: 22px; font-size: 11.5px; color: {{ $isAdmin ? '#6B7285' : 'rgb(108, 100, 95)' }}; }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>
    <x-vy-scrollbar />
</head>
<body>
    <main class="card">
        <div class="mark" aria-hidden="true">V</div>
        <p class="code">Error {{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="btn" href="{{ $homeUrl }}">{{ $homeLabel }}</a>
        <p class="foot">If this keeps happening, contact your Vyrobel administrator.</p>
    </main>
</body>
</html>
