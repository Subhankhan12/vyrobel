<?php

use App\Support\Marketing\MarketingContent;
use PHPUnit\Framework\Assert;

// FRONT-WEBSITE GATE 0 · THE CONTENT-HONESTY GATE (ruling D1) — CI Gate 5 runs this file directly, and it
// also runs inside the full suite (Gate 3), so the protection cannot be filtered away by either path.
//
// ⚠️ THE RULE: the marketing ledger (resources/marketing/content.json) cannot be DECLARED production-ready
// while any entry in it is unreal (`real` must be exactly true or an explicit JSON number) or unconsented
// (`consent` !== true on a named person) — and at runtime, any production-like environment FILTERS such
// entries out of rendering.
//
// ⚠️ §N.29 + §N.18.1 — TWO IMPLEMENTATIONS, ONE ARBITER. The raw helpers below re-implement the rule
// INDEPENDENTLY of MarketingContent (so the gate's teeth never assert through the thing they check), and
// the fixture table at the bottom is the arbiter BOTH implementations must satisfy, shape by shape — the
// agreement test turns any drift between them into a red build instead of a silent divergence (§N.8f).
// This file was HARDENED after an adversarial review caught the first version: a shared reject-list let
// `real: "no"` / `""` / `"TBD"` through BOTH layers, the illustrative check was a loose substring, the
// runtime-filter test was predicate-as-oracle gated on customers alone, and `staging` rendered unfiltered.
//
// ⚠️ THE HONEST LIMIT: this gate guards the FLAGGED entries in the ONE tracked ledger. A fabrication typed
// directly into a future Blade page carries no flags and is invisible here — the Gate 1+ discipline (pages
// render marketing claims ONLY through MarketingContent) is what closes that, and it is a review rule, not
// this test.

/** @return array<string, mixed> */
function marketingLedgerRaw(): array
{
    $raw = file_get_contents(base_path('resources/marketing/content.json'));
    Assert::assertNotFalse($raw, 'the marketing content ledger must exist — the gate has nothing to guard without it');
    $json = json_decode($raw, true);
    Assert::assertIsArray($json, 'the marketing content ledger must be valid JSON');

    return $json;
}

/** The rule, implemented INDEPENDENTLY of MarketingContent (§N.18.1). Allow-list: true or int/float. */
function marketingRawPublishable(array $entry, bool $requiresConsent = false): bool
{
    $real = $entry['real'] ?? null;
    $realOk = $real === true || is_int($real) || is_float($real);
    $consentOk = ! $requiresConsent || ($entry['consent'] ?? false) === true;

    return $realOk && $consentOk;
}

/** Illustrative ⇔ the status BEGINS with the word — independent of the class (§N.18.1). */
function marketingRawIllustrative(mixed $status): bool
{
    return is_string($status) && preg_match('/^\s*illustrative\b/iu', $status) === 1;
}

// ══ STRUCTURE — the flags the gate keys on must EXIST (removing a flag is an evasion, not a pass) ══
it('requires every ledger entry to carry the honesty flags the gate keys on', function () {
    $j = marketingLedgerRaw();

    // ⚠️ RAW reads, independent of MarketingContent (§N.18.1).
    Assert::assertArrayHasKey('status', $j, 'the ledger must declare its status — a missing status is treated as production-declared');
    Assert::assertArrayHasKey('figures', $j);
    Assert::assertArrayHasKey('customers', $j);
    Assert::assertArrayHasKey('testimonials', $j);

    // ⚠️ NO UNKNOWN SECTIONS. The gate iterates figures/customers/testimonials by name; a new section
    // ("partners", "press"…) would be UNGUARDED content wearing the ledger's credibility. Adding one must
    // first extend the gate — so its appearance is a red build, not a silent hole.
    $unknown = array_diff(array_keys($j), MarketingContent::SECTIONS);
    Assert::assertSame([], array_values($unknown),
        'unknown ledger section(s) ['.implode(', ', $unknown).'] — extend the honesty gate (MarketingContent::SECTIONS + this test) BEFORE adding a section, or its entries ship unguarded');

    foreach ($j['figures'] as $key => $figure) {
        Assert::assertArrayHasKey('real', $figure, "figure [{$key}] must carry a `real` flag — deleting the flag must never read as \"real\"");
    }
    foreach (['customers', 'testimonials'] as $section) {
        foreach ($j[$section] as $i => $person) {
            Assert::assertArrayHasKey('real', $person, "{$section}[{$i}] must carry a `real` flag");
            Assert::assertArrayHasKey('consent', $person, "{$section}[{$i}] is a NAMED PERSON and must carry an explicit `consent` flag — absence is a refusal, not a pass");
        }
    }
});

