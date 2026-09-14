<?php

use App\Jobs\Billing\SyncSubscriptionQuantityJob;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\Unit;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\BillingService;
use App\Support\Billing\BillableUnits;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ══ F3 · THE MISSING CALLER — WHAT IS PROVEN, AND WHAT IS NOT CLAIMED ═════════════════════════════
//
// ⚠️⚠️ NO REAL STRIPE CALL IS MADE ANYWHERE IN THIS FILE. `phpunit.xml` sets `STRIPE_FAKE=true`, so the
// container resolves `FakeStripeClient`, and every assertion below reads its `$quantityUpdates` array — the
// calls that WOULD have gone to Stripe. Nothing here touches the network or moves money.
//
//   ✅ PROVEN BELOW
//      · BOTH halves: a flat org's unit change sends NOTHING; a per-unit org's sends the RIGHT quantity;
//      · the second no-op — an org with no live `stripe_subscription_id` sends nothing;
//      · a ROLLED-BACK unit creation sends nothing (the `$afterCommit` guard);
//      · the count comes from the ACTIVE set — a pause re-quantifies DOWN;
//      · a non-count edit (a rename) sends nothing;
//      · the job needs no ambient tenant context, because the worker has none.
//
//   ⛔ NOT PROVABLE HERE — AND NOT CLAIMED
//      · the idempotency key. `FakeStripeClient::updateSubscriptionQuantity()` records subscription,
//        quantity and prorate and DISCARDS the key, so no assertion can reach it. Its derivation is
//        reported from source, not asserted: `subscription_quantity:{stripeId}_{quantity}`.
//      · the BULK collapse. `ShouldBeUniqueUntilProcessing` releases its lock when processing BEGINS, and
//        the `sync` driver processes instantly — so N creates run N syncs here. The collapse is a property
//        of a real queue worker and this suite cannot demonstrate it.

/** A per-unit plan, distinct from the one BillingPricingShapeTest defines. */
function f3PerUnitPlan(int $rateMinor = 900): Plan
{
    return Plan::create([
        'code' => 'f3_metered_monthly',
        'name' => 'F3 Metered',
        'price_minor' => 0,
        'pricing_type' => Plan::PRICING_PER_UNIT,
        'unit_rate_minor' => $rateMinor,
        'vat_pct' => 0,
        'currency' => 'EUR',
        'interval' => 'monthly',
        'is_active' => true,
        'stripe_price_id' => 'price_fake_f3_metered',
    ]);
}

function f3FlatPlan(): Plan
{
    return Plan::create([
        'code' => 'f3_flat_monthly',
        'name' => 'F3 Flat',
        'price_minor' => 4900,
        'pricing_type' => Plan::PRICING_FLAT,
        'vat_pct' => 0,
        'currency' => 'EUR',
        'interval' => 'monthly',
        'is_active' => true,
        'stripe_price_id' => 'price_fake_f3_flat',
    ]);
}

/** An org subscribed to `$plan`, live on Stripe unless `$stripeId` is null. */
function f3Subscribe(array $scenario, Plan $plan, ?string $stripeId = 'sub_fake_f3'): Organization
{
    Subscription::create([
        'organization_id' => $scenario['org']->getKey(),
        'plan_id' => $plan->getKey(),
        'status' => 'active',
        'stripe_subscription_id' => $stripeId,
    ]);

    return Organization::query()->findOrFail($scenario['org']->getKey());
}

/** Add one unit the way the product does — an ordinary Eloquent create on the model. */
function f3AddUnit(array $scenario): Unit
{
    $rt = RoomType::create(['property_id' => $scenario['property']->id, 'name' => 'F3 '.Str::random(4), 'kind' => 'room']);

    return Unit::create([
        'property_id' => $scenario['property']->id,
        'room_type_id' => $rt->id,
        'code' => 'F3-'.Str::upper(Str::random(6)),
        'is_active' => true,
    ]);
}

// ── ⚠️⚠️ HALF A — NOTHING CHANGES FOR ANY ORG THAT EXISTS TODAY ──────────────────────────────────
//
// ⚠️ All four seeded tier plans are FLAT, so this is the half that governs every real customer. If wiring
// the caller made a single Stripe call for them, the pass would have changed a live charging path.
it('⚠️ a FLAT-plan org adding a unit makes NO Stripe call at all', function () {
    $s = seedScenario(1);
    f3Subscribe($s, f3FlatPlan());

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    f3AddUnit($s);

    expect($fake->quantityUpdates)->toBe([]);
});

