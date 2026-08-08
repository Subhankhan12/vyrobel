<?php

declare(strict_types=1);

use App\Livewire\Operator\OperatorBilling;
use App\Livewire\Operator\OperatorBillingCheckout;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the wireframe-parity sweep's S3 (WAVE 2 · structural MEDIUM).
 *
 * The operator Billing page's "Switch to yearly" linked to a HARDCODED `plan=pro`, so an org on ESSENTIAL
 * clicking it landed on the PRO yearly checkout — a silent upgrade to a tier they never chose. The switch
 * changes the INTERVAL; the tier must come from the org's own subscription.
 *
 * ⚠️ THE TRAP THIS TEST EXISTS TO PIN: `plans.code` is `{tier}_{interval}` ("essential_monthly"), NOT the
 * tier code the checkout accepts. {@see OperatorBillingCheckout::resolveTier()}
 * whitelists against the catalog and falls back to 'pro' on anything unrecognised — so the "obvious" fix of
 * passing `$plan->code` straight through REPRODUCES the exact bug, silently. The tier suffix must be stripped.
 *
 * ⚠️ SEPARABLE FROM THE OPEN BILLING DECISION. This fixes WHICH TIER the link carries. It deliberately does
 * NOT touch the pricing contradiction (the flat tier price reaching Stripe vs the per-unit figure displayed) —
 * that is the founder's business decision and must not be resolved in code. The "save {saving}" figure is left
 * exactly as it was, still sourced from SubscriptionPricing.
 */
function billingOrgOn(string $orgId, string $planCode): void
{
    app(BillingService::class)->ensurePlans();

    Subscription::create([
        'organization_id' => $orgId,
        'plan_id' => Plan::query()->where('code', $planCode)->firstOrFail()->id,
        'status' => 'active',
    ]);
}

it('sends an Essential org to the ESSENTIAL yearly checkout, never a silent upgrade to Pro', function () {
    $s = seedScenario(1);
    billingOrgOn((string) $s['org']->id, 'essential_monthly');

    Livewire::actingAs(operatorUser($s, [], 'Property Owner'))
        ->test(OperatorBilling::class)
        ->assertSee(route('app.billing.checkout', ['plan' => 'essential', 'interval' => 'yearly']))
        ->assertDontSee(route('app.billing.checkout', ['plan' => 'pro', 'interval' => 'yearly']));
});

it('sends a Pro org to the Pro yearly checkout', function () {
    $s = seedScenario(1);
    billingOrgOn((string) $s['org']->id, 'pro_monthly');

    Livewire::actingAs(operatorUser($s, [], 'Property Owner'))
        ->test(OperatorBilling::class)
        ->assertSee(route('app.billing.checkout', ['plan' => 'pro', 'interval' => 'yearly']));
});

it('passes a TIER code the checkout actually honours, not the raw plan code', function () {
    $s = seedScenario(1);
    billingOrgOn((string) $s['org']->id, 'essential_monthly');

    // The link must carry `essential`. `essential_monthly` is NOT in the catalog whitelist, so the checkout
    // would silently fall back to 'pro' — the original defect wearing a different mask.
    Livewire::actingAs(operatorUser($s, [], 'Property Owner'))
        ->test(OperatorBilling::class)
        ->assertDontSee('plan=essential_monthly', false);

    // Prove the claim about the checkout rather than assuming it: the raw plan code resolves to Pro.
    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->get('/app/billing/checkout?plan=essential_monthly&interval=yearly')
        ->assertOk()
        ->assertSee('Pro');
});

it('does not offer "switch to yearly" to an org already on yearly', function () {
    $s = seedScenario(1);
    billingOrgOn((string) $s['org']->id, 'essential_yearly');

    // An affordance that does nothing is the same false-copy class this wave removes.
    Livewire::actingAs(operatorUser($s, [], 'Property Owner'))
        ->test(OperatorBilling::class)
        ->assertDontSee('Switch to yearly');
});
