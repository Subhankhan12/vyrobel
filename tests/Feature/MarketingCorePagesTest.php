<?php

use App\Support\Marketing\MarketingContent;
use App\Support\Marketing\MarketingUrls;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// FRONT-WEBSITE GATE 2 · THE CORE PAGES, PROVEN IN BOTH SHAPES.
//
// ⚠️ THE LAUNCH-DAY TRAP THIS FILE EXISTS FOR: D1's gate FILTERS every unreal figure and unconsented
// person in production, so each page has TWO shapes — local (illustrative content present) and production
// (filtered). A page that looks whole in dev and has holes in prod would ship as a defect nobody saw.
// Every page is therefore rendered BOTH ways and the filtered shape asserted COHERENT.
//
// ⚠️ Gate 1's three lessons are applied throughout: assert COUNTS not mere presence (a reduced subset
// orphans pages silently), scope each assertion to the REGION it guards (a page-wide needle for a
// region-specific property is vacuous), and never treat an empty result as a verdict without proving the
// instrument can produce a positive.

const MK_PAGES = ['/' => 'home', '/product' => 'product', '/features' => 'features',
    '/modules' => 'modules', '/how-it-works' => 'how-it-works', '/pricing' => 'pricing'];

// ⚠️ mkPage() / mkProdPage() live in tests/Support/marketing_helpers.php — ONE definition shared by
// every marketing gate suite (§N.8f), rather than a copy per file free to drift.

// ══ ROUTES — all six resolve, and NOTHING shadows the sealed money path ════════════════════════════
it('serves all six Core pages', function () {
    foreach (MK_PAGES as $path => $view) {
        $this->get($path)->assertOk();
    }
    // ⚠️ COUNT-PINNED (Gate 1 lesson b): exactly six marketing page routes exist — no more (a stray
    // registration is a surface nobody reviewed), no fewer (a silently dropped page).
    // ⚠️ `marketing.page.*`, not `marketing.*` — the shorter namespace already belongs to the guest
    // EMAIL-marketing unsubscribe routes, and a count-pin is what surfaced that collision.
    //
    // ⚠️ THE TOTAL is pinned ONCE, in MarketingSalesPagesTest — not here as well. This file pinned 6 until
    // Gate 3 registered four more, and two files each asserting "the total" is a second definition of one
    // fact, free to drift (§N.8f). This suite owns only its own six.
    $named = collect(app('router')->getRoutes()->getRoutesByName())
        ->keys()->filter(fn (string $n): bool => str_starts_with($n, 'marketing.page.'));
    foreach (MK_PAGES as $path => $key) {
        Assert::assertTrue($named->contains('marketing.page.'.$key), "the Core route [{$key}] disappeared");
    }
});

it('⚠️ never shadows the sealed money-carrying storefront', function () {
    $match = fn (string $uri): ?string => app('router')->getRoutes()
        ->match(Request::create($uri, 'GET'))->getName();

    Assert::assertSame('booking.browse', $match('/book/seaside-demo'));
    Assert::assertSame('booking.checkout', $match('/book/seaside-demo/checkout'));
    Assert::assertSame('booking.search', $match('/book/seaside-demo/search'));
    Assert::assertSame('storefront.landing', $match('/stay/vyrobel-demo'));
    Assert::assertSame('storefront.search', $match('/stay/vyrobel-demo/search'));
});

