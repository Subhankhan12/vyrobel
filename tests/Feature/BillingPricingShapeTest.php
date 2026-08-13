<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\Unit;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\BillingService;
use App\Support\Billing\BillableUnits;
use App\Support\Billing\SubscriptionPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * BILLING PASS 1 — THE SHAPE-AWARE QUANTITY ENGINE (2026-08-13).
 *
 * The founder ruled that pricing is configurable DATA supporting BOTH shapes, and that a per-unit plan prorates
 * immediately. This file guards the engine that makes those two rulings real.
 *
 * ⚠️ EVERY ASSERTION RUNS AGAINST THE FAKE STRIPE CLIENT. What is checked is the quantity, price and proration
 * flag that WOULD reach Stripe — never a live charge. That is not a weaker proof: the bug being guarded
 * (`'quantity' => 1` hardcoded) lives entirely in what we send, so the send is exactly the right thing to assert.
 *
 * ⚠️ THE LOAD-BEARING ONE IS "DERIVED, NOT STORED". A per-unit charge is a claim about how many units the org
 * has. If that number were stored it would be right until the next unit was added and then quietly wrong — the
 * §N.8d false-claim shape, with money attached.
 */

/** A per-unit plan (not in the config tier catalogue, so it never reaches the customer paywall). */
function perUnitPlan(int $rateMinor = 900, int $vatPct = 23): Plan
{
    return Plan::create([
        'code' => 'metered_monthly',
        'name' => 'Metered',
        'price_minor' => 0,
        'pricing_type' => Plan::PRICING_PER_UNIT,
        'unit_rate_minor' => $rateMinor,
        'vat_pct' => $vatPct,
        'currency' => 'EUR',
        'interval' => 'monthly',
        'is_active' => true,
        'stripe_price_id' => 'price_fake_metered_monthly',
    ]);
}

function flatPlan(int $priceMinor = 4900): Plan
{
    return Plan::create([
        'code' => 'flatty_monthly',
        'name' => 'Flatty',
        'price_minor' => $priceMinor,
        'pricing_type' => Plan::PRICING_FLAT,
        'currency' => 'EUR',
        'interval' => 'monthly',
        'is_active' => true,
        'stripe_price_id' => 'price_fake_flatty_monthly',
    ]);
}

/** N active units on the scenario's property. */
function withActiveUnits(array $scenario, int $n): void
{
    $rt = RoomType::create(['property_id' => $scenario['property']->id, 'name' => 'Billable '.Str::random(4), 'kind' => 'room']);
    for ($i = 0; $i < $n; $i++) {
        // A unique code per call — `units` is uniquely indexed on (property_id, code), and these tests
        // deliberately add units TWICE to prove the quantity tracks a mid-cycle change.
        Unit::create(['property_id' => $scenario['property']->id, 'room_type_id' => $rt->id, 'code' => 'BU-'.Str::upper(Str::random(6)), 'is_active' => true]);
    }
}

it('a FLAT plan charges quantity 1 — never the unit count', function () {
    $s = seedScenario(1);
    withActiveUnits($s, 6);

    $plan = flatPlan(4900);

    expect($plan->quantityFor())->toBe(1)
        ->and($plan->chargeFor()['gross_minor'])->toBe(4900);

    // ⚠️ Non-vacuity: there really ARE units to get wrong. If quantity followed the rack this would be 7, not 1.
    expect(BillableUnits::count())->toBeGreaterThan(1);
});

it('⚠️ a PER-UNIT plan charges the org’s REAL unit count × rate (pre-tax)', function () {
    $s = seedScenario(1);
    withActiveUnits($s, 5);          // + the scenario's own unit = 6 active

    $units = BillableUnits::count();
    $plan = perUnitPlan(900, 23);

    // ⚠️ G8 — AMENDED BY BILLING PASS 2b, AND ONLY THE TAX HALF. The invariant this test exists for is that the
    // charge is the org's REAL unit count × the plan's rate, derived and never stored — that is untouched and
    // still asserted below. What is dropped is the "+ 23% VAT": under Stripe Tax the rate is the customer's own
    // and an EU business under reverse charge pays €0, so the app no longer asserts a post-tax total it cannot
    // know. The figure is now the PRE-TAX subtotal, which is exactly what reaches Stripe.
    $charge = $plan->chargeFor();
    expect($plan->quantityFor())->toBe($units)
        ->and($charge['units'])->toBe($units)
        ->and($charge['net_minor'])->toBe($units * 900)
        ->and($charge['gross_minor'])->toBe($units * 900)
        // ⚠️ NON-VACUITY: the plan still carries vat_pct = 23, so a regression that re-added it would be caught
        // here rather than silently passing.
        ->and((int) $plan->vat_pct)->toBe(23)
        ->and($charge['vat_minor'])->toBe(0);
});

