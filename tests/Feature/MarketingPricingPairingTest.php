<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Support\Marketing\MarketingPricing;
use Database\Seeders\BillingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ THE PRICING PAGE'S CYCLE PAIRING — THE FOUNDER'S THREE PLANS, ONE OF THEM SINGLE-INTERVAL.
 *
 * ⚠️ WHAT THIS GUARDS, AND WHY A STATUS-ONLY TEST WOULD HAVE BEEN WORSE THAN NOTHING. `/pricing` rendered
 * both billing cycles by building TWO independent lists — `tiersForDisplay('monthly')` and `…('yearly')` —
 * and pairing them BY POSITION. Two things follow, and only the first announces itself:
 *
 *   · DIFFERENT LENGTHS → `Undefined array key 2`, a hard 500 on the page where money is decided.
 *   · ⚠️⚠️ EQUAL LENGTHS, DIFFERENT ORDER → **HTTP 200 and a false price**. `tiers()` sorts by
 *     `price_minor` independently per interval, so any yearly pricing that does not preserve the monthly
 *     rank transposes the columns silently. A wrong price under the right plan name is worse than a 500,
 *     because a 500 announces itself — and a test that asserted only `assertOk()` would have passed.
 *
 * ⚠️ SO EVERY ASSERTION BELOW IS ABOUT WHOSE PRICE IT IS, not whether a page rendered. The markup names the
 * `plans` row each figure came from (`data-plan`), which is what makes that checkable from outside the
 * code that produced it (§N.18.1) rather than re-asserting the mapping in the same breath as building it.
 *
 * ⚠️ THE FIXTURE IS THE FOUNDER'S ACTUAL NEXT ACTION: a third tier. The admin panel creates ONE plan per
 * interval, so a third tier CANNOT be added without passing through the single-interval state.
 */
uses(RefreshDatabase::class);

/**
 * The cards, as a visitor's browser would group them: tier → cycle → the `plans` row the figure came from.
 *
 * ⚠️ `<style>`, `<script>` AND COMMENTS COME OUT FIRST. This is the seventh time in this project a source
 * scan has read something that is not the visitor's markup; here it is load-bearing, because `/pricing`
 * ships a second, DEAD copy of the cards inside its stylesheet (pre-existing — the browser DOM has exactly
 * one grid and three cards). A scan that did not strip it would double every count it makes.
 *
 * @return array<string, array<string, ?string>>
 */
function mppCards(string $html): array
{
    $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = (string) preg_replace('/<!--.*?-->/s', '', $html);

    $out = [];
    // Each card opens with `<div data-planname="…">Name</div>`; the chunk runs to the next card or the end.
    preg_match_all('/<div data-planname="([^"]+)"[^>]*>(.*?)(?=<div data-planname=|$)/s', $html, $cards, PREG_SET_ORDER);

    foreach ($cards as [$_, $code, $chunk]) {
        $cycles = [];
        preg_match_all('/<div data-cycleprice="(\w+)"[^>]*>(.*?)(?=<div data-cycleprice=|<ul )/s', $chunk, $blocks, PREG_SET_ORDER);

        foreach ($blocks as [$__, $cycle, $body]) {
            $cycles[$cycle] = preg_match('/data-plan="([^"]+)"/', $body, $m) ? $m[1] : null;
        }

        $out[$code] = $cycles;
    }

    return $out;
}

/**
 * The comparison table's headers, same shape — because "fixed the cards" is not "fixed the page".
 *
 * @return array<string, array<string, ?string>>
 */
function mppHeaders(string $html): array
{
    $out = [];
    preg_match_all('/<th scope="col" data-planname="([^"]+)"[^>]*>(.*?)<\/th>/s', $html, $ths, PREG_SET_ORDER);

    foreach ($ths as [$_, $code, $chunk]) {
        $cycles = [];
        preg_match_all('/data-cycleprice="(\w+)"([^>]*)>/', $chunk, $spans, PREG_SET_ORDER);

        foreach ($spans as [$__, $cycle, $attrs]) {
            $cycles[$cycle] = preg_match('/data-plan="([^"]+)"/', $attrs, $m) ? $m[1] : null;
        }

        $out[$code] = $cycles;
    }

    return $out;
}

