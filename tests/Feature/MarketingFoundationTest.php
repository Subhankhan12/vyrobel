<?php

use App\Support\Marketing\MarketingUrls;
use Illuminate\Http\Request;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// FRONT-WEBSITE GATE 1 · THE FOUNDATION RENDER PROOF — the marketing shell/nav/footer/SEO component exist
// and RENDER, without any public route (D2's `/` swap lands with the home page in Gate 2).
//
// ⚠️ §N.29 THROUGHOUT: every assertion here is about the RENDERED OUTPUT (a resolved token value, an
// emitted tag, an absent CDN host) — never that a file exists or was included. The CDN-absence check
// carries a POSITIVE CONTROL proving the detector can fail.

/** Render the full marketing shell around a probe body. */
function mkShell(string $body = 'FOUNDATION-PROBE-BODY'): string
{
    return (string) test()->blade(
        '<x-marketing.shell title="Probe title" description="Probe description" canonical="/probe">'.$body.'</x-marketing.shell>'
    );
}

/** ⚠️ The E9 detector — no third-party CDN may survive into rendered marketing output. */
function mkAssertNoCdn(string $html): void
{
    foreach (['fonts.googleapis.com', 'fonts.gstatic.com', 'unpkg.com'] as $host) {
        Assert::assertStringNotContainsStringIgnoringCase($host, $html, "third-party CDN host {$host} survived into rendered marketing output (E9/F6-6)");
    }
}

// ══ THE SHELL RENDERS — nav, body slot, footer, all in one document ════════════════════════════════
it('renders the marketing shell with nav, content slot and footer', function () {
    $html = mkShell();

    Assert::assertStringContainsString('FOUNDATION-PROBE-BODY', $html);
    Assert::assertStringContainsString('aria-label="Main"', $html);              // the nav landmark
    Assert::assertStringContainsString('Start free', $html);                     // the CTA pair
    Assert::assertStringContainsString('Log in', $html);
    Assert::assertStringContainsString('Operator notes, monthly', $html);        // the footer brand column
    Assert::assertStringContainsString('&copy; 2026 Vyrobel', $html);            // the legal row (entity as emitted)
});

// ══ THE TOKENS RESOLVE — the palette is BOUND, and the refused tones are ABSENT (D6) ═══════════════
it('resolves the canonical --vy tokens and contains none of the refused design tones', function () {
    $html = mkShell();

    // ⚠️ Assert the RENDERED custom-property declarations (the tokens component inlined its :root), not
    // that a file was included (§N.29 — the LOCATION form).
    Assert::assertStringContainsString('--vy-ink: rgb(43, 35, 32)', $html);
    Assert::assertStringContainsString('--vy-accent: rgb(194, 65, 12)', $html);
    Assert::assertStringContainsString('--vy-grad:', $html);
    Assert::assertStringContainsString('var(--vy-ink)', $html);                  // …and the shell actually BINDS them
    Assert::assertStringContainsString('var(--vy-muted)', $html);

    // ⚠️ D6's teeth: the design's divergent tones must NOT appear — not as copied hex anywhere.
    foreach (['#4A403A', '#A8380B', '#B4400F', '#FFF7F0'] as $refused) {
        Assert::assertStringNotContainsStringIgnoringCase($refused, $html, "refused design tone {$refused} was copied into the marketing output (D6/E4)");
    }
});

// ══ SELF-HOSTED FONTS + NO CDN — with the positive control ═════════════════════════════════════════
it('references only the self-hosted fonts and no third-party CDN', function () {
    $html = mkShell();

    Assert::assertStringContainsString('/fonts/fonts.css', $html);               // F6-6: the self-hosted path
    mkAssertNoCdn($html);
});

