{{-- THE "VYROBEL BY THE NUMBERS" BAND — driven by the ONE guarded ledger (Gate 0 / D1).

     ⚠️ TWO SHAPES BY CONSTRUCTION. Locally the four illustrative figures render with the design's own
     ILLUSTRATIVE badge (its honesty affordance, reproduced — and driven by the real flags, so it clears
     itself the day the figures become real). In production MarketingContent FILTERS every unreal figure,
     so today the band is EMPTY — and an empty band must not render a heading over nothing, so the whole
     section omits cleanly. ⚠️ That is a real consequence the founder must see, not a bug: until D1's
     content is replaced, the production home page HAS NO NUMBERS.

     ⚠️ THE DESIGN'S OWN SCRIPT ALREADY DID THIS per-stat ("if (!f) … host.style.display = 'none'"). This is
     that rule taken to its limit: no figures at all ⇒ no band.

     ⚠️ THE SPARKLINES ARE NOT REPRODUCED. The design animates a 12-month series per figure; those 24
     points are invented trend data with NO entry in the guarded ledger, so rendering them would put
     illustrative content outside the content-honesty gate's reach — exactly what Gate 0 exists to
     prevent. Registered as a divergence.

     ⚠️ The figure VALUES come from the ledger, never from the design's page script — the two disagree
     (the script says 63% and 11h where the ledger says 62 and 9), and the ledger is the ONE source. --}}
@php
    $figures = \App\Support\Marketing\MarketingContent::figures();
    $illustrative = ! \App\Support\Marketing\MarketingContent::declaredProduction();
@endphp
@if (count($figures) > 0)
<section aria-labelledby="vy-stats-h" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 80px;">
    <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 22px;">
        <h2 id="vy-stats-h" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">Vyrobel by the numbers</h2>
        @if ($illustrative)
            {{-- The design's own badge — shown while the ledger says ILLUSTRATIVE, gone when it doesn't. --}}
            <span data-illustrative-badge style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; background: rgba(217,102,46,.13); border: 1px solid rgba(217,102,46,.24); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text);">Illustrative</span>
        @endif
    </div>
    <div style="background: rgba(255,252,248,.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 24px; box-shadow: 0 14px 40px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65); padding: 38px 40px;">
        <div data-stats style="display: grid; grid-template-columns: repeat({{ min(count($figures), 4) }}, 1fr); gap: 28px;">
            @foreach ($figures as $key => $figure)
                <div>
                    <div style="font-family: var(--vy-display); font-size: 38px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-fig="{{ $key }}">{{ $figure['value'] }}</span></div>
                    <div style="margin-top: 6px; font-size: 13.5px; color: var(--vy-muted);">{{ $figure['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
