<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\Unit;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\ChannelConnectionService;
use App\Services\PropertyRemovalService;
use App\Support\Billing\BillableUnits;
use App\Support\Billing\SubscriptionPricing;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ══ ARCHIVING A PROPERTY MUST STOP BILLING FOR ITS UNITS ══════════════════════════════════════════
//
// ⚠️⚠️ THE DEFECT, MEASURED BEFORE IT WAS FIXED: `PropertyRemovalService::archive()` soft-deletes the
// PROPERTY and deliberately leaves every unit row untouched (reservations, folios and statements must stay
// queryable, so nothing may cascade). `BillableUnits::count()` counted units, not properties — so the
// billable quantity did not move, and an operator kept being counted for inventory they believed was gone.
//
// ⚠️ THE FIX IS A FILTER, NEVER THE FLAG. `is_active = false` means the OWNER took a unit off sale (#69).
// Writing it on archive would fuse two different facts into one bit and destroy what any future un-archive
// would need — you could no longer tell which units were paused on purpose. Filtering by the property
// mutates nothing, so a restore needs no memory of prior state. Both halves are asserted below.
//
//   ⛔ NOT CLAIMED HERE
//      · that other surfaces hide an archived property's units. THEY DO NOT — the operator global search
//        and the owner portal both still return them (org-scoped `Unit::query()` with no property filter).
//        That is reported, not fixed, and deliberately not asserted here as though it were settled.
//      · that a real Stripe call happens. `STRIPE_FAKE=true`; every assertion reads `FakeStripeClient`.

/** A second property in the same org, with `$active` live units plus one paused and one out-of-service. */
function apProperty(string $name = 'Annexe'): array
{
    $p = Property::create([
        'name' => $name, 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC',
        'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)), 'status' => 'active',
    ]);
    $rt = RoomType::create(['property_id' => $p->id, 'name' => $name.' Std', 'kind' => 'room']);

    $mk = fn (string $code, array $extra = []) => Unit::create(array_merge([
        'property_id' => $p->id, 'room_type_id' => $rt->id, 'code' => $code, 'is_active' => true,
    ], $extra));

    return [
        'property' => $p,
        'live' => $mk('AP-LIVE'),
        'paused' => $mk('AP-PAUSED', ['is_active' => false]),
        'oos' => $mk('AP-OOS', ['condition_status' => 'out_of_service']),
    ];
}

function apArchive(Property $p): void
{
    app(PropertyRemovalService::class)->archive($p->fresh(), app(ChannelConnectionService::class));
}

// ── ⚠️⚠️ THE COUNT — the defect itself ───────────────────────────────────────────────────────────
it('⚠️ stops counting a property’s units the moment the property is archived', function () {
    seedScenario(2);                 // 2 billable units that must SURVIVE the archive
    $ap = apProperty();

    // AP-LIVE and AP-OOS are billable; AP-PAUSED is not. 2 + 2 = 4.
    expect(BillableUnits::count())->toBe(4);

    apArchive($ap['property']);

    // ⚠️ Only the surviving property's units remain. Before the fix this was still 4.
    expect(BillableUnits::count())->toBe(2);
});

// ⚠️ AND THE UNIT ROWS ARE UNTOUCHED — the archive changed the COUNT, not the inventory.
it('⚠️ leaves every unit row exactly as it was — no flag is rewritten', function () {
    seedScenario(1);
    $ap = apProperty();

    apArchive($ap['property']);

    // ⚠️ THE ROWS STILL EXIST (a soft-deleted property must not cascade) …
    //
    // ⚠️⚠️ AND "EXIST" MEANS `trashed() === false`, NOT `!== null`. `withoutGlobalScopes()` drops the
    //    SoftDeletingScope along with the tenant one, so `find()` returns a CASCADED unit exactly as it
    //    returns an untouched one — the assertion this replaces passed with BOTH rows soft-deleted, which
    //    is precisely the cascade the test exists to rule out. Demonstrated by planting, not argued.
    $live = Unit::withoutGlobalScopes()->withTrashed()->find($ap['live']->id);
    $paused = Unit::withoutGlobalScopes()->withTrashed()->find($ap['paused']->id);
    expect($live)->not->toBeNull()->and($live->trashed())->toBeFalse();
    expect($paused)->not->toBeNull()->and($paused->trashed())->toBeFalse();

    // ⚠️ … AND `is_active` MEANS WHAT IT ALWAYS MEANT. The live unit was NOT deactivated by the archive,
    //    and the owner-paused one is still paused — the information a future un-archive would need.
    expect((bool) $live->is_active)->toBeTrue();
    expect((bool) $paused->is_active)->toBeFalse();
});

