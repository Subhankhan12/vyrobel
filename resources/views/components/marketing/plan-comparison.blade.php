{{-- THE PLAN COMPARISON — every column and every row from `config/billing.php`.

     ⚠️ WHAT THIS REPLACES, AND WHY RE-NUMBERING WOULD NOT HAVE DONE. The design drew a four-column ladder —
     Starter / Growth / Pro / Enterprise — with twelve feature-difference rows. B12 rebuilt the CARDS one
     section above this to read from the billing config and pinned them against `Plan::chargeFor()`, and
     this table kept the invented ladder: same page, one section down, live, and about money.

     ⚠️ THE LIE WAS STRUCTURAL. A four-column ladder asserts three upgrade steps; the product has ONE tier
     boundary. Counted against the config, the real differences are exactly TWO — the channel manager
     (Pro only) and the team-seat limit (5 vs unlimited). The drawn table manufactured ten more.

     ⚠️ SO THE DESIGN'S LAYOUT COULD NOT HOLD THE HONEST VERSION, AND IS NOT PADDED OUT TO FIT. Its visual
     language is kept — the frosted card, the sticky header row, the accent check glyph — at the size the
     truth actually is. What is gone is the shape that only existed to fill columns.

     ⚠️ IT WAS ALSO BROKEN, WHICH ONLY THE BROWSER SHOWED. The design's `<sc-raw-table>` is not a table: it
     is an unknown element, so `border-collapse` and column alignment did nothing and the section rendered
     as inline soup — four tier headers jumbled over each other and the check cells floating as orphaned
     dashes between rows. Zero real `<table>` elements were in the section. This uses a real one, which is
     also what lets a screen reader read a cell against its row and column headers.

     ⚠️ A ROW CANNOT BE TYPED IN HERE. Rows come from `MarketingPricing::comparison()`, which reads the same
     catalogue the paywall and `Organization::planAllows()` read — so this table cannot promise a capability
     the org will not get, and a new real gate appears here the day it is added to the config. --}}

@php
    $cmp = \App\Support\Marketing\MarketingPricing::comparison();
    $tiers = $cmp['tiers'];
    $rows = $cmp['rows'];
@endphp

<section data-sec="" id="compare-table" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 80px;">
    <div data-reveal="" style="max-width: 720px; margin-bottom: 26px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">What&rsquo;s in each plan</h2>

        @if ($cmp['differences'] === 0)
            {{-- ⚠️ THE HONEST DEGENERATE CASE, and it is reachable: if the config ever gates nothing
                 differently, there is no comparison to draw and the page says so rather than rendering an
                 empty grid of identical ticks. --}}
            <p style="margin: 12px 0 0; font-size: 16px; line-height: 1.66; color: var(--vy-muted);">
                Every plan currently includes the same features. The plans differ only in price.
            </p>
        @else
            <p style="margin: 12px 0 0; font-size: 16px; line-height: 1.66; color: var(--vy-muted);">
                {{ count($tiers) }} plans, and {{ $cmp['differences'] === 1 ? 'one difference' : $cmp['differences'].' differences' }}
                between them. Everything else below is in both.
            </p>
        @endif
    </div>

    <div data-reveal="" data-cmpscroll="" style="background: rgba(255,252,248,.62); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 14px 44px rgba(150,90,50,.15); overflow-x: auto;">
        {{-- ⚠️ A REAL <table>, and `min-width` is deliberately absent: with two plans it fits a phone, so
             forcing a horizontal scroll would be a defect the design only needed because of four columns. --}}
        <table data-cmp="" style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <caption style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">
                Feature comparison across the {{ collect($tiers)->pluck('name')->join(' and ') }} plans
            </caption>
            <thead>
                <tr>
                    <th scope="col" style="text-align: left; padding: 16px; font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); border-bottom: 1px solid rgba(150,90,50,.14);">Capability</th>
                    @foreach ($tiers as $tier)
                        <th scope="col" data-planname="{{ $tier['code'] }}" style="text-align: center; padding: 16px 12px; font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); border-bottom: 1px solid rgba(150,90,50,.14);">
                            {{ $tier['name'] }}
                            {{-- ⚠️ The price repeats here from the SAME source as the cards above, so the two
                                 cannot disagree — which is exactly the failure this pass exists to close. --}}
                            <span style="display: block; font-family: var(--vy-sans); font-size: 12px; font-weight: 500; color: var(--vy-muted); margin-top: 3px;">{{ $tier['formatted'] }} {{ \App\Support\Marketing\MarketingPricing::suffix() }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr style="border-bottom: 1px solid rgba(150,90,50,.08);">
                        <th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400; color: var(--vy-ink);">
                            {{ $row['label'] }}
                            @if ($row['differs'])
                                <span style="display: inline-block; margin-left: 8px; padding: 2px 7px; border-radius: 6px; background: rgba(217,102,46,.12); font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-accent-text);">Differs</span>
                            @endif
                        </th>
                        @foreach ($row['cells'] as $i => $cell)
                            <td style="text-align: center; padding: 15px 12px;">
                                @if ($cell['has'])
                                    <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
                                    </span>
                                    @if ($cell['note'])
                                        <span style="display: block; font-size: 12px; color: var(--vy-muted); margin-top: 4px;">{{ $cell['note'] }}</span>
                                    @endif
                                    {{-- ⚠️ The visible glyph is aria-hidden, so the cell needs a text answer a
                                         screen reader can read against its row and column headers. --}}
                                    <span class="sr-only">{{ $cell['note'] ?: 'Included' }} in {{ $tiers[$i]['name'] }}</span>
                                @else
                                    <span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(150,90,50,.28);"></span>
                                    <span class="sr-only">Not included in {{ $tiers[$i]['name'] }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ⚠️ WHAT THE DESIGN'S OTHER TEN ROWS BECOME. They were not differences — they are things every plan
         has, and a comparison grid of identical ticks says nothing. ⚠️ They are NOT re-listed here as a
         feature roll-call either: that would be re-asserting twelve product claims this pass has no basis
         to make. The cards above already list what a plan grants, from the same catalogue. --}}
    <p style="margin: 18px 2px 0; font-size: 13px; line-height: 1.65; color: var(--vy-muted); max-width: 720px;">
        Everything else Vyrobel does &mdash; the direct booking engine, the front desk, housekeeping,
        maintenance, the owner portal and reporting &mdash; is in every plan. We list only what actually
        changes between them.
        @if (\App\Support\Marketing\MarketingPricing::isPlaceholder())
            <span style="display: block; margin-top: 6px;">{{ \App\Support\Marketing\MarketingPricing::priceNote() }}</span>
        @endif
    </p>
</section>
