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
    //
    // ⚠️⚠️ BOTH CYCLES ARE RENDERED, AND THE SCRIPT COLLAPSES ONE. Annual billing is real — the `plans`
    // table holds a `yearly` row for each tier and the operator Checkout bills against it — so a page that
    // showed only the monthly figure was hiding a capability the product has.
    // ⚠️ THE SHAPE IS NOT A TOGGLE OVER ONE PRICE. With scripts off, a control that reveals nothing leaves
    // a visitor with one price and a dead button; rendering BOTH leaves them with the complete truth,
    // labelled. That is the rule the rest of this site follows (§ the visible-by-default pane rule), and a
    // price nobody can see is worse than a panel nobody can see.
    // ⚠️ NO SAVING IS COMPUTED ANYWHERE. The arithmetic is available and it is a trap: the real difference
    // is 16.67% of two numbers NOBODY HAS SET (`isPlaceholder()` is true), and a "−15%" badge was already
    // cut from this page for being false. Deriving a percentage correctly would not make it true. The FAQ
    // says annual is "the cheaper of the two cycles" — true, unquantified, and the model for what may be said.
    //
    // ⚠️⚠️ ONE LIST, EACH TIER CARRYING BOTH CYCLES — AND THAT SHAPE IS THE FIX.
    // This block used to build TWO lists (`tiersForDisplay('monthly')` and `…('yearly')`) and the card
    // below paired them BY POSITION. That killed the page with `Undefined array key 2` the moment a plan
    // existed in one interval only — the unavoidable intermediate state when a founder adds a third tier,
    // since the admin panel creates one plan per interval — and, worse, SILENTLY transposed the prices
    // whenever the two lists were the same length in a different order, which `orderBy('price_minor')`
    // per interval permits. See `MarketingPricing::tiersWithCycles()` for both reproductions.
    // ⚠️ There is nothing to pair now: a figure is read from its own tier's own cycle slot.
    $mkTiers = \App\Support\Marketing\MarketingPricing::tiersWithCycles();
@endphp

{{-- ⚠️⚠️ A RADIOGROUP, NOT A TABLIST, AND THE ROLE IS ARGUED NOT COPIED. `tabGroup` drives the hour rail
     and the stepper, where each control reveals a DIFFERENT PANEL of content. These two select which
     PRESENTATION of the same fact is shown — the same two plans, priced over a different period — which is
     a choice between mutually exclusive options, i.e. a radio group. The precedent is this file's sibling:
     the module chips are a FILTER and were deliberately NOT made a tablist either.
     ⚠️ The keyboard contract is identical (roving tabindex, arrow keys), so `marketing.js` drives both from
     ONE implementation with the state attribute as a parameter — never a second copy (§N.8f).
     ⚠️ THE PRODUCT'S OWN PERIOD CONTROL IS DIFFERENT ON PURPOSE: operator Checkout uses two server-rendered
     LINKS carrying `?interval=`, because there the interval is DATA that must survive into a POST. Here it
     is display only. What must not drift is the PRICE SOURCE, and both read `MarketingPricing`. --}}
<div data-reveal style="display: flex; justify-content: center; margin-bottom: 26px;">
    <div role="radiogroup" aria-label="Billing period" data-cyclegroup
         style="display: inline-flex; align-items: center; gap: 6px; padding: 5px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); box-shadow: 0 8px 22px rgba(150,90,50,.12);">
        @foreach (['monthly' => 'Monthly', 'yearly' => 'Annual'] as $mkKey => $mkLabel)
            <button type="button" role="radio" data-cyclebtn="{{ $mkKey }}"
                    aria-checked="{{ $mkKey === 'monthly' ? 'true' : 'false' }}"
                    tabindex="{{ $mkKey === 'monthly' ? '0' : '-1' }}"
                    style="padding: 10px 22px; border-radius: 999px; border: 1px solid rgba(140,82,45,.7); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer;">{{ $mkLabel }}</button>
        @endforeach
    </div>
</div>

