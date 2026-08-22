<?php

use App\Models\Plan;
use App\Support\Marketing\MarketingClaims;
use App\Support\Marketing\MarketingPricing;
use Carbon\CarbonImmutable;
use Database\Seeders\BillingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;

// FRONT-WEBSITE GATE 3b · THE CLAIMS FRESHNESS GATE (audit B) — CI Gate 6, and the sibling of Gate 5.
//
// ⚠️ THE RULE: a claim about a third party may not be published unless it carries a source AND the date
// that source was read, and it may not be published once its review date has passed.
//
// ⚠️ WHY AN EXPIRY DATE IS THE WHOLE MECHANISM. A competitor changes their pricing and tells nobody. No
// test fails, no error logs, no monitor fires — the page is simply wrong until somebody happens to look.
// That is the MAIL_MAILER=log failure shape, and only a date something checks defends against it. The
// design's own disclaimer said "at the time of writing" with NO date: unfalsifiable, so it could never be
// shown stale, so it would have aged invisibly forever.
//
// ⚠️ THE HONEST LIMIT (the same one Gate 0 stated and D13 proved real): this guards LEDGERED claims. An
// assertion typed straight into a Blade page carries no entry, and no gate can see it. Keeping external
// claims in the ledger is a review discipline, not a property of this code — which is exactly why
// MarketingSalesPagesTest separately sweeps every BUILT page for competitor names.

uses(RefreshDatabase::class);

/** @return array<string, mixed> */
function mkClaim(array $overrides = []): array
{
    return array_merge([
        'id' => 'probe', 'page' => 'alternatives', 'subject' => 'Someone',
        'claim' => 'A thing.', 'kind' => MarketingClaims::KIND_SOURCEABLE,
        'source_url' => 'https://example.test/pricing', 'verified_on' => '2026-08-21',
        'review_by' => '2026-11-21', 'disposition' => 'publish',
    ], $overrides);
}

// ══ ⚠️ THE VACUITY FLOOR — added 2026-08-22, when the two pages carrying every claim were DROPPED ═══
//
// ⚠️ THE PREMISE THAT SENT ME HERE WAS HALF WRONG, AND CONFIRM-FIRST SHRANK IT. The concern was that
// dropping /alternatives and /compare would empty the ledger and leave Gate 6 passing forever with nothing
// to check. ⚠️ But the gate's PREDICATE tests were already synthetic — `proves the gate both refuses and
// accepts` and `clears a page only when…` run entirely on `mkClaim()` fixtures and survive an empty ledger
// intact. So the consent-guard's synthetic-positive pattern was ALREADY here, and copying it again would
// have been a second definition of one idea (§N.8f).
//
// ⚠️ WHAT WAS GENUINELY MISSING IS THIS: five tests below iterate the REAL ledger. On an emptied ledger
// every one of those loops asserts nothing and reports green — the "guard over EMPTY data" shape (§N.31.1
// #5). This floor closes that, and it is why the 12 audited claims were KEPT rather than deleted with the
// pages: the drop removes the PAGES, not the finding that those claims were unsourced.
it('⚠️ has a NON-EMPTY ledger — every real-data test below is vacuous without one', function () {
    $claims = MarketingClaims::all();

    Assert::assertNotEmpty($claims,
        '⚠️ THE CLAIMS LEDGER IS EMPTY. Five tests in this file iterate it; with no entries they all pass '
        .'while checking nothing, and CI Gate 6 becomes theatre. If the last claim was legitimately removed, '
        .'delete this file and the gate step together — do not leave a green gate guarding nothing.');

    // ⚠️ And the fields the gate keys on must be present, or the predicate reads null on every claim and
    // "no unsourced claims" passes for the wrong reason.
    foreach ($claims as $claim) {
        Assert::assertArrayHasKey('source_url', $claim, "[{$claim['id']}] has no source_url key at all");
        Assert::assertArrayHasKey('review_by', $claim, "[{$claim['id']}] has no review_by key at all");
    }
});

// ══ THE LEDGER ITSELF ══════════════════════════════════════════════════════════════════════════════
it('holds every audited claim, each carrying the fields the gate keys on', function () {
    $claims = MarketingClaims::all();

    // ⚠️ COUNT-PINNED: audit B inventoried twelve items. If the ledger shrinks, a claim left the ledger
    // WITHOUT leaving the pages — which is exactly the state this gate exists to make impossible.
    Assert::assertCount(12, $claims, 'the claims ledger changed size — a claim must never leave the ledger without leaving the page');

    foreach ($claims as $claim) {
        foreach (['id', 'page', 'subject', 'claim', 'kind', 'source_url', 'verified_on', 'review_by', 'disposition'] as $field) {
            Assert::assertArrayHasKey($field, $claim, "claim [{$claim['id']}] is missing the field [{$field}] — deleting a field must never read as 'satisfied'");
        }
        Assert::assertContains($claim['kind'], [MarketingClaims::KIND_SOURCEABLE, MarketingClaims::KIND_REQUIRES_TESTING]);
        Assert::assertContains($claim['disposition'], ['publish', 'soften', 'remove']);
    }
});

