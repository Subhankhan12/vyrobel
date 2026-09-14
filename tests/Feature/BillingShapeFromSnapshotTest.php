<?php

declare(strict_types=1);

use App\Exceptions\MissingPlanTermsException;
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
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ THE BILLING SHAPE IS A TERM, SO IT COMES FROM THE SNAPSHOT. THE QUANTITY IS NOT, SO IT STAYS LIVE.
 *
 * `PlanTerms`' own docblock has always said **"Terms frozen, quantity live."** The code froze the RATE and
 * read the SHAPE from the live catalogue row — the opposite of what the sentence promises, and the fourth
 * time in this project that a comment on a money path described something the code does not do. The comment
 * was right.
 *
 * ⚠️ WHY THE SHAPE IS A TERM. By the line drawn in `docs/BILLING-PLAN-ASSUMPTIONS.md` — *a term is anything
 * that decides what the customer pays* — flat-versus-per-unit **is** the billing model. Read live, it is a
 * term that can change under a customer, which is the whole reason the snapshot exists.
 *
 * ⚠️ AND WHY THE QUANTITY IS NOT. An org that adds a unit genuinely owes for it, so the COUNT is derived at
 * the moment of billing. Freezing it would bill a stale number. These two halves must not be confused, and
 * one of the mutations below exists purely to defend the half that must not move.
 *
 * ⚠️ Nothing here is proven over `artisan serve` — it was shown to execute a stale copy of an `app/` class,
 * which made a source mutation look identical in both directions. Every assertion calls the service.
 */
uses(RefreshDatabase::class);

function bsPerUnitPlan(string $code = 'bs_metered_monthly', int $rateMinor = 900): Plan
{
    return Plan::create([
        'code' => $code, 'name' => 'BS Metered', 'price_minor' => 0,
        'pricing_type' => Plan::PRICING_PER_UNIT, 'unit_rate_minor' => $rateMinor,
        'vat_pct' => 0, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true,
        'stripe_price_id' => 'price_fake_bs_metered',
    ]);
}

function bsFlatPlan(string $code = 'bs_flat_monthly'): Plan
{
    return Plan::create([
        'code' => $code, 'name' => 'BS Flat', 'price_minor' => 4900,
        'pricing_type' => Plan::PRICING_FLAT, 'vat_pct' => 0, 'currency' => 'EUR',
        'interval' => 'monthly', 'is_active' => true, 'stripe_price_id' => 'price_fake_bs_flat',
    ]);
}

function bsSubscribe(array $scenario, Plan $plan, ?string $stripeId = 'sub_fake_bs'): Organization
{
    Subscription::create([
        'organization_id' => $scenario['org']->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'stripe_subscription_id' => $stripeId,
    ]);

    return Organization::query()->findOrFail($scenario['org']->getKey());
}

/** Add units the way the product does — ordinary Eloquent creates. */
function bsAddUnits(array $scenario, int $n): void
{
    $rt = RoomType::create([
        'property_id' => $scenario['property']->id, 'name' => 'BS '.Str::random(4), 'kind' => 'room',
    ]);

    for ($i = 0; $i < $n; $i++) {
        Unit::create([
            'property_id' => $scenario['property']->id, 'room_type_id' => $rt->id,
            'code' => 'BS-'.Str::upper(Str::random(6)), 'is_active' => true,
        ]);
    }
}

/** The calls that WOULD have gone to Stripe. Nothing here touches the network or moves money. */
function bsFake(): object
{
    return app(StripeClientInterface::class);
}

it('⚠️⚠️ keeps an existing subscriber on their CAPTURED shape when an admin converts the live plan', function () {
    $s = seedScenario(1);
    $plan = bsFlatPlan();
    $org = bsSubscribe($s, $plan);

    $captured = Subscription::query()->where('organization_id', $org->getKey())->firstOrFail();
    Assert::assertSame(Plan::PRICING_FLAT, $captured->terms_pricing_type, 'the fixture did not capture a flat shape');

    // ⚠️ THE ADMIN CONVERTS THE CATALOGUE ROW. This is a real, reachable edit (`Admin\PlanController::update`
    //    writes `pricing_type`), and before this change it silently re-modelled every existing subscriber.
    $plan->update(['pricing_type' => Plan::PRICING_PER_UNIT, 'unit_rate_minor' => 900, 'price_minor' => 0]);

    bsFake()->quantityUpdates = [];
    $result = app(BillingService::class)->syncSubscriptionQuantity($org->fresh());

    Assert::assertNull($result,
        'the subscriber was re-modelled as per-unit by an edit to the catalogue row. The shape they agreed '
        .'to is captured on their subscription; reading it live lets a term change under a customer.');
    Assert::assertSame([], bsFake()->quantityUpdates,
        'a quantity was pushed to Stripe for a subscriber whose captured terms are FLAT');
});

