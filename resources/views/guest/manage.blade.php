{{-- Guest Manage Booking (Phase 4b · SG3) — date change / add-ons / cancellation, all as operator-confirmed
     REQUESTS (the guest never writes inventory/folio/refund). RAW-BLADE RULE: every `@` follows a space, newline
     or tag. --}}
<x-guest.shell active="trips" :title="'Manage '.$reference">
    <a class="vy-link" href="{{ route('guest.reservation', $reservation) }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← Back to booking</a>
    <h1 class="vy-h1" style="margin-top:10px">Manage your booking</h1>
    <p class="vy-lead">Changes are confirmed by the property — we’ll message you the moment they are.</p>

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-notice" style="background:rgba(224,146,30,.12);border-color:rgba(224,146,30,.4);border-left-color:var(--vy-amber)">{{ $errors->first() }}</div>
    @endif

    {{-- Pending requests --}}
    @if (! empty($pending))
        <section class="vy-card" style="margin-bottom:14px">
            <div class="vy-eyebrow" style="margin-bottom:10px">Pending requests</div>
            @foreach ($pending as $req)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--vy-hairline)">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $req['label'] }}</div>
                        <div style="font-size:12px;color:var(--vy-muted)">{{ $req['detail'] }}</div>
                    </div>
                    <span class="vy-badge vy-badge--pending" style="align-self:flex-start">Pending</span>
                </div>
            @endforeach
            <p style="font-size:11.5px;color:var(--vy-faint);margin:10px 0 0">Nothing has changed on your booking yet — a request is confirmed by the property first.</p>
        </section>
    @endif

    {{-- Date change --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:8px">Change dates</div>
        <p style="font-size:12.5px;color:var(--vy-muted);margin:0 0 12px">Currently {{ $checkIn }} → {{ $checkOut }}. Pick new dates to see the price difference, then request the change.</p>

        <form method="GET" action="{{ route('guest.manage', $reservation) }}">
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <div class="vy-field" style="flex:1;min-width:140px;margin-bottom:10px">
                    <label class="vy-label" for="new_check_in" style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--vy-muted);display:block;margin-bottom:5px">New check-in</label>
                    <input class="vy-input" type="date" id="new_check_in" name="new_check_in" value="{{ $newCheckIn }}" required style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.8);font-family:var(--vy-sans);font-size:14px">
                </div>
                <div class="vy-field" style="flex:1;min-width:140px;margin-bottom:10px">
                    <label class="vy-label" for="new_check_out" style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--vy-muted);display:block;margin-bottom:5px">New check-out</label>
                    <input class="vy-input" type="date" id="new_check_out" name="new_check_out" value="{{ $newCheckOut }}" required style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.8);font-family:var(--vy-sans);font-size:14px">
                </div>
            </div>
            <button type="submit" class="vy-btn vy-btn--ghost">See price difference</button>
        </form>

        @if ($quote)
            <div style="margin-top:14px;padding:14px;border:1px solid var(--vy-hairline);border-radius:12px">
                @if ($quote['priced'])
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--vy-muted);padding-bottom:5px"><span>New stay</span><span>{{ $quote['new'] }}</span></div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--vy-muted)"><span>Current</span><span>{{ $quote['current'] }}</span></div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-top:8px;padding-top:8px;border-top:1px solid var(--vy-hairline)"><span style="font-weight:700;color:var(--vy-ink)">Difference</span><span style="font-family:var(--vy-display);font-weight:700;color:var(--vy-ink)">{{ $quote['difference'] }}</span></div>
                    <p style="font-size:11px;color:var(--vy-faint);margin:8px 0 0">Estimate only — subject to the property’s confirmation. Any payment is handled after they confirm.</p>
                @else
                    <p style="font-size:12.5px;color:var(--vy-muted);margin:0">Those dates don’t have a published price yet — request the change and the property will confirm the price.</p>
                @endif

                <form method="POST" action="{{ route('guest.manage.date-change', $reservation) }}" style="margin-top:12px">
                    @csrf
                    <input type="hidden" name="new_check_in" value="{{ $newCheckIn }}">
                    <input type="hidden" name="new_check_out" value="{{ $newCheckOut }}">
                    <button type="submit" class="vy-btn">Request date change</button>
                </form>
            </div>
        @endif
    </section>

    {{-- Add-ons — the REAL catalogue lives on the dedicated Extras page (settles to the folio on confirm) --}}
    <section class="vy-card" style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
        <div>
            <div class="vy-eyebrow" style="margin-bottom:4px">Enhance your stay</div>
            <p style="font-size:12.5px;color:var(--vy-muted);margin:0">Add breakfast, transfers and more — everything settles to your booking.</p>
        </div>
        <a class="vy-btn vy-btn--accent" href="{{ route('guest.extras', $reservation) }}" style="flex:none">Browse extras</a>
    </section>

    {{-- Cancellation --}}
    <section class="vy-card">
        <div class="vy-eyebrow" style="margin-bottom:8px">Cancel booking</div>
        <p style="font-size:12.5px;color:var(--vy-muted);margin:0 0 12px">Request cancellation — the property confirms it and handles any refund per their policy.</p>
        <form method="POST" action="{{ route('guest.manage.cancel', $reservation) }}">
            @csrf
            <div class="vy-field" style="margin-bottom:12px">
                <label class="vy-label" for="reason" style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--vy-muted);display:block;margin-bottom:5px">Reason</label>
                <select name="reason" id="reason" required style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.8);font-family:var(--vy-sans);font-size:14px;color:var(--vy-ink)">
                    <option value="">Choose a reason…</option>
                    @foreach ($cancelReasons as $reason)
                        <option value="{{ $reason }}">{{ $reason }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="vy-btn" style="background:none;color:var(--vy-amber);border:1px solid rgba(178,106,8,.45);box-shadow:none">Request cancellation</button>
        </form>
    </section>
</x-guest.shell>