it('PROVES the CDN detector can fail (the §N.29 positive control)', function () {
    // ⚠️ A detector never shown to detect proves nothing. Feed it output that DOES carry the CDN link —
    // the design's own helmet shape — and require the assertion to throw.
    $poisoned = mkShell().'<link href="https://fonts.googleapis.com/css2?family=Sora" rel="stylesheet">';

    $failed = false;
    try {
        mkAssertNoCdn($poisoned);
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue();
});

// ══ THE SEO COMPONENT EMITS ════════════════════════════════════════════════════════════════════════
it('emits the SEO head: title, description, canonical, OG, Twitter — and the robots default', function () {
    $html = (string) test()->blade(
        '<x-marketing.seo title="Vyrobel — probe" description="Probe meta" canonical="/probe-page" />'
    );

    Assert::assertStringContainsString('<title>Vyrobel — probe</title>', $html);
    Assert::assertStringContainsString('<meta name="description" content="Probe meta">', $html);
    Assert::assertStringContainsString('<link rel="canonical" href="'.url('/probe-page').'">', $html);   // ⚠️ APP_URL-derived — the go-live requirement
    Assert::assertStringContainsString('<meta property="og:title" content="Vyrobel — probe">', $html);
    Assert::assertStringContainsString('<meta property="og:url" content="'.url('/probe-page').'">', $html);
    Assert::assertStringContainsString('<meta name="twitter:title" content="Vyrobel — probe">', $html);
    Assert::assertStringContainsString('content="index, follow"', $html);
    // No og:image was given → none may be emitted (a default pointing at a missing asset would 404).
    Assert::assertStringNotContainsString('og:image', $html);
});

it('emits hreflang alternates through the D7-deferred slot when given — and nothing when not', function () {
    $with = (string) test()->blade(
        '<x-marketing.seo title="T" description="D" canonical="/p" :alternates="[\'en\' => \'https://vyrobel.com/p\', \'nl\' => \'https://vyrobel.com/nl/p\']" />'
    );
    Assert::assertStringContainsString('hreflang="en"', $with);
    Assert::assertStringContainsString('hreflang="nl"', $with);

    $without = (string) test()->blade('<x-marketing.seo title="T" description="D" canonical="/p" />');
    Assert::assertStringNotContainsString('hreflang', $without);
});

// ══ PENDING LINKS — a not-yet-registered page renders as a MARKED non-link, never a silent 404 ═════
it('renders not-yet-registered marketing pages as marked non-links and real routes as links', function () {
    $html = mkShell();

    // No marketing.* routes exist in Gate 1 → every nav page item is pending, and NO href may point at a
    // marketing path that would 404.
    Assert::assertTrue(MarketingUrls::pending('product'));
    Assert::assertStringContainsString('data-vy-pending="product"', $html);
    Assert::assertStringNotContainsString('href="/product"', $html);

    // …while the REAL app routes render as real links (D9 Tier 1 — no auth change).
    Assert::assertStringContainsString('href="'.route('login').'"', $html);
    Assert::assertStringContainsString('href="'.route('signup').'"', $html);
    Assert::assertStringContainsString('href="'.route('password.request').'"', $html);
    Assert::assertStringContainsString('href="'.route('guest.login').'"', $html);   // the D9 guest entry
});

// ══ SCOPE GUARDS — nothing public changed ══════════════════════════════════════════════════════════
it('leaves `/` serving the welcome view — the D2 swap has NOT happened yet', function () {
    $response = $this->get('/');
    $response->assertOk();
    Assert::assertStringNotContainsString('vy-mk-nav', $response->getContent());   // no marketing chrome on / yet
});

it('still resolves /book/{property} to booking.browse — the standing E1 baseline', function () {
    // ⚠️ A ROUTE-TABLE fact (§N.29): the storefront prefix is matched by the storefront, and this is the
    // baseline assertion the locale route (deferred with D7) will be tested against when it lands.
    $match = app('router')->getRoutes()->match(Request::create('/book/seaside-demo', 'GET'));
    Assert::assertSame('booking.browse', $match->getName());
});

// ══ FIDELITY GUARDS — added after an adversarial fidelity review found a real navigational hole ═════
it('reaches every design mobile-drawer destination — the drawer is not a reduced subset', function () {
    $html = mkShell();

    // ⚠️ SCOPE THE READ TO THE DRAWER (§N.29). A page-wide needle is VACUOUS here — `contact` also appears
    // in the desktop nav and the footer, so deleting it from the drawer left a page-wide assertion GREEN.
    // Caught by mutation, not by review; the fix is to read the drawer REGION and nothing else.
    $start = strpos($html, 'class="vy-mk-mobile"');
    Assert::assertNotFalse($start, 'the mobile drawer did not render at all');
    $drawer = substr($html, $start, strpos($html, '</nav>', $start) - $start);

    // ⚠️ THE DEFECT THIS PINS: an earlier drawer carried 13 links in 2 unlabelled groups, leaving About,
    // Contact, FAQ, Glossary, Resources and Changelog unreachable on mobile entirely. The design has five
    // labelled groups and 21 links; every one must be present IN THE DRAWER.
    foreach (['Product', 'Solutions', 'Deciding', 'Learn', 'Company'] as $label) {
        Assert::assertStringContainsString('>'.$label.'</div>', $drawer, "the mobile drawer lost the \"{$label}\" group label");
    }
    foreach (['product', 'features', 'modules', 'how-it-works', 'for-hotels', 'for-villas', 'for-apartments',
        'pricing', 'alternatives', 'compare', 'switching', 'case-studies', 'docs', 'guide-reservation',
        'guide-statements', 'faq', 'glossary', 'resources', 'changelog', 'about', 'contact'] as $key) {
        Assert::assertStringContainsString('data-vy-pending="'.$key.'"', $drawer, "\"{$key}\" is unreachable ON MOBILE — it is missing from the drawer");
    }
    // …and the drawer really carries all 21 of the design's rows, not a subset that happens to name them.
    Assert::assertSame(21, substr_count($drawer, 'data-vy-pending='), 'the mobile drawer no longer carries the design\'s 21 links');
});

it('keeps the footer columns as the design groups them', function () {
    $html = mkShell();

    // Company carries the long list (the design's grouping), Solutions ends with the calculator anchor.
    Assert::assertStringContainsString('Commission calculator', $html);
    Assert::assertStringContainsString('Careers', $html);
    Assert::assertStringContainsString('Book a demo', $html);
    Assert::assertStringContainsString('Security &amp; GDPR', $html);

    // ⚠️ Nothing implies a capability we lack (#39): no cookie-consent control, no social profiles.
    Assert::assertStringNotContainsStringIgnoringCase('Cookie settings', $html);
    Assert::assertStringNotContainsStringIgnoringCase('linkedin', $html);
});
