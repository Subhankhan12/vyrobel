{{-- MARKETING SITE FOOTER (front-website Gate 1) — the design's "Site Footer" partial, extracted ONCE
     (§N.8f). Structure faithful: the what's-new banner → brand column + "Operator notes, monthly" →
     Product / Solutions / Company / Account columns → the legal row.

     ⚠️ HONEST-DISABLED: the newsletter has NO backend (no subscriber store, no sender) — the input and
     button render the design's look but are disabled with an honest label, never a form that posts
     nowhere (#39). A real subscribe backend is a founder call for a later gate.

     ⚠️ ADDED FOR RULING D9 (a registered divergence — not in the design): the Account column carries the
     small "Booked a stay? Guest sign-in" link to the real /guest/login, so the guest realm keeps its own
     entry without touching auth. --}}
@php use App\Support\Marketing\MarketingUrls; @endphp
<footer class="vy-mk-footer">
    {{-- ⚠️ GATE 5 · THE CHROME SWEEP — THE "WHAT'S NEW" STRIP IS REMOVED, AND BOTH HALVES OF IT WERE FALSE.
         It read: "Multi-currency pricing and a documentation library — prices in EUR, GBP or USD, and
         sixteen written guides." ⚠️ `config/billing.php` has ONE `default_currency` ('EUR') and all four
         plan rows are EUR — there is no GBP or USD price. ⚠️ And zero guides are published; Gate 5's
         doc-truth audit defers all sixteen. It announced a release that did not happen, on EVERY page.
         ⚠️ KIND-2 (fabricated content) is REFUSED, not restyled — so the strip goes rather than gaining a
         softer sentence. Real release notes belong on the Changelog page, which is Gate 6's.
         ⚠️ This is the FOURTH time the chrome has carried a claim a page-scoped ruling could not reach
         (competitor names · "Three operators who switched" · "A real booking, no signup" · this).
         See docs/audits/FRONT-GATE5-DOC-TRUTH-AUDIT-2026-08-22.md §6. --}}

    <div class="vy-mk-footgrid">
        <div class="vy-mk-footbrand">
            <a href="{{ MarketingUrls::path('home') }}" class="vy-mk-logo">
                <span class="vy-mk-logo-mark" aria-hidden="true"><span>V</span></span>
                <span class="vy-mk-logo-name">Vyrobel</span>
            </a>
            <p>Property management software for hotels, serviced apartments and short-stay operators &mdash; with a commission-free direct booking engine, live operations, and an owner portal that finally tells the truth.</p>
            <div class="vy-mk-notes">
                <div class="vy-mk-notes-label">Operator notes, monthly</div>
                {{-- ⚠️ Engine-less → disabled + honest (#39). No form, no action, nothing pretends to submit. --}}
                <div class="vy-mk-notes-row">
                    <input type="email" placeholder="you@property.com" disabled aria-disabled="true" title="Coming soon">
                    <button type="button" disabled aria-disabled="true">Join</button>
                </div>
                <div class="vy-mk-notes-soon">Coming soon &mdash; sign-up isn&rsquo;t wired up yet.</div>
            </div>
        </div>

        <div class="vy-mk-footcol">
            <div class="vy-mk-footlabel">Product</div>
            <x-marketing.nav-link key="product">Platform overview</x-marketing.nav-link>
            <x-marketing.nav-link key="features">Features</x-marketing.nav-link>
            <x-marketing.nav-link key="modules">Modules</x-marketing.nav-link>
            <x-marketing.nav-link key="how-it-works">How it works</x-marketing.nav-link>
            <x-marketing.nav-link key="integrations">Integrations</x-marketing.nav-link>
            <x-marketing.nav-link key="pricing">Pricing</x-marketing.nav-link>
        </div>
        <div class="vy-mk-footcol">
            <div class="vy-mk-footlabel">Solutions</div>
            <x-marketing.nav-link key="for-hotels">For hotels</x-marketing.nav-link>
            <x-marketing.nav-link key="for-villas">For villas</x-marketing.nav-link>
            <x-marketing.nav-link key="for-apartments">For apartments</x-marketing.nav-link>
            <x-marketing.nav-link key="case-studies">Example scenarios</x-marketing.nav-link>
            <x-marketing.nav-link key="compare">Compare</x-marketing.nav-link>
            {{-- The design's sixth entry — the home page's ROI/commission calculator anchor. The calculator
                 PARTIAL is deferred (D1 figures / Gate 2+), but the nav item is preserved as a pending link
                 rather than silently dropped. --}}
            <x-marketing.nav-link key="home" fragment="#calculator">Commission calculator</x-marketing.nav-link>
        </div>
        <div class="vy-mk-footcol">
            <div class="vy-mk-footlabel">Company</div>
            <x-marketing.nav-link key="about">About us</x-marketing.nav-link>
            <x-marketing.nav-link key="alternatives">Vs alternatives</x-marketing.nav-link>
            <x-marketing.nav-link key="switching">Switching to Vyrobel</x-marketing.nav-link>
            <x-marketing.nav-link key="docs">Documentation</x-marketing.nav-link>
            <x-marketing.nav-link key="glossary">Glossary</x-marketing.nav-link>
            <x-marketing.nav-link key="faq">FAQ</x-marketing.nav-link>
            <x-marketing.nav-link key="changelog">Changelog</x-marketing.nav-link>
            <x-marketing.nav-link key="careers">Careers</x-marketing.nav-link>
            <x-marketing.nav-link key="resources">Resources</x-marketing.nav-link>
            <x-marketing.nav-link key="contact">Contact</x-marketing.nav-link>
            <x-marketing.nav-link key="contact" fragment="#demo">Book a demo</x-marketing.nav-link>
            <x-marketing.nav-link key="security">Security &amp; GDPR</x-marketing.nav-link>
        </div>
        <div class="vy-mk-footcol">
            <div class="vy-mk-footlabel">Account</div>
            <a href="{{ route('login') }}">Log in</a>
            <a href="{{ route('signup') }}">Start free</a>
            <a href="{{ route('password.request') }}">Reset password</a>
            {{-- ⚠️ The design labels these "(draft)". Ours are NOT drafts-in-waiting — they are simply not
                 written yet, so they carry the same pending treatment as every other unbuilt page rather
                 than a "(draft)" badge that would imply a document exists. --}}
            <x-marketing.nav-link key="privacy">Privacy</x-marketing.nav-link>
            <x-marketing.nav-link key="terms">Terms</x-marketing.nav-link>
            {{-- Ruling D9: the guest realm keeps its own separate entry — a registered addition. --}}
            <a href="{{ route('guest.login') }}" class="vy-mk-guestlink">Booked a stay? Guest sign-in</a>
            {{-- ⚠️ The design's "Cookie settings" button + its full consent dialog are NOT rendered — see
                 the divergence register: the site currently sets no analytics or marketing cookies, so a
                 consent dialog would be theatre, and a real one is a founder decision (D11). --}}
        </div>
    </div>

    {{-- ⚠️ THE COOKIE DISCLOSURE, ALWAYS REACHABLE (Gate 6, D11). The banner appears once; this is here on
         every page for ever, so a visitor can read what is set and change their answer without hunting for
         it. Same component, same disclosure, same form — one definition (§N.8f). --}}
    <div class="vy-mk-footnote" style="padding-top:18px;border-top:1px solid var(--vy-hairline);">
        <details>
            <summary style="cursor:pointer;font-size:12px;font-weight:600;color:var(--vy-faint);">Cookies &amp; your choice</summary>
            <div style="margin-top:14px;">
                <x-marketing.cookie-notice mode="panel" />
            </div>
        </details>
    </div>

    <div class="vy-mk-legal">
        <span>&copy; 2026 Vyrobel. Built for hospitality operators in the EU and beyond.</span>
        {{-- ⚠️ The design pairs this line with three social icons (LinkedIn / X / Email us) — all three
             pointing at the Contact page as prototype placeholders. They are NOT rendered: an icon that
             says "LinkedIn" implies a profile that does not exist (#39). They return the day real profile
             URLs exist — a founder decision (D12), registered rather than silently dropped. --}}
    </div>
</footer>
