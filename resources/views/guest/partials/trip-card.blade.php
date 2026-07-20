{{-- One guest trip card (Phase 4b) — shared by Trips (and reused as the header on the Reservation detail).
     Expects $trip (an App\Support\Guest\GuestTrip::card array). RAW-BLADE RULE: every `@` follows a space,
     newline or tag. --}}
<div class="vy-card" style="margin-bottom:12px">
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
        <span style="font-size:12.5px;font-weight:600;color:{{ $trip['balance_due'] ? 'var(--vy-amber)' : 'rgb(70,120,60)' }}">{{ $trip['balance_label'] }}</span>
    </div>

    <div style="font-size:11px;color:var(--vy-faint);margin-top:6px">Confirmation {{ $trip['reference'] }}</div>
</div>
