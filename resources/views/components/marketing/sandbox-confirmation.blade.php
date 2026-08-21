{{-- THE HONEST SANDBOX (front-website Gate 4, ruling D3 = (b)).

     ⚠️ WHAT THIS REPLACES. The design devoted a 25,000-character section to a client-side SIMULATION of a
     booking on a fictional property ("Villa Azure, Paros, Greece · sleeps 6"), ending in a FABRICATED
     confirmation email — under a meta description promising *"Make a real booking on a demo property"*.
     The simulation WAS the fabrication, so it could not be corrected in place; it is replaced by the real
     thing.

     ⚠️ EVERYTHING BELOW IS TRUE OF SOMETHING THAT EXISTS: the property is the real `seaside-demo`, the
     price comes from the booking engine's OWN method over the same rate_calendar rows a guest is charged
     from, and the confirmation is the REAL BookingConfirmationMail template.

     ⚠️ AND NOTHING IS WRITTEN OR SENT. No reservation, no quote row, no email. The engine's `quote()`
     persists a row, so it is never called — only its read-only `priceOver()` half.

     ⚠️ #33 — every link the real mail computes (including `arrivalUrl`, the arrival-secrets page) is
     defused before the template renders. Asserted, with a positive control, in
     tests/Feature/MarketingTryBookingTest.php. --}}
@php
    $sandbox = \App\Support\Marketing\TryBookingSandbox::data();
    $preview = $sandbox === null ? null : \App\Support\Marketing\TryBookingSandbox::confirmationPreview();
@endphp

