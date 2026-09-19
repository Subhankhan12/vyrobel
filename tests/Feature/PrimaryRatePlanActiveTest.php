<?php

use App\Livewire\Operator\RatesGrid;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Services\UnitAvailabilityService;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * F-34 — A DEACTIVATED PLAN WAS STILL RETURNED AS THE ROOM TYPE'S PRIMARY.
 *
 * `RoomType::primaryRatePlan()` filtered `is_active` on its FALLBACK branch and not on its
 * DECLARED-DEFAULT branch. So taking the declared default off sale removed it from what a stranger is
 * offered (the storefront prices through `BookingEngineService::cheapestRatePlan()`, which does filter)
 * while every rate surface went on editing it.
 *
 * ⚠️ THERE WERE THREE COPIES OF THIS METHOD, not one: the model's, the operator Rates grid's, and
 * `UnitAvailabilityService`'s. The two copies did not even agree with the model — NEITHER of their
 * branches filtered; their fallback was saved only by a `where('is_active', true)` closure on an
 * eager-load hundreds of lines away. **Fixing only the reported one would have been the same defect with
 * fewer instances.** Every entry point is probed below.
 */

/** A room type with a declared default plus two more active plans — the ONLY shape where the defect is reachable. */
function f34RoomType(array $s): RoomType
{
    $first = $s['ratePlan'];

    foreach (['Non-refundable' => 'NONREF-F34', 'Advance saver' => 'ADV-F34'] as $name => $code) {
        RatePlan::create([
            'property_id' => $first->property_id,
            'room_type_id' => $first->room_type_id,
            'name' => $name, 'code' => $code,
            'currency' => (string) $first->currency,
            'is_refundable' => false, 'is_active' => true,
        ]);
    }

    $rt = RoomType::query()->findOrFail($first->room_type_id);
    $rt->forceFill(['default_rate_plan_id' => $first->getKey()])->save();

    return $rt->refresh();
}

it('⚠️ THE MODEL — a deactivated declared default is not returned; it falls through to an active plan', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);
    $default = $s['ratePlan'];

    // CONTROL: while the default is active it IS the primary. Without this, "never returns the default"
    // and "the method is broken" are the same observation.
    expect($rt->primaryRatePlan()?->getKey())->toBe($default->getKey());

    $default->forceFill(['is_active' => false])->save();

    $primary = RoomType::query()->findOrFail($rt->getKey())->primaryRatePlan();

    expect($primary)->not->toBeNull()
        ->and($primary->getKey())->not->toBe($default->getKey())
        ->and((bool) $primary->is_active)->toBeTrue();
});

it('⚠️ THE GRID COPY — the operator Rates grid resolves the same plan as the model', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);
    $s['ratePlan']->forceFill(['is_active' => false])->save();

    // Load it the way the grid does — `defaultRatePlan` UNCONSTRAINED, `ratePlans` pre-filtered.
    $loaded = RoomType::query()->whereKey($rt->getKey())
        ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])
        ->firstOrFail();

    $viaGrid = (fn () => $this->primaryRatePlan($loaded))
        ->call(new RatesGrid);

    expect($viaGrid)->not->toBeNull()
        ->and((bool) $viaGrid->is_active)->toBeTrue()
        ->and($viaGrid->getKey())->not->toBe($s['ratePlan']->getKey());
});

it('⚠️ THE SERVICE COPY — UnitAvailabilityService resolves an active plan too', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);
    $s['ratePlan']->forceFill(['is_active' => false])->save();

    $loaded = RoomType::query()->whereKey($rt->getKey())
        ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])
        ->firstOrFail();

    $viaService = (fn () => $this->primaryRatePlan($loaded))
        ->call(app(UnitAvailabilityService::class));

    expect($viaService)->not->toBeNull()
        ->and((bool) $viaService->is_active)->toBeTrue()
        ->and($viaService->getKey())->not->toBe($s['ratePlan']->getKey());
});

it('⚠️ all three entry points agree — which is the point of there being ONE definition', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);
    $s['ratePlan']->forceFill(['is_active' => false])->save();

    $loaded = RoomType::query()->whereKey($rt->getKey())
        ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])
        ->firstOrFail();

    $model = RoomType::query()->findOrFail($rt->getKey())->primaryRatePlan()?->getKey();
    $grid = (fn () => $this->primaryRatePlan($loaded))->call(new RatesGrid)?->getKey();
    $svc = (fn () => $this->primaryRatePlan($loaded))->call(app(UnitAvailabilityService::class))?->getKey();

    expect($grid)->toBe($model)->and($svc)->toBe($model);
});

it('⚠️ an eager-loaded caller is not turned into an N+1 by the fix', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);

    $loaded = RoomType::query()->whereKey($rt->getKey())
        ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])
        ->firstOrFail();

    DB::enableQueryLog();
    DB::flushQueryLog();
    $loaded->primaryRatePlan();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBe(0);   // resolved entirely from the loaded relations
});

