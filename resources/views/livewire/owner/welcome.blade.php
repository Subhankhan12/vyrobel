{{-- Owner WELCOME / onboarding (Wave B · GATE 6) — the honest thin flow: real units, the REAL management-fee rate,
     a hand-off to the certified Payout Method (NO IBAN capture, NO fake micro-deposit), real next steps. Nothing
     fabricated (#39). --}}
<x-owner.shell active="welcome" title="Welcome" :subtitle="'A quick tour to get you set up.'" :viewer="$viewer">

    <div style="max-width:720px;margin:0 auto">
        <section class="vy-card vy-fade" style="padding:26px;text-align:center">
            <div style="width:52px;height:52px;border-radius:14px;background:var(--vy-grad);display:grid;place-items:center;margin:0 auto 14px;color:#fff">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>
            </div>
            <h1 style="font-family:var(--vy-display);font-weight:600;font-size:22px;margin:0">Welcome{{ $firstName !== '' ? ', '.$firstName : '' }} 👋</h1>
            <p class="vy-line-sub" style="font-size:13.5px;margin-top:8px">Your owner portal is ready. Here’s everything that’s already set up — and the couple of things worth doing next.</p>
        </section>

        {{-- Step 1 — your units (real) --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.04s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:rgba(217,102,46,.14);color:var(--vy-accent);display:grid;place-items:center;font-weight:700;font-size:13px">1</span>
                <h2 class="vy-card-title" style="margin:0">Your units are live</h2>
            </div>
            @if ($unitCount > 0)
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 8px">{{ $unitCount }} {{ \Illuminate\Support\Str::plural('unit', $unitCount) }} on your account: <strong style="color:var(--vy-ink)">{{ implode(', ', $units) }}</strong>.</p>
                <a href="{{ route('owner.units') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Open My Units →</a>
            @else
                <p class="vy-line-sub" style="font-size:12.5px;margin:0">No units are linked to your account yet — your management team adds these. <a href="{{ route('owner.add-unit') }}" style="color:var(--vy-accent);font-weight:600">Request a unit →</a></p>
            @endif
        </section>

        {{-- Step 2 — your management fee (REAL rate, never the wireframe's hardcoded 15%) --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.08s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:rgba(217,102,46,.14);color:var(--vy-accent);display:grid;place-items:center;font-weight:700;font-size:13px">2</span>
                <h2 class="vy-card-title" style="margin:0">How you get paid</h2>
            </div>
            @if ($commission !== null)
                <p class="vy-line-sub" style="font-size:12.5px;margin:0">Your management agreement charges <strong style="color:var(--vy-ink)">{{ $commission }}% of booking revenue</strong>; the rest is yours, paid out per statement. You’ll see every figure on your Statement and Reports.</p>
            @else
                <p class="vy-line-sub" style="font-size:12.5px;margin:0">Your management fee is set by your agreement — it’ll show on your first statement.</p>
            @endif
        </section>

        {{-- Step 3 — payout account hand-off (NO IBAN capture here — the certified page owns it) --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.12s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:{{ $hasPayout ? 'rgba(34,120,60,.14)' : 'rgba(224,146,30,.16)' }};color:{{ $hasPayout ? '#227a3c' : 'var(--vy-amber)' }};display:grid;place-items:center;font-weight:700;font-size:13px">3</span>
                <h2 class="vy-card-title" style="margin:0">{{ $hasPayout ? 'Your payout account' : 'Add your payout account' }}</h2>
            </div>
            @if ($hasPayout)
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 8px">A payout account is on file — you’re all set to receive money.</p>
            @else
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 8px">So we can send your earnings, add a bank account on the secure Payout Method page. We’ll never ask for it here.</p>
            @endif
            <a href="{{ route('owner.payout-method') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">{{ $hasPayout ? 'Review payout method →' : 'Add payout method →' }}</a>
        </section>

        {{-- Step 4 — notifications --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.16s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:rgba(217,102,46,.14);color:var(--vy-accent);display:grid;place-items:center;font-weight:700;font-size:13px">4</span>
                <h2 class="vy-card-title" style="margin:0">Choose what we tell you</h2>
            </div>
            <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 8px">Statements, payouts and reviews — pick which reach your inbox, and set quiet hours if you like.</p>
            <a href="{{ route('owner.notification-preferences') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Notification settings →</a>
        </section>

        {{-- Done --}}
        <section class="vy-card vy-fade" style="padding:22px;margin-top:14px;text-align:center;animation-delay:.2s">
            <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 12px">That’s the tour. Your first statement lands after your first payout period — everything after that runs itself.</p>
            <a href="{{ route('owner.dashboard') }}" class="vy-btn vy-btn--accent" style="display:inline-flex;padding:10px 22px;text-decoration:none">Go to your dashboard</a>
        </section>
    </div>

</x-owner.shell>
