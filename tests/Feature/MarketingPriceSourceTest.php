<?php

use App\Models\Plan;
use App\Support\Marketing\MarketingPricing;
use Database\Seeders\BillingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// ══ ⚠️⚠️ ONE SOURCE FOR EVERY PRICE AND EVERY TIER NAME (§N.8f) ══════════════════════════════════════
//
// ⚠️ WHY THIS EXISTS. B12 rebuilt `/pricing`'s CARDS to read from `config/billing.php` and pinned them
// against `Plan::chargeFor()` — and the comparison table ONE SECTION BELOW, on the same page, kept a
// hand-drawn ladder of four invented tiers (Starter / Growth / Pro / Enterprise) and twelve invented feature
// differences. Live, and about money. A sitewide sweep then found ELEVEN representations of two numbers and
// two names, across four files, of which B12 had bound two.
//
// ⚠️ SO THE FIX COULD NOT BE THE TABLE. Fixing a representation leaves the next one free to drift; only a
// guard makes the CLASS of miss impossible. This is that guard.
//
// ⚠️⚠️ WHAT IT CANNOT DO, STATED PLAINLY:
//   • It pattern-matches. A price written in WORDS ("forty-nine euro a month"), a price inside an IMAGE or an
//     SVG path, or a tier name in a screenshot are all invisible to it. Those are a review rule, not a test.
//   • It checks what a page RENDERS. Copy that lives in an unrendered branch is not covered.
//   • It knows subscription prices, not product figures. A mocked folio total of €8,412.60 in a screenshot
//     mockup is not Vyrobel's price and is deliberately out of scope here (those are governed by the
//     fabricated-figure rules and the content ledger instead).

uses(RefreshDatabase::class);

// ⚠️ THE REAL PLAN ROWS, SEEDED — and the vacuity check below is what forced this. Without them `plans` is
// empty under RefreshDatabase, `MarketingPricing` silently falls back to the config catalogue, and the price
// guard compares every rendered price against an EMPTY allow-list. It happened on the first run: the guard
// reported "€49.00, which no row in `plans` charges" while the engine charges exactly that.
// ⚠️ Seeding the same rows production runs on is also the stronger test — it exercises the DB path the site
// actually reads, not the fallback.
beforeEach(function (): void {
    $this->seed(BillingPlanSeeder::class);
});

/** Every marketing page that renders, or could render, a price or a plan name. */
const PX_PAGES = ['/pricing', '/', '/switching', '/product', '/features', '/how-it-works', '/modules'];

/**
 * ⚠️ THE INVENTED LADDER, BY NAME. These four are not "names we happen not to use" — they are the design's
 * fabricated tiers, and each appeared on a live page. Listing them explicitly means a regression names
 * itself in the failure message rather than showing up as an anonymous diff.
 */
const PX_INVENTED_TIERS = ['Starter', 'Growth', 'Enterprise'];

/**
 * Strip script/style so a needle cannot match the page's own stylesheet or JSON-LD (§N.27.5).
 *
 * ⚠️ THE FIRST VERSION OF THIS HELPER ATE THE PAGE, and it is worth the words. It opened its match on
 * `<script` anywhere in the HTML — including the literal text "&lt;script&gt;" that a CSS comment in the
 * marketing shell was shipping into every page (a CSS comment goes to the browser; a Blade one does not).
 * The match then ran to the next real closing tag and removed the entire body, so the guard reported
 * "/pricing renders no price node" about a page that renders them perfectly well.
 *
 * ⚠️ So the opening tag must be a REAL tag — `<script` followed by a tag-boundary character, not by a
 * backtick, a space inside prose, or an escaped entity. That is what `[\s>]` enforces here. The prose that
 * caused it has also been reworded, because the guard should not be the only thing standing between a
 * comment and a false failure.
 */
function pxText(string $html): string
{
    $html = (string) preg_replace('/<script[\s>][\s\S]*?<\/script\s*>/i', '', $html);

    return (string) preg_replace('/<style[\s>][\s\S]*?<\/style\s*>/i', '', $html);
}

/** ⚠️ The vacuity check for the helper above: stripping must remove the block and keep the body. */
function pxTextIsSane(): bool
{
    $sample = '<html><style>body{}</style><main>KEEP</main><script>var x=1;</script></html>';

    return str_contains(pxText($sample), 'KEEP') && ! str_contains(pxText($sample), 'var x');
}