// ══ THE GATE — production-declared ⇒ everything real AND consented ═══════════════════════════════
it('refuses a production-declared ledger that still contains unreal or unconsented entries', function () {
    $j = marketingLedgerRaw();
    $status = $j['status'] ?? null;

    if (marketingRawIllustrative($status)) {
        // Illustrative mode: the ledger is honest ABOUT ITSELF, so dev may render it. The runtime filter
        // (tested below) is what keeps it out of production-like rendering meanwhile.
        expect(MarketingContent::declaredProduction($j))->toBeFalse();   // the class agrees with the raw read

        return;
    }

    // ⚠️ THE TEETH — via the RAW rule, never the class (§N.18.1). Once the ledger claims to be production
    // content, every entry must be real — and every named person consented. One violation fails the build.
    $violations = [];

    foreach ($j['figures'] as $key => $figure) {
        if (! marketingRawPublishable($figure)) {
            $violations[] = "figures.{$key} (real: ".var_export($figure['real'] ?? null, true).')';
        }
    }
    foreach (['customers', 'testimonials'] as $section) {
        foreach ($j[$section] as $i => $person) {
            if (! marketingRawPublishable($person, requiresConsent: true)) {
                $violations[] = "{$section}[{$i}] \"".($person['name'] ?? '?').'" (real: '.var_export($person['real'] ?? null, true).', consent: '.var_export($person['consent'] ?? null, true).')';
            }
        }
    }

    Assert::assertSame(
        [],
        $violations,
        '⛔ CONTENT-HONESTY GATE: the marketing ledger is declared production-ready (status: "'.var_export($status, true).'") but still contains '
        .count($violations)." unreal/unconsented entr(ies):\n  - ".implode("\n  - ", $violations)
        ."\nEither restore the ILLUSTRATIVE status, or replace these with real, consented content (ruling D1)."
    );
});

// ══ THE RUNTIME GUARD — expected output derived from the RAW ledger, compared UNCONDITIONALLY ══════
it('filters exactly the raw-unpublishable entries out of production-like rendering — figures included', function () {
    $j = marketingLedgerRaw();

    // ⚠️ The expectation comes from the RAW JSON via the INDEPENDENT rule — never from the predicate the
    // filter itself uses (the first version asserted isPublishable() over the filter's own output, which
    // could only ever catch the filter disagreeing with the predicate, not a wrong predicate). And it runs
    // UNCONDITIONALLY — no "$allFlagged" gate that silently retires the assertion the day one customer is
    // legitimately consented.
    $expectedFigureKeys = array_keys(array_filter($j['figures'], fn ($f) => is_array($f) && marketingRawPublishable($f)));
    Assert::assertSame($expectedFigureKeys, array_keys(MarketingContent::figures(filtered: true)),
        'production-like figures() must contain exactly the raw-publishable figures');

    foreach (['customers' => MarketingContent::customers(filtered: true), 'testimonials' => MarketingContent::testimonials(filtered: true)] as $section => $actual) {
        $expectedIds = array_values(array_map(
            fn (array $e) => $e['id'] ?? null,
            array_filter($j[$section], fn ($e) => is_array($e) && marketingRawPublishable($e, requiresConsent: true)),
        ));
        Assert::assertSame($expectedIds, array_map(fn (array $e) => $e['id'] ?? null, $actual),
            "production-like {$section}() must contain exactly the raw-publishable+consented entries");
    }
});

it('keeps the illustrative entries available in dev (ruling D1 — kept for the build, flags intact)', function () {
    $raw = marketingLedgerRaw();

    expect(MarketingContent::figures(filtered: false))->toHaveCount(count($raw['figures']))
        ->and(MarketingContent::customers(filtered: false))->toHaveCount(count($raw['customers']))
        ->and(MarketingContent::testimonials(filtered: false))->toHaveCount(count($raw['testimonials']));

    // The flags survive the read — a page CAN label an illustrative figure as illustrative.
    $first = MarketingContent::customers(filtered: false)[0] ?? null;
    Assert::assertIsArray($first);
    Assert::assertArrayHasKey('consent', $first);
});

