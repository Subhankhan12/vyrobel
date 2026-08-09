<!DOCTYPE html>
{{-- Login-approval CONFIRMED landing (Wave B · GATE 4) — the emailed signed link lands here. It marks the
     request approved (or shows an honest failure for a stale/expired/forged link). It logs NO ONE in — the
     original browser completes the sign-in by pressing "Continue". Self-contained (unauthenticated). --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign-in approved · Vyrobel</title>
    <style>
        :root { --ink:#2B2320; --muted:#8A8079; }
        *,*::before,*::after{box-sizing:border-box}
        body{margin:0;font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:var(--ink);min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(180deg,#FBF3EC,#F6EBE2)}
        .card{width:100%;max-width:400px;background:rgba(255,252,248,.9);border:1px solid rgba(255,255,255,.75);border-radius:22px;box-shadow:rgba(150,90,50,.16) 0 22px 60px;padding:34px 30px;text-align:center}
        .mark{width:44px;height:44px;border-radius:12px;color:#fff;display:grid;place-items:center;margin:0 auto 14px}
        .ok{background:linear-gradient(140deg,#43b06a,#227a3c)}
        .no{background:linear-gradient(140deg,#e0923c,#b26a08)}
        h1{font-family:'Sora',sans-serif;font-size:20px;margin:0 0 8px}
        p{color:#4A4038;font-size:14px;line-height:1.55;margin:0}
    </style>
    <x-vy-scrollbar />
</head>
<body>
    <main class="card">
        @if ($ok)
            <div class="mark ok"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
            <h1>Sign-in approved</h1>
            <p>You can return to the device you were signing in on and press <strong>Continue</strong>. You can close this tab.</p>
        @else
            <div class="mark no"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></div>
            <h1>This link can’t be used</h1>
            <p>It may have expired, already been used, or been superseded by a newer sign-in. Start signing in again to get a fresh approval link.</p>
        @endif
    </main>
</body>
</html>
