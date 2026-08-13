<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\BillingService;
use App\Support\Billing\BillableUnits;
use App\Support\Billing\StripePriceWiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * BILLING PASS 2 — THE PRICE WIRING AND ITS LOUD FAILURES (2026-08-13).
 *
 * ⚠️ WHAT THIS FILE CAN AND CANNOT PROVE. It proves the app sends the CONFIGURED Price id, refuses a
 * placeholder or a missing one in production, and matches `Plan::chargeFor()`. It CANNOT prove the real Price
 * exists in Stripe with the right amount — `StripeSafetyGuard` forbids a live key outside production, so that
 * confirmation belongs to the founder's dashboard (docs/BILLING-STRIPE-PRICES.md). Those are different claims
 * and this file is careful not to imply the second.
 *
 * ⚠️ THE LOUD-FAIL TESTS MUST ENTER THE PRODUCTION PATH OR THEY PROVE NOTHING. A guard that never reaches the
 * branch it guards is vacuous, so each one forces `production` + non-fake explicitly and asserts a throw.
 */

function wiringPlan(string $shape, ?string $priceId): Plan
{
    app(BillingService::class)->ensurePlans();

    return $shape === Plan::PRICING_PER_UNIT
        ? Plan::create([
            'code' => 'metered_monthly', 'name' => 'Metered', 'price_minor' => 0,
            'pricing_type' => Plan::PRICING_PER_UNIT, 'unit_rate_minor' => 900, 'vat_pct' => 23,
            'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true, 'stripe_price_id' => $priceId,
        ])
        : Plan::create([
            'code' => 'flatty_monthly', 'name' => 'Flatty', 'price_minor' => 4900,
            'pricing_type' => Plan::PRICING_FLAT, 'currency' => 'EUR', 'interval' => 'monthly',
            'is_active' => true, 'stripe_price_id' => $priceId,
        ]);
}

/** Run a closure as if this were the real production money path. */
function asProductionMoneyPath(callable $fn): mixed
{
    app()->detectEnvironment(fn () => 'production');
    config()->set('services.stripe.fake', false);

    try {
        return $fn();
    } finally {
        app()->detectEnvironment(fn () => 'testing');
        config()->set('services.stripe.fake', true);
    }
}

it('sends the CONFIGURED Price id per shape — the flat plan its Price, the per-unit plan its own', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $fake = app(StripeClientInterface::class);
    $billing = app(BillingService::class);

    wiringPlan(Plan::PRICING_FLAT, 'price_1RealFlatMonthly');
    $billing->startCheckout($org, 'flatty', 'monthly');
    expect(collect($fake->checkoutSessions)->last()['price'])->toBe('price_1RealFlatMonthly');

    wiringPlan(Plan::PRICING_PER_UNIT, 'price_1RealMeteredMonthly');
    $billing->startCheckout($org, 'metered', 'monthly');
    expect(collect($fake->checkoutSessions)->last()['price'])->toBe('price_1RealMeteredMonthly');
});

it('⚠️ refuses a PLACEHOLDER Price id on the production money path — no silent fake charge', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $plan = wiringPlan(Plan::PRICING_FLAT, StripePriceWiring::FAKE_PREFIX.'flatty_monthly');

    // ⚠️ NON-VACUITY: outside production the very same plan is fine — the fake client is what runs there.
    expect(StripePriceWiring::priceIdFor($plan))->toBe(StripePriceWiring::FAKE_PREFIX.'flatty_monthly');

    asProductionMoneyPath(function () use ($org) {
        expect(fn () => app(BillingService::class)->startCheckout($org, 'flatty', 'monthly'))
            ->toThrow(RuntimeException::class, 'does not exist in Stripe');
    });
});

it('⚠️ refuses a MISSING Price id on the production money path', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    wiringPlan(Plan::PRICING_FLAT, '');

    asProductionMoneyPath(function () use ($org) {
        expect(fn () => app(BillingService::class)->startCheckout($org, 'flatty', 'monthly'))
            ->toThrow(RuntimeException::class, 'has no Stripe Price id');
    });
});

it('⚠️ refuses to bill a PER-UNIT plan whose VAT convention nobody declared — the €16.56 gap', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    wiringPlan(Plan::PRICING_PER_UNIT, 'price_1RealMeteredMonthly');

    // Undeclared is the shipped default — choosing has compliance consequences, so it is the founder's call.
    expect(config('billing.stripe_tax_behavior'))->toBeNull();

    asProductionMoneyPath(function () use ($org) {
        expect(fn () => app(BillingService::class)->startCheckout($org, 'metered', 'monthly'))
            ->toThrow(RuntimeException::class, 'no Stripe tax convention is declared');
    });

    // …and once declared, it bills.
    asProductionMoneyPath(function () use ($org) {
        config()->set('billing.stripe_tax_behavior', 'inclusive');
        expect(fn () => app(BillingService::class)->startCheckout($org, 'metered', 'monthly'))
            ->not->toThrow(RuntimeException::class);
    });
});

it('a FLAT plan bills in production without a tax declaration — €49 is already the final price', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    wiringPlan(Plan::PRICING_FLAT, 'price_1RealFlatMonthly');

    // The founder's VAT ruling: flat tiers are VAT-INCLUSIVE, so price × 1 already equals what the page shows.
    // Nothing further needs declaring, and gating it would block a correct charge.
    asProductionMoneyPath(function () use ($org) {
        expect(fn () => app(BillingService::class)->startCheckout($org, 'flatty', 'monthly'))
            ->not->toThrow(RuntimeException::class);
    });
});

it('⚠️ STRIPE = CHARGE = PAGE — the quantity that reaches Stripe is the engine’s', function () {
    $s = seedScenario(1);
    $rt = RoomType::create(['property_id' => $s['property']->id, 'name' => 'W '.Str::random(4), 'kind' => 'room']);
    foreach (range(1, 4) as $i) {
        Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $rt->id, 'code' => 'W-'.Str::upper(Str::random(6)), 'is_active' => true]);
    }
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $plan = wiringPlan(Plan::PRICING_PER_UNIT, 'price_1RealMeteredMonthly');

    app(BillingService::class)->startCheckout($org, 'metered', 'monthly');
    $sent = collect(app(StripeClientInterface::class)->checkoutSessions)->last();

    // The quantity Stripe receives is the engine's unit count — the same number the page prints.
    expect($sent['quantity'])->toBe($plan->chargeFor()['units'])
        ->and($sent['quantity'])->toBe(BillableUnits::count());
});

it('the seeder keeps its placeholder fallback OUTSIDE production only', function () {
    $source = (string) file_get_contents(database_path('seeders/BillingPlanSeeder.php'));

    // The fallback still exists (dev needs it) but is now behind an environment check that throws in production.
    expect($source)->toContain('StripePriceWiring::FAKE_PREFIX')
        ->and($source)->toContain("app()->environment('production')")
        ->and($source)->toContain('throw new RuntimeException');
});