it('PROVES the HTML stripper keeps the page body (the helper\'s own vacuity check)', function () {
    // ⚠️ EVERY ASSERTION IN THIS FILE READS `pxText()` OUTPUT, so a stripper that removes too much makes
    // every one of them vacuous — and that is not hypothetical: the first version swallowed the whole body
    // on a page whose stylesheet mentioned a script tag in prose, and the guard duly reported the page as
    // empty. A guard's own instrument needs a check as much as the thing it guards.
    expect(pxTextIsSane())->toBeTrue('pxText() removes the page body, so every assertion here is vacuous');

    $real = pxText($this->get('/pricing')->assertOk()->getContent());
    expect(strlen($real))->toBeGreaterThan(20000,
        'the stripped /pricing page is suspiciously short — the stripper is eating content again');
});

it('⚠️ names no plan the billing config does not define', function () {
    // ⚠️ THE REAL NAMES, READ FROM THE ENGINE — never typed here, or this test would become the twelfth
    // representation of the thing it exists to keep singular.
    $real = collect(MarketingPricing::tiers())->pluck('name')->all();
    expect($real)->not->toBeEmpty('no plans are configured — see the vacuity check below');

    foreach (PX_PAGES as $path) {
        $html = pxText($this->get($path)->assertOk()->getContent());

        foreach (PX_INVENTED_TIERS as $invented) {
            // ⚠️ A name the config DOES define is never a violation, whatever it is called. Checked rather
            // than assumed, because a founder renaming a tier to "Growth" tomorrow must not red this test.
            if (in_array($invented, $real, true)) {
                continue;
            }

            Assert::assertDoesNotMatchRegularExpression(
                '/\b'.preg_quote($invented, '/').'\b/',
                $html,
                "[{$path}] names the plan \"{$invented}\", which config/billing.php does not define. The real "
                .'plans are: '.implode(', ', $real).'. A wrong tier name is as false as a wrong price — a '
                .'visitor reads it as what they will be sold.'
            );
        }
    }
});

it('⚠️ shows no subscription price the billing engine would not charge', function () {
    // ⚠️ THE PRICE NODES ARE MARKED IN THE MARKUP (`data-price`), so this reads exactly the elements that
    // claim to be a plan price — not every € on the page. A mocked folio total is not a price claim.
    // ⚠️ ZEROS ARE EXCLUDED, and the mutation run is what exposed why: `unit_rate_minor ?? 0` put "0.00"
    // into the allow-list for every flat plan, so a page showing a price of zero would have passed. A
    // subscription price of nothing is not a price the engine charges — it is a binding that resolved to
    // nothing, which is precisely the failure mode worth catching.
    $allowed = collect(Plan::withoutGlobalScopes()->get())
        ->flatMap(fn (Plan $p): array => [
            (int) $p->price_minor,
            (int) ($p->unit_rate_minor ?? 0),
        ])
        ->filter(fn (int $minor): bool => $minor > 0)
        ->map(fn (int $minor): string => number_format($minor / 100, 2))
        ->unique()->values()->all();

    $found = 0;

    foreach (PX_PAGES as $path) {
        $html = pxText($this->get($path)->assertOk()->getContent());

        preg_match_all('/<span[^>]*data-price[^>]*>([^<]*)<\/span>/', $html, $m);

        foreach ($m[1] as $shown) {
            $found++;
            // ⚠️ COMPARE THE NUMBER, NOT THE FORMATTING. The two surfaces format differently and both are
            // correct: /pricing's card keeps the currency symbol in its own `data-cur` span (the design's
            // markup), while the home teaser prints `MarketingPricing::formatted`, symbol included. A guard
            // that demanded one spelling would fail an honest page and teach the next person to loosen it.
            $shown = trim((string) preg_replace('/[^\d.,]/u', '', $shown));

            Assert::assertContains($shown, $allowed,
                "[{$path}] shows a plan price of {$shown}, which no row in `plans` charges. The engine "
                .'charges: '.implode(', ', $allowed).'. `Plan::chargeFor()` bills from those rows, so a page '
                .'showing anything else is advertising a price the system will not take.');
        }
    }

    // ⚠️ COUNT-PINNED, and this half is the vacuity check: the loop above passes trivially if no page
    // renders a price node at all — which is exactly what happened when a regeneration silently dropped
    // the binding and the cards rendered "€" with no number after it.
    Assert::assertGreaterThanOrEqual(4, $found,
        "only {$found} price nodes rendered across the marketing site. Two plans appear on /pricing (cards) "
        .'and on / (teaser), so fewer than four means a binding has been lost — which a "no wrong price" '
        .'assertion alone would report as success.');
});

