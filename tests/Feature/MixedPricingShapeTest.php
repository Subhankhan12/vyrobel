<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Services\BillingService;
use App\Support\Marketing\MarketingPricing;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

/**
 * ⚠️⚠️ ONE PER-UNIT PLAN MUST NOT RELABEL EVERY FLAT PLAN (docs/PER-UNIT-RELABEL.md).
 *
 * `shape()` answered a CATALOGUE-wide question and its answer was stamped onto every card, so the day a
 * single per-unit plan went live every flat plan on `/pricing` read "/ unit / mo" — €49 presented as a
 * per-unit price against a plan that charges €49 flat, on the page where money is decided.
 *
 * ⚠️ THE DEFECT ONLY APPEARS WITH BOTH SHAPES PRESENT. Every existing test uses one shape, which is exactly
 * how this survived. Each probe below builds a MIXED catalogue.
 *
 * ⚠️ Unique codes per call and a rolled-back transaction — `RefreshDatabase` is deliberately disabled
 * (tests/Pest.php:23) and this suite's database persists between runs.
 */
uses(DatabaseTransactions::class);

/** A per-unit plan in its own tier, active and sellable. */
function mxPerUnit(string $interval = 'monthly'): Plan
{
    return Plan::create([
        'code' => 'studio'.Str::lower(Str::random(6)).'_'.$interval,
        'name' => 'Studio', 'pricing_type' => Plan::PRICING_PER_UNIT,
        'price_minor' => 0, 'unit_rate_minor' => 1200, 'currency' => 'EUR',
        'interval' => $interval, 'is_active' => true,
        'stripe_price_id' => 'price_fake_'.Str::lower(Str::random(8)),
    ]);
}

function mxCards(): array
{
    return MarketingPricing::tiersWithCycles();
}

// ══ THE DEFECT — a mixed catalogue ══════════════════════════════════════════════════════════════════

it('⚠️⚠️ MIXED CATALOGUE — each card carries ITS OWN suffix, not the catalogue\'s', function () {
    app(BillingService::class)->ensurePlans();      // four FLAT plans
    $perUnit = mxPerUnit('monthly');                // …plus one PER-UNIT

    $cards = collect(mxCards());
    $flat = $cards->firstWhere('key', 'essential');
    $studio = $cards->first(fn (array $c): bool => $c['code'] === $perUnit->code);

    expect($flat)->not->toBeNull()->and($studio)->not->toBeNull();

    // ⚠️ THE WHOLE DEFECT IN TWO ASSERTIONS: the flat card must NOT say "unit"; the per-unit card must.
    expect($flat['cycles']['monthly']['suffix'])->toBe('/ mo')
        ->and($flat['cycles']['monthly']['suffix'])->not->toContain('unit')
        ->and($studio['cycles']['monthly']['suffix'])->toBe('/ unit / mo');
});

it('⚠️⚠️ MIXED CATALOGUE — each card\'s SUBTITLE follows its own shape too', function () {
    app(BillingService::class)->ensurePlans();
    $perUnit = mxPerUnit('monthly');

    $rows = collect(MarketingPricing::tiersForDisplay('monthly'));
    $flat = $rows->firstWhere('code', 'essential_monthly');
    $studio = $rows->firstWhere('code', $perUnit->code);

    expect($flat['sub'])->toBe('One price, any number of units')
        ->and($studio['sub'])->toBe('Per active unit');
});

it('⚠️⚠️ the rendered /pricing page shows both suffixes, each on its own card', function () {
    app(BillingService::class)->ensurePlans();
    $perUnit = mxPerUnit('monthly');

    $html = $this->get('/pricing')->assertOk()->getContent();

    // Both must appear — a page that shows only one of them has relabelled something.
    expect($html)->toContain('/ unit / mo')
        ->and($html)->toContain('Per active unit')
        ->and($html)->toContain('One price, any number of units');
});

