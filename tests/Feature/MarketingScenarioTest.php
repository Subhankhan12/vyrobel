<?php

use App\Support\Marketing\MarketingContent;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// FRONT-WEBSITE GATE 3b · THE SCENARIO GUARD (ruling A).
//
// ⚠️ THE STRUCTURAL GAP THIS CLOSES. The content ledger governs DATA; these pages are MARKUP. No existing
// gate could see a fabricated identity, an attributed quote or an achieved outcome typed into a Blade file
// — the limit Gate 0 stated in its own docs, that D13 proved real, and that this gate's own rewrite then
// proved twice more: the ledger names came back the moment the pages were REGENERATED (a post-generation
// patch is undone by the next generation), and three LIVE Sales pages were carrying the same fabricated
// testimonials the case-study ruling was written about.
//
// ⚠️ AND THIS FILE EXISTS BECAUSE A COMMENT CLAIMED IT ALREADY DID. The rewritten pages each carried
// "Guarded by MarketingScenarioTest" while no such test existed — stub-and-pretend inside the four files
// whose entire subject is not claiming things that are not true. An adversarial verifier caught it. The
// honest repair was to write the guard, not to soften the comment.

const MK_SCENARIOS = ['case-studies', 'story-cyclades', 'story-lisbon', 'story-rotterdam'];

/** Every marketing page, so the sweeps below cannot quietly cover a subset. */
function mkAllMarketingViews(): array
{
    $views = collect((array) glob(resource_path('views/marketing/*.blade.php')))
        ->map(fn (string $f): string => basename($f, '.blade.php'))
        ->sort()->values()->all();

    // ⚠️ COUNT-PINNED: a page added without extending these sweeps is a page nothing checks.
    // ⚠️ 15 since Gate 4 added the try-booking sandbox. A page added without extending these sweeps is a
    // page nothing checks, so the count moves only when a gate says so.
    // ⚠️ 19 since Gate 7: + about, security, integrations, contact. The other six company pages are
    // REFUSED, not pending — careers and changelog because their subjects (a company with staff, a release
    // history) do not exist; demo-confirmed with D5; privacy and terms because drafting legal text is not a
    // build step. See docs/audits/FRONT-GATE7-DOC-TRUTH-AUDIT-2026-08-22.md. The pin moves with a ruling.
    Assert::assertCount(19, $views, 'the marketing page set changed: '.implode(', ', $views));

    return $views;
}

// ══ CONDITION 5 — a PROMINENT label, in the page's own voice, at the top ═══════════════════════════
it('⚠️ carries the illustrative label once, immediately after the h1, on every scenario page', function () {
    foreach (MK_SCENARIOS as $view) {
        $source = (string) file_get_contents(resource_path("views/marketing/{$view}.blade.php"));

        Assert::assertSame(1, substr_count($source, 'data-scenario-label'), "[{$view}] must carry exactly one scenario label");
        Assert::assertStringContainsString('An illustrative scenario, not a customer.', $source, "[{$view}] lost the label's own words");

        // ⚠️ PROMINENCE IS POSITIONAL, and a footnote does not satisfy the condition — so assert the label
        // sits after the FIRST </h1> and before the next section, not merely that it exists somewhere.
        $h1 = strpos($source, '</h1>');
        Assert::assertNotFalse($h1, "[{$view}] has no h1");
        $label = strpos($source, 'data-scenario-label');
        Assert::assertGreaterThan($h1, $label, "[{$view}]'s label is above the h1");
        Assert::assertLessThan(700, $label - $h1, "[{$view}]'s label has drifted away from the h1 — prominence is positional, and a footnote does not satisfy the ruling");
    }
});

// ══ CONDITION 2 — no attributed quotes, ANYWHERE on the marketing site ═════════════════════════════
it('⚠️ carries no attributed quote on ANY marketing page', function () {
    // ⚠️ SITE-WIDE, not scenario-only. The ruling was written about the case studies, but three LIVE Sales
    // pages were carrying the same fabricated testimonials — a quote from an invented person is fabricated
    // speech wherever it sits, and renaming the speaker only invents a different person.
    // ⚠️ THE PRODUCTION SHAPE, DELIBERATELY — and the distinction is the whole design, not a loophole.
    // Home's only quotes come from <x-marketing.testimonials>, which is LEDGER-GOVERNED: D1 keeps the
    // illustrative six for the build and filters every unconsented one in production, where this renders
    // ZERO. Content the ledger governs is guarded by the ledger; what must never happen is a quote
    // reaching a VISITOR. Quotes typed into markup are a different case and are caught at source below.
    foreach (mkAllMarketingViews() as $view) {
        $published = mkProdPage($view);
        // ⚠️ `<blockquote`, NOT `<figcaption`. The first version of this needle swept figcaption too and
        // fired on the home page's IMAGE-GALLERY captions ("Paros, Cyclades") — a photo caption is not
        // attributed speech. (Those captions are orphaned outside any <figure> in the DESIGN itself: 29
        // figcaptions against 24 figures. Faithfully reproduced, registered, not "fixed".)
        Assert::assertStringNotContainsString('<blockquote', $published,
            "[{$view}] PUBLISHES a quotation — attributed speech from a person who does not exist");
    }

    // …and no page may carry one in its own MARKUP, where no ledger can reach it. This is the check that
    // caught three LIVE Sales pages carrying fabricated testimonials, and it reads the source, not a render.
    foreach (mkAllMarketingViews() as $view) {
        $source = (string) file_get_contents(resource_path("views/marketing/{$view}.blade.php"));
        Assert::assertStringNotContainsString('<blockquote', $source,
            "[{$view}] has a quote typed into its markup — outside any ledger's reach");
    }
});

