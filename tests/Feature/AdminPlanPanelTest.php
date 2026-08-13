<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Support\Billing\PlanIntegrity;
use App\Support\Billing\PlanTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/*
 * THE SUPER-ADMIN PLAN PANEL (2026-08-13) — the last billing build.
 *
 * ⚠️ THE PANEL WRITES DATA THE ENGINE CHARGES, so its validation IS billing safety (§N.18.2). Everything
 * rejected here is something that would otherwise reach a customer's checkout: a per-unit plan with no rate
 * bills €0 × units; an unknown shape falls through `isPerUnit()` to the flat branch and bills the wrong basis;
 * a plan with no Stripe Price reaches a session that cannot complete.
 *
 * ⚠️ AND THE RULING "EDITS AFFECT NEW SUBSCRIPTIONS ONLY" IS TESTED AS ARCHITECTURE, NOT INTENT. Before this
 * pass, subscriptions stored only `plan_id` and every price was read from the LIVE plan row — so an edit
 * changed what existing subscribers were shown. They now bill from CAPTURED terms, and the test below edits a
 * plan out from under a subscriber to prove it.
 */

// ⚠️ `platformAdmin()` comes from tests/Support/admin_helpers.php — 2FA-enrolled, so it clears the mandatory
// two-factor gate. A locally-rolled admin would sit behind that gate and every assertion below would be about
// the 2FA redirect rather than the panel.

/** A plan of the given shape, wired to a Price id so it is activatable. */
function panelPlan(string $shape, array $overrides = []): Plan
{
    return Plan::create(array_merge([
        'code' => $shape === Plan::PRICING_PER_UNIT ? 'panel_metered' : 'panel_flat',
        'name' => 'Panel '.$shape,
        'pricing_type' => $shape,
        'price_minor' => $shape === Plan::PRICING_PER_UNIT ? 0 : 4900,
        'unit_rate_minor' => $shape === Plan::PRICING_PER_UNIT ? 900 : null,
        'vat_pct' => 23,
        'currency' => 'EUR',
        'interval' => 'monthly',
        'is_active' => true,
        'stripe_price_id' => 'price_1RealPanel',
    ], $overrides));
}

it('⚠️ SUPER-ADMIN ONLY — a platform admin reaches the panel and an operator does NOT', function () {
    $admin = platformAdmin();

    // ⚠️ NON-VACUITY, and the half that actually protects the surface: assert BOTH roles. A test that only
    // checked the 403 would pass against a panel that was broken for everyone (DO-BILL-2's trap).
    $this->actingAs($admin, 'admin')->get('/admin/plans/create')->assertOk();

    // An operator — even an org's most privileged one — is on a different guard entirely and cannot reach it.
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');
    $this->actingAs($owner)->get('/admin/plans/create')->assertRedirect();
});

it('⚠️ WELL-FORMED OR REJECTED — a per-unit plan with no rate is not persisted', function () {
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post('/admin/plans', [
        'code' => 'no_rate', 'name' => 'No rate', 'pricing_type' => Plan::PRICING_PER_UNIT,
        'interval' => 'monthly', 'currency' => 'EUR', 'unit_rate' => '',
    ])->assertSessionHasErrors('unit_rate');

    // ⚠️ INDEPENDENT CHECK (§N.18.1): a raw query, not `Plan::query()`. `Plan` soft-deletes, so a scoped count
    // can report "absent" for a row that exists — the exact trap that hid pass 2b's leftover plan.
    expect(DB::table('plans')->where('code', 'no_rate')->count())->toBe(0);
});

it('⚠️ WELL-FORMED OR REJECTED — a ZERO rate is refused, because it silently bills nothing', function () {
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post('/admin/plans', [
        'code' => 'zero_rate', 'name' => 'Zero', 'pricing_type' => Plan::PRICING_PER_UNIT,
        'interval' => 'monthly', 'currency' => 'EUR', 'unit_rate' => '0',
    ])->assertSessionHasErrors('unit_rate');

    expect(DB::table('plans')->where('code', 'zero_rate')->count())->toBe(0);
});

it('⚠️ WELL-FORMED OR REJECTED — an unknown pricing shape is not persisted', function () {
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post('/admin/plans', [
        'code' => 'weird', 'name' => 'Weird', 'pricing_type' => 'metered_by_moonlight',
        'interval' => 'monthly', 'currency' => 'EUR', 'price' => '10',
    ])->assertSessionHasErrors('pricing_type');

    expect(DB::table('plans')->where('code', 'weird')->count())->toBe(0);
});

