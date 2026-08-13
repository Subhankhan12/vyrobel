<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\BillingService;
use App\Support\Billing\StripeTaxIdSync;
use App\Support\Billing\VatId;
use App\Support\Pdf\OperatorLetterhead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

/*
 * DO-BILL-1 — THE STORED VAT NUMBER REACHES THE STRIPE CUSTOMER (2026-08-13).
 *
 * Vyrobel collected a VAT number and used it for PDF letterheads only. Billing pass 2b then turned Stripe Tax
 * on, and Stripe Tax decides reverse charge from the CUSTOMER's `tax_ids` — so a business that had already told
 * Vyrobel its number, and skipped Stripe's own prompt, paid VAT it was entitled to reverse-charge.
 *
 * ⚠️ THE ASYMMETRY IS THE WHOLE DESIGN, AND MOST OF THIS FILE GUARDS THE UNHAPPY HALF. Overcharging is visible
 * and refundable; wrongly ZERO-RATING is silent and lands on the operator at an audit. So an untypeable number
 * is never sent, a Stripe rejection is never treated as success, and both are surfaced.
 *
 * ⚠️ EVERYTHING ASSERTS AGAINST THE FAKE CLIENT's recorded `tax_ids` — what WOULD reach Stripe. No live key, no
 * charge; `StripeSafetyGuard` is untouched.
 */

/** An org with a Stripe customer already attached (i.e. past its first checkout). */
function vatOrg(array $scenario, ?string $vatId, string $customerId = 'cus_fake_dobill1'): Organization
{
    app(BillingService::class)->ensurePlans();

    Subscription::create([
        'organization_id' => $scenario['org']->getKey(),
        'plan_id' => Plan::query()->firstOrFail()->getKey(),
        'status' => 'active',
        'current_period_end' => now()->addMonth(),
        'stripe_customer_id' => $customerId,
    ]);

    $org = Organization::query()->findOrFail($scenario['org']->getKey());
    $org->update(['billing_vat_id' => $vatId]);

    return $org->fresh() ?? $org;
}

it('⚠️ a VALID stored VAT number reaches Stripe as a TYPED tax id', function () {
    $s = seedScenario(1);
    $org = vatOrg($s, 'PT123456789');
    $fake = app(StripeClientInterface::class);

    // ⚠️ NON-VACUITY: nothing is on the customer before the push, so the assertion below cannot pass by accident.
    expect($fake->customerTaxIds)->toBe([]);

    expect(StripeTaxIdSync::push($org))->toBeTrue();

    // ⚠️ A TYPE AND A VALUE — never a bare string. The type is what tells Stripe how to read the number.
    expect($fake->customerTaxIds['cus_fake_dobill1'])->toBe([['type' => 'eu_vat', 'value' => 'PT123456789']]);
});

it('types by JURISDICTION, not by guessing — CH and GB are not eu_vat, and an unknown prefix is refused', function () {
    expect(VatId::typed('CH1234567'))->toBe(['type' => 'ch_vat', 'value' => 'CH1234567'])
        ->and(VatId::typed('GB123456789'))->toBe(['type' => 'gb_vat', 'value' => 'GB123456789'])
        ->and(VatId::typed('DE123456789'))->toBe(['type' => 'eu_vat', 'value' => 'DE123456789'])
        // ⚠️ The one that matters: an unrecognised prefix is NOT defaulted to eu_vat. Defaulting would mis-type
        // every non-EU number that happens to start with two letters, and Stripe might well accept it.
        ->and(VatId::typed('ZZ123456789'))->toBeNull();

    // Presentation tolerance is fine; validity tolerance is not.
    expect(VatId::typed('pt 123.456-789'))->toBe(['type' => 'eu_vat', 'value' => 'PT123456789']);
});

