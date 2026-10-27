{{-- Public confirmation (design pass · Booking Screen 3, step 3). Shows the REAL reservation
     reference for a genuinely-created booking (via AssignmentService + FolioService). Read-only. --}}
<x-public.shell :property="$property" :title="__('booking/reservation.booking_confirmed')" :seo="$seo">

    <div class="vy-confirm vy-fade">
        <div class="vy-confirm-check">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        {{-- ⚠️ PROMOTED FROM A <div>. This is the confirmation page's own visible title; only its element
             was wrong, so the page a guest lands on after paying had no heading at all. --}}
        <h1 class="vy-confirm-title" style="margin:0">{{ __('booking/reservation.booking_confirmed') }}</h1>
        <div class="vy-confirm-sub">{{ __('booking/reservation.confirmed_sub', ['property' => $property['name']]) }}</div>

        <div class="vy-card vy-confirm-rows">
            <div class="vy-summary-line"><span class="vy-sl-label">{{ __('booking/reservation.reference') }}</span><span class="vy-ref" style="font-size:16px;">{{ $reservation['reference'] }}</span></div>
            <div class="vy-summary-line"><span class="vy-sl-label">{{ __('booking/reservation.check_in') }}</span><span class="vy-sl-val">{{ $reservation['check_in'] }}</span></div>
            <div class="vy-summary-line"><span class="vy-sl-label">{{ __('booking/reservation.check_out') }}</span><span class="vy-sl-val">{{ $reservation['check_out'] }}</span></div>
            <div class="vy-summary-line"><span class="vy-sl-label">{{ __('booking/reservation.nights') }}</span><span class="vy-sl-val">{{ $reservation['nights'] }}</span></div>
            <div class="vy-summary-line"><span class="vy-sl-label">{{ __('booking/reservation.payment') }}</span><span class="vy-sl-val">{{ $payment['label'] }}</span></div>
            <div class="vy-summary-total">
                <span class="vy-st-label">{{ __('common.total') }}</span>
                <span class="vy-st-val">{{ $reservation['total'] }}</span>
            </div>
        </div>

        <div class="vy-deferred-note" style="justify-content:center; margin-top:16px;">
            {{ __('booking/reservation.confirmation_sent', ['email' => $reservation['email'], 'note' => $payment['note']]) }}
        </div>
        <div style="margin-top:18px;"><a class="vy-btn vy-btn--accent" href="{{ route('booking.browse', $property['slug']) }}">{{ __('booking/reservation.back_to_property', ['property' => $property['name']]) }}</a></div>
    </div>

</x-public.shell>
