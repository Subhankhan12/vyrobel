{{-- Public booking storefront — Browse / Search (Guest Portal · GATE 1, exact-match rebuild).
     PUBLIC, server-rendered, READ-ONLY. Rooms + availability + prices + ratings are REAL
     (BookingEngineService → AvailabilityService / rate_calendar / ReviewRating), scoped to THIS
     property only. The grand total shown === the folio total incl. the real cleaning fee (#17).
     Refine controls (bedrooms / amenities / max-price / sort) are real GET filters. No inventory is
     written here. Signed-in guest chrome is honest-gated; anonymous visitors get a sign-in link.
     No JS framework (public page): the peach-glass wireframe is pure CSS + tiny progressive-enhancement. --}}
@use('Illuminate\Support\Str')
@use('Illuminate\Support\Carbon')
@php
    $carry = ['check_in' => $search['check_in'], 'check_out' => $search['check_out'], 'adults' => $search['adults'], 'children' => $search['children']];
@endphp
<x-public.shell :property="$property" title="Book your stay" :seo="$seo">
    <x-slot:head>
        <style>
            main { max-width: 940px; }
            .vy2-hero-title { font-family: var(--vy-display); font-weight: 600; font-size: 32px; letter-spacing: -.02em; line-height: 1.1; margin: 0; }
            .vy2-hero-sub { margin: 8px 0 0; font-size: 14.5px; color: var(--vy-ink); }   /* G12: --vy-muted measured 3.39 on the hero gradient */

            /* frosted search pill */
            .vy2-search { display: flex; flex-wrap: wrap; align-items: stretch; gap: 10px; margin-top: 20px; padding: 12px;
                border-radius: var(--vy-r-lg); background: rgba(255,252,248,.7); backdrop-filter: blur(22px) saturate(1.15);
                border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow); }
            .vy2-field { flex: 1; min-width: 140px; display: flex; flex-direction: column; justify-content: center; gap: 2px;
                padding: 8px 14px; border-radius: 13px; background: rgba(255,255,255,.55); }
            .vy2-field-k { font-size: 10px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-faint); }
            .vy2-field input, .vy2-field .vy2-static { border: none; background: transparent; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); padding: 0; width: 100%; }
            .vy2-field input:focus { outline: none; }
            /* G12 — the line above removes the browser ring; without this one a keyboard user has NO
               focus indicator at all (WCAG 2.4.7). Fifth appearance of the invisible-focus family in
               this pass, and the second MECHANISM: G5/G8/G9 were opacity:0 controls, this is an
               explicit outline:none. Same defect to the user. */
            .vy2-field input:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
            .vy2-stepper { display: flex; align-items: center; gap: 10px; }
            .vy2-stepper button { width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.7); color: var(--vy-accent); font-size: 15px; line-height: 1; cursor: pointer; }
            .vy2-stepper input { width: 26px; text-align: center; font-variant-numeric: tabular-nums; -moz-appearance: textfield; }
            .vy2-stepper input::-webkit-outer-spin-button, .vy2-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
            .vy2-search-btn { padding: 0 22px; border-radius: 14px; background: var(--vy-accent); border: none; color: #fff; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; cursor: pointer; box-shadow: var(--vy-shadow-accent); }

            /* refine + filters */
            .vy2-refine { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 22px 2px 12px; }
            .vy2-chips { display: inline-flex; align-items: center; gap: 3px; padding: 3px; border-radius: var(--vy-r-pill); background: rgba(120,70,40,.06); border: 1px solid var(--vy-hairline); }
            .vy2-seg input { position: absolute; opacity: 0; pointer-events: none; }
            .vy2-seg span { display: inline-block; padding: 6px 13px; border-radius: var(--vy-r-pill); font-size: 11.5px; font-weight: 500; color: var(--vy-muted); cursor: pointer; }
            .vy2-seg input:checked + span { background: #fff; color: var(--vy-ink); box-shadow: 0 1px 3px rgba(60,30,10,.1); font-weight: 600; }
            .vy2-filter-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0 2px 14px; }
            .vy2-filter-k { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-ink); margin-right: 2px; }
            /* DO-EMAIL-5: was #9C9089 = 1.77. ⚠️ These labels sit on the PEACH GRADIENT, not a card — which is
               why --vy-muted (3.98) and --vy-faint also fail here and --vy-ink (8.78) is the only passing token
               this shell offers. The hierarchy is carried by the 11px uppercase weight, not by the colour.
               ⚠️ The public shell has no --vy-on-grad token the way the owner/operator shells do (§N.4i) — logged. */
            .vy2-pill { position: relative; }
            .vy2-pill input { position: absolute; opacity: 0; pointer-events: none; }
            .vy2-pill span { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: var(--vy-r-pill); background: rgba(120,70,40,.06); color: var(--vy-muted); border: 1px solid transparent; font-size: 12px; font-weight: 500; cursor: pointer; }
            .vy2-pill input:checked + span { background: rgba(217,102,46,.13); color: var(--vy-accent); border-color: var(--vy-accent-border); }
            .vy2-clear { display: inline-flex; align-items: center; gap: 6px; padding: 6px 13px; border-radius: var(--vy-r-pill); background: rgba(217,102,46,.1); border: 1px solid var(--vy-accent-border); color: var(--vy-accent); font-size: 12px; font-weight: 500; }
            .vy2-range { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin: 0 2px 18px; }
            .vy2-range input[type=range] { flex: 1; min-width: 160px; max-width: 300px; accent-color: var(--vy-accent); cursor: pointer; }
            .vy2-range-val { font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums; }

            /* result cards (horizontal, frosted) */
            .vy2-rooms { display: flex; flex-direction: column; gap: 16px; }
            .vy2-card { position: relative; display: flex; flex-wrap: wrap; gap: 18px; background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 16px; }
            .vy2-thumb { width: 180px; min-width: 140px; flex: 1; align-self: stretch; min-height: 130px; border-radius: 14px; background: var(--vy-grad); display: grid; place-items: center; color: rgba(255,255,255,.9); overflow: hidden; }
            .vy2-thumb img { width: 100%; height: 100%; object-fit: cover; }
            .vy2-body { flex: 2.4; min-width: 220px; display: flex; flex-direction: column; }
            .vy2-name { font-family: var(--vy-display); font-weight: 500; font-size: 17px; }
            .vy2-rate { display: flex; align-items: center; gap: 6px; margin-top: 7px; font-size: 12px; color: var(--vy-muted); }
            .vy2-rate .star { color: var(--vy-accent-3); }
            .vy2-rate strong { font-family: var(--vy-display); font-weight: 600; font-size: 13px; color: var(--vy-ink); }
            .vy2-feats { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 10px; }
            .vy2-feat { display: inline-flex; padding: 4px 10px; border-radius: var(--vy-r-pill); background: rgba(120,70,40,.06); color: var(--vy-muted); font-size: 11px; font-weight: 500; }
            .vy2-foot { flex: 1; display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-top: 12px; }
            .vy2-price-night { font-family: var(--vy-display); font-weight: 600; font-size: 20px; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
            .vy2-price-sub { font-size: 12px; color: var(--vy-faint); }
            .vy2-reserve { padding: 10px 20px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-family: var(--vy-sans); font-size: 13px; font-weight: 500; white-space: nowrap; box-shadow: var(--vy-shadow-accent); }
            .vy2-heart { position: absolute; top: 16px; right: 16px; z-index: 2; width: 34px; height: 34px; border-radius: 50%; background: rgba(255,252,248,.9); border: 1px solid var(--vy-glass-border); box-shadow: 0 4px 12px rgba(60,30,10,.15); display: grid; place-items: center; cursor: pointer; padding: 0; }
            .vy2-empty { text-align: center; padding: 50px 24px; background: rgba(255,252,248,.6); backdrop-filter: blur(22px); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); }
            .vy2-nav { display: flex; align-items: center; gap: 14px; }
            .vy2-nav a { font-size: 12.5px; font-weight: 500; color: var(--vy-accent); display: inline-flex; align-items: center; gap: 5px; }
            .vy2-nav .muted { color: var(--vy-muted); }
            @media (max-width: 620px) { .vy2-thumb { width: 100%; flex-basis: 100%; } }
        </style>
    </x-slot:head>

    <x-slot:nav>
        <div class="vy2-nav">
            @if ($guest !== null)
                <a href="{{ route('guest.saved') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg> Saved</a>
                <a class="muted" href="{{ route('guest.account') }}">{{ $guest['name'] }}</a>
            @else
                <a class="muted" href="{{ route('guest.login') }}">Sign in</a>
            @endif
        </div>
    </x-slot:nav>

    <section class="vy-fade" style="animation-delay:.02s">
        <h1 class="vy2-hero-title">Find your stay</h1>
        <p class="vy2-hero-sub">Book direct at {{ $property['name'] }} — best rates, no middleman fees.</p>
    </section>

    {{-- ONE GET form: the search + every refine control submits the same real query. --}}
    <form method="GET" action="" class="vy-fade" style="animation-delay:.06s">
        <div class="vy2-search">
            <div class="vy2-field">
                <span class="vy2-field-k">Where</span>
                <span class="vy2-static">{{ $property['name'] }}</span>
            </div>
            <div class="vy2-field">
                <span class="vy2-field-k">Check-in</span>
                <input type="date" name="check_in" value="{{ $search['check_in'] }}" aria-label="Check-in date">
            </div>
            <div class="vy2-field">
                <span class="vy2-field-k">Check-out</span>
                <input type="date" name="check_out" value="{{ $search['check_out'] }}" aria-label="Check-out date">
            </div>
            <div class="vy2-field">
                <span class="vy2-field-k">Adults</span>
                <span class="vy2-stepper">
                    <button type="button" onclick="this.nextElementSibling.stepDown()">&minus;</button>
                    <input type="number" name="adults" min="1" max="16" value="{{ $search['adults'] }}" readonly aria-label="Adults">
                    <button type="button" onclick="this.previousElementSibling.stepUp()">+</button>
                </span>
            </div>
            <div class="vy2-field">
                <span class="vy2-field-k">Children</span>
                <span class="vy2-stepper">
                    <button type="button" onclick="this.nextElementSibling.stepDown()">&minus;</button>
                    <input type="number" name="children" min="0" max="16" value="{{ $search['children'] }}" readonly aria-label="Children">
                    <button type="button" onclick="this.previousElementSibling.stepUp()">+</button>
                </span>
            </div>
            <button type="submit" class="vy2-search-btn">Search</button>
        </div>

        @if ($search['searched'])
            {{-- Refine: sort segmented control (submits on change) + clear --}}
            <div class="vy2-refine">
                <span class="vy-muted" style="font-size:13px;font-weight:500;">
                    {{ count($rooms) }} {{ Str::plural('stay', count($rooms)) }} ·
                    {{ Carbon::parse($search['check_in'])->format('j M') }} → {{ Carbon::parse($search['check_out'])->format('j M') }} ·
                    {{ $search['nights'] }} {{ Str::plural('night', $search['nights']) }}
                </span>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    @if ($refine['bedrooms'] !== null || $refine['max_price'] !== null || $refine['sort'] !== 'recommended' || $refine['amenities'] !== [])
                        <a class="vy2-clear" href="{{ route('booking.browse', $property['slug']) }}?{{ http_build_query($carry) }}">✕ Clear filters</a>
                    @endif
                    <span class="vy2-chips">
                        @foreach (['recommended' => 'Recommended', 'rating' => 'Top rated', 'price_asc' => 'Price ↑', 'price_desc' => 'Price ↓'] as $k => $label)
                            <label class="vy2-seg"><input type="radio" name="sort" value="{{ $k }}" @checked($refine['sort'] === $k) onchange="this.form.submit()"><span>{{ $label }}</span></label>
                        @endforeach
                    </span>
                </div>
            </div>

            {{-- Bedrooms (real options) --}}
            @if (count($filterOptions['bedrooms']) > 0)
                <div class="vy2-filter-row">
                    <span class="vy2-filter-k">Bedrooms</span>
                    <label class="vy2-pill"><input type="radio" name="bedrooms" value="" @checked($refine['bedrooms'] === null) onchange="this.form.submit()"><span>Any</span></label>
                    @foreach ($filterOptions['bedrooms'] as $b)
                        <label class="vy2-pill"><input type="radio" name="bedrooms" value="{{ $b }}" @checked($refine['bedrooms'] === $b) onchange="this.form.submit()"><span>{{ $b === 0 ? 'Studio' : $b.'+' }}</span></label>
                    @endforeach
                </div>
            @endif

            {{-- Amenities (real, present on this property) --}}
            @if (count($filterOptions['amenities']) > 0)
                <div class="vy2-filter-row">
                    <span class="vy2-filter-k">Amenities</span>
                    @foreach ($filterOptions['amenities'] as $a)
                        <label class="vy2-pill"><input type="checkbox" name="amenities[]" value="{{ $a }}" @checked(in_array($a, $refine['amenities'], true)) onchange="this.form.submit()"><span>{{ $a }}</span></label>
                    @endforeach
                </div>
            @endif

            {{-- Max price (real ceiling from the dearest grand total) --}}
            @if (count($rooms) > 0 || $refine['max_price'] !== null)
                @php $ceiling = max($filterOptions['max_price_ceiling'], (int) ($refine['max_price'] ?? 0)); $current = $refine['max_price'] ?? $ceiling; @endphp
                <div class="vy2-range">
                    <span class="vy2-filter-k">Max price</span>
                    <input type="range" name="max_price" min="0" max="{{ $ceiling }}" step="10" value="{{ $current }}"
                           oninput="document.getElementById('vy2-mp').textContent = (this.value >= this.max ? 'Any price' : ('€' + this.value))"
                           onchange="this.form.submit()">
                    <span class="vy2-range-val" id="vy2-mp">{{ $current >= $ceiling ? 'Any price' : '€'.$current }}</span>
                </div>
            @endif
        @endif
    </form>

    @if (! $search['searched'])
        {{-- Pre-search teaser — real room names only, NO price/availability claimed. --}}
        <div class="vy-card vy-fade" style="margin-top:18px;">
            <div class="vy-results-head" style="margin:0 0 6px;">
                <span class="vy-results-title">Our rooms</span>
                <span class="vy-muted" style="font-size:12.5px;">Pick dates above to see availability &amp; prices</span>
            </div>
            @forelse ($teaser as $room)
                <div class="vy-teaser-row">
                    <div style="display:flex;align-items:center;gap:12px;">
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
    @elseif (count($rooms) === 0)
        <div class="vy2-empty vy-fade">
            <div style="font-family:var(--vy-display);font-weight:500;font-size:17px;">No rooms fit these dates</div>
            <div class="vy-muted" style="font-size:13px;margin-top:6px;line-height:1.5;">Try different dates, fewer guests, or clear your filters.</div>
        </div>
    @else
        <div class="vy2-rooms vy-fade">
            @foreach ($rooms as $room)
                @php $q = http_build_query($carry); @endphp
                <article class="vy2-card">
                    @if ($guest !== null)
                        <form method="POST" action="{{ route('guest.saved.wishlist.add') }}">
                            @csrf
                            <input type="hidden" name="room_type_id" value="{{ $room['room_type_id'] }}">
                            <button class="vy2-heart" type="submit" title="Save to wishlist" aria-label="Save to wishlist">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E8703A" stroke-width="1.9"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                            </button>
                        </form>
                    @endif
                    <div class="vy2-thumb">
                        @if ($room['cover'])<img src="{{ $room['cover'] }}" alt="{{ $room['name'] }}" loading="lazy">
                        @else<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>@endif
                    </div>
                    <div class="vy2-body">
                        <div class="vy2-name">{{ $room['name'] }}</div>
                        @if ($room['rating']['has_reviews'] ?? false)
                            <div class="vy2-rate"><span class="star">★</span> <strong>{{ $room['rating']['average'] }}</strong> · {{ $room['rating']['count'] }} {{ Str::plural('review', (int) $room['rating']['count']) }}</div>
                        @else
                            <div class="vy2-rate">No reviews yet</div>
                        @endif
                        <div class="vy2-feats">
                            <span class="vy2-feat" style="background:rgba(232,112,58,.12);color:var(--vy-accent);">{{ $room['available'] }} left</span>
                            @foreach ($room['features'] as $f)<span class="vy2-feat">{{ $f }}</span>@endforeach
                        </div>
                        <div class="vy2-foot">
                            <div>
                                <span class="vy2-price-night">{{ $room['per_night'] }}</span> <span class="vy2-price-sub">/ night{{ ($room['nightly_note'] ?? '') !== '' ? ' · '.$room['nightly_note'] : '' }}</span>
                                <div class="vy2-price-sub" style="margin-top:2px;">{{ $room['grand_total'] }} total{{ $room['fees_note'] !== '' ? ' · '.$room['fees_note'] : '' }}</div>
                            </div>
                            <a class="vy2-reserve" href="{{ route('booking.room', [$property['slug'], $room['room_type_id']]) }}?{{ $q }}">Reserve</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

</x-public.shell>
