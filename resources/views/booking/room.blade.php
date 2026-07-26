{{-- Public room detail (design pass · Booking Screen 2). PUBLIC, server-rendered, READ-ONLY. Room
     specs are REAL (RoomType schema); availability + price are REAL for the searched dates
     (BookingEngineService → AvailabilityService), scoped to THIS property. Writes NOTHING. --}}
@php
    $q = http_build_query(array_filter([
        'check_in' => $search['check_in'], 'check_out' => $search['check_out'],
        'adults' => $search['adults'], 'children' => $search['children'],
    ]));
@endphp
<x-public.shell :property="$property" :title="$room['name']">

    <a class="vy-back vy-fade" href="{{ route('booking.browse', $property['slug']) }}?{{ $q }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        All rooms
    </a>

    <div class="vy-detail-grid">
        {{-- LEFT: banner + specs + amenities --}}
        <div class="vy-fade">
            {{-- REAL photos when present; the decorative gradient banner is the honest fallback for none. --}}
            @if ($room['cover'])
                <div class="vy-detail-banner has-photo">
                    <img src="{{ $room['cover'] }}" alt="{{ $room['photos'][0]['alt'] ?? $room['name'] }}" class="vy-banner-img">
                    <span class="vy-room-kind">{{ $room['kind'] }}</span>
                </div>
                @if (count($room['photos']) > 1)
                    <div class="vy-thumbs">
                        @foreach ($room['photos'] as $photo)
                            <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" class="vy-thumb" loading="lazy">
                        @endforeach
                    </div>
                @endif
            @else
                <div class="vy-detail-banner">
                    <span class="vy-room-kind">{{ $room['kind'] }}</span>
                </div>
            @endif

            <h1 class="vy-detail-title">{{ $room['name'] }}</h1>
            <div class="vy-detail-kind">{{ $room['kind'] }} · sleeps up to {{ $room['capacity'] }}</div>

            {{-- REAL derived rating (published reviews only) — never a hard-coded number (Phase 5). --}}
            @if ($rating['has_reviews'])
                <div style="margin-top:7px;font-size:14px;color:var(--vy-ink)"><span style="color:var(--vy-accent-3)">★</span> <strong>{{ $rating['average'] }}</strong> <span style="color:var(--vy-muted)">· {{ $rating['count'] }} {{ \Illuminate\Support\Str::plural('review', $rating['count']) }}</span></div>
            @else
                <div style="margin-top:7px;font-size:13px;color:var(--vy-muted)">No reviews yet</div>
            @endif

            {{-- Save to wishlist — shown only to a signed-in guest (Phase 4c · SG4). A real POST to the guest's
                 own wishlist; anonymous visitors see nothing (no bouncing button). --}}
            @if (\Illuminate\Support\Facades\Auth::guard('guest')->check())
                <form method="POST" action="{{ route('guest.saved.wishlist.add') }}" style="margin-top:12px">
                    @csrf
                    <input type="hidden" name="room_type_id" value="{{ $room['id'] }}">
                    <button type="submit" style="display:inline-flex;align-items:center;gap:7px;padding:8px 15px;border-radius:22px;border:1px solid rgba(120,70,40,.18);background:#fff;color:var(--vy-accent);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer">♥ Save to wishlist</button>
                </form>
            @endif

            {{-- REAL specs from the RoomType schema (only what genuinely exists) --}}
            <div class="vy-specs">
                <div class="vy-spec"><div class="vy-spec-label">Sleeps</div><div class="vy-spec-val">{{ $room['capacity'] }}</div></div>
                <div class="vy-spec"><div class="vy-spec-label">Adults</div><div class="vy-spec-val">{{ $room['max_adults'] }}</div></div>
                <div class="vy-spec"><div class="vy-spec-label">Children</div><div class="vy-spec-val">{{ $room['max_children'] }}</div></div>
                @if ($room['bedrooms'] !== null)
                    <div class="vy-spec"><div class="vy-spec-label">Bedrooms</div><div class="vy-spec-val">{{ $room['bedrooms'] }}</div></div>
                @endif
                @if ($room['std_occupancy'] !== null)
                    <div class="vy-spec"><div class="vy-spec-label">Standard occupancy</div><div class="vy-spec-val">{{ $room['std_occupancy'] }}</div></div>
                @endif
            </div>

            <h2 class="vy-section-title">Amenities</h2>
            @if (count($amenities) > 0)
                <div class="vy-amenities">
                    @foreach ($amenities as $amenity)
                        <span class="vy-amenity">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ $amenity }}
                        </span>
                    @endforeach
                </div>
            @else
                {{-- DEFERRED: no amenities catalogued for this room — flagged, never fabricated. --}}
                <div class="vy-deferred-note"><span class="vy-soon-tag">Not yet listed</span> Amenities for this room aren’t published yet.</div>
            @endif

            {{-- Guest reviews — REAL published (moderated) reviews + first-name byline (#28); never fabricated. --}}
            <h2 class="vy-section-title">Guest reviews</h2>
            @forelse ($reviews as $review)
                <div style="padding:12px 0;border-bottom:1px solid rgba(120,70,40,.10)">
                    <div><span style="color:var(--vy-accent-3);letter-spacing:2px">{{ $review['stars'] }}</span><span style="font-size:12.5px;color:var(--vy-ink);font-weight:600;margin-left:7px">{{ $review['byline'] }}</span>@if ($review['at'] !== '')<span style="font-size:11px;color:var(--vy-faint)"> · {{ $review['at'] }}</span>@endif</div>
                    @if ($review['body'] !== '')<div style="font-size:13px;color:#4A4038;margin-top:6px;line-height:1.5">{{ $review['body'] }}</div>@endif
                    @if ($review['response'] !== '')<div style="margin-top:8px;padding:8px 11px;border-radius:var(--vy-r-sm);background:var(--vy-wash);font-size:12.5px;color:#6b6b6b"><strong style="color:var(--vy-ink)">Response:</strong> {{ $review['response'] }}</div>@endif
                </div>
            @empty
                <div class="vy-deferred-note">No reviews yet — be the first to stay and share your experience.</div>
            @endforelse

            {{-- STILL DEFERRED: full description & location map are not in the schema — flagged, not faked. --}}
            <div class="vy-deferred-note" style="margin-top:16px;">
                <span class="vy-soon-tag">Coming soon</span>
                A full description &amp; location map are on the way — the specs, availability, price and reviews above are real.
            </div>
        </div>

        {{-- RIGHT: booking panel — REAL price/availability for the searched dates --}}
        <div class="vy-book-panel vy-fade">
            <div class="vy-card">
                @if ($offer !== null)
                    <div class="vy-book-price">{{ $offer['per_night'] }} <span class="vy-price-unit">/ night</span></div>
                    <div class="vy-book-total">{{ $offer['total'] }} total · {{ $offer['nights'] }} {{ \Illuminate\Support\Str::plural('night', $offer['nights']) }}</div>
                    <div class="vy-book-avail">
                        <span class="vy-avail-chip @if ($offer['available'] <= 2) is-tight @endif">{{ $offer['available'] }} left for your dates</span>
                    </div>
                    {{-- Forward to the real checkout (review → confirm). No inventory is written until book(). --}}
                    @php $bq = http_build_query(['room_type_id' => $room['id'], 'rate_plan_id' => $offer['rate_plan_id'], 'check_in' => $search['check_in'], 'check_out' => $search['check_out'], 'adults' => $search['adults'], 'children' => $search['children']]); @endphp
                    <a href="{{ route('booking.checkout', $property['slug']) }}?{{ $bq }}" class="vy-btn vy-btn--accent vy-book-cta">Book this room</a>
                    <div class="vy-deferred-note" style="margin-top:12px;">Nothing is reserved until you confirm at checkout.</div>
                @elseif ($search['searched'])
                    <div class="vy-eyebrow">Your dates</div>
                    <div class="vy-book-total" style="margin-top:8px;">Not available for these dates — try different dates.</div>
                    <form method="GET" action="" class="vy-mini-form">
                        <div class="vy-full"><label class="vy-field-label" for="ci">Check-in</label><input class="vy-input" type="date" id="ci" name="check_in" value="{{ $search['check_in'] }}"></div>
                        <div class="vy-full"><label class="vy-field-label" for="co">Check-out</label><input class="vy-input" type="date" id="co" name="check_out" value="{{ $search['check_out'] }}"></div>
                        <div><label class="vy-field-label" for="ad">Adults</label><input class="vy-input" type="number" id="ad" name="adults" min="1" value="{{ $search['adults'] }}"></div>
                        <div><label class="vy-field-label" for="ch">Children</label><input class="vy-input" type="number" id="ch" name="children" min="0" value="{{ $search['children'] }}"></div>
                        <button type="submit" class="vy-btn vy-btn--accent vy-full">Check dates</button>
                    </form>
                @else
                    <div class="vy-eyebrow">Check availability &amp; price</div>
                    <form method="GET" action="" class="vy-mini-form">
                        <div class="vy-full"><label class="vy-field-label" for="ci">Check-in</label><input class="vy-input" type="date" id="ci" name="check_in" value="{{ $search['check_in'] }}"></div>
                        <div class="vy-full"><label class="vy-field-label" for="co">Check-out</label><input class="vy-input" type="date" id="co" name="check_out" value="{{ $search['check_out'] }}"></div>
                        <div><label class="vy-field-label" for="ad">Adults</label><input class="vy-input" type="number" id="ad" name="adults" min="1" value="{{ $search['adults'] }}"></div>
                        <div><label class="vy-field-label" for="ch">Children</label><input class="vy-input" type="number" id="ch" name="children" min="0" value="{{ $search['children'] }}"></div>
                        <button type="submit" class="vy-btn vy-btn--accent vy-full">See price</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

</x-public.shell>