// ── ⚠️⚠️ RESTORE — proven, even though no in-product path reaches it ─────────────────────────────
//
// ⚠️ THERE IS NO UN-ARCHIVE ROUTE, COMMAND OR SERVICE METHOD IN THE PRODUCT. Removal is cancellable before
// it runs and irreversible after. This restores the model directly to prove the PROPERTY OF THE FIX: because
// nothing was mutated, the count comes back correct on its own — including the unit the owner had paused,
// which must NOT come back on sale.
it('⚠️ returns the right count on restore — and a pre-paused unit stays paused', function () {
    seedScenario(2);
    $ap = apProperty();
    $ap['paused'];                              // paused BEFORE the archive

    apArchive($ap['property']);
    expect(BillableUnits::count())->toBe(2);

    Property::withTrashed()->findOrFail($ap['property']->id)->restore();

    // ⚠️ Back to 4, not 5 — the paused unit did not silently return to sale.
    expect(BillableUnits::count())->toBe(4);
    expect((bool) Unit::query()->findOrFail($ap['paused']->id)->is_active)->toBeFalse();
});

// ── ⚠️ THE DELIBERATE BEHAVIOUR THE FILTER MUST NOT CATCH ────────────────────────────────────────
//
// ⚠️ An OUT-OF-SERVICE unit on a LIVE property still bills — `BillableUnits` is deliberately not
// `Unit::scopeBookable()`, because maintenance inventory is still the operator's inventory. The founder has
// not ruled otherwise. A `whereHas` that reached past the soft delete could silently break this.
it('⚠️ still bills an out-of-service unit while its property is live', function () {
    seedScenario(1);
    $p = Property::query()->firstOrFail();
    $rt = RoomType::query()->firstOrFail();

    Unit::create([
        'property_id' => $p->id, 'room_type_id' => $rt->id, 'code' => 'OOS-LIVE',
        'is_active' => true, 'condition_status' => 'out_of_service',
    ]);

    expect(BillableUnits::count())->toBe(2);   // the seeded unit + the out-of-service one
});

// ── ⚠️⚠️ THE SYNC MUST FIRE ON ARCHIVE — the F3 defect one table over ────────────────────────────
//
// ⚠️ Archiving touches only the property row, so NONE of `Unit`'s model events fire. Without a hook on
// `Property` the count would move and Stripe would never be told.
it('⚠️ syncs the subscription quantity when a property is archived', function () {
    $s = seedScenario(2);
    $ap = apProperty();

    $plan = Plan::create([
        'code' => 'ap_metered_monthly', 'name' => 'AP Metered', 'price_minor' => 0,
        'pricing_type' => Plan::PRICING_PER_UNIT, 'unit_rate_minor' => 900, 'vat_pct' => 0,
        'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true,
        'stripe_price_id' => 'price_fake_ap',
    ]);
    Subscription::create([
        'organization_id' => $s['org']->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'stripe_subscription_id' => 'sub_fake_ap',
    ]);

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    apArchive($ap['property']);

    $update = collect($fake->quantityUpdates)->last();
    expect($update)->not->toBeNull();
    expect($update['subscription'])->toBe('sub_fake_ap');
    expect($update['quantity'])->toBe(2);          // the archived property's units are gone from the quantity
    expect($update['quantity'])->toBe(BillableUnits::count());
});

