<!DOCTYPE html>
{{-- Login-approval PENDING screen (Wave B · GATE 4) — shown after a correct password on an untrusted device when
     the user has login-approval on. The visitor is NOT authenticated (a plain pending session); the sign-in
     completes only after the emailed link is clicked, then "Continue". Self-contained (unauthenticated). --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Approve your sign-in · Vyrobel</title>
    <style>
        :root { --ink:#2B2320; --muted:#5F5853; --accent:#C2410C; --hair:rgba(120,70,40,.12); }
        *,*::before,*::after{box-sizing:border-box}
        body{margin:0;font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:var(--ink);min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(180deg,#FBF3EC,#F6EBE2)}
        .card{width:100%;max-width:400px;background:rgba(255,252,248,.9);border:1px solid rgba(255,255,255,.75);border-radius:22px;box-shadow:rgba(150,90,50,.16) 0 22px 60px;padding:34px 30px}
        .brand{display:flex;align-items:center;gap:9px;margin-bottom:18px}
        .mark{width:34px;height:34px;border-radius:9px;background:linear-gradient(140deg,#E8703A,#C2410C);color:#fff;display:grid;place-items:center;font-family:'Sora',sans-serif;font-weight:700;font-size:17px}
        .name{font-family:'Sora',sans-serif;font-weight:600;font-size:16px}
        h1{font-family:'Sora',sans-serif;font-size:21px;margin:0 0 8px}
        p{color:#4A4038;font-size:14px;line-height:1.55;margin:0 0 12px}
        .btn{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:12px 18px;border:none;border-radius:11px;background:#C2410C;color:#fff;   /* DO-EMAIL-6: 3.08 on the gradient's lighter stop -> 5.18 solid */font:inherit;font-weight:600;cursor:pointer}
        .status{background:rgba(224,146,30,.12);border:1px solid rgba(224,146,30,.3);color:#7a5200;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:14px}
        .row{display:flex;align-items:center;gap:8px;font-size:13px;color:#4A4038;margin:2px 0 14px;cursor:pointer}
        .hint{font-size:12px;color:var(--muted);margin-top:14px;line-height:1.5}
        .hint a{color:var(--accent);text-decoration:none;font-weight:500}
    </style>
    <x-vy-scrollbar />
    <x-vy-selects />
</head>
<body>
    <main class="card">
        <div class="brand"><span class="mark">V</span><span class="name">Vyrobel</span></div>
        <h1>Approve this sign-in</h1>
        <p>We’ve emailed you a link to approve signing in from this device. Open it — on this device or your phone — then come back here and continue.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login-approval.complete') }}">
            @csrf
            <label class="row"><input type="checkbox" name="trust_device" value="1"> Trust this device for 30 days (skip this next time)</label>
            <button type="submit" class="btn">I’ve approved — continue</button>
        </form>

        <p class="hint">Didn’t get the email? Check spam, or <a href="{{ route('login') }}">start over</a>. The link expires in 30 minutes.</p>
    </main>
</body>
</html>