// ── ⚠️⚠️ HALF B — AND THE WIRING ACTUALLY WORKS IN THE DIRECTION THAT MATTERS ────────────────────
it('⚠️ a PER-UNIT org adding a unit syncs, with the org’s real active-unit count', function () {
    $s = seedScenario(1);
    f3Subscribe($s, f3PerUnitPlan());

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    f3AddUnit($s);

    $expected = BillableUnits::count();
    expect($expected)->toBeGreaterThan(0);

    $update = collect($fake->quantityUpdates)->last();
    expect($update)->not->toBeNull();
    expect($update['subscription'])->toBe('sub_fake_f3');
    expect($update['quantity'])->toBe($expected);
    // The ruled behaviour, unchanged by this pass: a mid-cycle change is charged now.
    expect($update['prorate'])->toBeTrue();
});

// ── ⚠️ THE SECOND NO-OP — A TRIAL OR PRE-CHECKOUT ORG HAS NOTHING TO RE-QUANTIFY ────────────────
//
// ⚠️ This guard already existed inside `syncSubscriptionQuantity()`. The point of the test is that the new
// caller does not BYPASS it — it reaches the sync and the sync refuses.
it('⚠️ an org with no live Stripe subscription makes no call, even on a per-unit plan', function () {
    $s = seedScenario(1);
    f3Subscribe($s, f3PerUnitPlan(), stripeId: null);

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    f3AddUnit($s);

    expect($fake->quantityUpdates)->toBe([]);
});

// ── ⚠️⚠️ THE ROLLBACK — TELLING STRIPE ABOUT A UNIT THAT NEVER EXISTED COSTS MONEY ───────────────
//
// ⚠️ Both real creation paths (`InventoryActionController::storeUnit`, `OwnerUnitRequestService::approve`)
// run inside `DB::transaction`. This reproduces that shape and rolls it back: the unit is gone, and Stripe
// must never have heard of it. `$afterCommit = true` on the job is the only thing making that true.
it('⚠️ a unit created inside a transaction that ROLLS BACK tells Stripe nothing', function () {
    $s = seedScenario(1);
    f3Subscribe($s, f3PerUnitPlan());

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    $before = BillableUnits::count();

    try {
        DB::transaction(function () use ($s): void {
            f3AddUnit($s);
            throw new RuntimeException('the write failed after the unit row');
        });
    } catch (RuntimeException) {
        // expected
    }

    // The unit really is gone…
    expect(BillableUnits::count())->toBe($before);
    // …and no quantity was ever sent for it.
    expect($fake->quantityUpdates)->toBe([]);
});

// ── ⚠️⚠️ THE SET — WHICH UNITS BILL, PROVEN BY MOVING ONE OUT OF IT ─────────────────────────────
//
// ⚠️ `BillableUnits` counts ACTIVE units, so an owner pausing a unit must reduce the billed quantity —
// charging for inventory the operator has taken off sale is the false-claim shape with money attached.
it('⚠️ pausing a unit re-quantifies DOWNWARD — the count is the ACTIVE set', function () {
    $s = seedScenario(1);
    f3Subscribe($s, f3PerUnitPlan());
    $unit = f3AddUnit($s);

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    $atFull = BillableUnits::count();
    $unit->forceFill(['is_active' => false])->save();

    $update = collect($fake->quantityUpdates)->last();
    expect($update)->not->toBeNull();
    expect($update['quantity'])->toBe($atFull - 1);
    expect(BillableUnits::count())->toBe($atFull - 1);
});

// ⚠️ AND THE FILTER: an edit that cannot move the count must not send a quantity Stripe already has.
it('⚠️ renaming a unit sends nothing — only is_active moves the billable count', function () {
    $s = seedScenario(1);
    f3Subscribe($s, f3PerUnitPlan());
    $unit = f3AddUnit($s);

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    $unit->forceFill(['name' => 'Renamed, still billable'])->save();

    expect($fake->quantityUpdates)->toBe([]);
});

// ── ⚠️ THE WORKER HAS NO TENANT CONTEXT, AND THE COUNT DEPENDS ON ONE ───────────────────────────
//
// ⚠️ `BillableUnits::count()` reads `Unit` under the organization global scope, which THROWS without a
// context. Every booking-confirmation email once died on exactly this. The payload is scalars only and the
// body establishes its own context — so the job must work with the ambient one cleared.
it('⚠️ syncs from a worker with no ambient tenant context', function () {
    $s = seedScenario(1);
    $org = f3Subscribe($s, f3PerUnitPlan());
    f3AddUnit($s);

    $expected = BillableUnits::count();

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    $tenant = app(TenantContext::class);
    $tenant->forget();
    expect($tenant->hasContext())->toBeFalse();

    (new SyncSubscriptionQuantityJob((string) $org->getKey()))
        ->handle($tenant, app(BillingService::class));

    // ⚠️ And the context is RESTORED (to "none") rather than leaked to the next job on the worker.
    expect($tenant->hasContext())->toBeFalse();

    $update = collect($fake->quantityUpdates)->last();
    expect($update)->not->toBeNull();
    expect($update['quantity'])->toBe($expected);
});
