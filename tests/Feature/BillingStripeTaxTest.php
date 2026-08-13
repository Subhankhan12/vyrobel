<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\Unit;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\BillingService;
use App\Support\Billing\BillableUnits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * BILLING PASS 2b — STRIPE TAX, AND THE APP GIVING UP A CLAIM IT COULD NOT SUPPORT (2026-08-13).
 *
 * ⚠️ THE SHIFT THIS FILE GUARDS. Under the founder's ruling, a per-unit Price is EXCLUSIVE of tax and Stripe
 * computes the real amount per customer — which for an EU business under reverse charge is €0. That makes the
 * old hardcoded "+ 23% VAT" false for a whole class of customers, so the app stopped asserting a post-tax total
 * for per-unit altogether. It now states the PRE-TAX SUBTOTAL — the exact amount that reaches Stripe — and
 * defers the tax to checkout.
 *
 * ⚠️ THE THREE LAYERS ARE TESTED TOGETHER ON PURPOSE. Checkout, engine and page had to move as one: had the page
 * kept its fixed VAT while the checkout switched to Stripe Tax, the page would assert a tax the charge no longer
 * fixes. So each layer has its own guard here, and one more ties the engine's figure to what Stripe receives.
 *
 * ⚠️ NO LIVE CHARGE ANYWHERE. Everything asserts against the FAKE client's recorded call; `StripeSafetyGuard` is
 * untouched and still forbids a live key outside production.
 */

function taxPlan(string $shape): Plan
{
    // ⚠️ Catalogue first — `ensurePlans()` skips when any plan exists, and the page 404s without the tiers.
    app(BillingService::class)->ensurePlans();

    return $shape === Plan::PRICING_PER_UNIT
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
}

function taxUnits(array $scenario, int $n): void
{
    $rt = RoomType::create(['property_id' => $scenario['property']->id, 'name' => 'Tx '.Str::random(4), 'kind' => 'room']);
    for ($i = 0; $i < $n; $i++) {
        Unit::create([
            'property_id' => $scenario['property']->id, 'room_type_id' => $rt->id,
            'code' => 'TX-'.Str::upper(Str::random(6)), 'is_active' => true,
        ]);
    }
}

it('⚠️ THE CHECKOUT — a per-unit checkout turns Stripe Tax ON and a flat one does not', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $fake = app(StripeClientInterface::class);

    taxPlan(Plan::PRICING_PER_UNIT);
    app(BillingService::class)->startCheckout($org, 'metered', 'monthly');
    $perUnit = collect($fake->checkoutSessions)->last();

    taxPlan(Plan::PRICING_FLAT);
    app(BillingService::class)->startCheckout($org, 'flatty', 'monthly');
    $flat = collect($fake->checkoutSessions)->last();

    // The exclusive Price needs Stripe to add the customer's own tax…
    expect($perUnit['automatic_tax'])->toBeTrue()
        // …and the ruling that switches it on is the declared convention, not a hardcoded true.
        ->and(config('billing.stripe_tax_behavior'))->toBe('exclusive')
        // ⚠️ FLAT IS UNTOUCHED — €49 is VAT-inclusive and final; asking Stripe to add tax would inflate it.
        ->and($flat['automatic_tax'])->toBeFalse();
});

it('⚠️ THE CHECKOUT — Stripe Tax is only requested while the convention says EXCLUSIVE', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    taxPlan(Plan::PRICING_PER_UNIT);

    // ⚠️ NON-VACUITY, and the real point: the flag tracks the DECLARATION. Flip the convention back to a
    // VAT-inclusive unit Price and Stripe must NOT be asked to add tax on top — that would double-charge it.
    config()->set('billing.stripe_tax_behavior', 'inclusive');
    app(BillingService::class)->startCheckout($org, 'metered', 'monthly');
    expect(collect(app(StripeClientInterface::class)->checkoutSessions)->last()['automatic_tax'])->toBeFalse();
});

it('⚠️ THE CHECKOUT — the pass-2 refusal is LIFTED for per-unit, because the correct path now exists', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    Plan::query()->delete();
    app(BillingService::class)->ensurePlans();
    Plan::create([
        'code' => 'metered_monthly', 'name' => 'Metered', 'price_minor' => 0,
        'pricing_type' => Plan::PRICING_PER_UNIT, 'unit_rate_minor' => 900, 'vat_pct' => 23,
        'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true,
        'stripe_price_id' => 'price_1RealMeteredMonthly',
    ]);

    // Pass 2 threw here in production. It no longer does — the convention is declared AND wired.
    app()->detectEnvironment(fn () => 'production');
    config()->set('services.stripe.fake', false);

    try {
        expect(fn () => app(BillingService::class)->startCheckout($org, 'metered', 'monthly'))
            ->not->toThrow(RuntimeException::class);

        // ⚠️ THE GUARD WAS NOT REMOVED — unset the convention and production refuses to bill again.
        config()->set('billing.stripe_tax_behavior', null);
        expect(fn () => app(BillingService::class)->startCheckout($org, 'metered', 'monthly'))
            ->toThrow(RuntimeException::class, 'no Stripe tax convention is declared');
    } finally {
        app()->detectEnvironment(fn () => 'testing');
        config()->set('services.stripe.fake', true);
        config()->set('billing.stripe_tax_behavior', 'exclusive');
    }
});

