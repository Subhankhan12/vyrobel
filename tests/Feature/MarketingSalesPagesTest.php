<?php

use App\Support\Marketing\MarketingContent;
use App\Support\Marketing\MarketingUrls;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// FRONT-WEBSITE GATE 3 · the four Sales pages that make NO third-party claims.
//
// ⚠️ `alternatives` and `compare` are NOT here because they are NOT BUILT: their subject is a set of
// unsourced claims about named competitors, which sit entirely outside the content gate's reach. Audited
// and surfaced instead of shipped — docs/audits/FRONT-GATE3-HONESTY-AUDITS-2026-08-21.md. The case studies
// are absent for the same class of reason: their subject is D1's three invented, unconsented customers.
//
// ⚠️ Gate 2's lessons carried: a positive control must be STRUCTURALLY CAPABLE of the failure it claims to
// demonstrate, route-NAME collisions are checked, and counts are pinned rather than mere presence.

const MK_SALES = ['/switching' => 'switching', '/for-hotels' => 'for-hotels',
    '/for-apartments' => 'for-apartments', '/for-villas' => 'for-villas'];

it('serves the four claim-free Sales pages', function () {
    foreach (MK_SALES as $path => $view) {
        $this->get($path)->assertOk();
    }
    // ⚠️ COUNT-PINNED: six Core + four Sales = ten. A stray registration is a public surface nobody
    // reviewed; a missing one is a silently dropped page. This pin is what caught the `marketing.*`
    // name collision in Gate 2.
    $named = collect(app('router')->getRoutes()->getRoutesByName())->keys()
        ->filter(fn (string $n): bool => str_starts_with($n, 'marketing.page.'));
    // ⚠️ 14 since Gate 3b: the six Core + four Sales + the four ILLUSTRATIVE SCENARIOS, which ruling (A)
    // reframed and routed under /example-scenarios. The pin moves with a ruling; it never moves silently.
    // ⚠️ 15 since Gate 4: the six Core + four Sales + four scenarios + the try-booking SANDBOX.
    // ⚠️ 19 since Gate 7: + about, security, integrations and contact. ⚠️ `marketing.contact.store` — the
    // form's POST — is deliberately NOT in this count: it sits outside the `marketing.page.*` group because
    // that prefix is the contract MarketingUrls::pending() reads to decide whether a PAGE exists, and a
    // write is not a page. That separation is what this pin caught a name collision over in Gate 2.
    Assert::assertSame(19, $named->count(), 'the marketing route set changed: '.$named->implode(', '));
});

it('⚠️ leaves the audited pages UNROUTED — an empty or unsourced page must not be publicly reachable', function () {
    // ⚠️ UPDATED BY GATE 3b. The scenario pages were held in Gate 3 because their subject was three
    // invented customers; ruling (A) reframed them as labelled illustrative scenarios and they are now
    // routed — under /example-scenarios, NOT /case-studies, because the URL was itself part of the claim.
    // The two COMPETITOR pages remain held: their claims are still unsourced.
    foreach (['alternatives', 'compare'] as $held) {
        Assert::assertTrue(MarketingUrls::pending($held), "[{$held}] became routable while its claims are unsourced");
    }
    foreach (['/alternatives', '/compare'] as $path) {
        $this->get($path)->assertNotFound();
    }

    // ⚠️ AND THE OLD, CLAIM-BEARING URLs MUST NOT RESOLVE — a live /case-studies/… would re-assert the
    // very thing the rewrite removed, whatever the page now says.
    foreach (['/case-studies', '/case-studies/cyclades', '/case-studies/lisbon', '/case-studies/rotterdam'] as $oldClaimUrl) {
        $this->get($oldClaimUrl)->assertNotFound();
    }
    foreach (['/example-scenarios', '/example-scenarios/villa-portfolio'] as $path) {
        $this->get($path)->assertOk();
    }
});

it('⚠️ never shadows the sealed money-carrying storefront', function () {
    $match = fn (string $uri): ?string => app('router')->getRoutes()
        ->match(Request::create($uri, 'GET'))->getName();

    Assert::assertSame('booking.browse', $match('/book/seaside-demo'));
    Assert::assertSame('booking.checkout', $match('/book/seaside-demo/checkout'));
    Assert::assertSame('storefront.landing', $match('/stay/vyrobel-demo'));
});

it('⚠️ PROVES the storefront control is STRUCTURALLY CAPABLE of the failure it demonstrates', function () {
    // ⚠️ GATE 2's LESSON, CARRIED. Its first control planted a ONE-segment `/{shadow}`, which cannot match
    // a two-segment URI at all — it "passed" while proving nothing. The threat's real shape is E1's
    // `/{locale}/{page}`. Two assertions, in this order:
    //   1. the control CAN produce the failure (an EARLY two-segment wildcard really does swallow /book/…)
    //   2. our actual registration order does NOT
    $fresh = new Router(app('events'), app());
    $fresh->get('/{locale}/{page}', fn () => 'shadow')->name('early.shadow');
    $fresh->get('/book/{property}', fn () => 'browse')->name('booking.browse');
    Assert::assertSame('early.shadow', $fresh->getRoutes()->match(Request::create('/book/seaside-demo', 'GET'))->getName(),
        'the control cannot produce the failure it exists to demonstrate — it proves nothing');

    // …and the real table, where marketing is registered AFTER the storefront, is safe.
    Assert::assertSame('booking.browse', app('router')->getRoutes()
        ->match(Request::create('/book/seaside-demo', 'GET'))->getName());
});

