<?php

use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 7b · SG2 — the Paywall + Checkout UI. Real tiers + honest derived trial status + company/VAT capture;
// the subscribe action is the ACCOUNT-GATED Stripe seam (never a faked payment).

/** A trialing subscription on an org (context already set by seedScenario). */
function trialSub(string $orgId): Subscription
{
    app(BillingService::class)->ensurePlans();

    return Subscription::create([
        'organization_id' => $orgId,
        'plan_id' => app(BillingService::class)->trialPlan()->id,
        'status' => 'trialing',
        'trial_ends_at' => now()->addDays(10),
    ]);
}

// ── 1. 🔒 PERMISSION — billing.manage sees billing; Front Desk is refused (mutation-proven) ─────────
it('gates billing on billing.manage — the founder sees it, Front Desk is refused', function () {
    $s = seedScenario(1);
    trialSub((string) $s['org']->id);

    foreach (['/app/billing', '/app/billing/checkout?plan=pro'] as $url) {
        $this->actingAs(operatorUser($s, [], 'Property Owner'))->get($url)->assertOk();
        $this->actingAs(operatorUser($s, [], 'Front Desk'))->get($url)->assertForbidden();
    }
});

// ── 2. ✅ PAYWALL — real tiers + placeholder prices + the honest DERIVED trial status ───────────────
it('shows the real tiers, placeholder prices, and the honest trial status', function () {
    $s = seedScenario(1);
    trialSub((string) $s['org']->id);

    $res = $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/billing');

    $res->assertOk()
        ->assertSee('Essential')->assertSee('Pro')
        ->assertSee('€49.00')->assertSee('€99.00')            // placeholder monthly prices (config)
        ->assertSee('left in your free trial')                // real, derived from trial_ends_at
        ->assertSee('placeholders')                            // honest placeholder-pricing note
        ->assertSee('collecting payments yet');                // honest account-gated statement

    assertNoRawBlade($res);
});

// ── 3. ✅ CHECKOUT — chosen tier + period toggle + summary + company/VAT (no card) ─────────────────
it('renders checkout with the period-toggled placeholder summary and a company/VAT form', function () {
    $s = seedScenario(1);
    trialSub((string) $s['org']->id);
    $founder = operatorUser($s, [], 'Property Owner');

    $this->actingAs($founder)->get('/app/billing/checkout?plan=pro&interval=yearly')
        ->assertOk()
        ->assertSee('Pro')->assertSee('Yearly')
        ->assertSee('€990.00')                                 // pro yearly placeholder (config)
        ->assertSee('Company name')->assertSee('VAT / tax ID')
        ->assertSee('Card payment is coming soon')             // the account-gated seam, honest
        ->assertDontSee('Card number');                        // 🪤 NO card field / no raw PAN

    // Company/VAT capture (the org's own business data, no card).
    $this->actingAs($founder)->post('/app/billing/company', [
        'billing_company' => 'Seaside Group Lda', 'billing_vat_id' => 'PT512034776',
    ])->assertRedirect();

    $org = $s['org']->fresh();
    expect($org->billing_company)->toBe('Seaside Group Lda')->and($org->billing_vat_id)->toBe('PT512034776');
});

// ── 4. 🪤 THE SUBSCRIBE SEAM — account-gated: NO charge, NO activation, subscription unchanged ──────
it('never charges or activates on subscribe — the trial is untouched (account-gated seam)', function () {
    $s = seedScenario(1);
    trialSub((string) $s['org']->id);
    $founder = operatorUser($s, [], 'Property Owner');

    $this->actingAs($founder)->post('/app/billing/subscribe', [
        'plan' => 'pro', 'interval' => 'yearly', 'billing_company' => 'Seaside Group Lda',
    ])->assertRedirect(route('app.billing'))
        ->assertSessionHas('billing_notice');

    // 🪤 The subscription is STILL trialing — nothing flipped to active, no Stripe id, no charge.
    $sub = Subscription::withoutGlobalScopes()->where('organization_id', $s['org']->id)->firstOrFail();
    expect($sub->status)->toBe('trialing')
        ->and($sub->stripe_subscription_id)->toBeNull();

    // The company detail WAS captured (ready for real invoicing later).
    expect($s['org']->fresh()->billing_company)->toBe('Seaside Group Lda');
});