<section aria-labelledby="vy-sandbox-h" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 80px;">
    {{-- ⚠️ CONSTRAINT 2 — the framing is PROMINENT, not a badge. The visitor must not be able to believe
         they booked anything, and this sits above the content rather than beneath it. --}}
    <div data-sandbox-banner style="display: flex; align-items: flex-start; gap: 13px; padding: 16px 20px; border-radius: 18px; background: rgba(217,102,46,.13); border: 1px solid rgba(217,102,46,.28); margin-bottom: 26px;">
        <span aria-hidden="true" style="flex-shrink: 0; margin-top: 1px; color: var(--vy-accent-text);">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16.5v.01"></path></svg>
        </span>
        <p style="margin: 0; font-size: 14px; line-height: 1.6; color: var(--vy-ink); max-width: 720px;">
            <strong>This is a preview, not a booking.</strong> Nothing on this page reserves a room, takes a
            payment or sends an email. It shows the real demo property, priced by the real booking engine,
            and the real confirmation email &mdash; so you can see exactly what your guests would see.
            @if ($sandbox !== null)
                To make a booking that actually happens, use
                <a href="{{ $sandbox['bookForRealUrl'] }}" style="color: var(--vy-accent-text); font-weight: 600;">the live demo storefront</a>.
            @endif
        </p>
    </div>

    <h2 id="vy-sandbox-h" style="margin: 0 0 6px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">What a direct booking looks like</h2>

    @if ($sandbox === null)
        {{-- ⚠️ A REAL STATE, NOT AN EDGE CASE. Production is never seeded (DEPLOYMENT §4), so the demo
             property may simply not exist — and with nothing to mirror there is nothing honest to show.
             The page says so rather than inventing a stand-in, which is the whole point of mirroring. --}}
        <p style="margin: 12px 0 0; font-size: 14.5px; line-height: 1.62; color: var(--vy-muted); max-width: 640px;">
            The demo property isn&rsquo;t available on this environment, so there is nothing real to show
            here. Rather than invent one, we&rsquo;d rather say so &mdash;
            <x-marketing.nav-link key="contact" fragment="#demo" style="color: var(--vy-accent-text); font-weight: 600;">ask us for a walkthrough</x-marketing.nav-link>
            and you&rsquo;ll see it on real data.
        </p>
    @else
        <p style="margin: 0 0 24px; font-size: 14.5px; line-height: 1.62; color: var(--vy-muted); max-width: 640px;">
            Every figure below is read from the same rate calendar the booking engine charges from.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: start;">
            {{-- The real property + the real stay, priced by the engine's own method --}}
            <div style="padding: 24px 26px; border-radius: 22px; background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint);">The demo property</div>
                <div data-sandbox-property style="margin-top: 8px; font-family: var(--vy-display); font-weight: 600; font-size: 22px; letter-spacing: -.02em; color: var(--vy-ink);">{{ $sandbox['propertyName'] }}</div>
                <div style="margin-top: 4px; font-size: 13.5px; color: var(--vy-muted);">{{ $sandbox['place'] }}</div>

                <dl style="margin: 20px 0 0; display: grid; grid-template-columns: auto 1fr; gap: 9px 18px; font-size: 13.5px;">
                    <dt style="color: var(--vy-muted);">Room type</dt>
                    <dd style="margin: 0; color: var(--vy-ink); font-weight: 600;">{{ $sandbox['roomTypeName'] }} <span style="font-weight: 400; color: var(--vy-muted);">&middot; {{ $sandbox['units'] }} {{ \Illuminate\Support\Str::plural('unit', $sandbox['units']) }}</span></dd>
                    <dt style="color: var(--vy-muted);">Rate plan</dt>
                    <dd style="margin: 0; color: var(--vy-ink); font-weight: 600;">{{ $sandbox['ratePlanName'] }}</dd>
                    <dt style="color: var(--vy-muted);">Stay</dt>
                    <dd style="margin: 0; color: var(--vy-ink); font-weight: 600;">{{ $sandbox['checkIn']->format('D, j M Y') }} &rarr; {{ $sandbox['checkOut']->format('D, j M Y') }} <span style="font-weight: 400; color: var(--vy-muted);">&middot; {{ $sandbox['nights'] }} nights</span></dd>
                    <dt style="color: var(--vy-muted);">Per night</dt>
                    <dd style="margin: 0; color: var(--vy-ink); font-weight: 600;">{{ $sandbox['perNight'] }}</dd>
                </dl>

                <div style="display: flex; align-items: baseline; justify-content: space-between; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--vy-hairline);">
                    <span style="font-size: 13.5px; color: var(--vy-muted);">Total, {{ $sandbox['nights'] }} nights</span>
                    <span data-sandbox-total style="font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); font-variant-numeric: tabular-nums;">{{ $sandbox['total'] }}</span>
                </div>
                <p style="margin: 10px 0 0; font-size: 12px; line-height: 1.55; color: var(--vy-faint);">
                    ⚠️ Not a quote and not a hold &mdash; the dates above are simply the next three
                    consecutive nights this property has published a rate for.
                </p>

                <a href="{{ $sandbox['bookForRealUrl'] }}" style="display: inline-flex; align-items: center; gap: 8px; margin-top: 18px; padding: 11px 18px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-size: 13.5px; font-weight: 600; text-decoration: none; box-shadow: var(--vy-shadow-accent);">
                    Book it for real on the demo storefront <x-marketing.arrow />
                </a>
            </div>

            {{-- ⚠️ THE REAL CONFIRMATION EMAIL — the product's own template, not a drawing of one. --}}
            <div style="padding: 24px 26px; border-radius: 22px; background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint);">The confirmation your guest receives</div>
                <p style="margin: 8px 0 16px; font-size: 13px; line-height: 1.55; color: var(--vy-muted);">
                    This is Vyrobel&rsquo;s real confirmation email, rendered from the product&rsquo;s own
                    template &mdash; not a mock-up of one. ⚠️ Its links are deliberately inert here.
                </p>
                @if ($preview !== null)
                    <div data-mail-preview style="max-height: 560px; overflow-y: auto; border-radius: 14px; border: 1px solid var(--vy-hairline); background: #fff;">
                        {!! $preview !!}
                    </div>
                @endif
            </div>
        </div>
    @endif
</section>