it('⚠️ PROVES the storefront guard can fail — a shadowing route is DETECTED (the positive control)', function () {
    // ⚠️ A route-resolution test never seen to fail is exactly Gate 1's vacuous-guard shape. So plant the
    // thing the guard exists to catch — an unconstrained top-level wildcard, registered FIRST, which is
    // precisely what an unguarded /{locale}/... would be (risk E1) — and require the check to break.
    app('router')->get('/{shadow}/{rest?}', fn () => 'shadow')->name('mk.shadow.probe');
    $routes = app('router')->getRoutes();
    $routes->refreshNameLookups();

    $shadowed = $routes->match(Request::create('/book/seaside-demo', 'GET'))->getName();

    // ⚠️ Laravel matches in registration order, so a LATE wildcard cannot win — which is itself the
    // property that keeps marketing safe. Assert the mechanism directly rather than assuming it: the
    // storefront must still win, and the probe route must genuinely exist (a no-op probe proves nothing).
    Assert::assertTrue($routes->hasNamedRoute('mk.shadow.probe'), 'the shadow probe was never registered — this control proves nothing');
    Assert::assertSame('booking.browse', $shadowed, 'a LATE wildcard must never win — registration order is the protection');

    // …and the reverse, which is the real danger: a wildcard registered BEFORE the storefront DOES win.
    // Proven on a throwaway router so the app's own table is never mutated.
    $fresh = new Router(app('events'), app());
    $fresh->get('/{locale}/{page}', fn () => 'shadow')->name('early.shadow');
    $fresh->get('/book/{property}', fn () => 'browse')->name('booking.browse');
    Assert::assertSame('early.shadow', $fresh->getRoutes()->match(Request::create('/book/seaside-demo', 'GET'))->getName(),
        'the control itself is broken: an EARLY wildcard should shadow the storefront');
});

it('registers no wildcard and no fallback of its own', function () {
    foreach (app('router')->getRoutes() as $route) {
        $name = (string) $route->getName();
        if (! str_starts_with($name, 'marketing.page.')) {
            continue;
        }
        Assert::assertStringNotContainsString('{', $route->uri(), "marketing route [{$name}] carries a parameter — every slug must be whitelisted");
    }
    Assert::assertFalse(collect(app('router')->getRoutes()->getRoutes())
        ->contains(fn ($r): bool => $r->isFallback ?? false), 'a Route::fallback exists — it would swallow unmatched storefront URLs');
});

it('uses the slugs from the design’s own sitemap, not filename guesses', function () {
    // The design's sitemap is the authority; a guessed slug breaks SEO before it starts.
    foreach (MK_PAGES as $path => $key) {
        Assert::assertSame($path, MarketingUrls::path($key));
        Assert::assertFalse(MarketingUrls::pending($key), "[{$key}] should now be a real link, not pending");
    }
    // …and the pages NOT built this gate are still pending, so their links stay marked.
    // ⚠️ `try-booking` left this list in Gate 4, which rebuilt it as an honest sandbox (ruling D3 = (b)).
    // The pin moves with a ruling, never silently.
    // ⚠️ Gate 7 built `contact` and `about` (plus `security` and `integrations`), so they leave this list —
    // and `careers` STAYS, because its refusal is a ruling, not a backlog item: the page's subject is a
    // company with staff, offices and a hiring history that do not exist.
    // ⚠️ `privacy` and `terms` left this list on 2026-09-23 — see MarketingCompanyPagesTest for the
    //    ruling that moved them. The pin moves with a ruling, never silently.
    foreach (['docs', 'careers', 'changelog'] as $unbuilt) {
        Assert::assertTrue(MarketingUrls::pending($unbuilt), "[{$unbuilt}] must remain pending until its gate builds it");
    }
});

