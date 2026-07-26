{{-- Guest Payments hub (Phase 4c · SG1) — balance across the guest's own bookings + payment history (real, via
     FolioTotals). Paying by card is the deferred Stripe rail — shown honestly, never faked. RAW-BLADE RULE: every
     `@` follows a space, newline or tag. --}}
<x-guest.shell active="payments" title="Payments">
    <h1 class="vy-h1">Payments</h1>
    <p class="vy-lead">Your balance and payment history across your stays.</p>

    @if (session('pay_notice'))
        <div class="vy-card" style="margin-bottom:12px;border-left:3px solid var(--vy-accent)"><div style="font-size:13px;color:var(--vy-ink)">{{ session('pay_notice') }}</div></div>
    @endif
    @if (session('pay_error'))
        <div class="vy-card" style="margin-bottom:12px;border-left:3px solid var(--vy-accent)"><div style="font-size:13px;color:var(--vy-accent)">{{ session('pay_error') }}</div></div>
    @endif

    {{-- Summary --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
            <div>
                <div class="vy-eyebrow">Total due</div>
                <div style="font-family:var(--vy-display);font-size:26px;font-weight:700;color:{{ $overview['total_balance_minor'] > 0 ? 'var(--vy-amber)' : 'rgb(70,120,60)' }};margin-top:4px">
                    {{ $overview['total_balance_minor'] > 0 ? $overview['total_balance'] : 'Nothing due' }}
                </div>
                <div style="font-size:12px;color:var(--vy-faint);margin-top:2px">Paid to date · {{ $overview['total_paid'] }}</div>
            </div>
            @if ($overview['total_balance_minor'] > 0)
                <div style="text-align:right;font-size:11px;color:var(--vy-faint);max-width:230px">Pay a balance below by card — securely on Stripe. Your card details never touch Vyrobel.</div>
            @endif
        </div>
    </section>

    {{-- Per-booking balances --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">Your bookings</div>
        @forelse ($overview['bookings'] as $booking)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--vy-hairline)">
                <div>
                    <div style="font-size:14px;color:var(--vy-ink)">{{ $booking['property'] }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $booking['stay'] }} · {{ $booking['reference'] }}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:14px;font-weight:600;color:{{ $booking['balance_due'] ? 'var(--vy-amber)' : 'rgb(70,120,60)' }}">{{ $booking['balance_due'] ? $booking['balance'] : 'Paid' }}</div>
                    @if ($booking['balance_due'])
                        {{-- 🔒 Pay by card — hosted Stripe Checkout (card never touches Vyrobel); settles on the webhook. --}}
                        <form method="POST" action="{{ route('guest.payments.pay', $booking['id']) }}" style="margin:4px 0 0"
                              data-guard-submit>
                            @csrf
                            <x-busy-btn class="vy-btn vy-btn--accent" style="padding:5px 12px;font-size:12px" busy-label="Redirecting…">Pay by card</x-busy-btn>
                        </form>
                    @endif
                    <a class="vy-link" href="{{ route('guest.receipt', $booking['id']) }}" style="font-size:12px;color:var(--vy-accent);text-decoration:none">Receipt →</a>
                </div>
            </div>
        @empty
            <div class="vy-empty">No bookings yet.</div>
        @endforelse
    </section>

    {{-- Payment history --}}
    <section class="vy-card">
        <div class="vy-eyebrow" style="margin-bottom:10px">Payment history</div>
        @forelse ($overview['history'] as $entry)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--vy-hairline);font-size:13px">
                <span style="color:var(--vy-ink)">{{ $entry['label'] }}<span style="color:var(--vy-faint)"> · {{ $entry['reference'] }}</span></span>
                <span style="color:var(--vy-ink);white-space:nowrap">{{ $entry['amount'] }} <span style="color:var(--vy-faint);font-size:11.5px">{{ $entry['date'] }}</span></span>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">No payments recorded yet.</div>
        @endforelse
    </section>
</x-guest.shell>
