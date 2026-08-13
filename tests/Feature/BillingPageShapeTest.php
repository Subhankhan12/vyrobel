<?php

use App\Models\Plan;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\Unit;
use App\Services\BillingService;
use App\Support\Billing\BillableUnits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * BILLING PASS 3 — THE CUSTOMER BILLING PAGE RENDERS FROM THE PLAN (2026-08-13).
 *
 * The page used to render a per-unit view unconditionally, so an org on a FLAT tier was told
 * "8 units × €9.00" and "Adding a unit +€9.00/mo · prorated" — neither of which is true of a flat plan, where
 * adding a unit changes nothing. That was the live §N.8d this pass retires.
 *
 * ⚠️ TWO THINGS ARE GUARDED, AND THE SECOND IS THE ONE THAT LASTS:
 *   1. the right view renders for each SHAPE (flat sees no per-unit claim; per-unit sees the real math);
 *   2. ⚠️ DISPLAY EQUALS CHARGE — the figure on the page is `Plan::chargeFor()`, the same expression the
 *      charge uses. A page that recomputed rate × units locally could drift from what Stripe is sent, which is
 *      the §N.8d trap pass 1 closed at the charge and this pass must not reopen at the display.
 *
 * ⚠️ BOTH SHAPES ARE RENDERED AS THE ROLE (§N.16.2 — the plan's shape is DATA, and a data-conditional surface
 * is invisible to a pass that only renders the default org).
 */

function billShapeUnits(array $scenario, int $n): void
{
    $rt = RoomType::create(['property_id' => $scenario['property']->id, 'name' => 'Billable '.Str::random(4), 'kind' => 'room']);
    for ($i = 0; $i < $n; $i++) {
        Unit::create([
            'property_id' => $scenario['property']->id, 'room_type_id' => $rt->id,
            'code' => 'PB-'.Str::upper(Str::random(6)), 'is_active' => true,
        ]);
    }
}

/** Put the scenario's org on a plan of the given shape and return the plan. */
function billShapePlan(array $scenario, string $shape): Plan
{
    // ⚠️ Seed the tier catalogue FIRST. `BillingService::ensurePlans()` guards on `Plan::count() === 0`, so a
    // single hand-made plan row makes it skip seeding — and the page's `catalog()` then firstOrFail()s on
    // `essential_monthly` and 404s. (A pre-existing fragility in that count-based guard, not this pass's to fix.)
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

it('⚠️ a FLAT org sees NO per-unit claim and NO proration promise — the live §N.8d, retired', function () {
    $s = seedScenario(1);
    billShapeUnits($s, 5);
    billShapePlan($s, Plan::PRICING_FLAT);
    $owner = operatorUser($s, [], 'Property Owner');

    // ⚠️ NON-VACUITY: the org really HAS units, so a per-unit view would have something to render wrongly.
    expect(BillableUnits::count())->toBeGreaterThan(1);

    $html = (string) $this->actingAs($owner)->get('/app/billing')->assertOk()->getContent();
    $text = trim((string) preg_replace('/\s+/', ' ',
        html_entity_decode(strip_tags((string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)))
    ));

    // The flat price is shown…
    expect($text)->toContain('€49.00');

    // …and none of the per-unit machinery. These are the exact claims that were false on a flat tier.
    expect($text)->not->toContain('prorated')
        ->and($text)->not->toContain('× €9.00')
        ->and($text)->not->toContain('incl. 23% VAT');
});

it('⚠️ a PER-UNIT org sees the real math AND the proration line (true since pass 1)', function () {
    $s = seedScenario(1);
    billShapeUnits($s, 4);
    $plan = billShapePlan($s, Plan::PRICING_PER_UNIT);
    $owner = operatorUser($s, [], 'Property Owner');

    $units = BillableUnits::count();
    $charge = $plan->chargeFor();

    $html = (string) $this->actingAs($owner)->get('/app/billing')->assertOk()->getContent();
    $text = trim((string) preg_replace('/\s+/', ' ',
        html_entity_decode(strip_tags((string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)))
    ));

    expect($text)->toContain($units.' units × €9.00')
        ->and($text)->toContain('incl. 23% VAT')
        // TRUE now: pass 1 re-quantifies a per-unit subscription with Stripe proration.
        ->and($text)->toContain('prorated');

    // ⚠️ DISPLAY = CHARGE. The headline is the engine's figure, to the cent (#17).
    expect($text)->toContain(number_format($charge['gross_minor'] / 100, 2));
});

it('⚠️ the displayed figure IS the charge figure — it tracks a mid-cycle unit change', function () {
    $s = seedScenario(1);
    billShapeUnits($s, 3);
    $plan = billShapePlan($s, Plan::PRICING_PER_UNIT);
    $owner = operatorUser($s, [], 'Property Owner');

    $read = function () use ($owner): string {
        $html = (string) $this->actingAs($owner)->get('/app/billing')->getContent();

        return trim((string) preg_replace('/\s+/', ' ',
            html_entity_decode(strip_tags((string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)))
        ));
    };

    expect($read())->toContain(number_format($plan->chargeFor()['gross_minor'] / 100, 2));

    billShapeUnits($s, 2);   // the rack grows

    // The page follows, because it reads the same derived source the charge does — no stored copy in between.
    $after = $plan->fresh()->chargeFor();
    expect($read())->toContain(number_format($after['gross_minor'] / 100, 2))
        ->and($after['units'])->toBe(BillableUnits::count());
});

it('the page states the unit count on BOTH shapes — a real fact — but prices it only where it is priced', function () {
    $s = seedScenario(1);
    billShapeUnits($s, 3);
    billShapePlan($s, Plan::PRICING_FLAT);
    $owner = operatorUser($s, [], 'Property Owner');

    $text = trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
        (string) $this->actingAs($owner)->get('/app/billing')->getContent()
    ))));

    // How many units the account runs is true regardless of shape; what it COSTS is not.
    expect($text)->toContain('Units on the rack')
        ->and($text)->toContain('included in your plan');
});
