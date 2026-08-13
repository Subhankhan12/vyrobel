<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * DO-BILL-2 — THE ADD-PROPERTY MODAL'S BILLING CLAIM IS KEYED BY THE PLAN'S SHAPE (2026-08-13).
 *
 * The modal told every org "It joins your organization — billed per unit." That is false on a FLAT tier, where
 * adding a property changes the bill by nothing — and the demo org is flat, so it was live-false on the default
 * demo. It is the same §N.8d billing pass 3 retired on /app/billing, one page over (§N.17.4: the per-unit claim
 * RECURS across surfaces).
 *
 * ⚠️ TWO THINGS MAKE THESE GUARDS NON-VACUOUS, AND BOTH WERE THE ORIGINAL BUG'S HIDING PLACES:
 *   1. ⚠️ THE ROLE. The modal is inside `@if ($canManage)` — `properties.manage`. A test acting as a role
 *      without it renders a page where the markup is ABSENT, so every "no false claim" assertion passes for
 *      the wrong reason (§N.14.1). Each test below asserts the modal is PRESENT before judging its copy.
 *   2. ⚠️ THE SHAPE. The plan's shape is DATA (§N.16.2), so a single-shape test can only ever see half the
 *      behaviour. Both shapes are rendered here.
 */

/** Put the tenant org on a plan of the given shape. */
function portfolioPlan(array $scenario, string $shape): Plan
{
    // Seed the tier catalogue first — `ensurePlans()` guards on `Plan::count() === 0`.
    app(BillingService::class)->ensurePlans();

    $plan = $shape === Plan::PRICING_PER_UNIT
        ? Plan::create([
            'code' => 'metered_monthly', 'name' => 'Metered', 'price_minor' => 0,
            'pricing_type' => Plan::PRICING_PER_UNIT, 'unit_rate_minor' => 900, 'vat_pct' => 23,
            'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true,
            'stripe_price_id' => 'price_fake_metered_monthly',
        ])
        : Plan::create([
            'code' => 'flatty_monthly', 'name' => 'Flatty', 'price_minor' => 4900,
            'pricing_type' => Plan::PRICING_FLAT, 'currency' => 'EUR', 'interval' => 'monthly',
            'is_active' => true, 'stripe_price_id' => 'price_fake_flatty_monthly',
        ]);

    Subscription::create([
        'organization_id' => $scenario['org']->getKey(),
        'plan_id' => $plan->getKey(),
        'status' => 'active',
        'current_period_end' => now()->addMonth(),
    ]);

    return $plan;
}

/** The Portfolio page as a manager sees it, tags stripped. */
function portfolioText(object $test, array $scenario): string
{
    // ⚠️ `properties.manage` is what gates the modal — render as a role that HOLDS it, or the assertions below
    // are satisfied by absent markup rather than by correct markup.
    $manager = operatorUser($scenario, [], 'Property Owner');

    $html = (string) $test->actingAs($manager)->get('/app/portfolio')->assertOk()->getContent();

    return trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
        (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)
    ))));
}

it('⚠️ a FLAT org is NOT told its new property is "billed per unit" — the live §N.8d, retired', function () {
    $s = seedScenario(1);
    portfolioPlan($s, Plan::PRICING_FLAT);

    $text = portfolioText($this, $s);

    // ⚠️ NON-VACUITY: the modal must actually be on the page. Without this, a role that cannot see it (or a
    // renamed modal) would make the absence assertion below pass while proving nothing.
    expect($text)->toContain('Add a property')
        ->and($text)->toContain('It joins your organization');

    // The claim that was false: adding a property to Essential/Pro changes the bill by nothing.
    expect($text)->not->toContain('billed per unit');

    // …and the truthful alternative is what renders instead — matching the Billing page's own framing.
    expect($text)->toContain('included in your plan');
});

it('⚠️ a PER-UNIT org IS told "billed per unit" — because for them it is true', function () {
    $s = seedScenario(1);
    portfolioPlan($s, Plan::PRICING_PER_UNIT);

    $text = portfolioText($this, $s);

    expect($text)->toContain('Add a property')
        ->and($text)->toContain('billed per unit')
        // The flat wording must not also appear — the branch is exclusive, not additive.
        ->and($text)->not->toContain('included in your plan');
});

it('an org with NO plan makes NO billing claim at all — the honest rendering', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();   // a catalogue exists, but this org is on none of it

    $text = portfolioText($this, $s);

    // ⚠️ NON-VACUITY: the modal is present, so this is a statement about its copy, not about a missing page.
    expect($text)->toContain('It joins your organization')
        ->and($text)->not->toContain('billed per unit');
});

it('⚠️ the guard would be VACUOUS without the role — a non-manager cannot see the modal at all', function () {
    $s = seedScenario(1);
    portfolioPlan($s, Plan::PRICING_PER_UNIT);

    // Front Desk lacks `properties.manage`. This documents the exact trap that let DO-BILL-2 survive the design
    // walk: rendered as this role there is no modal to judge — here the page is refused outright, which makes
    // the point even more sharply. Either way, "no false claim" would be true for the wrong reason.
    $clerk = operatorUser($s, [], 'Front Desk');
    $response = $this->actingAs($clerk)->get('/app/portfolio');

    expect($response->status())->toBe(403)
        ->and($response->getContent())->not->toContain('It joins your organization');
});