it('registers no wildcard among the marketing routes', function () {
    foreach (app('router')->getRoutes() as $route) {
        if (! str_starts_with((string) $route->getName(), 'marketing.page.')) {
            continue;
        }
        Assert::assertStringNotContainsString('{', $route->uri(), 'a marketing route carries a parameter — every slug must be whitelisted');
    }
});

// ══ ⚠️ BOTH SHAPES ═════════════════════════════════════════════════════════════════════════════════
it('⚠️ renders every Sales page coherently in BOTH shapes', function () {
    foreach (MK_SALES as $path => $view) {
        $local = mkPage($view);
        $prod = mkProdPage($view);

        // ⚠️ These four pages consume NO ledger data, so the two shapes must be IDENTICAL. Asserting that
        // is stronger than asserting each is "coherent": if a future edit wires ledger content into one of
        // them, this test reds immediately rather than at launch.
        Assert::assertSame(strlen($local), strlen($prod), "[{$view}] changed shape under the production filter — it now consumes ledger content and needs a both-shapes review");

        Assert::assertGreaterThan(20000, strlen($prod), "[{$view}] collapsed in production");
        Assert::assertStringNotContainsString('data-fig=', $prod);
        Assert::assertStringNotContainsString('NaN', $prod);
        Assert::assertStringNotContainsString('data-illustrative-badge', $prod);
    }
});

// ══ THE FOUNDATION RULES STILL HOLD ════════════════════════════════════════════════════════════════
it('keeps the Sales pages free of CDN references, refused tones and dc-runtime remnants', function () {
    foreach (MK_SALES as $path => $view) {
        $html = mkPage($view);
        foreach (['fonts.googleapis.com', 'fonts.gstatic.com', 'unpkg.com'] as $host) {
            Assert::assertStringNotContainsStringIgnoringCase($host, $html, "[{$view}] leaked {$host}");
        }
        Assert::assertStringContainsString('/fonts/fonts.css', $html);
        Assert::assertStringContainsString('var(--vy-ink)', $html);
        foreach (['#4A403A', '#A8380B', '#B4400F', '#FFF7F0', '#9C9089', '#B7ADA4', '#8A8079', '#A69A92'] as $refused) {
            Assert::assertStringNotContainsStringIgnoringCase($refused, $html, "[{$view}] copied the refused tone {$refused}");
        }
        foreach (['dc-import', '<x-dc', 'sc-camel', 'style-hover=', 'onMouseEnter='] as $remnant) {
            Assert::assertStringNotContainsString($remnant, $html, "[{$view}] shipped the dc-runtime remnant {$remnant}");
        }
    }
});

it('PROVES the CDN sweep can fail (the positive control)', function () {
    $poisoned = mkPage('switching').'<link href="https://fonts.googleapis.com/css2?family=Sora" rel="stylesheet">';
    $failed = false;
    try {
        Assert::assertStringNotContainsStringIgnoringCase('fonts.googleapis.com', $poisoned);
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue();
});

it('names no competitor anywhere in the built pages', function () {
    // ⚠️ The whole reason Alternatives/Compare were held. If a competitor name ever appears in a BUILT
    // page, it arrived without going through the audit — and no CI gate but this one can see it.
    foreach (array_merge(array_values(MK_SALES), ['home', 'product', 'features', 'modules', 'how-it-works', 'pricing']) as $view) {
        $html = mkPage($view);
        foreach (['Mews', 'Cloudbeds', 'Guesty', 'Hostaway'] as $competitor) {
            Assert::assertStringNotContainsString($competitor, $html, "[{$view}] names the competitor {$competitor} — it must go through the Gate-3 claim audit first");
        }
    }
});

// ══ ⚠️ D13 — no ledger-flagged person may appear in page MARKUP, on any page ════════════════════════
it('⚠️ keeps every ledger-flagged name out of page markup (D13 — the gate cannot see these)', function () {
    // ⚠️ D13 named TWO instances, found by inspection. A sweep found SIX, across five pages — including
    // three of the four pages built this gate. This test is the sweep, made permanent: the honesty gate
    // reads the ledger and can never see a name typed into markup, so this is the only thing that can.
    $ledger = collect(MarketingContent::customers(filtered: false))
        ->merge(MarketingContent::testimonials(filtered: false))
        ->pluck('name')->all();
    Assert::assertGreaterThanOrEqual(9, count($ledger), 'the ledger shrank — this sweep would silently check less');

    foreach (array_merge(array_values(MK_SALES), ['home', 'product', 'features', 'modules', 'how-it-works', 'pricing']) as $view) {
        $html = mkProdPage($view);   // ⚠️ the PRODUCTION shape: this is about what publishes
        foreach ($ledger as $name) {
            Assert::assertStringNotContainsString($name, $html,
                "[{$view}] carries the ledger-flagged name \"{$name}\" in its markup, where no gate can see it (D13)");
        }
    }
});