/** Add a plan the way the admin panel does — one row, one interval. */
function mppPlan(string $code, string $interval, int $minor): Plan
{
    return Plan::create([
        'code' => $code, 'name' => 'Growth', 'price_minor' => $minor, 'currency' => 'EUR',
        'interval' => $interval, 'is_active' => true, 'pricing_type' => Plan::PRICING_FLAT,
    ]);
}

it('⚠️⚠️ renders THREE plans with one sold on a single interval — and pairs every price to its own tier', function () {
    $this->seed(BillingPlanSeeder::class);
    // ⚠️ MONTHLY ONLY, priced BETWEEN the two seeded tiers — this is the exact state that returned 500.
    mppPlan('growth_monthly', 'monthly', 6900);

    $html = $this->get('/pricing')->assertOk()->getContent();

    $cards = mppCards($html);
    Assert::assertCount(3, $cards, 'three active tiers exist but the page drew '.count($cards).' cards');

    // ⚠️⚠️ THE PAIRING ITSELF. Each card's figure must come from a `plans` row whose own tier key is that
    //    card's — the assertion a 200-only test cannot make.
    foreach ($cards as $cardCode => $cycles) {
        $tier = MarketingPricing::tierKey($cardCode);

        foreach ($cycles as $cycle => $rowCode) {
            if ($rowCode === null) {
                continue;   // a cycle this tier is not sold on — asserted separately below
            }

            Assert::assertSame($tier, MarketingPricing::tierKey($rowCode),
                "the {$tier} card shows a {$cycle} price belonging to [{$rowCode}] — another plan's price "
                .'under this plan\'s name. A 500 announces itself; this does not.');

            Assert::assertSame($cycle, (string) Plan::where('code', $rowCode)->value('interval'),
                "the [{$rowCode}] row is rendered in the {$cycle} block but is not a {$cycle} plan");
        }
    }

    // ⚠️ AND THE SAME ON THE COMPARISON TABLE, which built the same two lists and indexed the yearly one by
    //    header position. Fixing only the cards would have left the page half broken.
    $headers = mppHeaders($html);
    Assert::assertCount(3, $headers, 'the comparison table drew '.count($headers).' columns for three tiers');

    foreach ($headers as $thCode => $cycles) {
        foreach (array_filter($cycles) as $cycle => $rowCode) {
            Assert::assertSame(MarketingPricing::tierKey($thCode), MarketingPricing::tierKey($rowCode),
                "the comparison header for [{$thCode}] shows a {$cycle} price from [{$rowCode}]");
        }
    }
});

it('⚠️⚠️ pairs by identity even when the two cycles sort in DIFFERENT ORDERS — the silent case', function () {
    $this->seed(BillingPlanSeeder::class);
    // ⚠️ EQUAL LENGTHS, INVERTED RANK. Monthly: essential 49 · growth 69 · pro 99. Yearly: growth 100 ·
    //    essential 490 · pro 990. `orderBy('price_minor')` per interval therefore returns DIFFERENT orders,
    //    which positional pairing transposes with NO error at all. Nothing forbids this pricing.
    mppPlan('growth_monthly', 'monthly', 6900);
    mppPlan('growth_yearly', 'yearly', 10000);

    $html = $this->get('/pricing')->assertOk()->getContent();
    $cards = mppCards($html);

    Assert::assertCount(3, $cards);

    // ⚠️ PINNED EXPLICITLY, not derived from the same grouping the page used — a check must be independent
    //    of what it checks (§N.18.1). These three pairings are written out by hand.
    Assert::assertSame('essential_yearly', $cards['essential_monthly']['yearly'] ?? null,
        'the Essential card shows a yearly price that is not Essential\'s');
    Assert::assertSame('growth_yearly', $cards['growth_monthly']['yearly'] ?? null,
        'the Growth card shows a yearly price that is not Growth\'s');
    Assert::assertSame('pro_yearly', $cards['pro_monthly']['yearly'] ?? null,
        'the Pro card shows a yearly price that is not Pro\'s');
});