// ── THE ONE-WAY DOOR ─────────────────────────────────────────────────────────────────────────────
// ⚠️ FIX 12 built the rate-plan routes and wired NO control, so the capability existed and no operator
// could reach it — F-20's shape, created by the fix programme itself. FIX 15 wired the controls.
//
// ⚠️ THESE ARE DATA ASSERTIONS AND THEY ARE NOT THE PROOF. A markup test passed four assertions
// throughout F-20 while the dialogs could never open; the proof for the controls is the browser pass
// recorded in the FIX 15 report (clicked, row read, dialog position measured). What these guard is the
// thing a browser pass cannot re-run: that an off-sale plan STAYS LISTED so it can be put back.

it('⚠️ an off-sale plan is still listed, so taking one off sale is not a one-way door', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);
    $operator = operatorUser($s, [], 'Hotel Manager');
    setPermissionsTeamId($s['org']->id);
    $propertyId = (string) $s['property']->getKey();
    app(PropertyContext::class)->activate([$propertyId], $propertyId);

    $s['ratePlan']->forceFill(['is_active' => false])->save();

    $plans = Livewire\Livewire::actingAs($operator)
        ->test(RatesGrid::class)
        ->viewData('ratePlans');

    expect($plans)->not->toBeEmpty();   // NON-VACUITY: an empty list satisfies any per-row assertion

    $row = collect($plans)->firstWhere('id', (string) $s['ratePlan']->getKey());

    // The plan the operator just took off sale is STILL on the only screen that can put it back.
    expect($row)->not->toBeNull()
        ->and($row['is_active'])->toBeFalse()
        // …and it sorts last, so the live plans stay at the top of the card.
        ->and(collect($plans)->last()['id'])->toBe((string) $s['ratePlan']->getKey());
});

it('⚠️ an off-sale plan is NOT offerable to a new season — a season must not hang off a dead plan', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);
    $operator = operatorUser($s, [], 'Hotel Manager');
    setPermissionsTeamId($s['org']->id);
    $propertyId = (string) $s['property']->getKey();
    app(PropertyContext::class)->activate([$propertyId], $propertyId);

    $s['ratePlan']->forceFill(['is_active' => false])->save();

    $html = Livewire\Livewire::actingAs($operator)
        ->test(RatesGrid::class)
        ->html();

    // CONTROL: an ACTIVE plan IS offered, so "no options at all" cannot pass this.
    $active = RatePlan::query()->where('room_type_id', $rt->getKey())
        ->where('is_active', true)->firstOrFail();

    $selector = str_contains($html, '<select id="vy-season-plan"')
        ? substr($html, strpos($html, '<select id="vy-season-plan"'))
        : '';
    $selector = substr($selector, 0, strpos($selector, '</select>') ?: 0);

    expect($selector)->toContain((string) $active->getKey())
        ->not->toContain((string) $s['ratePlan']->getKey());
});

/**
 * ⚠️⚠️ RULED BY THE FOUNDER, 2026-09-19 — R7: LEAVE THE POINTER.
 *
 * ⚠️ Taking a rate plan off sale does NOT clear `room_types.default_rate_plan_id`. The accessor's `is_active`
 * filter (the whole of F-34's fix, above) is the mechanism that makes this safe — the lookup steps over the
 * dead plan and falls through. The operator's declared choice is kept, so the default reasserts itself if the
 * plan goes back on sale.
 *
 * ⚠️⚠️ A FUTURE PASS WILL READ THE DANGLING POINTER AS AN ORPHAN AND "TIDY" IT. It is not an orphan; it is a
 * remembered preference. Clearing it on deactivation would silently discard the operator's choice.
 *
 * ⚠️ THE MUTATION: clear `default_rate_plan_id` when a plan is deactivated → the first assertion goes RED.
 */
it('⚠️ RULED 2026-09-19 (R7) — deactivating the declared default LEAVES the pointer in place, on purpose', function () {
    $s = seedScenario(1);
    $rt = f34RoomType($s);
    $declared = $s['ratePlan'];

    $declared->forceFill(['is_active' => false])->save();

    $fresh = RoomType::query()->findOrFail($rt->getKey());

    // ⚠️ THE RULING: the pointer is REMEMBERED, not cleared.
    expect((string) $fresh->default_rate_plan_id)
        ->toBe((string) $declared->getKey(), 'R7: the declared default is remembered, not discarded');

    // ⚠️ AND THE MECHANISM THAT MAKES THAT SAFE — the accessor steps over the dead plan (F-34).
    expect((string) $fresh->primaryRatePlan()?->getKey())
        ->not->toBe((string) $declared->getKey(), 'R7 is only safe because the accessor filters is_active');
});
