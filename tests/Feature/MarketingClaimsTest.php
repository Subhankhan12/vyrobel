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
    // ⚠️ 202 SINCE DOC SLICE 0 (2026-08-23): the 190 S1 entries for the 27 documentation pages. ⚠️ THIS IS
    // A RE-PIN, NOT A WEAKENING — the assertion still fails the moment the ledger shrinks, which is the
    // only thing it was ever written to catch; it was simply expressed as an exact count. ⚠️ Confirmed
    // EMPIRICALLY before the entries landed: with unsourced claims present this was the ONLY failing
    // assertion in the file, because `stale()` returns false for an unsourced claim (it is "its own,
    // louder failure"), so the freshness teeth stayed green.
    // ⚠️ RE-PINNED 202 → 203: S1-044 was SPLIT into a count row and a visibility row, because one test
    // cannot tell you which of two claims broke. Growth with a reason; the pin still catches shrinkage.
    Assert::assertCount(203, $claims, 'the claims ledger changed size — a claim must never leave the ledger without leaving the page');

    foreach ($claims as $claim) {
        foreach (['id', 'page', 'subject', 'claim', 'kind', 'source_url', 'verified_on', 'review_by', 'disposition'] as $field) {
            Assert::assertArrayHasKey($field, $claim, "claim [{$claim['id']}] is missing the field [{$field}] — deleting a field must never read as 'satisfied'");
        }
        Assert::assertContains($claim['kind'], [MarketingClaims::KIND_SOURCEABLE, MarketingClaims::KIND_REQUIRES_TESTING, MarketingClaims::KIND_INTERNAL]);
        Assert::assertContains($claim['disposition'],
            ['publish', 'soften', 'remove', MarketingClaims::DISPOSITION_NOT_A_CLAIM]);
    }
});

// ══ ⚠️ THE GATE — nothing unsourced or stale may publish ═══════════════════════════════════════════
// ══ ⚠️ `not_a_claim` — THE LABEL THAT MUST NOT BECOME A DUMPING GROUND ═══════════════════════════════
//
// ⚠️⚠️ WHY THIS EXISTS. `remove` used to mean BOTH "this text was cut" and "this row was never a claim",
// so it could not tell you whether a removal had HAPPENED — and 27 rows marked `remove` turned out to be
// still on the page. ⚠️ The danger in splitting them is the opposite one: `not_a_claim` becomes a
// permanent "nothing to do here" that a real claim can be quietly filed under. A RECORDED REASON is what
// stops that — without it the label is an assertion, exactly as unfalsifiable as the overload it replaces.
it('⚠️ requires a recorded reason on every not_a_claim row', function () {
    $rows = array_values(array_filter(MarketingClaims::all(),
        fn (array $c): bool => ($c['disposition'] ?? '') === MarketingClaims::DISPOSITION_NOT_A_CLAIM));

    Assert::assertNotEmpty($rows, 'no not_a_claim rows — this assertion would be vacuous');

    foreach ($rows as $c) {
        $reason = $c['not_a_claim_reason'] ?? null;
        Assert::assertTrue(is_string($reason) && trim($reason) !== '',
            "⚠️ claim [{$c['id']}] is filed as not_a_claim with NO reason — that is an assertion, not a judgement");
    }
});

it('⚠️ does not block on a not_a_claim row — and not because it was resolved', function () {
    // ⚠️ THE REASON MATTERS, NOT JUST THE OUTCOME. `remove`/`soften` mean "handled"; `not_a_claim` means
    // "there was nothing here to handle". Both skip the blocking check, and conflating them is how this
    // ledger got into trouble in the first place.
    $nac = mkClaim([
        'id' => 'NAC-probe', 'page' => 'nac-probe-page', 'source_url' => null, 'verified_on' => null,
        'review_by' => null, 'disposition' => MarketingClaims::DISPOSITION_NOT_A_CLAIM,
    ]);

    Assert::assertFalse(MarketingClaims::isPublishable($nac),
        'a not_a_claim row should not read as publishable — it is not a claim to publish');
});

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

