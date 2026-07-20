{{-- Guest online check-in (Phase 4b · SG4) — review details → confirm → a submitted pre-check-in intent. This is
     the GUEST side; the front desk still does the authoritative check-in. Passport/ID = honest KYC stub (deferred).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="'Check in · '.$reference">
    <a class="vy-link" href="{{ route('guest.reservation', $reservation) }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← Back to booking</a>

    <h1 class="vy-h1" style="margin-top:10px">Online check-in</h1>
    <p class="vy-lead" style="margin-bottom:16px">A quick head start for a smooth arrival at {{ $property }}. The front desk completes your check-in when you arrive.</p>

    @if (session('status'))
        <div class="vy-note" style="margin-bottom:14px;padding:12px 14px;border-radius:12px;background:rgba(70,120,60,.10);color:rgb(60,105,52);font-size:13px">{{ session('status') }}</div>
    @endif

    {{-- Review the details we hold --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Your details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 20px;font-size:13.5px">
            <div><div style="color:var(--vy-faint);font-size:11px">Lead guest</div><div style="color:var(--vy-ink)">{{ $guestName }}</div></div>
            <div><div style="color:var(--vy-faint);font-size:11px">Confirmation</div><div style="color:var(--vy-ink)">{{ $reference }}</div></div>
            <div><div style="color:var(--vy-faint);font-size:11px">Arrival</div><div style="color:var(--vy-ink)">{{ $checkIn }}</div></div>
            <div><div style="color:var(--vy-faint);font-size:11px">Departure</div><div style="color:var(--vy-ink)">{{ $checkOut }}</div></div>
            <div><div style="color:var(--vy-faint);font-size:11px">Nights</div><div style="color:var(--vy-ink)">{{ $nights }}</div></div>
            <div><div style="color:var(--vy-faint);font-size:11px">Guests</div><div style="color:var(--vy-ink)">{{ $adults }} {{ \Illuminate\Support\Str::plural('adult', $adults) }}@if ($children > 0), {{ $children }} {{ \Illuminate\Support\Str::plural('child', $children) }}@endif</div></div>
        </div>
    </section>

    {{-- Passport / ID — honest KYC stub (account-gated; nothing captured) --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:8px">ID verification</div>
        <div style="display:flex;align-items:center;gap:12px">
            <span style="flex:none;width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:var(--vy-wash);color:var(--vy-muted)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2"/><path d="M14 10h4M14 14h4"/></svg>
            </span>
            <div>
                <div style="font-size:13.5px;color:var(--vy-ink)">Passport / ID check — coming soon</div>
                <div style="font-size:11.5px;color:var(--vy-faint)">Secure ID verification is on the way. For now, please bring your ID to reception on arrival.</div>
            </div>
        </div>
    </section>

    {{-- Confirm / submitted state --}}
    @if ($submitted)
        <section class="vy-card" style="border:1px solid rgba(70,120,60,.35);background:rgba(70,120,60,.06)">
            <div style="display:flex;align-items:center;gap:10px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgb(60,105,52)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                <div>
                    <div style="font-size:14px;font-weight:600;color:rgb(52,92,46)">You’re checked in online</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">Just collect your key at reception when you arrive. See you soon.</div>
                </div>
            </div>
        </section>
    @elseif ($checkinable)
        <form method="POST" action="{{ route('guest.checkin.submit', $reservation) }}">
            @csrf
            <p style="font-size:12px;color:var(--vy-faint);margin:0 0 10px">By confirming, you’re telling us these details are correct and you’re on your way.</p>
            <button type="submit" class="vy-btn">Confirm &amp; check in</button>
        </form>
    @else
        <section class="vy-card">
            <div style="font-size:13px;color:var(--vy-muted)">Online check-in isn’t available for this booking.</div>
        </section>
    @endif
</x-guest.shell>
