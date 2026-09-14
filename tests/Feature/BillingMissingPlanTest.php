<?php

declare(strict_types=1);

use App\Exceptions\MissingPlanTermsException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Support\Billing\PlanTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ BILLING MUST NOT ANSWER A QUESTION IT CANNOT ANSWER.
 *
 * Two defects, one root — code assuming a plan row always resolves:
 *
 *   · `PlanTerms::fromCaptured([])` returned a charge of **€0 with `tax_known: true`**. Not merely the wrong
 *     number: an assertion that the number is CERTAIN. A 404, a null and an exception each STOP something;
 *     a confident zero lets every caller downstream proceed on a certainty nobody established.
 *   · `BillingService::catalog()` called `firstOrFail()` under a comment claiming `ensurePlans()` guaranteed
 *     both interval rows exist. It does not — it reseeds only on a COMPLETELY EMPTY table — so a paying
 *     customer's billing page returned **404**.
 *
 * ⚠️ THE RULE THESE DEFEND IS "MUST NOT ANSWER", NOT "MUST NOT ANSWER ZERO". A plausible non-zero default
 * would be worse — an invented price on a charging path — so every assertion below demands a REFUSAL, and
 * none of them would pass if the empty snapshot simply returned a different number.
 *
 * ⚠️ NOTHING HERE IS PROVEN OVER `artisan serve`: the dev server was shown last pass to keep executing a
 * stale copy of an `app/` class, which made a source mutation look identical in both directions. Every claim
 * is settled by calling the class or by a request through the test kernel.
 */
uses(RefreshDatabase::class);

it('⚠️⚠️ REFUSES to price an empty snapshot — it does not return a better zero', function () {
    // ⚠️ The assertion is on the REFUSAL. A mutation that made this return a plausible €49 instead of €0
    //    must still fail here, because the defect is answering at all.
    $this->expectException(MissingPlanTermsException::class);
    PlanTerms::fromCaptured([]);
});

it('⚠️ REFUSES a PARTIAL snapshot too — a null basis is not a price of zero', function () {
    // ⚠️ Not in the original report, found by testing every path rather than reasoning: a capture carrying a
    //    shape but no amount coerced through `?? 0` just as silently as an empty one.
    $this->expectException(MissingPlanTermsException::class);
    PlanTerms::fromCaptured(['terms_pricing_type' => 'flat', 'terms_currency' => 'EUR']);
});

it('⚠️ still prices a REAL captured snapshot, including a flat one with a null unit rate', function () {
    // ⚠️ THE GUARD MUST NOT BE WIDER THAN THE DEFECT. The migration's backfill writes the plan's own NULL
    //    straight into `terms_unit_rate_minor` for every flat plan, so demanding both bases would refuse
    //    every backfilled subscription in the database — a "fix" that breaks billing for everyone.
    $charge = PlanTerms::fromCaptured([
        'terms_pricing_type' => 'flat', 'terms_price_minor' => 4900,
        'terms_unit_rate_minor' => null, 'terms_vat_pct' => null, 'terms_currency' => 'EUR',
    ])->charge();

    expect($charge['gross_minor'])->toBe(4900)->and($charge['tax_known'])->toBeTrue();
});

it('⚠️⚠️ never reports `tax_known: true` without terms behind it', function () {
    // ⚠️ A FIELD THAT LIES IS THE DEFECT REGARDLESS OF THE NUMBER BESIDE IT. `tax_known: true` means "this
    //    gross is final"; it was being emitted for a snapshot that held nothing at all.
    foreach ([[], ['terms_pricing_type' => 'flat'], ['terms_pricing_type' => 'per_unit']] as $i => $captured) {
        try {
            $charge = PlanTerms::fromCaptured($captured)->charge();
            Assert::fail("snapshot #{$i} produced a charge of {$charge['net_minor']} with tax_known="
                .var_export($charge['tax_known'], true).' — from terms that do not exist');
        } catch (MissingPlanTermsException) {
            Assert::assertTrue(true);
        }
    }
});

it('⚠️ REFUSES to price a subscription with neither captured terms nor a resolvable plan', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();

    $plan = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
    $plan->delete();   // SoftDeletes — the one reachable way a plan_id stops resolving (an FK forbids the rest)

    // ⚠️ Created AFTER the delete, so the model's capture hook finds no plan and silently captures nothing —
    //    which is exactly how a real row reaches this state. Measured, not hypothesised.
    $subscription = Subscription::create([
        'organization_id' => (string) $s['org']->id, 'plan_id' => $plan->id, 'status' => 'active',
    ]);

    Assert::assertNull($subscription->fresh()->terms_captured_at,
        'the capture hook filled terms after all — this test is no longer exercising the uncaptured path');

    $this->expectException(MissingPlanTermsException::class);
    $subscription->chargeFor();
});

it('⚠️⚠️ renders /app/billing for a real subscriber whose plan row was deleted — it used to 404', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();

    $plan = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    // Created BEFORE the delete, so terms are captured — the normal case, and the one that matters: this
    // customer is being charged, and the page they manage that from must not vanish.
    $subscription = Subscription::create([
        'organization_id' => (string) $s['org']->id, 'plan_id' => $plan->id, 'status' => 'active',
    ]);
    Assert::assertNotNull($subscription->fresh()->terms_captured_at, 'terms were not captured — wrong fixture');

    $plan->delete();

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->get('/app/billing')
        ->assertOk();   // ⚠️ was 404, from `catalog()`'s firstOrFail — never from the summary, which guards
});

it('⚠️ REFUSES to sync when the terms are GENUINELY unknown — but that is now the only such case', function () {
    // ⚠️⚠️ THIS TEST CHANGED SHAPE BECAUSE THE DEFECT IT GUARDED WAS DELETED, NOT WORKED AROUND.
    //    It used to assert that the sync refuses whenever the plan ROW does not resolve. The shape now
    //    comes from the subscription's captured terms, so a deleted catalogue row no longer stops a
    //    per-unit subscriber syncing — that case does not need handling because it does not exist
    //    (`BillingShapeFromSnapshotTest` asserts the sync succeeds there). ⚠️ A case removed beats a case
    //    handled; what survives is the state where the answer is genuinely unknowable.
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();

    $plan = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
    $plan->delete();

    // Created AFTER the delete, so the capture hook finds no plan and captures nothing: no snapshot AND no
    // plan. What this subscription is billed on is not knowable from anything.
    $subscription = Subscription::create([
        'organization_id' => (string) $s['org']->id, 'plan_id' => $plan->id, 'status' => 'active',
        'stripe_subscription_id' => 'sub_test_quantity',
    ]);
    Assert::assertNull($subscription->fresh()->terms_captured_at, 'wrong fixture — terms were captured');

    $this->expectException(MissingPlanTermsException::class);
    app(BillingService::class)->syncSubscriptionQuantity($s['org']->fresh());
});
