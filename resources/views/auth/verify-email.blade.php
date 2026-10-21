<!DOCTYPE html>
{{-- Verify email (GATE 01) — styled on the shared Vyrobel auth tokens, consistent with login + set-password.
     ⚠️ This page sits OUTSIDE the `verified` gate on purpose: it is the way out of it. --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Confirm your email · Vyrobel</title>
    <x-favicon />
    <link href="/fonts/fonts.css" rel="stylesheet">
    <style>
        :root { --vy-ink: rgb(43,35,32); --vy-muted: rgb(95,88,83); --vy-faint: rgb(108,100,95);
                --vy-accent: rgb(197,90,17); --vy-hairline: rgba(120,70,40,.16); }
        *,*::before,*::after { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:28px;
               font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif; color:var(--vy-ink);
               background:linear-gradient(165deg, rgb(253,250,247), rgb(247,241,235)); }
        .card { width:100%; max-width:452px; background:rgba(255,255,255,.72); backdrop-filter:blur(12px);
                border:1px solid var(--vy-hairline); border-radius:20px; padding:32px 30px;
                box-shadow:0 18px 44px rgba(90,55,30,.09); }
        h1 { font-family:Sora,Inter,sans-serif; font-weight:600; font-size:21px; margin:0 0 10px; }
        p { font-size:13.5px; line-height:1.62; color:var(--vy-muted); margin:0 0 14px; }
        .addr { font-weight:600; color:var(--vy-ink); }
        .btn { display:inline-flex; align-items:center; justify-content:center; width:100%; padding:11px 18px;
               border:none; border-radius:12px; background:var(--vy-accent); color:#fff; font-size:14px;
               font-weight:600; cursor:pointer; font-family:inherit; }
        .muted-btn { display:inline-block; margin-top:12px; background:none; border:none; padding:0;
                     color:var(--vy-faint); font-size:12.5px; text-decoration:underline; cursor:pointer;
                     font-family:inherit; }
        .flash { margin:0 0 14px; padding:10px 13px; border-radius:11px; font-size:13px; font-weight:500;
                 background:rgba(46,125,50,.09); border:1px solid rgba(46,125,50,.28); color:var(--vy-ink); }
        .note { margin-top:18px; padding-top:14px; border-top:1px solid var(--vy-hairline);
                font-size:12.5px; color:var(--vy-faint); line-height:1.6; }
    </style>
</head>
<body>
<main class="card">
    <h1>Confirm your email</h1>

    @if (session('status') === 'verification-link-sent')
        <div class="flash">A new link has been sent to your address.</div>
    @endif

    <p>
        We need to confirm <span class="addr">{{ auth()->user()?->email }}</span> before you can open your
        workspace. Click the link we sent you, and this page will let you through.
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn">Resend the link</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="muted-btn">Sign out</button>
    </form>

    {{-- ⚠️ HONEST ABOUT THE CURRENT STATE. With no mail transport configured the send SUCCEEDS and nothing
         arrives — the exact "appears to succeed" failure measured on 2026-09-23. Saying so beats implying an
         email is on its way. This block disappears on its own the moment a real mailer is configured. --}}
    @if (in_array(config('mail.default'), ['log', 'array'], true))
        <div class="note">
            <strong>Heads up:</strong> email delivery isn’t switched on for this environment yet, so the link
            won’t actually arrive. Ask whoever set this up to finish the mail configuration.
        </div>
    @endif
</main>
</body>
</html>