it('⚠️ still syncs a genuinely PER-UNIT subscriber, and the quantity is the LIVE unit count', function () {
    $s = seedScenario(1);
    // ⚠️⚠️ VACUITY FIRST. The bare scenario has exactly ONE billable unit, so `assertSame(count(), $quantity)`
    //    would have passed against a frozen `return 1` — the mutation that defends "quantity live" could not
    //    have gone red. Units are added until the count is unmistakably not a constant.
    bsAddUnits($s, 3);
    $live = BillableUnits::count();
    Assert::assertGreaterThan(1, $live, 'the fixture has one unit, so this assertion cannot discriminate');

    $org = bsSubscribe($s, bsPerUnitPlan());

    bsFake()->quantityUpdates = [];
    $quantity = app(BillingService::class)->syncSubscriptionQuantity($org->fresh());

    // ⚠️ THE HALF THAT MUST NOT MOVE. `BillableUnits::count()` is read at the moment of billing, because an
    //    org that adds a unit genuinely owes for it. A mutation that froze this must go red.
    Assert::assertSame($live, $quantity, 'the synced quantity is not the org’s live billable-unit count');
    Assert::assertNotSame([], bsFake()->quantityUpdates, 'no quantity reached Stripe for a per-unit subscriber');
});

it('⚠️⚠️ syncs a PER-UNIT subscriber even when the live plan row no longer resolves — the case is GONE', function () {
    $s = seedScenario(1);
    $plan = bsPerUnitPlan();
    $org = bsSubscribe($s, $plan);

    $plan->delete();   // SoftDeletes — `$subscription->plan` is now null

    bsFake()->quantityUpdates = [];
    $quantity = app(BillingService::class)->syncSubscriptionQuantity($org->fresh());

    // ⚠️ THIS USED TO THROW. Deciding whether to sync no longer needs the plan ROW at all, so the
    //    "plan does not resolve" branch does not need handling — it does not exist. A case removed beats a
    //    case handled, and this is the assertion that says so.
    Assert::assertSame(BillableUnits::count(), $quantity,
        'a per-unit subscriber stopped syncing because a catalogue row was archived — the terms it needs '
        .'are on the subscription');
});

it('⚠️ a FLAT org still makes NO Stripe call — the no-op every existing org depends on', function () {
    $s = seedScenario(1);
    $org = bsSubscribe($s, bsFlatPlan());

    bsFake()->quantityUpdates = [];

    Assert::assertNull(app(BillingService::class)->syncSubscriptionQuantity($org->fresh()));
    Assert::assertSame([], bsFake()->quantityUpdates,
        'a flat subscription sent a quantity to Stripe — that would multiply the bill');
});

it('⚠️ treats a BACKFILLED row by its captured shape, and refuses one with no shape at all', function () {
    $s = seedScenario(1);
    $org = bsSubscribe($s, bsPerUnitPlan());
    $sub = Subscription::query()->where('organization_id', $org->getKey())->firstOrFail();

    // ⚠️ THE MIGRATION'S BACKFILL WRITES `terms_pricing_type` (coalescing a null plan shape to flat), so every
    //    backfilled row HAS a shape — checked before this change, because reading the shape from a snapshot
    //    that lacked one would have broken every backfilled subscription in the database.
    Assert::assertSame(Plan::PRICING_PER_UNIT, $sub->terms_pricing_type);

    // ⚠️ AND IF A SHAPE WERE SOMEHOW ABSENT, THE FALLBACK IS NOT "ASSUME FLAT". Assuming flat would silently
    //    bill a per-unit customer once instead of per unit — a wrong number, confidently. It refuses.
    $sub->forceFill(['terms_pricing_type' => null])->saveQuietly();

    $this->expectException(MissingPlanTermsException::class);
    app(BillingService::class)->syncSubscriptionQuantity($org->fresh());
});

it('⚠️⚠️ REFUSES a plan change it cannot re-capture, rather than freezing the old terms under a new plan', function () {
    $s = seedScenario(1);
    $org = bsSubscribe($s, bsFlatPlan());
    $sub = Subscription::query()->where('organization_id', $org->getKey())->firstOrFail();
    Assert::assertSame(4900, (int) $sub->terms_price_minor);

    $target = bsPerUnitPlan('bs_target_monthly');
    $target->delete();   // the new plan does not resolve

    // ⚠️ A QUIET FAILURE HERE IS NOT "NO TERMS", IT IS THE OLD TERMS SURVIVING UNDER A NEW `plan_id` — every
    //    screen would show the new plan while billing kept the old model. That is a confident wrong answer,
    //    and this pass is what made the capture load-bearing enough to matter.
    try {
        $sub->update(['plan_id' => $target->getKey()]);
        Assert::fail('the plan change was accepted without re-capturing its terms');
    } catch (MissingPlanTermsException) {
        Assert::assertSame(4900, (int) $sub->fresh()->terms_price_minor, 'the old terms were altered anyway');
    }
});

it('⚠️ still re-captures on a plan change that CAN resolve — the guard is not wider than the defect', function () {
    $s = seedScenario(1);
    $org = bsSubscribe($s, bsFlatPlan());
    $sub = Subscription::query()->where('organization_id', $org->getKey())->firstOrFail();

    $sub->update(['plan_id' => bsPerUnitPlan()->getKey()]);

    $sub->refresh();
    expect($sub->terms_pricing_type)->toBe(Plan::PRICING_PER_UNIT)
        ->and((int) $sub->terms_unit_rate_minor)->toBe(900);
});