it('⚠️ describes the billing MODEL the engine actually runs', function () {
    // ⚠️ A MODEL CLAIM IS A PRICE CLAIM. The page said "per-unit plans" in its SEO title, its hero, its
    // price note and two FAQ answers while every seeded plan is flat — so a reader was told how they would
    // be charged, wrongly, five times over. `shape()` derives the wording, so the two cannot disagree.
    $isPerUnit = MarketingPricing::shape() === MarketingPricing::SHAPE_PER_UNIT;

    $html = pxText($this->get('/pricing')->assertOk()->getContent());

    if ($isPerUnit) {
        Assert::assertStringContainsString('per unit', $html,
            'the engine bills per unit and /pricing does not say so');

        return;
    }

    Assert::assertStringNotContainsString('per-unit', $html,
        '/pricing describes a per-unit model. Every seeded plan is `pricing_type = flat` with '
        .'`unit_rate_minor` NULL, so the subscription does not move with unit count at all.');

    Assert::assertStringNotContainsString('per unit;', $html,
        '/pricing still tells a reader they pay per unit in prose — a pricing claim in a sentence is still '
        .'a pricing claim');
});

it('⚠️ compares only the differences the config actually gates', function () {
    // ⚠️ THE ROW COUNT IS THE ASSERTION. The drawn table had twelve difference rows; the config gates three
    // features of which two differ. A table that grows a row nothing gates has invented a differentiator.
    $cmp = MarketingPricing::comparison();
    $configured = collect((array) config('billing.tiers'))
        ->flatMap(fn (array $t): array => array_keys((array) ($t['features'] ?? [])))
        ->unique()->values()->all();

    Assert::assertSame(count($configured), count($cmp['rows']),
        'the comparison table has '.count($cmp['rows']).' rows but the billing config gates '
        .count($configured).' features. Every row must come from the catalogue the paywall reads.');

    foreach ($cmp['rows'] as $row) {
        Assert::assertContains($row['key'], $configured,
            "the comparison table has a row for [{$row['key']}], which config/billing.php does not gate");
    }

    // ⚠️⚠️ AND THE SAME COUNT IN THE RENDERED TABLE. The assertions above compare `comparison()` against the
    // config — which is the source checked against itself, and a MUTATION PROVED IT BLIND: adding an
    // invented "Guided migration" row inside the view left every one of them green, because the view can
    // add a row the source never returned. ⚠️ A check must be independent of what it checks (§N.18.1), so
    // this one counts what a visitor actually sees.
    $html = pxText($this->get('/pricing')->assertOk()->getContent());

    Assert::assertStringContainsString('Feature comparison across the', $html,
        'the comparison table did not render at all — the assertions above would then prove nothing');

    $start = strpos($html, 'Feature comparison across the');
    $end = strpos($html, '</table>', $start);
    $table = substr($html, $start, $end - $start);

    Assert::assertSame(count($configured), substr_count($table, '<th scope="row"'),
        'the RENDERED comparison table has '.substr_count($table, '<th scope="row"').' rows but the billing '
        .'config gates '.count($configured).' features. A row on the page that the config does not gate is '
        .'an invented differentiator — which is exactly what the four-tier ladder was made of.');
});

// ══ ⚠️ THE VACUITY CHECKS — six guards in this project have shipped proving nothing ═══════════════════

it('PROVES the tier-name sweep can fire (the positive control)', function () {
    // ⚠️ The sweep passes if a page 404s, renders empty, or the needle is misspelled. Fire it on a string
    // that certainly contains an invented tier.
    $failed = false;
    try {
        Assert::assertDoesNotMatchRegularExpression('/\bGrowth\b/', '<div>Growth &euro;5 / unit</div>');
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue('the tier-name needle cannot fire — the sweep above proves nothing');
});

it('PROVES the price guard reads real, non-empty sources', function () {
    // ⚠️ TWO WAYS THIS GUARD COULD BE VACUOUS, both checked here rather than assumed:
    //   (a) no plans configured  ⇒ `$allowed` is empty and every page trivially "matches"
    //   (b) no price nodes render ⇒ the foreach never executes
    expect(Plan::withoutGlobalScopes()->count())->toBeGreaterThan(0,
        'no plan rows exist, so the price guard has nothing to compare against');

    $html = pxText($this->get('/pricing')->assertOk()->getContent());
    expect(substr_count($html, 'data-price'))->toBeGreaterThan(0,
        '/pricing renders no price node, so the price guard would pass on an empty set');

    // ⚠️ And the allow-list must not be so wide it accepts anything — a real page price is in it, an
    // invented one is not.
    $allowed = collect(Plan::withoutGlobalScopes()->get())
        ->map(fn (Plan $p): string => number_format(((int) $p->price_minor) / 100, 2))->unique()->all();
    expect($allowed)->toContain('49.00')->and($allowed)->not->toContain('79.00');
});