// ══ THE FIXTURE TABLE — the arbiter both implementations must satisfy, shape by shape (§N.29) ══════
/** @return array<string, array{entry: array<string, mixed>, consent: bool, publishable: bool}> */
function marketingPublishabilityFixtures(): array
{
    return [
        // REJECT — the unreal shapes, including the ones the first version let through.
        'real null' => ['entry' => ['real' => null], 'consent' => false, 'publishable' => false],
        'real false' => ['entry' => ['real' => false], 'consent' => false, 'publishable' => false],
        'real absent' => ['entry' => [], 'consent' => false, 'publishable' => false],
        'real "no"' => ['entry' => ['real' => 'no'], 'consent' => false, 'publishable' => false],
        'real ""' => ['entry' => ['real' => ''], 'consent' => false, 'publishable' => false],
        'real "TBD"' => ['entry' => ['real' => 'TBD'], 'consent' => false, 'publishable' => false],
        'real "false"' => ['entry' => ['real' => 'false'], 'consent' => false, 'publishable' => false],
        'real "true" (string)' => ['entry' => ['real' => 'true'], 'consent' => false, 'publishable' => false],
        'real "62" (numeric string)' => ['entry' => ['real' => '62'], 'consent' => false, 'publishable' => false],
        'real [] (array)' => ['entry' => ['real' => []], 'consent' => false, 'publishable' => false],
        // REJECT — the unconsented shapes (consent must be STRICTLY true where required).
        'consent false' => ['entry' => ['real' => true, 'consent' => false], 'consent' => true, 'publishable' => false],
        'consent absent' => ['entry' => ['real' => true], 'consent' => true, 'publishable' => false],
        'consent 1 (int)' => ['entry' => ['real' => true, 'consent' => 1], 'consent' => true, 'publishable' => false],
        'consent "true"' => ['entry' => ['real' => true, 'consent' => 'true'], 'consent' => true, 'publishable' => false],
        'consent "pending"' => ['entry' => ['real' => true, 'consent' => 'pending'], 'consent' => true, 'publishable' => false],
        // ACCEPT — a filter that rejects everything guards nothing (§N.29: show the positive).
        'real true + consent true' => ['entry' => ['real' => true, 'consent' => true], 'consent' => true, 'publishable' => true],
        'real 1400 (the value itself)' => ['entry' => ['real' => 1400], 'consent' => false, 'publishable' => true],
        'real 2.1 (float value)' => ['entry' => ['real' => 2.1], 'consent' => false, 'publishable' => true],
        'real 0 (an explicit zero is a typed value, not an ambiguity)' => ['entry' => ['real' => 0], 'consent' => false, 'publishable' => true],
    ];
}

it('pins the publishability rule shape by shape — and both implementations must agree on every one', function () {
    foreach (marketingPublishabilityFixtures() as $name => $case) {
        // The CLASS predicate against the arbiter…
        Assert::assertSame($case['publishable'], MarketingContent::isPublishable($case['entry'], requiresConsent: $case['consent']),
            "MarketingContent::isPublishable disagrees with the pinned rule on: {$name}");
        // …the RAW rule against the arbiter…
        Assert::assertSame($case['publishable'], marketingRawPublishable($case['entry'], requiresConsent: $case['consent']),
            "the raw (independent) rule disagrees with the pinned rule on: {$name}");
    }
});

it('pins the production-declaration rule — begins-with, not contains; missing = strict', function () {
    $cases = [
        // status → declaredProduction?
        ['ILLUSTRATIVE — replace before launch', false],   // the shipped status
        ['illustrative', false],
        ['  Illustrative (updated 2026-09-01)', false],
        // ⚠️ the loose-substring hole the review caught: MENTIONING the word must not keep the teeth off.
        ['Production ready — illustrative content replaced 2026-09-01', true],
        ['PRODUCTION', true],
        ['', true],
        [null, true],
    ];

    foreach ($cases as [$status, $expected]) {
        $ledger = $status === null ? [] : ['status' => $status];
        Assert::assertSame($expected, MarketingContent::declaredProduction($ledger),
            'declaredProduction disagrees with the pinned rule on status: '.var_export($status, true));
        Assert::assertSame(! $expected, marketingRawIllustrative($status),
            'the raw illustrative rule disagrees with the pinned rule on status: '.var_export($status, true));
    }
});

it('filters in every environment except the explicit dev list — unknown environments fail CLOSED', function () {
    // ⚠️ The first version keyed on environment === 'production' exactly: a `staging` or `prod` host
    // rendered every fabricated entry UNFILTERED. The rule is an inverted allow-list now.
    foreach (['production', 'staging', 'prod', 'Production', 'preview', 'demo', ''] as $env) {
        Assert::assertTrue(MarketingContent::filtersFor($env), "environment \"{$env}\" must FILTER (fail closed)");
    }
    foreach (['local', 'development', 'testing'] as $env) {
        Assert::assertFalse(MarketingContent::filtersFor($env), "dev environment \"{$env}\" renders unfiltered (D1)");
    }
});