it('⚠️ THE ENGINE — a per-unit charge is the PRE-TAX subtotal, with no VAT added', function () {
    $s = seedScenario(1);
    taxUnits($s, 4);
    $plan = taxPlan(Plan::PRICING_PER_UNIT);

    $units = BillableUnits::count();
    $charge = $plan->chargeFor();

    // ⚠️ NON-VACUITY: the plan still CARRIES vat_pct = 23, so an engine that wanted to add VAT could. It does
    // not — because the number it would produce is not true of every customer.
    expect((int) $plan->vat_pct)->toBe(23);

    expect($charge['gross_minor'])->toBe($units * 900)
        ->and($charge['net_minor'])->toBe($units * 900)
        ->and($charge['vat_minor'])->toBe(0)
        ->and($charge['tax_known'])->toBeFalse();
});

it('THE ENGINE — a flat charge is unchanged: the price IS the total, and the app knows it', function () {
    $s = seedScenario(1);
    taxUnits($s, 4);
    $charge = taxPlan(Plan::PRICING_FLAT)->chargeFor();

    expect($charge['gross_minor'])->toBe(4900)
        ->and($charge['tax_known'])->toBeTrue()
        // ⚠️ The unit count does not PRICE a flat tier, so the charge quantity stays 1 however many units the
        // org runs — while `BillableUnits` still counts them, because the page states that real fact either way.
        ->and($charge['units'])->toBe(1)
        ->and(BillableUnits::count())->toBeGreaterThan(1);
});

it('⚠️ SUBTOTAL = WHAT REACHES STRIPE — the engine figure is the pre-tax amount Stripe is sent', function () {
    $s = seedScenario(1);
    taxUnits($s, 6);
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $plan = taxPlan(Plan::PRICING_PER_UNIT);

    app(BillingService::class)->startCheckout($org, 'metered', 'monthly');
    $sent = collect(app(StripeClientInterface::class)->checkoutSessions)->last();

    $charge = $plan->chargeFor();

    // Price × quantity is the pre-tax amount. The engine's subtotal must equal it exactly — this is the chain
    // pass 2 found broken at the Stripe hop (page said €88.56, Stripe would take €72.00), now closed by the app
    // claiming only the pre-tax half and Stripe owning the rest.
    expect($sent['quantity'] * 900)->toBe($charge['gross_minor'])
        ->and($sent['quantity'])->toBe(BillableUnits::count())
        ->and($sent['automatic_tax'])->toBeTrue();
});

it('⚠️ THE PAGE — a per-unit org sees the subtotal and "tax calculated at checkout", never a fixed VAT', function () {
    $s = seedScenario(1);
    taxUnits($s, 4);
    $plan = taxPlan(Plan::PRICING_PER_UNIT);
    Subscription::create([
        'organization_id' => $s['org']->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'current_period_end' => now()->addMonth(),
    ]);
    $owner = operatorUser($s, [], 'Property Owner');

    $html = (string) $this->actingAs($owner)->get('/app/billing')->assertOk()->getContent();
    $text = trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
        (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)
    ))));

    $charge = $plan->chargeFor();

    // The subtotal it CAN stand behind…
    expect($text)->toContain(number_format($charge['gross_minor'] / 100, 2))
        // …and the tax it cannot, honestly deferred.
        ->and($text)->toContain('tax calculated at checkout');

    // ⚠️ THE NEW §N.8d MUST NOT APPEAR — not in the headline, not in the next-invoice strip, nowhere.
    expect($text)->not->toContain('incl. 23% VAT')
        ->and($text)->not->toContain('23% VAT');
});

it('⚠️ THE PAGE — a FLAT org is unchanged: no tax-at-checkout note and no percentage', function () {
    $s = seedScenario(1);
    taxUnits($s, 4);
    $plan = taxPlan(Plan::PRICING_FLAT);
    Subscription::create([
        'organization_id' => $s['org']->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'current_period_end' => now()->addMonth(),
    ]);
    $owner = operatorUser($s, [], 'Property Owner');

    $text = trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
        (string) $this->actingAs($owner)->get('/app/billing')->assertOk()->getContent()
    ))));

    expect($text)->toContain('€49.00')
        // Stripe Tax is not applied to an inclusive price, so promising a checkout calculation would be false…
        ->and($text)->not->toContain('tax calculated at checkout')
        // …and "incl. 0% VAT" — what a naive `tax_known` branch prints when `vat_pct` is null — is false too.
        ->and($text)->not->toContain('0% VAT');
});
