{{-- Multi-property storefront LANDING (Guest Browse · founder decision D1). PUBLIC, server-rendered,
     org-resolved (ResolvePublicOrganization). Every figure is REAL — org rating (#76), per-town
     from-price, featured rooms, amenity chips, recent reviews (#28 byline). No Alpine (guest realm):
     the search is a native GET form → the real per-property booking.browse. Nothing is fabricated:
     the rating badge hides when the org has no published reviews; a town without a priced room omits
     its from-price; "browse by vibe" shows only amenities that actually exist. --}}
@php
    $heroTowns = collect($towns)->pluck('town')->filter()->take(4)->values();
@endphp
<x-public.shell :property="['name' => $org['name'], 'type' => __('booking/landing.book_direct')]" :title="__('booking/landing.page_title')" :seo="$seo">
    <x-slot:head>
        <style>
            .vy-lp-badge { display: inline-flex; align-items: center; gap: 7px; padding: 6px 13px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.6); border: 1px solid var(--vy-glass-border); font-size: 11.5px; font-weight: 500; color: #b4400f; margin-bottom: 18px; }
            .vy-lp-h1 { margin: 0; font-family: var(--vy-display); font-weight: 700; font-size: 44px; line-height: 1.05; letter-spacing: -0.03em; }
            .vy-lp-sub { margin: 16px 0 0; font-size: 15.5px; color: var(--vy-ink); line-height: 1.55; max-width: 460px; }
            .vy-lp-hero { display: flex; flex-wrap: wrap; align-items: center; gap: 40px; padding: 20px 0 18px; }
            .vy-lp-tiles { flex: 1 1 340px; min-width: 260px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px; height: 300px; }
            .vy-lp-tile { border-radius: 22px; box-shadow: 0 18px 44px rgba(150, 90, 50, 0.22); position: relative; overflow: hidden; display: flex; align-items: flex-end; padding: 14px; }
            .vy-lp-tile::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(43, 25, 15, 0.72), transparent 62%); }
            .vy-lp-tile span { position: relative; z-index: 1; font-family: var(--vy-display); font-weight: 600; font-size: 14px; color: #fff; }

            .vy-lp-search { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; margin-top: 8px; padding: 14px; border-radius: 22px; background: rgba(255, 252, 248, 0.72); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: 0 16px 44px rgba(150, 90, 50, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.65); }
            .vy-lp-field { flex: 1 1 150px; min-width: 130px; display: flex; flex-direction: column; gap: 4px; }
            .vy-lp-field label { font-size: 10px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; color: var(--vy-faint); padding-left: 2px; }
            .vy-lp-field select, .vy-lp-field input { width: 100%; box-sizing: border-box; font-family: var(--vy-sans); font-size: 14px; font-weight: 500; color: var(--vy-ink); padding: 11px 13px; border-radius: 14px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.7); }
            .vy-lp-search button { display: inline-flex; align-items: center; gap: 8px; padding: 13px 26px; border-radius: 15px; background: var(--vy-accent); border: none; color: #fff; font-family: var(--vy-sans); font-size: 14px; font-weight: 500; cursor: pointer; box-shadow: 0 8px 20px rgba(194, 65, 12, 0.38); white-space: nowrap; }

            .vy-lp-sec { margin-top: 52px; }
            .vy-lp-sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
            .vy-lp-h2 { margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 24px; letter-spacing: -0.02em; }
            .vy-lp-lede { margin: 5px 0 0; font-size: 13.5px; color: var(--vy-ink); }
            .vy-lp-seeall { display: inline-flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 500; color: var(--vy-accent); white-space: nowrap; }

            .vy-lp-grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
            .vy-lp-grid4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }

            .vy-lp-town { display: block; border-radius: 20px; overflow: hidden; }
            .vy-lp-town-img { height: 176px; border-radius: 20px; position: relative; overflow: hidden; box-shadow: 0 14px 40px rgba(150, 90, 50, 0.2); }
            .vy-lp-town-img::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(43, 25, 15, 0.78), rgba(43, 25, 15, 0.30) 52%, transparent 78%); }
            .vy-lp-town-cap { position: absolute; left: 16px; bottom: 14px; right: 16px; z-index: 1; }
            .vy-lp-town-name { font-family: var(--vy-display); font-weight: 600; font-size: 20px; color: #fff; letter-spacing: -0.01em; }
            .vy-lp-town-sub { font-size: 12px; color: rgba(255, 255, 255, 0.85); margin-top: 2px; }
            .vy-lp-town-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 4px 4px; }
            .vy-lp-town-foot .cnt { font-size: 12.5px; color: var(--vy-muted); }
            .vy-lp-town-foot .from { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; font-weight: 500; color: var(--vy-accent); }

            .vy-lp-card { display: flex; flex-direction: column; background: rgba(255, 252, 248, 0.6); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: 20px; box-shadow: 0 14px 40px rgba(150, 90, 50, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.65); overflow: hidden; }
            .vy-lp-card-img { height: 150px; position: relative; background-size: cover; background-position: center; }
            .vy-lp-card-img::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(43, 25, 15, 0.25), transparent 55%); }
            .vy-lp-card-body { display: flex; flex-direction: column; flex: 1; padding: 15px 16px 16px; }
            .vy-lp-card-name { font-family: var(--vy-display); font-weight: 600; font-size: 16px; }
            .vy-lp-card-rate { display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0; font-family: var(--vy-display); font-weight: 600; font-size: 12.5px; }
            .vy-lp-card-meta { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--vy-faint); margin-top: 3px; }
            .vy-lp-card-foot { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-top: 14px; }
            .vy-lp-price { font-family: var(--vy-display); font-weight: 600; font-size: 19px; letter-spacing: -0.02em; }
            .vy-lp-view { padding: 9px 18px; border-radius: var(--vy-r-pill); background: var(--vy-accent); border: none; color: #fff; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; box-shadow: 0 4px 12px rgba(194, 65, 12, 0.3); }

            .vy-lp-vibe { display: flex; flex-direction: column; gap: 12px; padding: 18px; border-radius: 18px; background: rgba(255, 252, 248, 0.6); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: 0 12px 34px rgba(150, 90, 50, 0.13), inset 0 1px 0 rgba(255, 255, 255, 0.6); }
            .vy-lp-vibe-ic { width: 42px; height: 42px; border-radius: 13px; background: var(--vy-grad); color: #fff; display: grid; place-items: center; box-shadow: 0 6px 16px rgba(194, 65, 12, 0.24); }
            .vy-lp-vibe-lbl { display: block; font-family: var(--vy-display); font-weight: 600; font-size: 15px; }
            .vy-lp-vibe-sub { display: block; font-size: 12px; color: var(--vy-faint); margin-top: 2px; }

            .vy-lp-direct { margin-top: 52px; padding: 26px 28px; border-radius: 22px; background: linear-gradient(160deg, #3a2e27 0%, #251c17 100%); box-shadow: 0 18px 50px rgba(60, 30, 10, 0.28); position: relative; overflow: hidden; }
            .vy-lp-direct h3 { margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 19px; color: #fff; letter-spacing: -0.01em; }
            .vy-lp-perks { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; margin-top: 20px; }
            .vy-lp-perk-ic { width: 38px; height: 38px; border-radius: 11px; background: rgba(255, 255, 255, 0.1); color: #f3b98e; display: grid; place-items: center; }
            .vy-lp-perk-lbl { display: block; font-size: 13.5px; font-weight: 500; color: #f3e7dd; margin-top: 9px; }
            .vy-lp-perk-sub { display: block; font-size: 12px; color: #b9aba0; line-height: 1.45; margin-top: 4px; }

            .vy-lp-rev { display: flex; flex-direction: column; padding: 20px; border-radius: 18px; background: rgba(255, 252, 248, 0.6); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: 0 12px 34px rgba(150, 90, 50, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.6); }
            .vy-lp-rev-stars { color: var(--vy-accent); letter-spacing: 2px; font-size: 14px; margin-bottom: 10px; }
            .vy-lp-rev-q { margin: 0; font-size: 13.5px; color: #4a3f38; line-height: 1.55; flex: 1; }
            .vy-lp-rev-by { display: flex; align-items: center; gap: 10px; margin-top: 16px; }
            .vy-lp-rev-av { width: 34px; height: 34px; border-radius: 50%; background: var(--vy-accent); display: grid; place-items: center; color: #fff; font-size: 12px; font-weight: 500; }

            .vy-lp-cta { margin-top: 44px; text-align: center; padding: 40px 24px; }
            .vy-lp-cta h2 { margin: 0; font-family: var(--vy-display); font-weight: 700; font-size: 30px; letter-spacing: -0.02em; }
            .vy-lp-cta p { margin: 10px 0 22px; font-size: 14.5px; color: var(--vy-muted); }
            .vy-lp-cta a { display: inline-flex; align-items: center; gap: 9px; padding: 15px 30px; border-radius: var(--vy-r-pill); background: var(--vy-accent); color: #fff; font-size: 15px; font-weight: 500; box-shadow: 0 10px 28px rgba(194, 65, 12, 0.4); }

            .vy-lp-nav { display: flex; align-items: center; gap: 18px; }
            .vy-lp-nav a { font-size: 13px; color: var(--vy-muted); font-weight: 500; }
            .vy-lp-nav a.cta { color: var(--vy-accent); }

            @media (max-width: 820px) {
                .vy-lp-grid3 { grid-template-columns: 1fr 1fr; }
                .vy-lp-grid4 { grid-template-columns: 1fr 1fr; }
                .vy-lp-perks { grid-template-columns: 1fr 1fr; }
                .vy-lp-h1 { font-size: 34px; }
                .vy-lp-tiles { display: none; }
            }
            @media (max-width: 560px) {
                .vy-lp-grid3, .vy-lp-grid4, .vy-lp-perks { grid-template-columns: 1fr; }
            }
        </style>
    </x-slot:head>

    <x-slot:nav>
        <nav class="vy-lp-nav">
            <a href="#stays">{{ __('booking/landing.nav_stays') }}</a>
            @if ($signedInGuest)
                <a href="{{ route('guest.account') }}" class="cta">{{ __('booking/landing.my_account') }}</a>
            @else
                <a href="{{ route('guest.login') }}" class="cta">{{ __('booking/landing.sign_in') }}</a>
            @endif
        </nav>
    </x-slot:nav>

    {{-- HERO --}}
    <section class="vy-lp-hero vy-fade">
        <div style="flex: 1 1 420px; min-width: 280px;">
            @if ($rating['has_reviews'])
                <div class="vy-lp-badge">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="var(--vy-accent-3)" stroke="none"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.8 5.9 21.4l1.4-6.8L2.2 9.9l6.9-.8z"></path></svg>
                    {{ trans_choice('booking/landing.rating_across', $rating['count'], ['average' => number_format((float) $rating['average'], 1), 'count' => $rating['count']]) }}@if ($heroTowns->isNotEmpty()) · {{ $heroTowns->join(' · ') }}@endif
                </div>
            @endif
            <h1 class="vy-lp-h1">{!! __('booking/landing.heading', ['org' => e($org['name'])]) !!}</h1>
            <p class="vy-lp-sub">{{ __('booking/landing.subheading') }}</p>
        </div>

        {{-- Floating town tiles (real town names; decorative gradients) --}}
        @if ($heroTowns->isNotEmpty())
            <div class="vy-lp-tiles">
                @foreach ($heroTowns as $i => $t)
                    <div class="vy-lp-tile" style="background: {{ $towns[$i]['thumb'] }}; @if ($i % 2 === 1) margin-top: 28px; @endif">
                        <span>{{ $t }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- SEARCH — native GET → the real per-property booking.browse --}}
    <form class="vy-lp-search vy-fade" method="GET" action="{{ route('storefront.search', ['organization' => $org['slug']]) }}">
        <div class="vy-lp-field">
            <label for="lp-where">{{ __('booking/landing.where') }}</label>
            <select id="lp-where" name="property">
                @foreach ($properties as $p)
                    <option value="{{ $p['slug'] }}">{{ $p['town'] !== '' ? $p['town'].' — ' : '' }}{{ $p['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="vy-lp-field">
            <label for="lp-in">{{ __('booking/landing.check_in') }}</label>
            <input id="lp-in" type="date" name="check_in" min="{{ now()->toDateString() }}">
        </div>
        <div class="vy-lp-field">
            <label for="lp-out">{{ __('booking/landing.check_out') }}</label>
            <input id="lp-out" type="date" name="check_out" min="{{ now()->addDay()->toDateString() }}">
        </div>
        <div class="vy-lp-field" style="flex: 0 1 92px; min-width: 84px;">
            <label for="lp-ad">{{ __('booking/landing.adults') }}</label>
            <select id="lp-ad" name="adults">@for ($n = 1; $n <= 12; $n++)<option value="{{ $n }}" @if ($n === 2) selected @endif>{{ $n }}</option>@endfor</select>
        </div>
        <div class="vy-lp-field" style="flex: 0 1 92px; min-width: 84px;">
            <label for="lp-ch">{{ __('booking/landing.children') }}</label>
            <select id="lp-ch" name="children">@for ($n = 0; $n <= 8; $n++)<option value="{{ $n }}">{{ $n }}</option>@endfor</select>
        </div>
        <button type="submit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
            {{ __('common.search') }}
        </button>
    </form>

    {{-- WHERE TO STAY --}}
    @if (count($towns) > 0)
        <section class="vy-lp-sec" id="stays">
            <div class="vy-lp-sec-head">
                <div>
                    <h2 class="vy-lp-h2">{{ __('booking/landing.where_to_stay') }}</h2>
                    <p class="vy-lp-lede">{{ trans_choice('booking/landing.towns_lede', count($towns), ['count' => count($towns)]) }}</p>
                </div>
            </div>
            <div class="vy-lp-grid3">
                @foreach ($towns as $t)
                    <a class="vy-lp-town vy-fade" style="animation-delay: {{ $t['delay'] }};" href="{{ $t['href'] }}">
                        <div class="vy-lp-town-img" style="background: {{ $t['thumb'] }};">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.6)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; top: 16px; left: 16px; z-index: 1;"><path d="M12 21s-7-5.4-7-11a7 7 0 0 1 14 0c0 5.6-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                            <div class="vy-lp-town-cap">
                                <div class="vy-lp-town-name">{{ $t['town'] }}</div>
                                <div class="vy-lp-town-sub">{{ $t['sub'] }}</div>
                            </div>
                        </div>
                        <div class="vy-lp-town-foot">
                            <span class="cnt">{{ $t['count'] }}</span>
                            @if ($t['from'] !== null)
                                <span class="from">{{ __('booking/landing.from_price', ['price' => $t['from']]) }}<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"></path></svg></span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- FEATURED STAYS --}}
    @if (count($featured) > 0)
        <section class="vy-lp-sec">
            <div class="vy-lp-sec-head">
                <div>
                    <h2 class="vy-lp-h2">{{ __('booking/landing.featured_stays') }}</h2>
                    <p class="vy-lp-lede">{{ __('booking/landing.featured_lede') }}</p>
                </div>
            </div>
            <div class="vy-lp-grid3">
                @foreach ($featured as $r)
                    <a class="vy-lp-card vy-fade" style="animation-delay: {{ $r['delay'] }};" href="{{ $r['href'] }}">
                        <div class="vy-lp-card-img" style="@if ($r['cover']) background-image: url('{{ $r['cover'] }}'); @else background: {{ $r['thumb'] }}; @endif"></div>
                        <div class="vy-lp-card-body">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <span class="vy-lp-card-name">{{ $r['name'] }}</span>
                                @if ($r['rating'] !== null)
                                    <span class="vy-lp-card-rate"><svg width="13" height="13" viewBox="0 0 24 24" fill="var(--vy-accent-3)" stroke="none"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.8 5.9 21.4l1.4-6.8L2.2 9.9l6.9-.8z"></path></svg>{{ $r['rating'] }}</span>
                                @endif
                            </div>
                            <div class="vy-lp-card-meta">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.4-7-11a7 7 0 0 1 14 0c0 5.6-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                                {{ $r['town'] }}{{ $r['kind'] !== '' ? ' · '.$r['kind'] : '' }}
                            </div>
                            <div style="flex: 1;"></div>
                            <div class="vy-lp-card-foot">
                                <div>@if ($r['from'] !== null)<span class="vy-lp-price">{{ $r['from'] }}</span><span style="font-size: 12px; color: var(--vy-faint);"> {{ __('booking/landing.per_night') }}</span>@else<span style="font-size: 12.5px; color: var(--vy-faint);">{{ __('booking/landing.see_dates_for_pricing') }}</span>@endif</div>
                                <span class="vy-lp-view">{{ __('common.view') }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- BROWSE BY AMENITY (only real, present amenities) --}}
    @if (count($vibes) > 0)
        <section class="vy-lp-sec">
            <h2 class="vy-lp-h2" style="margin-bottom: 18px;">{{ __('booking/landing.browse_by_what_matters') }}</h2>
            <div class="vy-lp-grid4">
                @foreach ($vibes as $v)
                    <a class="vy-lp-vibe vy-fade" href="{{ $v['href'] }}">
                        <span class="vy-lp-vibe-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $v['icon'] !!}</svg></span>
                        <span><span class="vy-lp-vibe-lbl">{{ $v['label'] }}</span><span class="vy-lp-vibe-sub">{{ $v['sub'] }}</span></span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- WHY BOOK DIRECT --}}
    <section class="vy-lp-direct vy-fade">
        <div style="position: absolute; top: -40px; right: -20px; width: 260px; height: 260px; border-radius: 50%; background: radial-gradient(circle, rgba(232,112,58,.4), transparent 70%); filter: blur(20px); pointer-events: none;"></div>
        <div style="position: relative;">
            <h3>{{ __('booking/landing.why_book_direct') }}</h3>
            <div class="vy-lp-perks">
                @foreach ($perks as $p)
                    <div>
                        <span class="vy-lp-perk-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
                        <span class="vy-lp-perk-lbl">{{ $p['label'] }}</span>
                        <span class="vy-lp-perk-sub">{{ $p['sub'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- RECENT GUESTS --}}
    <section class="vy-lp-sec">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
            <h2 class="vy-lp-h2">{{ __('booking/landing.recent_guests') }}</h2>
            @if ($rating['has_reviews'])
                <span class="vy-lp-badge" style="margin: 0;"><svg width="12" height="12" viewBox="0 0 24 24" fill="var(--vy-accent-3)" stroke="none"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.8 5.9 21.4l1.4-6.8L2.2 9.9l6.9-.8z"></path></svg>{{ trans_choice('booking/landing.rating_short', $rating['count'], ['average' => number_format((float) $rating['average'], 1), 'count' => $rating['count']]) }}</span>
            @endif
        </div>
        @if (count($reviews) > 0)
            <div class="vy-lp-grid3">
                @foreach ($reviews as $rv)
                    <div class="vy-lp-rev vy-fade">
                        <div class="vy-lp-rev-stars" role="img" aria-label="{{ __('booking/landing.stars_aria', ['rating' => $rv['rating']]) }}"><span aria-hidden="true">{{ $rv['stars'] }}</span></div>
                        <p class="vy-lp-rev-q">{{ __('booking/landing.review_quote', ['body' => $rv['body']]) }}</p>
                        <div class="vy-lp-rev-by">
                            <span class="vy-lp-rev-av">{{ $rv['initials'] }}</span>
                            <span><span style="display: block; font-size: 13px; font-weight: 500;">{{ $rv['byline'] }}</span><span style="display: block; font-size: 11.5px; color: var(--vy-faint);">{{ $rv['villa'] !== '' ? $rv['villa'].' · ' : '' }}{{ $rv['at'] }}</span></span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="vy-lp-rev" style="text-align: center; color: var(--vy-muted); font-size: 13.5px;">{{ __('booking/landing.no_reviews_yet') }}</div>
        @endif
    </section>

    {{-- CTA --}}
    <section class="vy-lp-cta">
        <h2>{{ __('booking/landing.cta_heading') }}</h2>
        <p>{{ __('booking/landing.cta_sub') }}</p>
        <a href="#stays">{{ __('booking/landing.browse_all_stays') }}<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"></path></svg></a>
    </section>
</x-public.shell>