// ⚠️ AND IT WORKS FROM THE NIGHTLY COMMAND, WHICH HAS NO REQUEST AND NO AMBIENT TENANT CONTEXT.
// `properties:process-removals` discovers across tenants as super-admin, then acts inside
// `forOrganization()`. If the hook depended on a request this would be the run that broke.
it('⚠️ syncs from the scheduled removal command, with no request and no ambient context', function () {
    $s = seedScenario(2);
    $ap = apProperty();

    $plan = Plan::create([
        'code' => 'ap_nightly_monthly', 'name' => 'AP Nightly', 'price_minor' => 0,
        'pricing_type' => Plan::PRICING_PER_UNIT, 'unit_rate_minor' => 900, 'vat_pct' => 0,
        'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true,
        'stripe_price_id' => 'price_fake_ap2',
    ]);
    Subscription::create([
        'organization_id' => $s['org']->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'stripe_subscription_id' => 'sub_fake_ap2',
    ]);

    // Due yesterday, so the nightly pass picks it up.
    $ap['property']->forceFill([
        'removal_scheduled_at' => now()->subDays(2),
        'removal_effective_at' => now()->subDay(),
    ])->save();

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    // ⚠️ THE SCHEDULER'S CONDITIONS: no request, no tenant context at all.
    $tenant = app(TenantContext::class);
    $tenant->forget();
    expect($tenant->hasContext())->toBeFalse();

    $this->artisan('properties:process-removals')->assertExitCode(0);

    // ⚠️ The context is still cleared — the command restores its own, exactly as the scheduler leaves it —
    //    so this read must establish one, the same as any other consumer would.
    $archived = $tenant->forOrganization(
        (string) $s['org']->getKey(),
        fn () => Property::withTrashed()->findOrFail($ap['property']->id)->trashed(),
    );
    expect($archived)->toBeTrue();

    $update = collect($fake->quantityUpdates)->last();
    expect($update)->not->toBeNull();
    expect($update['quantity'])->toBe(2);
});

// ── ⚠️⚠️ THE VISIBLE CONSEQUENCE — display and charge share ONE expression, on purpose ───────────
//
// ⚠️ So the number an operator READS on the billing page drops too. That is correct (its label is "Units on
// the rack", and an archived property's units are not on the rack) but it IS a visible change, and this
// test pins that the two stay equal rather than drifting apart.
it('⚠️ moves the DISPLAYED unit number with the charged one — never one without the other', function () {
    seedScenario(2);
    $ap = apProperty();

    expect(SubscriptionPricing::current('monthly')['units'])->toBe(BillableUnits::count());

    apArchive($ap['property']);

    expect(SubscriptionPricing::current('monthly')['units'])->toBe(2);
    expect(SubscriptionPricing::current('monthly')['units'])->toBe(BillableUnits::count());
});

// ── ⚠️ NOTHING MOVES FOR AN ORG ON A FLAT PLAN — which is every org that exists today ────────────
it('⚠️ makes no Stripe call at all when the org is on a FLAT plan', function () {
    $s = seedScenario(2);
    $ap = apProperty();

    $plan = Plan::create([
        'code' => 'ap_flat_monthly', 'name' => 'AP Flat', 'price_minor' => 4900,
        'pricing_type' => Plan::PRICING_FLAT, 'vat_pct' => 0, 'currency' => 'EUR',
        'interval' => 'monthly', 'is_active' => true, 'stripe_price_id' => 'price_fake_ap_flat',
    ]);
    Subscription::create([
        'organization_id' => $s['org']->getKey(), 'plan_id' => $plan->getKey(),
        'status' => 'active', 'stripe_subscription_id' => 'sub_fake_ap_flat',
    ]);
    Organization::query()->findOrFail($s['org']->getKey());

    $fake = app(StripeClientInterface::class);
    $fake->quantityUpdates = [];

    apArchive($ap['property']);

    expect($fake->quantityUpdates)->toBe([]);
});