// ══ ⚠️ BOTH SHAPES — the launch-day trap ═══════════════════════════════════════════════════════════
it('⚠️ renders every Core page COHERENTLY in the production (filtered) shape', function () {
    // Today the ledger filters to nothing, so this is the real launch shape.
    Assert::assertCount(0, MarketingContent::figures(filtered: true));
    Assert::assertCount(0, MarketingContent::testimonials(filtered: true));

    foreach (MK_PAGES as $path => $view) {
        $prod = mkProdPage($view);

        Assert::assertGreaterThan(20000, strlen($prod), "[{$view}] collapsed in the production shape");

        // ⚠️ NO DANGLING FRAME: a section heading whose content was filtered away must not survive.
        Assert::assertStringNotContainsString('Vyrobel by the numbers', $prod, "[{$view}] kept the stats heading with no figures under it");
        Assert::assertStringNotContainsString('What operators tell us', $prod, "[{$view}] kept the testimonials heading with no quotes under it");
        Assert::assertStringNotContainsString('data-illustrative-badge', $prod, "[{$view}] kept the ILLUSTRATIVE badge in production");
        Assert::assertStringNotContainsString('data-custnote', $prod, "[{$view}] kept the illustrative-testimonials note in production");

        // ⚠️ NO ZERO/NaN FIGURE left behind by a filtered value.
        Assert::assertStringNotContainsString('data-fig=', $prod, "[{$view}] left a figure slot with nothing to put in it");
        Assert::assertStringNotContainsString('NaN', $prod);

        // ⚠️ NO UNCONSENTED TESTIMONIAL AUTHOR — the legal exposure D1 exists for.
        foreach (['Elena Kastellis', 'Joris van Dijk', 'Sofia Neri', 'Thomas Brandt'] as $person) {
            Assert::assertStringNotContainsString($person, $prod, "[{$view}] published the unconsented testimonial author {$person}");
        }
        // …and no quote can survive detached from the person it was attributed to.
        Assert::assertStringNotContainsString('We went from three tools and a shared spreadsheet', $prod, "[{$view}] kept an orphaned testimonial quote");
    }
});

it('renders the illustrative shape locally, with the design’s own honesty labels', function () {
    $home = mkPage('home');

    // The four figures render, each with the ledger's own label…
    foreach (MarketingContent::figures(filtered: false) as $figure) {
        Assert::assertStringContainsString($figure['label'], $home);
    }
    // ⚠️ COUNT-PINNED, REGION-SCOPED (Gate 1 lessons b + c): exactly the ledger's figures, inside the band.
    // ⚠️ ANCHORED ON THE ELEMENT, NOT THE BARE STRING — §N.27.5, hit for the fourth time in this project.
    // This read `strpos($home, 'data-stats')`, which was unambiguous until Gate 7 restored the design's own
    // media queries into the shell: the stylesheet in the <head> now contains `[data-stats] { … }`, which
    // matches FIRST, so "the band" became a slice of the document head containing zero figures. ⚠️ The guard
    // was right and its needle had become ambiguous — a page's own stylesheet is part of the page.
    $start = strpos($home, '<div data-stats');
    Assert::assertNotFalse($start, 'the stats band did not render locally');
    $band = substr($home, $start, strpos($home, '</section>', $start) - $start);
    Assert::assertSame(count(MarketingContent::figures(filtered: false)), substr_count($band, 'data-fig='));

    // …and the design's OWN honesty affordances are reproduced, driven by the real flags.
    Assert::assertStringContainsString('data-illustrative-badge', $home);
    Assert::assertStringContainsString('Illustrative of the conversations we have during onboarding', $home);

    // All six testimonial people, each beside their own quote — count-pinned.
    $tStart = strpos($home, 'vy-tst-h');
    $tBlock = substr($home, $tStart, strpos($home, '</section>', $tStart) - $tStart);
    Assert::assertSame(6, substr_count($tBlock, '<blockquote'));
    Assert::assertSame(6, substr_count($tBlock, '<figcaption'));
    foreach (MarketingContent::testimonials(filtered: false) as $person) {
        Assert::assertStringContainsString($person['name'], $tBlock);
    }
});

it('⚠️ takes the figure VALUES from the ledger, never from the design’s divergent page script', function () {
    // The design's own script and its ledger disagree (63%/11h vs 62/9). Gate 0 made the ledger the ONE
    // source; this pins that the page follows it, so the drift can never re-enter through the markup.
    $home = mkPage('home');
    // ⚠️ The element, not the bare attribute name — see the note in the test above.
    $start = strpos($home, '<div data-stats');
    $band = substr($home, $start, strpos($home, '</section>', $start) - $start);

    foreach (MarketingContent::figures(filtered: false) as $key => $figure) {
        Assert::assertStringContainsString('data-fig="'.$key.'">'.$figure['value'].'<', $band,
            "the stats band shows a value for [{$key}] that is not the ledger's");
    }
    Assert::assertStringNotContainsString('data-spark', $band, 'the sparkline series is illustrative data with no home in the guarded ledger');
});