it('⚠️ FAIL SAFE — an UNTYPEABLE number is never sent, so the customer stays TAXABLE', function () {
    $s = seedScenario(1);
    $org = vatOrg($s, 'not-a-vat-id');
    $fake = app(StripeClientInterface::class);

    Log::spy();

    expect(StripeTaxIdSync::push($org))->toBeFalse();

    // ⚠️ THE POINT: nothing reached Stripe. Sending a number we cannot type risks it being accepted under the
    // wrong regime and ZERO-RATING a sale that owes VAT.
    expect($fake->customerTaxIds)->toBe([]);

    // …and it is surfaced rather than swallowed.
    Log::shouldHaveReceived('warning')->withArgs(fn (string $m) => str_contains($m, 'could not be typed'))->once();
});

it('⚠️ FAIL SAFE — a number STRIPE REJECTS leaves the customer taxable and does not throw', function () {
    $s = seedScenario(1);
    $org = vatOrg($s, 'PT999999999');
    $fake = app(StripeClientInterface::class);
    $fake->rejectTaxIdValues = ['PT999999999'];

    Log::spy();

    // ⚠️ NON-VACUITY: this number IS typeable, so it genuinely reaches the client and is refused there — this
    // exercises the rejection path, not the untypeable one above.
    expect(VatId::typed('PT999999999'))->not->toBeNull();

    // No exception — a bad VAT number must not break a subscription.
    expect(StripeTaxIdSync::push($org))->toBeFalse()
        ->and($fake->customerTaxIds)->toBe([]);

    Log::shouldHaveReceived('warning')->withArgs(fn (string $m) => str_contains($m, 'Stripe rejected'))->once();
});

it('no Stripe customer yet → a no-op, because there is nothing to attach to', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    $org = Organization::query()->findOrFail($s['org']->getKey());
    $org->update(['billing_vat_id' => 'PT123456789']);

    // The first checkout has not happened, so Stripe has not minted a customer. Stripe's own
    // `tax_id_collection` (pass 2b) covers that session; the webhook attaches the number afterwards.
    expect(StripeTaxIdSync::push($org->fresh() ?? $org))->toBeFalse()
        ->and(app(StripeClientInterface::class)->customerTaxIds)->toBe([]);
});

it('⚠️ a CORRECTED number replaces the old one — a stale id must not keep zero-rating', function () {
    $s = seedScenario(1);
    $org = vatOrg($s, 'PT123456789');
    $fake = app(StripeClientInterface::class);

    StripeTaxIdSync::push($org);
    expect($fake->customerTaxIds['cus_fake_dobill1'][0]['value'])->toBe('PT123456789');

    $org->update(['billing_vat_id' => 'DE987654321']);
    StripeTaxIdSync::push($org->fresh() ?? $org);

    // Exactly ONE id — replaced, not appended. Two numbers on one customer is ambiguous and the stale one could
    // win, which would zero-rate against a number the business no longer claims.
    expect($fake->customerTaxIds['cus_fake_dobill1'])->toBe([['type' => 'eu_vat', 'value' => 'DE987654321']]);
});

it('⚠️ display = charge = Stripe still HOLDS — the app computes no reverse-charge total of its own', function () {
    $s = seedScenario(1);
    vatOrg($s, 'PT123456789');
    $plan = Plan::query()->firstOrFail();

    // The engine's figure is unchanged by the sync: it is still the plan's charge, with no tax reasoning in it.
    // The whole point of pass 2b was that the app does not know the customer's tax — a VAT id it forwarded to
    // Stripe does not change that.
    $charge = $plan->chargeFor();

    expect($charge['vat_minor'])->toBe(0)
        ->and($charge['gross_minor'])->toBe($charge['net_minor']);
});

it('the PDF letterhead use of billing_vat_id is untouched', function () {
    $s = seedScenario(1);
    $org = vatOrg($s, 'PT123456789');

    // The letterhead reads the same column this pass now also forwards — it must still see it verbatim.
    expect(OperatorLetterhead::vatId($org))->toBe('PT123456789');
});