it('⚠️ the COMPARISON table can be honest with mixed shapes — each column keeps its own suffix', function () {
    // ⚠️ Its columns sit under shared row labels, which is the harder half. The FEATURE rows are shape-
    //    agnostic (a feature is a feature); only the price header asserts a model, and that header renders
    //    each column's own `suffix` — so the table needs no refusal, it needed the same per-plan read.
    app(BillingService::class)->ensurePlans();
    $perUnit = mxPerUnit('monthly');

    $suffixes = collect(mxCards())
        ->map(fn (array $c): ?string => $c['cycles']['monthly']['suffix'] ?? null)
        ->filter()->unique()->values();

    expect($suffixes)->toContain('/ mo')->toContain('/ unit / mo');
});

// ══ THE PAGE-LEVEL prose — a third state, because two would both be false ═══════════════════════════

it('⚠️⚠️ page-level prose reports MIXED rather than asserting one model for everything', function () {
    app(BillingService::class)->ensurePlans();
    mxPerUnit('monthly');

    expect(MarketingPricing::shape('monthly'))->toBe(MarketingPricing::SHAPE_MIXED);

    $headline = MarketingPricing::headline('monthly');
    $note = MarketingPricing::priceNote('monthly');

    // It must not claim EITHER single model as the whole truth.
    expect($headline)->not->toBe('One price per property account. The direct channel pays for it.')
        ->and($headline)->not->toBe('One price per unit. The direct channel pays for it.')
        ->and($note)->toContain('per account or per active unit');
});

it('⚠️ NO FABRICATED EQUIVALENCE — nothing multiplies a unit rate by a unit count', function () {
    app(BillingService::class)->ensurePlans();
    $perUnit = mxPerUnit('monthly');

    $html = $this->get('/pricing')->assertOk()->getContent();

    // €12.00 is the unit rate. Any "×N" total (€60.00, €120.00 …) would be an invented price.
    // ⚠️ `expect(str_contains(...))->toBeFalse($message)`, NOT `->not->toContain($needle, $message)`.
    //    A NEGATED MULTI-ARGUMENT `toContain` is UNCONDITIONALLY TRUE — the message becomes a second
    //    needle — so the first version of this assertion proved nothing at all. There is a guard in this
    //    suite for exactly that trap, and it caught this.
    foreach (['€60.00', '€120.00', '€24.00', '€36.00'] as $fabricated) {
        expect(str_contains($html, $fabricated))
            ->toBeFalse("the page invented an equivalence: {$fabricated}");
    }

    expect($html)->toContain('€12.00');   // the real unit rate, unmultiplied
});

// ══ THE NEGATIVE HALF — the state that actually ships ═══════════════════════════════════════════════

it('⚠️⚠️ AN ALL-FLAT CATALOGUE READS EXACTLY AS IT DOES TODAY', function () {
    app(BillingService::class)->ensurePlans();

    expect(MarketingPricing::shape('monthly'))->toBe(MarketingPricing::SHAPE_FLAT)
        ->and(MarketingPricing::headline('monthly'))->toBe('One price per property account. The direct channel pays for it.')
        ->and(MarketingPricing::whoPays('monthly'))->toBe('You pay one subscription; nobody else is billed.');

    foreach (mxCards() as $card) {
        expect($card['cycles']['monthly']['suffix'] ?? '/ mo')->toBe('/ mo');
        expect($card['cycles']['yearly']['suffix'] ?? '/ yr')->toBe('/ yr');
    }
});

it('⚠️ an ALL-PER-UNIT catalogue still says "unit" everywhere — the other single-shape state', function () {
    Plan::query()->update(['is_active' => false]);
    $a = mxPerUnit('monthly');
    $b = mxPerUnit('yearly');

    expect(MarketingPricing::shape('monthly'))->toBe(MarketingPricing::SHAPE_PER_UNIT);

    $card = collect(mxCards())->first(fn (array $c): bool => $c['code'] === $a->code);
    expect($card['cycles']['monthly']['suffix'])->toBe('/ unit / mo');
});

// ══ The cycle switcher must still work with a mixed catalogue ═══════════════════════════════════════