// ══ THE FOUNDATION RULES STILL HOLD ON REAL PAGES ══════════════════════════════════════════════════
it('keeps every page free of third-party CDN references', function () {
    foreach (MK_PAGES as $path => $view) {
        $html = mkPage($view);
        foreach (['fonts.googleapis.com', 'fonts.gstatic.com', 'unpkg.com'] as $host) {
            Assert::assertStringNotContainsStringIgnoringCase($host, $html, "[{$view}] leaked the CDN host {$host}");
        }
        Assert::assertStringContainsString('/fonts/fonts.css', $html);
    }
});

it('PROVES the CDN sweep can fail (the positive control)', function () {
    $poisoned = mkPage('product').'<script src="https://unpkg.com/react@18.3.1/umd/react.production.min.js"></script>';
    $failed = false;
    try {
        Assert::assertStringNotContainsStringIgnoringCase('unpkg.com', $poisoned);
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue();
});

it('binds the palette and never copies the design’s refused tones', function () {
    foreach (MK_PAGES as $path => $view) {
        $html = mkPage($view);
        Assert::assertStringContainsString('--vy-ink: rgb(43, 35, 32)', $html);
        Assert::assertStringContainsString('var(--vy-ink)', $html);
        foreach (['#4A403A', '#A8380B', '#B4400F', '#FFF7F0'] as $refused) {
            Assert::assertStringNotContainsStringIgnoringCase($refused, $html, "[{$view}] copied the refused design tone {$refused} (D6/E4)");
        }
        // ⚠️ THE PRE-REMEDIATION PROTOTYPE GREYS — all four fail AA and were removed from this codebase in
        // the accessibility pass. The certified RoleCoverageContrastTest sweeps two of them and CAUGHT the
        // first generated Home page; the other two (#8A8079 ×3, #A69A92 ×5) it does not sweep, and they
        // were present too. Pinned here for all four, so a regenerated page cannot reintroduce any of them.
        foreach (['#9C9089', '#B7ADA4', '#8A8079', '#A69A92'] as $preAA) {
            Assert::assertStringNotContainsStringIgnoringCase($preAA, $html, "[{$view}] reintroduced the pre-AA prototype grey {$preAA}");
        }
        Assert::assertStringNotContainsString('Sora, sans-serif', $html, "[{$view}] hard-codes the font stack instead of binding --vy-display");
    }
});

it('carries no dc-runtime remnant into rendered output', function () {
    foreach (MK_PAGES as $path => $view) {
        $html = mkPage($view);
        foreach (['dc-import', '<x-dc', 'sc-camel', 'style-hover=', 'onMouseEnter=', 'DCLogic'] as $remnant) {
            Assert::assertStringNotContainsString($remnant, $html, "[{$view}] shipped the dc-runtime remnant {$remnant}");
        }
        // ⚠️ The design's handlebars must render as TEXT if any survived — never be evaluated by Blade.
        Assert::assertStringNotContainsString('{{ open', $html);
    }
});

// ══ IMAGE SLOTS — intentional, never a lie, never a stock photo (D8 open) ══════════════════════════
it('renders the image placeholders as intentional, in both shapes', function () {
    foreach (['home' => 6] as $view => $expected) {
        foreach ([mkPage($view), mkProdPage($view)] as $html) {
            Assert::assertSame($expected, substr_count($html, 'vy-mk-slot"'), "[{$view}] image-slot count changed");
            Assert::assertStringContainsString('Photography pending', $html);
            // ⚠️ No alt-text lie: the placeholder is not an <img> claiming to be a photograph.
            Assert::assertStringNotContainsString('<img class="vy-mk-slot"', $html);
        }
    }
});

it('defers the commission calculator honestly rather than inventing a figure', function () {
    foreach (['home', 'pricing'] as $view) {
        $html = mkPage($view);
        Assert::assertStringContainsString('The commission calculator is coming', $html);
        Assert::assertStringNotContainsString('data-roi-result', $html);
    }
});
