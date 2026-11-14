{{-- What a guest accepted when they booked: the date, the version and the terms exactly as shown (the reservation's stored snapshot).
     ONE component for the guest's own pages and the operator's reservation, so they cannot read differently. Renders nothing when the
     booking has no acceptance on record (an operator, OTA or older booking) — never a guess. --}}
@props(['reservation'])
@php
    $snapshot = is_array($reservation->terms_snapshot ?? null) ? $reservation->terms_snapshot : null;
    $at = $reservation->terms_accepted_at ?? null;
@endphp
@if ($at !== null && $snapshot !== null)
    <details {{ $attributes->merge(['style' => 'font-size:13px;']) }}>
        <summary style="cursor:pointer;color:var(--vy-ink, #2a2320);font-weight:500;">{{ __('components/booking/accepted-terms.summary', ['date' => $at->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M Y'))]) }}</summary>
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:10px;">
            <p style="margin:0;font-size:12px;color:var(--vy-faint, #9c9089);">{{ __('components/booking/accepted-terms.note') }}@if (is_string($reservation->terms_version ?? null) && $reservation->terms_version !== '') · {{ __('components/booking/accepted-terms.version', ['version' => $reservation->terms_version]) }}@endif</p>
            <x-booking.terms :terms="$snapshot" />
        </div>
    </details>
@endif