it('⚠️ the quantity is DERIVED, not stored — add a unit and the charge follows (§N.8d)', function () {
    $s = seedScenario(1);
    withActiveUnits($s, 3);
    $plan = perUnitPlan();

    $before = $plan->quantityFor();
    $beforeGross = $plan->chargeFor()['gross_minor'];

    withActiveUnits($s, 2);          // the org grows mid-cycle

    // Same plan object, no reload, no stored count — the charge tracks reality because it is recomputed.
    expect($plan->quantityFor())->toBe($before + 2)
        ->and($plan->chargeFor()['gross_minor'])->toBeGreaterThan($beforeGross);

    // …and deactivating a unit takes it off the bill: an operator who pauses inventory is not charged for it.
    Unit::query()->where('is_active', true)->first()?->update(['is_active' => false]);
    expect($plan->quantityFor())->toBe($before + 1);
});

it('⚠️ the DISPLAY figure and the CHARGE quantity come from the same source', function () {
    $s = seedScenario(1);
    withActiveUnits($s, 4);
    $plan = perUnitPlan(900, 23);

    // SubscriptionPricing is what the Billing page shows; Plan::quantityFor is what Stripe is sent. If these
    // two ever diverge, one of them is lying to the customer — which is the whole reason BillableUnits exists.
    expect(SubscriptionPricing::current('monthly')['units'])->toBe($plan->quantityFor())
        ->and(SubscriptionPricing::current('monthly')['gross_minor'])->toBe($plan->chargeFor()['gross_minor']);
});

it('⚠️ checkout sends the right quantity + proration per shape (against the FAKE client, no live charge)', function () {
    $s = seedScenario(1);
    withActiveUnits($s, 5);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $org->update(['billing_email' => 'ops@example.test']);

    $fake = app(StripeClientInterface::class);
    expect($fake)->toBeInstanceOf(FakeStripeClient::class);   // non-vacuity: we are on the fake rail

    $billing = app(BillingService::class);
    $units = BillableUnits::count();

    // PER-UNIT → the real count, proration ON.
    perUnitPlan();
    $billing->startCheckout($org, 'metered', 'monthly');
    $session = collect($fake->checkoutSessions)->last();
    expect($session['quantity'])->toBe($units)
        ->and($session['prorate'])->toBeTrue()
        ->and($session['price'])->toBe('price_fake_metered_monthly');

    // FLAT → 1, proration off (a flat price has nothing to prorate).
    flatPlan();
    $billing->startCheckout($org, 'flatty', 'monthly');
    $flat = collect($fake->checkoutSessions)->last();
    expect($flat['quantity'])->toBe(1)
        ->and($flat['prorate'])->toBeFalse();
});

it('⚠️ a mid-cycle unit change re-quantifies a live per-unit subscription, PRORATED', function () {
    $s = seedScenario(1);
    withActiveUnits($s, 3);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $plan = perUnitPlan();

    Subscription::create([
        'organization_id' => $org->getKey(),
        'plan_id' => $plan->getKey(),
        'status' => 'active',
        'stripe_subscription_id' => 'sub_fake_live',
    ]);

    withActiveUnits($s, 2);   // the org adds two units mid-cycle
    $expected = BillableUnits::count();

    $fake = app(StripeClientInterface::class);
    $applied = app(BillingService::class)->syncSubscriptionQuantity($org);

    expect($applied)->toBe($expected);
    $update = collect($fake->quantityUpdates)->last();
    expect($update['subscription'])->toBe('sub_fake_live')
        ->and($update['quantity'])->toBe($expected)
        // The ruled behaviour: charged now, not at renewal.
        ->and($update['prorate'])->toBeTrue();
});

it('a FLAT subscription is never re-quantified — its price does not move with the rack', function () {
    $s = seedScenario(1);
    withActiveUnits($s, 4);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $plan = flatPlan();

    Subscription::create([
        'organization_id' => $org->getKey(),
        'plan_id' => $plan->getKey(),
        'status' => 'active',
        'stripe_subscription_id' => 'sub_fake_flat',
    ]);

    $fake = app(StripeClientInterface::class);
    expect(app(BillingService::class)->syncSubscriptionQuantity($org))->toBeNull()
        ->and($fake->quantityUpdates)->toBe([]);
});

it('leaves the four seeded tier plans FLAT — this pass changes no existing charge', function () {
    app(BillingService::class)->ensurePlans();

    foreach (['essential_monthly', 'essential_yearly', 'pro_monthly', 'pro_yearly'] as $code) {
        $plan = Plan::query()->where('code', $code)->firstOrFail();
        expect($plan->pricing_type)->toBe(Plan::PRICING_FLAT)
            ->and($plan->quantityFor())->toBe(1);
    }
});
