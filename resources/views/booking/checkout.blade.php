{{-- Public checkout — review + guest details (design pass · Booking Screen 3, step 1). PUBLIC,
     server-rendered. The price is REAL (certified search path). NO inventory is written here — the
     advisory hold + the booking happen on the CSRF-protected POST below (which routes through
     AssignmentService + FolioService). --}}
@php
    $back = http_build_query(array_filter([
        'check_in' => $search['check_in'], 'check_out' => $search['check_out'],
        'adults' => $search['adults'], 'children' => $search['children'],
    ]));
@endphp
<x-public.shell :property="$property" title="Checkout">

    <a class="vy-back vy-fade" href="{{ route('booking.browse', $property['slug']) }}?{{ $back }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        Back to rooms
    </a>

    @if (session('booking_error'))
        <div class="vy-error vy-fade" role="alert">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            {{ session('booking_error') }}
        </div>
    @endif

    @if ($offer === null)
        <div class="vy-card vy-fade">
            <div class="vy-empty">
                That room isn’t available for these dates.
                <div style="margin-top:14px;"><a class="vy-btn vy-btn--accent" href="{{ route('booking.browse', $property['slug']) }}?{{ $back }}">Choose another room</a></div>
            </div>
        </div>
    @else
        <h1 class="vy-hero-title vy-fade" style="font-size:28px;">Complete your booking</h1>

        <div class="vy-checkout-grid vy-fade" style="margin-top:18px;">
            {{-- Guest details → CSRF-protected POST that books through AssignmentService --}}
            <div class="vy-card">
                <span class="vy-eyebrow">Guest details</span>
                <form method="POST" action="{{ route('booking.reserve', $property['slug']) }}" style="margin-top:14px;"
                      data-guard-submit>
                    @csrf
                    <input type="hidden" name="room_type_id" value="{{ $offer['room_type_id'] }}">
                    <input type="hidden" name="rate_plan_id" value="{{ $offer['rate_plan_id'] }}">
                    <input type="hidden" name="check_in" value="{{ $search['check_in'] }}">
                    <input type="hidden" name="check_out" value="{{ $search['check_out'] }}">
                    <input type="hidden" name="adults" value="{{ $search['adults'] }}">
                    <input type="hidden" name="children" value="{{ $search['children'] }}">

                    <div class="vy-form-grid">
                        <div><label class="vy-field-label" for="first_name">First name</label><input class="vy-input" type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required></div>
                        <div><label class="vy-field-label" for="last_name">Last name</label><input class="vy-input" type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required></div>
                        <div class="vy-full"><label class="vy-field-label" for="email">Email</label><input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" required></div>
                    </div>

                    {{-- Payment: the wired method is pay-on-arrival (manual gateway) recorded for real through
                         FolioService. Secure card payment (Stripe) is the deferred rail — no fake card field. --}}
                    <div class="vy-pay-note">
                        <span class="vy-soon-tag">Pay on arrival</span>
                        <span>Reserve now and settle at the property. Secure card payment is coming soon — no card is collected here.</span>
                    </div>

                    <x-busy-btn class="vy-btn vy-btn--accent" style="width:100%; margin-top:16px;" busy-label="Reserving…">Confirm reservation</x-busy-btn>
                    <div class="vy-deferred-note" style="margin-top:10px;">Your room is only reserved once you confirm — availability is re-checked at that moment.</div>
                </form>
            </div>

            {{-- REAL price summary (from the certified offer) --}}
            <div class="vy-card">
                <span class="vy-eyebrow">Your stay</span>
                <div style="margin-top:12px;">
                    <div class="vy-room-name">{{ $offer['name'] }}</div>
                    <div class="vy-room-meta" style="margin-top:6px;">
                        {{ \Illuminate\Support\Carbon::parse($search['check_in'])->format('j M') }} → {{ \Illuminate\Support\Carbon::parse($search['check_out'])->format('j M Y') }}
                        · {{ $offer['nights'] }} {{ \Illuminate\Support\Str::plural('night', $offer['nights']) }}
                    </div>
                </div>
                <div style="margin-top:14px;">
                    <div class="vy-summary-line"><span class="vy-sl-label">{{ $offer['per_night'] }} × {{ $offer['nights'] }} {{ \Illuminate\Support\Str::plural('night', $offer['nights']) }}</span><span class="vy-sl-val">{{ $offer['total'] }}</span></div>
                    <div class="vy-summary-line"><span class="vy-sl-label">{{ $search['adults'] }} {{ \Illuminate\Support\Str::plural('adult', $search['adults']) }}@if ($search['children'] > 0), {{ $search['children'] }} {{ \Illuminate\Support\Str::plural('child', $search['children']) }}@endif</span><span class="vy-sl-val">{{ $offer['available'] }} left</span></div>
                </div>
                <div class="vy-summary-total">
                    <span class="vy-st-label">Total</span>
                    <span class="vy-st-val">{{ $offer['total'] }}</span>
                </div>
            </div>
        </div>
    @endif

</x-public.shell>
