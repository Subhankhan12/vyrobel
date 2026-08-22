{{-- MARKETING SITE NAV (front-website Gate 1) — the design's "Site Nav" partial, extracted ONCE (§N.8f: the
     design inlines it into all 84 pages; here it is ONE component). Markup reproduced faithfully from the
     partial's __bundler/template; the dc-runtime bindings ({{ openProduct }}, refs, sc-if) are REMOVED, not
     escaped — they were React state wiring, and rendering them literally would ship framework syntax as
     text. Interactivity is CSS-native (hover/focus-within mega-menus, a checkbox mobile toggle): no React,
     no CDN (E9); an Alpine enhancement can layer on later without markup changes.

     ⚠️ PENDING LINKS: a page not yet registered by Gate 2+ renders as a marked non-link (data-vy-pending),
     never an href that 404s while looking fine. Log in / Start free point at the ALREADY-REAL /login and
     /signup (ruling D9 Tier 1 — the existing role dispatch; no auth change).

     ⚠️ DEFERRED WITH D7: the design's language switcher (EN/NL/ES/EL) is not rendered — English first; the
     translated pages and the /{locale} route (E1) come later. Registered in the divergence register. --}}
@php use App\Support\Marketing\MarketingUrls; @endphp

@php
    // One local helper shape: [key, label, optional description]. Rendering resolves pending state per item.
    $mkItem = function (string $key, string $label, ?string $desc = null, ?string $icon = null) {
        return ['key' => $key, 'label' => $label, 'desc' => $desc, 'icon' => $icon];
    };
@endphp