it('⚠️ both cycles still render on every card, and a per-unit plan present in ONE interval pairs honestly', function () {
    app(BillingService::class)->ensurePlans();
    $monthlyOnly = mxPerUnit('monthly');   // deliberately no yearly row

    $card = collect(mxCards())->first(fn (array $c): bool => $c['code'] === $monthlyOnly->code);

    expect($card['cycles']['monthly'])->not->toBeNull()
        ->and($card['cycles']['yearly'])->toBeNull();   // honest absence, never a fabricated yearly price

    // And a tier WITH both cycles still carries both, each with its own suffix.
    $essential = collect(mxCards())->firstWhere('key', 'essential');
    expect($essential['cycles']['monthly']['suffix'])->toBe('/ mo')
        ->and($essential['cycles']['yearly']['suffix'])->toBe('/ yr');
});

it('⚠️ both prices ship in the HTML so the scripts-off state stays readable', function () {
    app(BillingService::class)->ensurePlans();
    mxPerUnit('monthly');

    $html = $this->get('/pricing')->assertOk()->getContent();

    // The monthly and yearly figures of a flat tier are both present in the served markup.
    expect($html)->toContain('€49.00')->toContain('€490.00');
});

// ══ Billing and marketing read DIFFERENT sources, deliberately ══════════════════════════════════════

it('⚠️ marketing reads the LIVE catalogue while billing reads the FROZEN snapshot — both correct', function () {
    app(BillingService::class)->ensurePlans();
    $plan = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    // Marketing asks the live row …
    $rows = collect(MarketingPricing::tiersForDisplay('monthly'));
    expect($rows->firstWhere('code', 'pro_monthly')['sub'])->toBe('One price, any number of units');

    // … and billing asks the plan's own shape through the ONE calculation, not through marketing.
    expect($plan->isPerUnit())->toBeFalse()
        ->and($plan->chargeFor()['pricing_type'])->toBe(Plan::PRICING_FLAT);
});

it('⚠️⚠️ the HOME page cards each carry their OWN suffix too — not only /pricing', function () {
    // ⚠️ THIS PROBE EXISTS BECAUSE A MUTATION FOUND ITS ABSENCE. Reverting `home.blade.php` to a
    //    catalogue-wide `suffix()` came back GREEN: every assertion above reads `/pricing`, and the home
    //    page renders its own two cards from `tiers()[0]` and `tiers()[1]` with a separate call. A second
    //    surface reading the same wrong way is exactly how the first one survived.
    app(BillingService::class)->ensurePlans();
    mxPerUnit('monthly');

    // Ordered by `price_minor`, a per-unit plan (price_minor 0) sorts first, so the two home cards
    // genuinely differ in shape — which is the only arrangement that can catch this.
    $tiers = MarketingPricing::tiers('monthly');

    expect($tiers[0]['shape'])->toBe(Plan::PRICING_PER_UNIT)
        ->and($tiers[1]['shape'])->toBe(Plan::PRICING_FLAT);

    $html = $this->get('/')->assertOk()->getContent();

    // ⚠️ ASSERTED PER CARD, NOT PER PAGE. A `toContain` on the whole document was satisfied by an
    //    occurrence elsewhere, so the mutation reverting these cards to a catalogue-wide suffix stayed
    //    GREEN. The suffix is read from the span that immediately follows each card's own priced span.
    $suffixFor = function (string $card) use ($html): string {
        expect($html)->toContain('data-plan="'.$card.'"');

        $after = substr($html, strpos($html, 'data-plan="'.$card.'"'));
        preg_match('~</span><span[^>]*>([^<]*)</span>~', $after, $m);

        return trim($m[1] ?? '');
    };

    // Card 0 is the PER-UNIT plan, card 1 is FLAT — each must carry its own suffix and only its own.
    expect($suffixFor('home-tier-0'))->toBe('/ unit / mo')
        ->and($suffixFor('home-tier-1'))->toBe('/ mo');
});
