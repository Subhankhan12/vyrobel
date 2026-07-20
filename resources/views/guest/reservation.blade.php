{{-- Guest Reservation detail (Phase 4b · SG2) — one owned booking: stay, charges/balance, timeline. Payment is
     4c (balance shown, "Settle" stubbed). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="'Booking '.$trip['reference']">
    <a class="vy-link" href="{{ route('guest.trips') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← All trips</a>

    <h1 class="vy-h1" style="margin-top:10px">{{ $trip['property'] }}</h1>
    @if ($trip['location'] !== '')
        <p class="vy-lead" style="margin-bottom:14px">{{ $trip['location'] }}</p>
    @endif

    {{-- Stay --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
            <div class="vy-eyebrow">Your stay</div>
            <span class="vy-badge vy-badge--{{ $trip['stage_tone'] }}">{{ $trip['stage_label'] }}</span>
        </div>
        <div style="font-size:15px;color:var(--vy-ink);margin-top:12px">{{ $trip['check_in'] }} → {{ $trip['check_out'] }}</div>
        <div style="font-size:12.5px;color:var(--vy-muted)">{{ $trip['nights'] }} {{ \Illuminate\Support\Str::plural('night', $trip['nights']) }} · {{ $adults }} {{ \Illuminate\Support\Str::plural('adult', $adults) }}@if ($children > 0), {{ $children }} {{ \Illuminate\Support\Str::plural('child', $children) }}@endif</div>
        <div style="font-size:11px;color:var(--vy-faint);margin-top:8px">Confirmation {{ $trip['reference'] }}</div>
    </section>

    {{-- Charges + balance --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Charges</div>
        @forelse ($folio['lines'] as $line)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid var(--vy-hairline);font-size:13.5px">
                <span style="color:var(--vy-ink)">{{ $line['label'] }}</span>
                <span style="color:var(--vy-ink)">{{ $line['amount'] }}</span>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted);padding-bottom:6px">No charges posted yet.</div>
        @endforelse

        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:11px;font-size:13px;color:var(--vy-muted)">
            <span>Total charges</span><span>{{ $folio['charges'] }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:4px;font-size:13px;color:var(--vy-muted)">
            <span>Paid</span><span>{{ $folio['paid'] }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;margin-top:10px;padding-top:10px;border-top:1px solid var(--vy-hairline)">
            <span style="font-family:var(--vy-display);font-size:15px;font-weight:700;color:var(--vy-ink)">Balance</span>
            <span style="font-family:var(--vy-display);font-size:16px;font-weight:700;color:{{ $folio['balance_due'] ? 'var(--vy-amber)' : 'rgb(70,120,60)' }}">{{ $folio['balance_due'] ? $folio['balance'] : 'Paid in full' }}</span>
        </div>

        @if ($folio['balance_due'])
            <div style="margin-top:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <span class="vy-btn" style="opacity:.55;cursor:not-allowed" title="Coming soon">Settle your stay · Soon</span>
                <span style="font-size:11.5px;color:var(--vy-faint)">Online payment is coming — for now, settle on arrival or reply to your confirmation.</span>
            </div>
        @endif
    </section>

    {{-- Timeline --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Timeline</div>
        @forelse ($timeline as $event)
            <div style="display:flex;gap:11px;padding:6px 0">
                <span style="flex:none;width:7px;height:7px;border-radius:50%;background:var(--vy-accent);margin-top:6px"></span>
                <div>
                    <div style="font-size:13.5px;color:var(--vy-ink)">{{ $event['label'] }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $event['at'] }}</div>
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">Nothing to show yet.</div>
        @endforelse
    </section>

    @if ($trip['stage'] !== 'past' && $trip['stage'] !== 'cancelled')
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="vy-btn" href="{{ route('guest.checkin', $trip['id']) }}" style="text-decoration:none">Online check-in</a>
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.arrival', $trip['id']) }}" style="text-decoration:none">Arrival info</a>
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.manage', $trip['id']) }}" style="text-decoration:none">Manage booking</a>
        </div>
    @endif
</x-guest.shell>
