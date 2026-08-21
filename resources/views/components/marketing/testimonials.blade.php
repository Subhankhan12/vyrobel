{{-- "WHAT OPERATORS TELL US" — driven by the ONE guarded ledger (Gate 0 / D1).

     ⚠️ TWO SHAPES. Locally the six illustrative testimonial authors render, carrying the design's OWN
     note ("Illustrative of the conversations we have during onboarding. Real names and figures land here
     at launch.") — the design's honesty affordance, reproduced and driven by the real `consent` flags so
     it clears itself as approvals land. In production MarketingContent filters every unconsented person,
     so today this section renders NOTHING and omits entirely — a named person who has not consented must
     never reach a public page (#39, and it is the legal exposure D1 exists for).

     ⚠️ Consequence the founder must see: until real consented quotes exist, the production home page has
     NO social proof.

     ⚠️ THE QUOTE BODIES stay in the design's markup (they are copy, not identity) but are rendered ONLY
     beside a person the ledger permits — so no quote can appear detached from, or misattributed to, a
     consented name. --}}
@props(['quotes' => []])
@php
    $people = \App\Support\Marketing\MarketingContent::testimonials();
    $anyUnconsented = collect(\App\Support\Marketing\MarketingContent::testimonials(filtered: false))
        ->contains(fn (array $p): bool => ($p['consent'] ?? false) !== true);
@endphp
@if (count($people) > 0)
<section aria-labelledby="vy-tst-h" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 86px;">
    <div style="margin-bottom: 26px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-3); margin-bottom: 14px;">From the floor</div>
        <h2 id="vy-tst-h" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">What operators tell us</h2>
        @if ($anyUnconsented)
            <p data-custnote style="margin: 12px 0 0; font-size: 13px; color: var(--vy-muted); max-width: 620px;">Illustrative of the conversations we have during onboarding. Real names and figures land here at launch.</p>
        @endif
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
        @foreach ($people as $person)
            <figure style="margin: 0; padding: 22px; border-radius: 20px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 10px 28px rgba(150,90,50,.12);">
                <blockquote style="margin: 0; font-size: 14.5px; line-height: 1.62; color: var(--vy-ink);">{{ $quotes[$person['id']] ?? '' }}</blockquote>
                <figcaption style="display: flex; align-items: center; gap: 11px; margin-top: 16px;">
                    <span aria-hidden="true" style="width: 36px; height: 36px; border-radius: 11px; background: var(--vy-grad-peach); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-weight: 700; font-size: 13px; color: #fff;">{{ \Illuminate\Support\Str::of($person['name'])->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('') }}</span>
                    <span style="display: flex; flex-direction: column;">
                        <span style="font-size: 13.5px; font-weight: 600; color: var(--vy-ink);">{{ $person['name'] }}</span>
                        <span style="font-size: 12px; color: var(--vy-muted);">{{ $person['role'] }}</span>
                    </span>
                </figcaption>
            </figure>
        @endforeach
    </div>
</section>
@endif
