{{-- One guest trip card (Phase 4b) — shared by Trips (and reused as the header on the Reservation detail).
     Expects $trip (an App\Support\Guest\GuestTrip::card array). RAW-BLADE RULE: every `@` follows a space,
     newline or tag. --}}
<div class="vy-card @if ($tappable ?? false) vy-card--tappable @endif" style="margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
        <div>
            <div style="font-family:var(--vy-display);font-size:16.5px;font-weight:600;color:var(--vy-ink)">{{ $trip['property'] }}</div>
            @if ($trip['location'] !== '')
                <div style="font-size:12.5px;color:var(--vy-muted);margin-top:1px">{{ $trip['location'] }}</div>
            @endif
        </div>
        <span class="vy-badge vy-badge--{{ $trip['stage_tone'] }}">{{ $trip['stage_label'] }}</span>
    </div>

    <div style="font-size:13.5px;color:var(--vy-ink);margin-top:12px">{{ $trip['check_in'] }} → {{ $trip['check_out'] }}</div>
    <div style="font-size:12px;color:var(--vy-muted)">{{ $trip['nights'] }} {{ \Illuminate\Support\Str::plural('night', $trip['nights']) }}</div>

    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid var(--vy-hairline)">
        <span style="font-family:var(--vy-display);font-size:16px;font-weight:700;color:var(--vy-ink)">{{ $trip['total'] }}</span>
        {{-- G10 · TEXT tokens, not fill tokens. --vy-amber is a FILL (rails/borders); as 12.5px text it measures
             4.08 on the card plate, under AA. The amber branch only renders for an OWED trip, which is why it
             survived G1's sweep — the probe can only measure the branch that actually rendered. --}}
        <span style="font-size:12.5px;font-weight:600;color:{{ $trip['balance_due'] ? 'var(--vy-amber-text)' : 'var(--vy-green-text)' }}">{{ $trip['balance_label'] }}</span>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:6px">
        <span style="font-size:11px;color:var(--vy-faint)">Confirmation {{ $trip['reference'] }}</span>
        <a href="{{ route('guest.reservation', $trip['id']) }}" style="font-size:12.5px;font-weight:600;color:var(--vy-accent-text);text-decoration:none">View booking →</a>
    </div>

    @if ($trip['reviewable'] ?? false)
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--vy-hairline)">
            <a href="{{ route('guest.review', $trip['id']) }}" style="font-size:12.5px;font-weight:600;color:var(--vy-accent-text);text-decoration:none">★ Review your stay →</a>
        </div>
    @elseif ($trip['reviewed'] ?? false)
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--vy-hairline);font-size:12px;color:var(--vy-faint)">★ Reviewed — thank you</div>
    @endif
</div>
