{{-- Public booking storefront — Browse / Search + sellable rooms (design pass · Booking Screen 1).
     PUBLIC, server-rendered, READ-ONLY. Rooms + availability + prices are REAL (BookingEngineService
     → AvailabilityService), scoped to THIS property only. No inventory is written here. --}}
<x-public.shell :property="$property" title="Book your stay">

    {{-- Hero + REAL search (GET to this same public URL → re-runs ResolvePublicProperty) --}}
    <section class="vy-hero vy-fade">
        <h1 class="vy-hero-title">Stay at {{ $property['name'] }}</h1>
        <p class="vy-hero-sub">Search live availability and book direct — best rates, no middleman fees.</p>

        <form method="GET" action="" class="vy-card vy-search" style="margin-top:18px;">
            <div>
                <label class="vy-field-label" for="check_in">Check-in</label>
                <input class="vy-input" type="date" id="check_in" name="check_in" value="{{ $search['check_in'] }}">
            </div>
            <div>
                <label class="vy-field-label" for="check_out">Check-out</label>
                <input class="vy-input" type="date" id="check_out" name="check_out" value="{{ $search['check_out'] }}">
            </div>
            <div>
                <label class="vy-field-label" for="adults">Adults</label>
                <input class="vy-input" type="number" id="adults" name="adults" min="1" value="{{ $search['adults'] }}">
            </div>
            <div>
                <label class="vy-field-label" for="children">Children</label>
                <input class="vy-input" type="number" id="children" name="children" min="0" value="{{ $search['children'] }}">
            </div>
            <button type="submit" class="vy-btn vy-btn--accent">Search</button>
        </form>
    </section>

    @if (! $search['searched'])
        {{-- No date search yet — a real room-type teaser (names only), NO availability/price claimed. --}}
        <div class="vy-card vy-fade">
            <div class="vy-results-head" style="margin:0 0 6px;">
                <span class="vy-results-title">Our rooms</span>
                <span class="vy-muted" style="font-size:12.5px;">Search dates above to see availability &amp; prices</span>
            </div>
            @forelse ($teaser as $room)
                <div class="vy-teaser-row">
                    <div style="display:flex; align-items:center; gap:12px;">
                        @if ($room['cover'])<img src="{{ $room['cover'] }}" alt="{{ $room['name'] }}" class="vy-thumb" loading="lazy">@endif
                        <div>
                            <div class="vy-teaser-name">{{ $room['name'] }}</div>
                            <div class="vy-teaser-sub">{{ $room['kind'] }} · sleeps up to {{ $room['capacity'] }}</div>
                        </div>
                    </div>
                    <span class="vy-soon-tag">Pick dates</span>
                </div>
            @empty
                <div class="vy-empty">No rooms are published for this property yet.</div>
            @endforelse
        </div>
    @else
        <div class="vy-results-head vy-fade">
            <span class="vy-results-title">
                {{ count($rooms) }} {{ \Illuminate\Support\Str::plural('room', count($rooms)) }} available
            </span>
            <span class="vy-muted" style="font-size:12.5px;">
                {{ \Illuminate\Support\Carbon::parse($search['check_in'])->format('j M') }} → {{ \Illuminate\Support\Carbon::parse($search['check_out'])->format('j M Y') }}
                · {{ $search['nights'] }} {{ \Illuminate\Support\Str::plural('night', $search['nights']) }}
                · {{ $search['adults'] }} {{ \Illuminate\Support\Str::plural('adult', $search['adults']) }}@if ($search['children'] > 0), {{ $search['children'] }} {{ \Illuminate\Support\Str::plural('child', $search['children']) }}@endif
            </span>
        </div>

        @if (count($rooms) === 0)
            <div class="vy-card vy-fade">
                <div class="vy-empty">No rooms available for these dates — try different dates or fewer guests.</div>
            </div>
        @else
            <div class="vy-rooms vy-fade">
                @foreach ($rooms as $room)
                    <article class="vy-room-card">
                        <div class="vy-room-banner">
                            @if ($room['cover'])<img src="{{ $room['cover'] }}" alt="{{ $room['name'] }}" class="vy-card-cover" loading="lazy">@endif
                            <span class="vy-room-kind">{{ $room['kind'] }}</span>
                        </div>
                        <div class="vy-room-body">
                            <div class="vy-room-name">{{ $room['name'] }}</div>
                            <div class="vy-room-meta">
                                <span class="vy-avail-chip @if ($room['available'] <= 2) is-tight @endif">
                                    {{ $room['available'] }} left
                                </span>
                                <span>·</span>
                                <span>{{ $room['nights'] }} {{ \Illuminate\Support\Str::plural('night', $room['nights']) }}</span>
                            </div>
                            <div class="vy-room-foot">
                                <div>
                                    <div class="vy-price-night">{{ $room['per_night'] }} <span class="vy-price-unit">/ night</span></div>
                                    <div class="vy-price-total">{{ $room['total'] }} total for {{ $room['nights'] }} {{ \Illuminate\Support\Str::plural('night', $room['nights']) }}</div>
                                </div>
                                {{-- Forward to the room detail (read-only), carrying the searched dates. No write. --}}
                                @php $q = http_build_query(['check_in' => $search['check_in'], 'check_out' => $search['check_out'], 'adults' => $search['adults'], 'children' => $search['children']]); @endphp
                                <a href="{{ route('booking.room', [$property['slug'], $room['room_type_id']]) }}?{{ $q }}" class="vy-btn vy-btn--accent">View room</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="vy-deferred-note">
                <span class="vy-soon-tag">Coming soon</span>
                Room photos, amenities &amp; guest reviews are on the way — prices and availability above
                are live, and you can book direct now.
            </div>
        @endif
    @endif

</x-public.shell>