// ══ CONDITION 1 — no invented business identity survives ═══════════════════════════════════════════
it('⚠️ names none of the invented businesses or ledger people on any marketing page', function () {
    $ledger = collect(MarketingContent::customers(filtered: false))
        ->merge(MarketingContent::testimonials(filtered: false))
        ->pluck('name')->all();
    Assert::assertGreaterThanOrEqual(9, count($ledger), 'the ledger shrank — this sweep would silently check less');

    // The bare surnames too: "Kastellis" alone still reads as the business.
    // ⚠️ "Alfama" is deliberately NOT in this list, and the reason is a judgement worth recording: it is a
    // real Lisbon DISTRICT, and it survives only as an image-placeholder caption ("Alfama / Lisbon") on the
    // home page — condition 1 explicitly permits real place names as generic context. It is also the root
    // of the deleted business name "Hotel Alfama Reyes", so an adversarial reviewer flagged it; it is kept,
    // and surfaced to the founder, rather than silently swept or silently deleted.
    $fragments = array_merge($ledger, ['Kastellis', 'Van Dijk', 'Havenzicht', 'Casa Mirante']);

    foreach (mkAllMarketingViews() as $view) {
        $html = mkProdPage($view);   // ⚠️ the PRODUCTION shape — this is about what publishes
        foreach ($fragments as $name) {
            Assert::assertStringNotContainsString($name, $html, "[{$view}] publishes the invented identity \"{$name}\"");
        }
    }
});

// ══ CONDITION 3 — no quantified ACHIEVED outcome ═══════════════════════════════════════════════════
it('⚠️ renders no achieved-outcome counter on any marketing page', function () {
    foreach (mkAllMarketingViews() as $view) {
        $html = mkPage($view);
        Assert::assertStringNotContainsString('data-count=', $html,
            "[{$view}] renders an animated result counter — a measured outcome from a customer who does not exist");
    }
    // The specific figure the ruling named, in every form it appeared in.
    foreach (MK_SCENARIOS as $view) {
        $html = mkPage($view);
        foreach (['50%+', 'Share of nights arriving direct', '4 days'] as $claim) {
            Assert::assertStringNotContainsString($claim, $html, "[{$view}] kept the achieved-outcome claim \"{$claim}\"");
        }
    }
});

// ══ CONDITION 4 — no offer presupposing a real customer ════════════════════════════════════════════
it('⚠️ makes no offer that presupposes a real reference customer', function () {
    foreach (mkAllMarketingViews() as $view) {
        $html = mkPage($view);
        foreach (['talk to one of them', 'Ask for a reference', 'introduce you to an operator', 'Three operators who switched'] as $offer) {
            Assert::assertStringNotContainsStringIgnoringCase($offer, $html,
                "[{$view}] offers \"{$offer}\" — there is no illustrative version of introducing someone to a person who does not exist");
        }
    }
});

// ══ CONDITION 6 — not presented as a case study, INCLUDING in the chrome ═══════════════════════════
it('⚠️ presents this content as scenarios, in the page AND in the nav and footer that label it', function () {
    foreach (MK_SCENARIOS as $view) {
        $html = mkPage($view);
        // ⚠️ The label's own required wording contains "customer stories" ("has no published customer
        // stories yet"), so strip the label before sweeping — otherwise the guard fires on the very
        // sentence the ruling mandates. Scoping, not softening.
        $stripped = preg_replace('/<p[^>]*data-scenario-label.*?<\/p>/s', '', $html) ?? $html;

        foreach (['case study', 'case studies', 'customer story', 'customer stories'] as $phrase) {
            Assert::assertStringNotContainsStringIgnoringCase($phrase, strip_tags($stripped),
                "[{$view}] still presents itself as a \"{$phrase}\"");
        }
    }
});

// ══ §N.29 — the sweeps must be shown capable of FIRING ═════════════════════════════════════════════
it('PROVES the scenario sweeps can fail (the positive controls)', function () {
    // ⚠️ Four detectors, four poisoned inputs. A sweep never seen to fire is the vacuous-guard shape this
    // project has been bitten by three times; each of these was written after a real miss.
    $cases = [
        'quote' => ['<blockquote>They said a thing.</blockquote>', '<blockquote'],
        'identity' => ['<p>Kastellis Villas manages holiday houses.</p>', 'Kastellis'],
        'counter' => ['<span data-count="58" data-suffix="%">0</span>', 'data-count='],
        'offer' => ['<a>Want to talk to one of them?</a>', 'talk to one of them'],
    ];

    foreach ($cases as $name => [$poison, $needle]) {
        $failed = false;
        try {
            Assert::assertStringNotContainsStringIgnoringCase($needle, mkPage('story-lisbon').$poison);
        } catch (AssertionFailedError) {
            $failed = true;
        }
        expect($failed)->toBeTrue("the {$name} detector cannot fire — it proves nothing");
    }
});