<header class="vy-mk-header">
    <nav class="vy-mk-nav" aria-label="Main">
        <a href="{{ MarketingUrls::path('home') }}" class="vy-mk-logo">
            <span class="vy-mk-logo-mark" aria-hidden="true"><span>V</span><span class="vy-mk-shimmer"></span></span>
            <span class="vy-mk-logo-name">Vyrobel</span>
        </a>

        <div class="vy-mk-links">
            {{-- Product — the mega panel: The platform · Connect & learn · Start here · Popular guides --}}
            <div class="vy-mk-drop">
                <x-marketing.nav-link key="product" class="vy-mk-navlink">Product <x-marketing.caret /></x-marketing.nav-link>
                <div class="vy-mk-mega" role="group" aria-label="Product menu">
                    <div class="vy-mk-megagrid">
                        <div>
                            <div class="vy-mk-megalabel">The platform</div>
                            <x-marketing.mega-row key="product" title="Platform overview" desc="Operators, owners and guests" icon="overview" />
                            <x-marketing.mega-row key="features" title="Features" desc="Every capability in detail" icon="features" />
                            <x-marketing.mega-row key="modules" title="Modules" desc="All eighteen, in every plan" icon="modules" />
                            {{-- ⚠️ GATE 4: was desc="A real booking, no signup" — a promise repeated on EVERY page, and false: the
                                 sandbox books nothing. Found by sweeping the CHROME, which the ruling (scoped to
                                 /try-booking) did not reach — the same way Gate 3b found three live Sales pages
                                 carrying a deleted claim.
                                 ⚠️ GATE 4b re-swept it the other way: with the picker restored, "A preview on our
                                 real demo property" had become an UNDERSTATEMENT of a page that now prices real
                                 dates. Sharpened — and deliberately NOT to "Pick your dates", which is an
                                 INVITATION and belongs only where the picker actually is (this row renders on
                                 every page; MarketingTryBookingTest sweeps for exactly that). --}}
                            <x-marketing.mega-row key="try-booking" title="Try the booking engine" desc="Real dates and real prices — nothing booked" icon="try" />
                        </div>
                        <div>
                            <div class="vy-mk-megalabel">Connect &amp; learn</div>
                            <x-marketing.mega-row key="integrations" title="Integrations &amp; channels" desc="OTAs, payments and apps" icon="integrations" />
                            <x-marketing.nav-link key="faq" class="vy-mk-megasmall"><span>FAQ</span><span>Straight answers, including the awkward ones</span></x-marketing.nav-link>
                            <x-marketing.nav-link key="glossary" class="vy-mk-megasmall"><span>Glossary</span><span>ADR, RevPAR, folio and the rest</span></x-marketing.nav-link>
                            {{-- ⚠️ GATE 5 · was desc="Sixteen written guides" — asserted on EVERY page, and false: zero are
                                 published, and the doc-truth audit defers all sixteen. The row itself already
                                 renders as a marked non-link (the page is unrouted); the DESCRIPTION was still
                                 claiming the content exists. Replaced with something that asserts no count. --}}
                            <x-marketing.mega-row key="docs" title="Documentation" desc="Guides and reference — not published yet" icon="docs" />
                            <x-marketing.mega-row key="changelog" title="Changelog" desc="What shipped, every two weeks" icon="changelog" />
                            <x-marketing.mega-row key="security" title="Security &amp; GDPR" desc="EU-ready, data minimisation" icon="security" />
                        </div>
                        <div class="vy-mk-megafeature">
                            <div class="vy-mk-megalabel">Start here</div>
                            <div class="vy-mk-megafeature-title">Setting up your first property</div>
                            <div class="vy-mk-megafeature-desc">Units, types and the first import, in the order that means nothing has to be redone.</div>
                            <x-marketing.nav-link key="guide-setup" class="vy-mk-megafeature-cta">Read the guide <x-marketing.arrow /></x-marketing.nav-link>
                        </div>
                    </div>
                    <div class="vy-mk-megastrip">
                        <div class="vy-mk-megastrip-head">
                            <span>Popular guides</span>
                            <x-marketing.nav-link key="docs">All sixteen &rarr;</x-marketing.nav-link>
                        </div>
                        <div class="vy-mk-megaguides">
                            <x-marketing.nav-link key="guide-reservation" class="vy-mk-megaguide">Taking a reservation</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-checkin" class="vy-mk-megaguide">Check-in &amp; the folio</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-housekeeping" class="vy-mk-megaguide">Housekeeping board</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-rates" class="vy-mk-megaguide">Rate plans</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-statements" class="vy-mk-megaguide">Owner statements</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-payouts" class="vy-mk-megaguide">Payout runs</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-channels" class="vy-mk-megaguide">Connecting channels</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-arrival" class="vy-mk-megaguide">Online check-in</x-marketing.nav-link>
                            <x-marketing.nav-link key="guide-refunds" class="vy-mk-megaguide">Refunds &amp; changes</x-marketing.nav-link>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Solutions — By property type · Deciding · the honest footer strip --}}
            <div class="vy-mk-drop">
                <x-marketing.nav-link key="for-hotels" class="vy-mk-navlink">Solutions <x-marketing.caret /></x-marketing.nav-link>
                <div class="vy-mk-mega vy-mk-mega--solutions" role="group" aria-label="Solutions menu">
                    <div class="vy-mk-megagrid vy-mk-megagrid--2">
                        <div>
                            <div class="vy-mk-megalabel">By property type</div>
                            <x-marketing.mega-row key="for-hotels" title="Hotels" desc="Independent, 40 to 200 keys" icon="hotels" />
                            <x-marketing.mega-row key="for-villas" title="Villas &amp; vacation rentals" desc="Owner-owned, seasonal" icon="villas" />
                            <x-marketing.mega-row key="for-apartments" title="Serviced apartments" desc="Contactless, no front desk" icon="apartments" />
                        </div>
                        <div>
                            <div class="vy-mk-megalabel">Deciding</div>
                            {{-- ⚠️ GATE 3b: was title="Customer stories" desc="Three operators who switched" — on EVERY page.
                                 Both claim real reference customers, and the pages behind them are explicitly
                                 ILLUSTRATIVE SCENARIOS (ruling A). A nav label is a label for its content. --}}
                            <x-marketing.mega-row key="case-studies" title="Example scenarios" desc="Three shapes of operation, illustrated" icon="stories" />
                            {{-- ⚠️ THE DROP, EXECUTED (2026-08-22). Two rows stood here — "Compare the options" and
                                 "Vyrobel vs the alternatives". Gate 3 removed the four named COMPETITORS from their
                                 descriptions but left the ROWS, so every page on the site went on advertising two pages
                                 that were never going to be served: 12 unsourced competitor claims, none of them sourceable
                                 without the founder supplying evidence. The founder ruled DROP; this is that ruling
                                 executed, in the nav as well as the URL map — a ruling scoped to a page never reaches the
                                 chrome, and the chrome is what appears on all 19 published pages. --}}
                            <x-marketing.mega-row key="switching" title="Switching to Vyrobel" desc="What migrates, and the cutover" icon="switching" />
                        </div>
                    </div>
                    <div class="vy-mk-megastrip vy-mk-megastrip--cta">
                        <span>Not sure which fits? Tell us your portfolio and we&rsquo;ll say honestly where we&rsquo;d win.</span>
                        <x-marketing.nav-link key="contact" fragment="#demo" class="vy-mk-megacta">Book a demo <x-marketing.arrow /></x-marketing.nav-link>
                    </div>
                </div>
            </div>

            <x-marketing.nav-link key="resources" class="vy-mk-navlink">Resources</x-marketing.nav-link>
            <x-marketing.nav-link key="pricing" class="vy-mk-navlink">Pricing</x-marketing.nav-link>
            <x-marketing.nav-link key="how-it-works" class="vy-mk-navlink">How it works</x-marketing.nav-link>
            <x-marketing.nav-link key="about" class="vy-mk-navlink">About</x-marketing.nav-link>
            <x-marketing.nav-link key="contact" class="vy-mk-navlink">Contact</x-marketing.nav-link>
        </div>

        <div class="vy-mk-ctas">
            {{-- ⚠️ The design's language switcher sat here — deferred with D7 (English first). --}}
            <a href="{{ route('login') }}" class="vy-mk-login">Log in</a>
            <a href="{{ route('signup') }}" class="vy-mk-signup">Start free</a>
        </div>

        {{-- Mobile — a checkbox toggle (no JS, no framework); the label is the burger. --}}
        <input type="checkbox" id="vy-mk-menu" class="vy-mk-menu-box" aria-hidden="true">
        <label for="vy-mk-menu" class="vy-mk-burger" role="button" aria-label="Open menu" tabindex="0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"></path></svg>
        </label>
        <div class="vy-mk-mobile" aria-label="Mobile menu">
            {{-- ⚠️ THE DESIGN'S FIVE LABELLED GROUPS, ALL 21 LINKS. An earlier draft of this drawer carried
                 two unlabelled groups and 13 links, which left About, Contact, FAQ, Glossary, Resources and
                 Changelog UNREACHABLE on mobile — a real navigational hole, not a styling divergence.
                 Labels and row copy are the design's own ("Product", "Vs alternatives", "Documentation"…). --}}
            <div class="vy-mk-mgroup">
                <div class="vy-mk-mlabel">Product</div>
                <x-marketing.nav-link key="product" class="vy-mk-mrow">Product</x-marketing.nav-link>
                <x-marketing.nav-link key="features" class="vy-mk-mrow">Features</x-marketing.nav-link>
                <x-marketing.nav-link key="modules" class="vy-mk-mrow">Modules</x-marketing.nav-link>
                <x-marketing.nav-link key="how-it-works" class="vy-mk-mrow">How it works</x-marketing.nav-link>
            </div>
            <div class="vy-mk-mgroup">
                <div class="vy-mk-mlabel">Solutions</div>
                <x-marketing.nav-link key="for-hotels" class="vy-mk-mrow">For hotels</x-marketing.nav-link>
                <x-marketing.nav-link key="for-villas" class="vy-mk-mrow">For villas</x-marketing.nav-link>
                <x-marketing.nav-link key="for-apartments" class="vy-mk-mrow">For apartments</x-marketing.nav-link>
            </div>
            <div class="vy-mk-mgroup">
                <div class="vy-mk-mlabel">Deciding</div>
                <x-marketing.nav-link key="pricing" class="vy-mk-mrow">Pricing</x-marketing.nav-link>
                {{-- ⚠️ THE DROP: the drawer must match the desktop nav exactly. A reduced drawer once orphaned
                     six pages on mobile, which is why the row count is pinned; the pin moves 21 → 19 here. --}}
                <x-marketing.nav-link key="switching" class="vy-mk-mrow">Switching to Vyrobel</x-marketing.nav-link>
                <x-marketing.nav-link key="case-studies" class="vy-mk-mrow">Example scenarios</x-marketing.nav-link>
            </div>
            <div class="vy-mk-mgroup">
                <div class="vy-mk-mlabel">Learn</div>
                <x-marketing.nav-link key="docs" class="vy-mk-mrow">Documentation</x-marketing.nav-link>
                <x-marketing.nav-link key="guide-reservation" class="vy-mk-msub">Taking a reservation</x-marketing.nav-link>
                <x-marketing.nav-link key="guide-statements" class="vy-mk-msub">Owner statements</x-marketing.nav-link>
                <x-marketing.nav-link key="faq" class="vy-mk-mrow">FAQ</x-marketing.nav-link>
                <x-marketing.nav-link key="glossary" class="vy-mk-mrow">Glossary</x-marketing.nav-link>
                <x-marketing.nav-link key="resources" class="vy-mk-mrow">Resources</x-marketing.nav-link>
                <x-marketing.nav-link key="changelog" class="vy-mk-mrow">Changelog</x-marketing.nav-link>
            </div>
            <div class="vy-mk-mgroup">
                <div class="vy-mk-mlabel">Company</div>
                <x-marketing.nav-link key="about" class="vy-mk-mrow">About</x-marketing.nav-link>
                <x-marketing.nav-link key="contact" class="vy-mk-mrow">Contact</x-marketing.nav-link>
            </div>
            <div class="vy-mk-mobile-ctas">
                <a href="{{ route('login') }}" class="vy-mk-login">Log in</a>
                <a href="{{ route('signup') }}" class="vy-mk-signup">Start free</a>
            </div>
        </div>
    </nav>
</header>
