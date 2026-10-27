{{-- Guest Manage Booking (Phase 4b · SG3; guest Phase-4 pixel-diff G2) — date change / add-ons / cancellation,
     all as operator-confirmed REQUESTS (the guest never writes inventory/folio/refund; can't confirm its own).
     Matched to the wireframe: a hero + card sections + motion, over the already-real GuestChangeRequest flow.
     ⚠️ HONEST (#39): the wireframe's "Convert to travel credit" / "Instead of losing your deposit…" retention
     upsell has NO backend → REFUSED (the honest path is a cancellation request the property confirms + refunds
     per policy). The guest-count stepper has no change-request type → omitted. RAW-BLADE RULE: `@` after a space,
     newline or tag. --}}
<x-guest.shell active="trips" :title="__('guest/manage.page_title', ['reference' => $reference])">
    <a class="vy-link" href="{{ route('guest.reservation', $reservation) }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('guest/manage.back_to_booking') }}</a>

    {{-- Hero --}}
    <div style="background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:22px 26px;margin:12px 0 18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
        <h1 style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin:0">{{ __('guest/manage.heading') }}</h1>
        <div style="font-size:13px;color:var(--vy-muted);margin-top:4px">{{ __('guest/manage.subheading', ['property' => $property, 'check_in' => $checkIn, 'check_out' => $checkOut]) }}</div>
    </div>

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-notice" style="background:rgba(224,146,30,.12);border-color:rgba(224,146,30,.4);border-left-color:var(--vy-amber)">{{ $errors->first() }}</div>
    @endif

    {{-- Pending requests --}}
    @if (! empty($pending))
        <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
            <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/manage.pending_requests') }}</div>
            @foreach ($pending as $req)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--vy-hairline)">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $req['label'] }}</div>
                        <div style="font-size:12px;color:var(--vy-muted)">{{ $req['detail'] }}</div>
                    </div>
                    <span class="vy-badge vy-badge--pending" style="align-self:flex-start">{{ __('guest/manage.pending_badge') }}</span>
                </div>
            @endforeach
            <p style="font-size:11.5px;color:var(--vy-faint);margin:10px 0 0">{{ __('guest/manage.pending_note') }}</p>
        </section>
    @endif

    {{-- Date change --}}
@if ($canRequestChanges)
    {{-- ⚠️⚠️ THE ACTIONS ARE GATED, THE PAGE IS NOT — AND THE SPLIT IS DELIBERATE. A guest has every reason to
         open a stay that has ENDED: their dates, their folio, their receipt are all above this line and stay
         visible. What makes no sense on a finished booking is ASKING TO CHANGE IT.
         ⚠️ 🔬 Measured before this gate existed: all three requests went straight through on a cancelled,
         checked-out or no-show stay — the guest was told "we'll be in touch" and an operator received a
         pending request they could not action. ⚠️ The server refuses them too; hiding a form is the UI half
         of a gate, never the gate (#30, read in both directions). --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
        <div class="vy-eyebrow" style="margin-bottom:8px">{{ __('guest/manage.pick_new_dates') }}</div>
        <p style="font-size:12.5px;color:var(--vy-muted);margin:0 0 12px">{{ __('guest/manage.currently', ['check_in' => $checkIn, 'check_out' => $checkOut]) }}</p>

        <form method="GET" action="{{ route('guest.manage', $reservation) }}">
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <div class="vy-field" style="flex:1;min-width:140px;margin-bottom:10px">
                    <label class="vy-label" for="new_check_in">{{ __('guest/manage.new_check_in') }}</label>
                    <input class="vy-input" type="date" id="new_check_in" name="new_check_in" value="{{ $newCheckIn }}" required>
                </div>
                <div class="vy-field" style="flex:1;min-width:140px;margin-bottom:10px">
                    <label class="vy-label" for="new_check_out">{{ __('guest/manage.new_check_out') }}</label>
                    <input class="vy-input" type="date" id="new_check_out" name="new_check_out" value="{{ $newCheckOut }}" required>
                </div>
            </div>
            <button type="submit" class="vy-btn vy-btn--ghost">{{ __('guest/manage.see_price_difference') }}</button>
        </form>

        @if ($quote)
            <div style="margin-top:14px;padding:14px;border:1px solid var(--vy-hairline);border-radius:12px;background:var(--vy-wash)">
                @if ($quote['priced'])
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--vy-muted);padding-bottom:5px"><span>{{ __('guest/manage.new_stay') }}</span><span>{{ $quote['new'] }}</span></div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--vy-muted)"><span>{{ __('guest/manage.current') }}</span><span>{{ $quote['current'] }}</span></div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-top:8px;padding-top:8px;border-top:1px solid var(--vy-hairline)"><span style="font-weight:700;color:var(--vy-ink)">{{ __('guest/manage.difference') }}</span><span style="font-family:var(--vy-display);font-weight:700;color:var(--vy-ink)">{{ $quote['difference'] }}</span></div>
                    <p style="font-size:11px;color:var(--vy-faint);margin:8px 0 0">{{ __('guest/manage.estimate_note') }}</p>
                @else
                    <p style="font-size:12.5px;color:var(--vy-muted);margin:0">{{ __('guest/manage.unpriced_note') }}</p>
                @endif

                <form method="POST" action="{{ route('guest.manage.date-change', $reservation) }}" style="margin-top:12px">
                    @csrf
                    <input type="hidden" name="new_check_in" value="{{ $newCheckIn }}">
                    <input type="hidden" name="new_check_out" value="{{ $newCheckOut }}">
                    <button type="submit" class="vy-btn">{{ __('guest/manage.request_date_change') }}</button>
                </form>
            </div>
        @endif
    </section>

    {{-- Add-ons — the REAL catalogue lives on the dedicated Extras page (settles to the folio on confirm) --}}
    <section class="vy-card" style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .18s both">
        <div>
            <div class="vy-eyebrow" style="margin-bottom:4px">{{ __('guest/manage.add_to_your_stay') }}</div>
            <p style="font-size:12.5px;color:var(--vy-muted);margin:0">{{ __('guest/manage.add_to_your_stay_sub') }}</p>
        </div>
        <a class="vy-btn vy-btn--accent" href="{{ route('guest.extras', $reservation) }}" style="flex:none">{{ __('guest/manage.browse_extras') }}</a>
    </section>

    {{-- Cancellation — real policy (free-until), request only; NO fabricated "convert to travel credit" --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .22s both">
        <div class="vy-eyebrow" style="margin-bottom:8px">{{ __('guest/manage.cancellation_policy') }}</div>
        @if ($freeUntil !== null)
            <div style="display:flex;align-items:center;gap:9px;font-size:13px;color:var(--vy-ink);margin-bottom:12px"><span style="width:8px;height:8px;border-radius:50%;background:var(--vy-green-text)"></span>{!! __('guest/manage.free_until', ['date' => e($freeUntil)]) !!}</div>
        @else
            <p style="font-size:12.5px;color:var(--vy-muted);margin:0 0 12px">{{ __('guest/manage.policy_by_property') }}</p>
        @endif
        {{-- ⚠️ §N.4k — this form is ONE required <select> and one submit, so pressing Enter with a reason
             chosen fired the cancellation request immediately, with no chance to reconsider: the guest
             means "confirm my choice" and gets "send the request". G4 proved Enter can fire a real
             write; here the write is the most consequential action on the page. The prototype puts a
             REVIEW step in front of it (its cancellation modal has review → done); this is the same
             intent at markup level, using the destructive-submit idiom the repo already uses in admin.
             `data-guard-submit` is the shared double-submit guard. --}}
        <form method="POST" action="{{ route('guest.manage.cancel', $reservation) }}" data-guard-submit
              onsubmit="return confirm(@js(__('guest/manage.cancel_confirm')));">
            @csrf
            <div class="vy-field" style="margin-bottom:12px">
                <label class="vy-label" for="reason">{{ __('guest/manage.reason') }}</label>
                <select class="vy-input" name="reason" id="reason" required>
                    <option value="">{{ __('guest/manage.choose_reason') }}</option>
                    @foreach ($cancelReasons as $reason)
                        <option value="{{ $reason }}">{{ $cancelReasonLabels[$reason] ?? $reason }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="vy-btn" style="background:none;color:var(--vy-amber-text);border:1px solid rgba(178,106,8,.45);box-shadow:none">{{ __('guest/manage.request_cancellation') }}</button>
        </form>
    </section>
@else
    {{-- ⚠️ AN HONEST CLOSE, NOT A BLANK SPACE. A page that simply stops offering things reads as broken; this
         says why, and points at what IS still here. --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
        <div class="vy-eyebrow" style="margin-bottom:8px">{{ __('guest/manage.closed_heading') }}</div>
        <p style="font-size:12.5px;color:var(--vy-muted);margin:0">
            {{ __('guest/manage.closed_body') }}
        </p>
    </section>
@endif
</x-guest.shell>