it('creates a PER-UNIT plan — the shape that previously had no in-app path at all', function () {
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post('/admin/plans', [
        'code' => 'studio_monthly', 'name' => 'Studio', 'pricing_type' => Plan::PRICING_PER_UNIT,
        'interval' => 'monthly', 'currency' => 'eur', 'unit_rate' => '9.00', 'vat_pct' => '23',
    ])->assertRedirect();

    $plan = Plan::query()->where('code', 'studio_monthly')->firstOrFail();

    expect($plan->pricing_type)->toBe(Plan::PRICING_PER_UNIT)
        ->and($plan->unit_rate_minor)->toBe(900)      // major → integer minor (#17)
        ->and($plan->currency)->toBe('EUR')
        // ⚠️ A NEW PLAN IS A DRAFT. Its Stripe Price does not exist yet.
        ->and($plan->is_active)->toBeFalse();
});

it('⚠️ NOT BILLABLE WITHOUT A PRICE — a Price-less plan cannot be activated', function () {
    $admin = platformAdmin();
    $plan = panelPlan(Plan::PRICING_PER_UNIT, ['is_active' => false, 'stripe_price_id' => null]);

    // ⚠️ NON-VACUITY: everything else about this plan is valid, so only the missing Price can be blocking it.
    // Asserted as a string first — if the guard stops blocking it returns null, and a bare `toContain` would
    // fail with a type error rather than saying what actually broke.
    $blocked = PlanIntegrity::blockedFromActivation($plan);
    expect($blocked)->toBeString()->and($blocked)->toContain('Stripe Price');

    $this->actingAs($admin, 'admin')->post("/admin/plans/{$plan->id}/toggle")->assertRedirect();

    expect($plan->fresh()->is_active)->toBeFalse();

    // …and with a real Price it activates.
    $plan->update(['stripe_price_id' => 'price_1RealPanel']);
    $this->actingAs($admin, 'admin')->post("/admin/plans/{$plan->id}/toggle");
    expect($plan->fresh()->is_active)->toBeTrue();
});

it('⚠️ EDITS AFFECT NEW SUBSCRIPTIONS ONLY — an existing subscriber keeps the terms they agreed to', function () {
    $admin = platformAdmin();
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    $plan = panelPlan(Plan::PRICING_FLAT);

    $org = Organization::query()->findOrFail($s['org']->getKey());
    $subscription = Subscription::create(array_merge([
        'organization_id' => $org->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'current_period_end' => now()->addMonth(),
    ], PlanTerms::fromPlan($plan)->toCapturedColumns()));

    expect($subscription->chargeFor()['gross_minor'])->toBe(4900);

    // The platform doubles the catalogue price.
    $this->actingAs($admin, 'admin')->put("/admin/plans/{$plan->id}", [
        'code' => $plan->code, 'name' => $plan->name, 'pricing_type' => Plan::PRICING_FLAT,
        'interval' => 'monthly', 'currency' => 'EUR', 'price' => '98.00',
        'stripe_price_id' => 'price_1RealPanel', 'is_active' => '1',
    ])->assertRedirect();

    // ⚠️ NON-VACUITY: the edit really landed — the catalogue row IS now €98.
    expect((int) $plan->fresh()->price_minor)->toBe(9800);

    // ⚠️ THE RULING: the existing subscriber is untouched. Before captured terms this returned 9800, because
    // the charge was read from the live plan — and worse, Stripe would have kept billing €49 while the app
    // displayed €98 (display ≠ charge).
    expect($subscription->fresh()->chargeFor()['gross_minor'])->toBe(4900);

    // A NEW subscription gets the new terms — the edit is not inert, it is scoped.
    $fresh = Subscription::create(array_merge([
        'organization_id' => $org->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'current_period_end' => now()->addMonth(),
    ], PlanTerms::fromPlan($plan->fresh())->toCapturedColumns()));

    expect($fresh->chargeFor()['gross_minor'])->toBe(9800);
});

it('a trial subscription captures its terms at creation — the ruling holds from the very first row', function () {
    $s = seedScenario(1);
    $org = Organization::query()->findOrFail($s['org']->getKey());

    $subscription = app(BillingService::class)->startTrialFor($org);

    expect($subscription->terms_captured_at)->not->toBeNull()
        ->and($subscription->terms_pricing_type)->not->toBeNull();
});

it('display = charge — the panel saves into the ONE calculation, not a parallel price', function () {
    // A tenant is needed because a per-unit charge counts the org's REAL units — the quantity stays LIVE even
    // though the terms are frozen, which is the split this pass had to get right.
    seedScenario(1);
    $plan = panelPlan(Plan::PRICING_PER_UNIT);

    // `Plan::chargeFor()` and the terms object agree by construction: chargeFor DELEGATES to PlanTerms, so
    // there is no second arithmetic for the panel's output to drift from (§N.8f / §N.17.4).
    expect($plan->chargeFor())->toBe(PlanTerms::fromPlan($plan)->charge());
});
