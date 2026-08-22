{{-- THE PLAN CARDS — names, prices, cycle and features all from `config/billing.php`.

     ⚠️ WHY THIS IS A COMPONENT AND NOT MARKUP IN THE PAGE. B12 rebuilt these cards correctly, but it did so
     by hand-editing the GENERATED page: its inline PHP block and its tier loop existed nowhere in the
     converter.
     ⚠️ (Those two directive names are DELIBERATELY not written here. Blade compiles STATEMENTS BEFORE
     it strips COMMENTS, so a directive inside a comment is compiled anyway — and an inline-PHP one
     opens a real PHP block that swallows everything after it. This component 500'd on exactly that,
     with "Undefined variable $mkTiers", because the compiled comment ate the block that defines it.)
     ⚠️ So `/pricing` had quietly become un-regenerable — running the generator over it would have restored
     the design's four hardcoded tiers ON THE CARDS TOO, silently, and the only warning would have been a
     reader noticing "Starter €6". That is precisely the failure the converter's own doctrine exists to
     prevent ("a post-generation patch is undone by the next generation"), applied to the fix rather than to
     the defect.

     ⚠️ Moving the loop into a component makes the page regenerable again: the converter swaps the design's
     cards section for one tag, and the binding lives somewhere a regeneration cannot reach.

     ⚠️ THE MARKUP IS B12'S, VERBATIM — the design's card styling, unchanged. Only its home moved. --}}

@php
    // ⚠️ THE ONE PRICE SOURCE (§N.8f). Names, amounts, the per-unit-vs-flat suffix and the feature gates all
    // come from `MarketingPricing`, which reads the same `plans` rows `Plan::chargeFor()` bills from.
    // Nothing about a price or a tier name may be typed into a marketing page.
    $mkSuffix = \App\Support\Marketing\MarketingPricing::suffix();
    $mkTiers = \App\Support\Marketing\MarketingPricing::tiersForDisplay();
@endphp

@foreach ($mkTiers as $mkTier)
<div data-reveal style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; padding: 30px 26px; box-shadow: 0 14px 40px rgba(150,90,50,.14); display: flex; flex-direction: column;">
  <div data-planname="{{ $mkTier['code'] }}" style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">{{ $mkTier['name'] }}</div>
  <div style="font-size: 13px; color: var(--vy-muted); margin-top: 5px;">{{ $mkTier['sub'] }}</div>
  <div style="margin: 24px 0 4px; display: flex; align-items: baseline; gap: 5px;">
    <span style="font-family: var(--vy-display); font-size: 42px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.035em; font-variant-numeric: tabular-nums;"><span data-cur>&euro;</span><span data-price data-plan="{{ $mkTier['code'] }}">{{ number_format($mkTier['amount_minor'] / 100, 2) }}</span></span>
    <span style="font-size: 13px; color: var(--vy-muted);">{{ $mkSuffix }}</span>
  </div>
  <div data-cyclenote style="font-size: 12px; color: var(--vy-muted); margin-bottom: 18px;">{{ $mkTier['cycle'] }}</div>
  <ul style="margin: 0 0 24px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px;">
    @foreach ($mkTier['features'] as $mkFeature)
    <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg><span>{{ $mkFeature }}</span></li>
    @endforeach
  </ul>

  <a data-plancta="{{ $mkTier['code'] }}" href="{{ route('signup') }}" style="margin-top: auto; text-align: center; padding: 13px; border-radius: 13px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14px; font-weight: 500; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease;">Start free</a>
</div>
@endforeach