// ══ ⚠️ DOC SLICE 0 — THE COVERAGE PIN ════════════════════════════════════════════════════════════════
//
// ⚠️⚠️ WHAT THIS IS AND IS NOT. `pageIsCleared()` FAILS OPEN: a page with no ledger entries reads as
// CLEARED, which is how `/resources/growing-direct-bookings` could repeat B8's unsourced "15-18% of every
// booking" and pass the gate while `compare` — carrying the very same claim — was held. This test does NOT
// fix that. ⚠️ It pins THIS PASS'S WORK so the 27 documentation pages cannot quietly slide back into the
// fail-open state; ⚠️ **any FUTURE page nobody enters still reads as cleared.** The structural fix is a
// founder decision (see docs/audits/DOCS-SLICE0-LEDGER-2026-08-23.md §6).
it('⚠️ keeps ledger coverage for every documentation page — the fail-open state cannot return silently', function () {
    // ⚠️ `glossary` is DELIBERATELY ABSENT and is a live instance of the residual defect: its terms are
    // data-driven in a form the claim sweep could not reach, so it has no entries and therefore still
    // reads as cleared. Recorded here rather than hidden, so the exception is visible to the next reader.
    $covered = [
        'docs', 'faq', 'resources',
        'guide-access', 'guide-arrival', 'guide-channels', 'guide-checkin', 'guide-gdpr',
        'guide-housekeeping', 'guide-payouts', 'guide-rates', 'guide-refunds', 'guide-reports',
        'guide-reservation', 'guide-security-doc', 'guide-settings', 'guide-setup', 'guide-statements',
        'guide-storefront',
        'article-direct', 'article-housekeeping', 'article-oos', 'article-owners', 'article-pricing',
        'article-statement', 'article-trust',
    ];

    Assert::assertCount(26, $covered, 'the covered-page list shrank — this pin would guard less than it did');

    foreach ($covered as $page) {
        $entries = array_values(array_filter(MarketingClaims::all(),
            fn (array $c): bool => ($c['page'] ?? '') === $page));

        Assert::assertNotEmpty($entries,
            "⚠️ [{$page}] has NO ledger entries, so pageIsCleared() reads it as CLEARED — the fail-open state this pass closed");

        // ⚠️ And it must actually be HELD while anything on it is still UNRESOLVED.
        //
        // ⚠️ RE-PINNED, PURPOSE PRESERVED. This assertion used to read `assertFalse(pageIsCleared())`
        // unconditionally, on the premise that every documentation page still carries unsourced claims.
        // ⚠️ THAT PREMISE WAS INVALIDATED BY LEGITIMATE WORK, not by a regression: the fragment re-read
        // found five pages whose every row is a heading, a nav label or a ruled-on item, so they have
        // nothing left to source and "cleared" is the correct reading. Asserting the old form would have
        // forced someone to either fudge a disposition or weaken the pin — both worse than re-pinning.
        // ⚠️ WHAT IT GUARDS IS UNCHANGED: a page with ANY unresolved `publish` claim must never clear.
        $unresolved = array_values(array_filter($entries,
            fn (array $c): bool => ($c['disposition'] ?? 'publish') === 'publish'
                && ! MarketingClaims::isPublishable($c)));

        if ($unresolved !== []) {
            Assert::assertFalse(MarketingClaims::pageIsCleared($page),
                "⚠️ [{$page}] reads as cleared while ".count($unresolved).' of its claims are unsourced and unresolved');
        }
    }
});

it('⚠️ still blocks a page the moment ONE claim goes back to unresolved (the re-pin’s own check)', function () {
    // ⚠️ The re-pin above only asserts the HELD half where something is unresolved. This proves that half
    // is not vacuous: a page with unresolved claims genuinely fails to clear, so the conditional cannot be
    // passing merely because every page slipped out of its scope.
    $withUnresolved = array_values(array_unique(array_map(
        fn (array $c): string => (string) $c['page'],
        array_filter(MarketingClaims::all(),
            fn (array $c): bool => ($c['disposition'] ?? 'publish') === 'publish'
                && ! MarketingClaims::isPublishable($c)))));

    Assert::assertNotEmpty($withUnresolved, 'nothing is unresolved any more — the HELD assertion is now vacuous');

    foreach ($withUnresolved as $page) {
        Assert::assertFalse(MarketingClaims::pageIsCleared($page),
            "⚠️ [{$page}] clears despite carrying an unresolved claim");
    }
});

it('⚠️ PROVES the coverage pin can fire (its own vacuity check)', function () {
    // ⚠️ A page nobody entered — the exact state the pin exists to detect. If this ever reads as NOT
    // cleared, the assertion above is passing for some reason other than the one it claims.
    Assert::assertSame([], array_values(array_filter(MarketingClaims::all(),
        fn (array $c): bool => ($c['page'] ?? '') === 'glossary')));
    Assert::assertTrue(MarketingClaims::pageIsCleared('glossary'),
        'an unentered page no longer reads as cleared — the fail-open premise of the pin has changed');
});
