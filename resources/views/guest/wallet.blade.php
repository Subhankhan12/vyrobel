{{-- Guest Wallet (Phase 4c · SG2) — DERIVED balance over the append-only credit ledger, redeem-a-code, apply to a
     booking, added/used history. Integer money, reconciling. RAW-BLADE RULE: every `@` follows a space, newline or
     tag. --}}
<x-guest.shell active="wallet" title="Wallet">
    <h1 class="vy-h1">Wallet</h1>
    <p class="vy-lead">Your credit balance — refunds, promo codes and rewards.</p>

    @if (session('status'))
        <div class="vy-notice" style="margin-bottom:14px">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="vy-notice" style="margin-bottom:14px;background:rgba(180,68,58,.10);border-color:rgba(180,68,58,.3);border-left-color:rgb(150,50,42)">{{ session('error') }}</div>
    @endif

    {{-- Balance --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow">Available credit</div>
        <div style="font-family:var(--vy-display);font-size:30px;font-weight:700;color:{{ $spendable_minor > 0 ? 'rgb(70,120,60)' : 'var(--vy-ink)' }};margin-top:4px">{{ $spendable }}</div>
        <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">Applied to your bookings at checkout · expired credit isn’t included.</div>
    </section>

    {{-- Redeem a code --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">Redeem a code</div>
        <form method="POST" action="{{ route('guest.wallet.redeem') }}" style="display:flex;gap:8px;flex-wrap:wrap">
            @csrf
            <input type="text" name="code" placeholder="Enter a code" maxlength="64" style="flex:1;min-width:160px;padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff">
            <button type="submit" class="vy-btn">Redeem</button>
        </form>
    </section>

    {{-- Apply to a booking --}}
    @if ($spendable_minor > 0 && count($owed) > 0)
        <section class="vy-card" style="margin-bottom:14px">
            <div class="vy-eyebrow" style="margin-bottom:10px">Apply to a booking</div>
            @foreach ($owed as $booking)
                <form method="POST" action="{{ route('guest.wallet.apply', $booking['id']) }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:9px 0;border-bottom:1px solid var(--vy-hairline)">
                    @csrf
                    <div style="flex:1;min-width:150px">
                        <div style="font-size:13.5px;color:var(--vy-ink)">{{ $booking['property'] }}</div>
                        <div style="font-size:11.5px;color:var(--vy-faint)">{{ $booking['reference'] }} · {{ $booking['balance'] }} owed</div>
                    </div>
                    <input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" style="width:96px;padding:8px 10px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
                    <button type="submit" class="vy-btn vy-btn--ghost">Apply</button>
                </form>
            @endforeach
        </section>
    @endif

    {{-- History --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">Added &amp; used</div>
        @forelse ($entries as $entry)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid var(--vy-hairline)">
                <div>
                    <div style="font-size:13.5px;color:var(--vy-ink)">{{ $entry['label'] }}@if ($entry['expired']) <span style="font-size:10.5px;color:var(--vy-faint)">· expired</span>@endif</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $entry['description'] }}@if ($entry['expires'] && ! $entry['expired']) · expires {{ $entry['expires'] }}@endif</div>
                </div>
                <div style="text-align:right;white-space:nowrap">
                    <div style="font-size:14px;font-weight:600;color:{{ $entry['is_debit'] ? 'var(--vy-muted)' : 'rgb(70,120,60)' }}">{{ $entry['signed_amount'] }}</div>
                    <div style="font-size:11px;color:var(--vy-faint)">{{ $entry['date'] }}</div>
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">No credit yet — redeem a code or check back after a refund.</div>
        @endforelse
    </section>

    {{-- Deferred, honestly --}}
    <section class="vy-card">
        <div class="vy-eyebrow" style="margin-bottom:6px">Rewards</div>
        <div style="font-size:13px;color:var(--vy-muted)">Loyalty tiers and referral rewards are coming soon.</div>
    </section>
</x-guest.shell>