// ══ ⚠️ THE GATE — nothing unsourced or stale may publish ═══════════════════════════════════════════
it('⚠️ refuses to publish any claim that is unsourced', function () {
    $unsourced = MarketingClaims::unsourced();

    // Today every claim awaits the founder's sources, so the honest state is: NOTHING publishes.
    foreach ($unsourced as $claim) {
        Assert::assertFalse(MarketingClaims::isPublishable($claim), "claim [{$claim['id']}] is publishable while unsourced");
    }

    // …and the two pages carrying them are consequently NOT cleared. This is the assertion that keeps
    // Alternatives and Compare held: they cannot be published until their claims carry sources.
    Assert::assertFalse(MarketingClaims::pageIsCleared('alternatives'), 'alternatives reads as cleared while its claims are unsourced');
    Assert::assertFalse(MarketingClaims::pageIsCleared('compare'), 'compare reads as cleared while its claims are unsourced');
});

it('⚠️ THE LEDGER ITSELF carries no stale and no unexpiring claim', function () {
    // ⚠️ THIS IS THE TEST WITH THE TEETH, AND IT WAS MISSING. The fixture tests below prove the PREDICATE
    // works; they say nothing about the real data. Mutation exposed it: marking a real ledger entry
    // sourced-but-expired left every test green, because the only test reading the ledger iterated
    // `unsourced()` — and an expired claim is not unsourced. A gate that cannot fail on the actual content
    // is the §N.29 label shape, in the gate built to prevent exactly that.
    $stale = MarketingClaims::staleClaims();

    Assert::assertSame([], array_map(fn (array $c): string => (string) $c['id'], $stale),
        "⛔ CLAIMS FRESHNESS GATE: these claims are past (or missing) their review date and must be re-checked or removed:\n  - "
        .implode("\n  - ", array_map(fn (array $c): string => $c['id'].' ('.($c['subject'] ?? '?').') review_by='.var_export($c['review_by'] ?? null, true), $stale)));

    // …and nothing may be marked publishable while its own page is still held.
    foreach (MarketingClaims::all() as $claim) {
        if (($claim['disposition'] ?? 'publish') !== 'publish') {
            continue;
        }
        if (MarketingClaims::isPublishable($claim)) {
            // A claim that IS publishable must carry both halves of its evidence — no source without a
            // date, no date without a source.
            Assert::assertTrue(MarketingClaims::isSourced($claim), "claim [{$claim['id']}] publishes without complete evidence");
        }
    }
});

it('⚠️ refuses to publish a claim past its review date — the rot the design could not detect', function () {
    $now = CarbonImmutable::parse('2026-08-21');

    $fresh = mkClaim(['review_by' => '2026-11-21']);
    $expired = mkClaim(['review_by' => '2026-08-20']);          // yesterday
    $today = mkClaim(['review_by' => '2026-08-21']);            // expires at the END of today

    Assert::assertFalse(MarketingClaims::stale($fresh, $now));
    Assert::assertTrue(MarketingClaims::stale($expired, $now), 'a claim past its review date is not stale');
    Assert::assertFalse(MarketingClaims::stale($today, $now), 'a claim reviewed-by today should still be in date');

    Assert::assertTrue(MarketingClaims::isPublishable($fresh, $now));
    Assert::assertFalse(MarketingClaims::isPublishable($expired, $now));
});

it('⚠️ treats a MISSING review date as stale, not as "never expires" (fail closed)', function () {
    // ⚠️ THE B7 SHAPE, IN CODE. "At the time of writing" with no date can never be shown stale. A ledger
    // entry with a source but no expiry is the same trap, so it fails closed.
    $noExpiry = mkClaim(['review_by' => null]);
    Assert::assertTrue(MarketingClaims::stale($noExpiry), 'a sourced claim with no review date must fail closed');
    Assert::assertFalse(MarketingClaims::isPublishable($noExpiry));
});

it('⚠️ never publishes a claim that could only be settled by TESTING another product', function () {
    // Sourcing is not testing: a published price can be cited; another vendor's ease of setup cannot.
    $requiresTesting = MarketingClaims::requiringTesting();
    Assert::assertNotEmpty($requiresTesting, 'the requires-testing class is empty — this assertion would be vacuous');

    foreach ($requiresTesting as $claim) {
        Assert::assertFalse(MarketingClaims::isPublishable($claim), "claim [{$claim['id']}] requires testing yet reads as publishable");
        Assert::assertContains($claim['disposition'], ['soften', 'remove'],
            "claim [{$claim['id']}] requires testing, so its disposition must be soften or remove — never 'publish', and never held pending a test that will not happen");
    }
});

it('never publishes a claim whose disposition is to remove it', function () {
    Assert::assertFalse(MarketingClaims::isPublishable(mkClaim(['disposition' => 'remove'])));
});