{{-- ⚠️⚠️ THE GRID LIVES HERE NOW, AND THE COLUMN COUNT IS DERIVED.
     The design drew the cards inside `<div data-plans>` with `display: grid`, and the converter replaces
     that whole element with this component — so the grid went with it, while the shell's `[data-plans]`
     media rules still target an attribute NO LONGER ON THE PAGE. The cards rendered stacked full-width.
     ⚠️ THE COUNT IS NEVER TYPED. `repeat(count($mkTiers), …)` follows the plans that actually exist: two
     today, three if a third tier is added, four if a fourth is. A hardcoded `repeat(3, …)` would leave a
     HOLE today and overflow at four — and "never type a number into a page" is a named never-do here.
     ⚠️ `minmax(0, 1fr)`, NOT a bare `1fr`: that exact trap has caused three overflow defects in this
     codebase (the marketing footer, the operator shell, the owner shell) and a guard now enforces it.
     ⚠️ ONE COLUMN ON A PHONE at the shell's existing 760px breakpoint — no new breakpoint invented. --}}
<div class="vy-plan-grid" style="display: grid; grid-template-columns: repeat({{ count($mkTiers) }}, minmax(0, 1fr)); gap: 20px; align-items: stretch;">
@foreach ($mkTiers as $mkTier)
<div data-reveal style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; padding: 30px 26px; box-shadow: 0 14px 40px rgba(150,90,50,.14); display: flex; flex-direction: column;">
  <div data-planname="{{ $mkTier['code'] }}" style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">{{ $mkTier['name'] }}</div>
  <div style="font-size: 13px; color: var(--vy-muted); margin-top: 5px;">{{ $mkTier['sub'] }}</div>
  {{-- ⚠️ BOTH CYCLES, EACH COMPLETE: its own amount, its own suffix and its own "Billed …" line. Nothing
       here has to "follow" a switch, because nothing is shared between the two — which is why there is no
       way for the number to change and the period beside it to stay behind. --}}
  @foreach (\App\Support\Marketing\MarketingPricing::CYCLES as $mkCycle)
    {{-- ⚠️⚠️ THE TIER'S OWN SLOT, NEVER AN INDEX. `$mkTier['cycles'][$mkCycle]` can only ever hold a row
         whose own `interval` is this cycle and whose own tier key is this card's — so there is no ordering,
         length or price-rank under which this card could show another plan's price. --}}
    @php($mkRow = $mkTier['cycles'][$mkCycle])
    <div data-cycleprice="{{ $mkCycle }}">
      @if ($mkRow !== null)
      <div style="margin: 24px 0 4px; display: flex; align-items: baseline; gap: 5px;">
        <span style="font-family: var(--vy-display); font-size: 42px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.035em; font-variant-numeric: tabular-nums;"><span data-cur>&euro;</span><span data-price data-plan="{{ $mkRow['code'] }}">{{ number_format($mkRow['amount_minor'] / 100, 2) }}</span></span>
        <span style="font-size: 13px; color: var(--vy-muted);">{{ $mkRow['suffix'] }}</span>
      </div>
      <div data-cyclenote style="font-size: 12px; color: var(--vy-muted); margin-bottom: 18px;">{{ $mkRow['cycle'] }}</div>
      @else
      {{-- ⚠️ A CYCLE THIS PLAN IS NOT SOLD ON. The block still ships — with scripts off both cycles are
           visible and the switcher collapses one, so omitting it would leave a HOLE where a price belongs
           the moment a visitor pressed "Annual". ⚠️ NO figure: not a blank, not a zero, and above all not
           12 × the monthly price, which would be a price the engine does not charge. --}}
      <div data-cyclenote data-cycleunavailable="{{ $mkCycle }}" style="font-size: 12px; color: var(--vy-muted); margin: 24px 0 18px;">{{ \App\Support\Marketing\MarketingPricing::cycleUnavailableNote($mkCycle) }}</div>
      @endif
    </div>
  @endforeach
  <ul style="margin: 0 0 24px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px;">
    @foreach ($mkTier['features'] as $mkFeature)
    <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg><span>{{ $mkFeature }}</span></li>
    @endforeach
  </ul>

  <a data-plancta="{{ $mkTier['code'] }}" href="{{ route('signup') }}" style="margin-top: auto; text-align: center; padding: 13px; border-radius: 13px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14px; font-weight: 500; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease;">Start free</a>
</div>
@endforeach
</div>