it('⚠️ says something HONEST where a cycle is missing — no blank, no zero, and nothing derived', function () {
    $this->seed(BillingPlanSeeder::class);
    mppPlan('growth_monthly', 'monthly', 6900);

    $html = $this->get('/pricing')->assertOk()->getContent();

    // The block still SHIPS — with scripts off both cycles are visible and the switcher collapses one, so
    // an omitted block would leave a hole where a price belongs the moment a visitor pressed "Annual".
    // ⚠️ `array_key_exists`, NOT `?? 'missing'` — the first draft of this line used `??`, which cannot tell
    //    "no yearly block rendered" from "a yearly block with no price", and so reported the CORRECT output
    //    as a failure. A null IS the answer here.
    $growth = mppCards($html)['growth_monthly'] ?? [];
    Assert::assertArrayHasKey('yearly', $growth,
        'the Growth card has no yearly block at all — pressing "Annual" would leave a hole where a price belongs');
    Assert::assertNull($growth['yearly'], 'the Growth card claims a yearly plan row that does not exist');

    Assert::assertStringContainsString(MarketingPricing::cycleUnavailableNote('yearly'), $html,
        'nothing on the page states that this tier is not sold annually');

    // ⚠️⚠️ AND NO FIGURE IS INVENTED FOR IT. 12 × €69.00 is €828.00 — the obvious derivation, and a price
    //    the engine would not charge. `isPlaceholder()` is true besides, so it would be arithmetic over
    //    numbers nobody has set.
    foreach (['828.00', '82,800', '69.00 / yr'] as $invented) {
        Assert::assertStringNotContainsString($invented, $html,
            "the page shows [{$invented}] — an annual figure derived from a monthly one is an invented price");
    }

    // ⚠️ NOT A ZERO EITHER: a price of nothing is not a cheap plan, it is a binding that resolved to
    //    nothing — the failure `MarketingPriceSourceTest` already excludes zeros to catch.
    Assert::assertStringNotContainsString('data-price data-plan="growth_yearly"', $html);
});

it('⚠️ derives the card grid from the tier COUNT, so three tiers get three columns and one on a phone', function () {
    $this->seed(BillingPlanSeeder::class);
    mppPlan('growth_monthly', 'monthly', 6900);

    $html = $this->get('/pricing')->assertOk()->getContent();

    // ⚠️⚠️ THE STYLESHEET COMES OUT FIRST, and the first draft of this test proves why: the shell's CSS
    //    carries `repeat(3, 1fr) !important` for an unrelated grid, so the negative assertion below fired
    //    on the site's own stylesheet rather than on the plan grid. A guard must read what RUNS on this
    //    element, not every string that resembles it — the eighth time this project has hit that.
    $markup = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

    preg_match('/class="vy-plan-grid" style="([^"]*)"/', $markup, $grid);
    Assert::assertNotEmpty($grid, 'the plan grid did not render at all — the assertions below would be vacuous');

    // ⚠️ THE COUNT IS NEVER TYPED. A hardcoded `repeat(2, …)` would leave the third tier with no column.
    Assert::assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr))', $grid[1],
        'the plan grid did not follow the tier count — three active tiers must produce three columns');

    // ⚠️ `minmax(0, 1fr)` NOT a bare `1fr`: `1fr` is `minmax(auto, 1fr)`, whose min-content floor has caused
    //    four overflow defects in this codebase. The browser measured 0px overflow at 1280 and 375.
    Assert::assertStringNotContainsString('repeat(3, 1fr)', $grid[1],
        'the plan grid track lost its minmax(0, …) floor');

    // ⚠️ AND THE PHONE RULE EXISTS — one column at the shell's own 760px breakpoint, no new breakpoint.
    $shell = file_get_contents(resource_path('views/components/marketing/shell.blade.php'));
    Assert::assertMatchesRegularExpression('/\.vy-plan-grid\s*\{[^}]*grid-template-columns:\s*(1fr|minmax\(0, ?1fr\))/', (string) $shell,
        'the .vy-plan-grid phone rule is gone — three columns would stay three columns on a 375px screen');
});

it('⚠️ derives ONE tier identity, and it survives a multi-word plan code', function () {
    // ⚠️ THE PREVIOUS DERIVATION WAS `str($code)->before('_')`, which agrees on every code that exists
    //    today and is WRONG on the first multi-word one — `pro_plus_monthly` → `pro`, colliding with Pro
    //    and handing that card Pro's feature list. This is the shape of defect that ships quietly.
    Assert::assertSame('essential', MarketingPricing::tierKey('essential_monthly'));
    Assert::assertSame('essential', MarketingPricing::tierKey('essential_yearly'));
    Assert::assertSame('pro_plus', MarketingPricing::tierKey('pro_plus_monthly'));
    Assert::assertSame('growth', MarketingPricing::tierKey('growth'));
});