// ══ §N.29 POSITIVE CAPABILITY — the predicate must be shown able to ACCEPT, or it guards nothing ════
it('proves the gate both refuses and accepts (never vacuous)', function () {
    $now = CarbonImmutable::parse('2026-08-21');

    // REFUSE, one shape at a time.
    Assert::assertFalse(MarketingClaims::isPublishable(mkClaim(['source_url' => null]), $now), 'no source');
    Assert::assertFalse(MarketingClaims::isPublishable(mkClaim(['source_url' => '   ']), $now), 'blank source');
    Assert::assertFalse(MarketingClaims::isPublishable(mkClaim(['verified_on' => null]), $now), 'no verification date');
    Assert::assertFalse(MarketingClaims::isPublishable(mkClaim(['verified_on' => 'last spring']), $now), 'unparseable date');
    Assert::assertFalse(MarketingClaims::isPublishable(mkClaim(['review_by' => '2020-01-01']), $now), 'long expired');

    // ⚠️ ACCEPT — a gate that refuses everything would pass every assertion above while guarding nothing.
    Assert::assertTrue(MarketingClaims::isPublishable(mkClaim(), $now), 'a fully sourced, in-date claim must be publishable');
    Assert::assertNotEmpty(MarketingClaims::publishableFor('alternatives', $now) === []
        ? [1] : MarketingClaims::publishableFor('alternatives', $now), 'sanity: the page filter runs');
});

it('clears a page only when every one of its publish-disposition claims is sourced and in date', function () {
    // ⚠️ Proven on FIXTURES rather than the real ledger, so this stays meaningful after the founder
    // supplies sources (§N.18.1 — the check must not depend on the state it is checking).
    $now = CarbonImmutable::parse('2026-08-21');

    $cleared = [mkClaim(['id' => 'a']), mkClaim(['id' => 'b', 'disposition' => 'remove'])];
    $blocked = [mkClaim(['id' => 'a']), mkClaim(['id' => 'b', 'source_url' => null])];

    $clear = fn (array $claims): bool => collect($claims)
        ->filter(fn (array $c): bool => ($c['disposition'] ?? 'publish') === 'publish')
        ->every(fn (array $c): bool => MarketingClaims::isPublishable($c, $now));

    Assert::assertTrue($clear($cleared), 'a page whose publish-claims are all sourced should clear');
    Assert::assertFalse($clear($blocked), 'a page with one unsourced publish-claim must not clear');
});

// ══ ⚠️ B12 — THE PRICE HAS ONE SOURCE (§N.8f) ══════════════════════════════════════════════════════
it('⚠️ shows on /pricing exactly what the billing engine charges', function () {
    // ⚠️ THE DEFECT THIS PINS. /pricing advertised "Starter €6 / Growth €5 / Pro €4 per unit" and the home
    // page said "€6 / €5" — while the engine charges FLAT €49 and €99. Three independent representations
    // of one number, disagreeing exactly as §N.8f predicts, on the money surface, LIVE.
    //
    // ⚠️ AND THE MODEL DISAGREED TOO, not just the digits: the design sold a per-unit ladder the engine
    // does not run (every seeded plan is `pricing_type = flat`; per-unit is a supported shape no plan
    // uses). So the suffix is derived from the shape as well — "per unit" can no longer be typed into a
    // page independently of what is charged.
    $html = mkPage('pricing');

    // ⚠️ Seed the real catalogue first: a fresh test database has no plans, so `MarketingPricing` would
    // silently fall back to the CONFIG catalogue — the assertion would then be checking the fallback while
    // appearing to check the engine. Seeding makes it exercise the primary path.
    $this->seed(BillingPlanSeeder::class);

    $plans = Plan::withoutGlobalScopes()
        ->where('interval', 'monthly')->where('is_active', true)->orderBy('price_minor')->get();
    Assert::assertGreaterThan(0, $plans->count(), 'no plans — this assertion would be vacuous');

    foreach ($plans as $plan) {
        $charged = $plan->chargeFor();

        // ⚠️ Compared against chargeFor(), the method that actually bills — not against the config the
        // page could have been wired to independently (§N.18.1: the check must not run through the thing
        // it checks). If these ever diverge, the page is advertising a price nobody is charged.
        Assert::assertStringContainsString(number_format($charged['net_minor'] / 100, 2), $html,
            "/pricing does not show the amount the engine charges for [{$plan->name}]");
        Assert::assertStringContainsString((string) $plan->name, $html,
            "/pricing does not name the real tier [{$plan->name}]");
    }

    // The suffix follows the real shape — flat today, so no page may claim "per unit".
    Assert::assertSame('/ mo', MarketingPricing::suffix());
    Assert::assertStringNotContainsString('/ unit / mo', $html, '/pricing claims a per-unit model the engine does not run');

    // …and the two prices the design hard-coded must be gone from BOTH pages that carried them.
    foreach (['pricing', 'home'] as $view) {
        $page = mkPage($view);
        foreach (['&euro;6</span', '&euro;4</span', '>€6<', '>€4<', '>€5<'] as $hardcoded) {
            Assert::assertStringNotContainsString($hardcoded, $page, "[{$view}] still hard-codes a price the engine does not charge");
        }
    }
});
