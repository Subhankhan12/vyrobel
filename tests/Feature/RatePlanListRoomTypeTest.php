<?php

use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\User;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// D18-6 — the Rate plans list says WHICH room type each plan prices.
//
// ⚠️ THE DEFECT WAS INDISTINGUISHABLE ROWS ON A PRICING SCREEN. A rate plan belongs to a room type, and
// several types routinely carry a plan of the same name — the demo has THREE active "Flexible" plans. The
// list rendered only the plan name and its meta line, so those rows were word-for-word identical and an
// operator could not tell which plan they were reading.
//
// ⚠️ READ PAYLOAD + DISPLAY ONLY. No rate value, no derivation, no pricing computation is touched — test 4
// pins that with a raw before/after read of `rate_calendar`.
//
// ⚠️ ASSERTED THROUGH THE RENDERED PAGE, NOT `Livewire::test`. The grid scopes on the PROPERTY CONTEXT, which
// is resolved by HTTP middleware — a component test mounts with an empty read set and renders no plans at
// all (#20's shape, one layer along). And the needles are the plan list's OWN composed line: a bare "Std"
// also appears in the grid rows above, so a loose needle proves nothing.

/** Two room types under one property, each carrying a plan of the SAME name — the real collision. */
function ratePlanFixture(): array
{
    $s = seedScenario(1);
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s, [], 'Revenue Manager');

    // The scenario ships room type "Std" with plan "Flex". Add a second type carrying the same plan name.
    $second = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Suite', 'kind' => 'room']);
    RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $second->id, 'name' => 'Flex', 'currency' => 'EUR']);

    return ['s' => $s, 'operator' => $operator, 'second' => $second];
}

/** The plan list's own composed meta line for a base, refundable plan. */
const RATE_PLAN_BASE_SUB = ' · Free cancellation · the grid above';

// ── 1. ⚠️⚠️ THE COLLISION IS RESOLVED — two identically-named plans read differently ──────────────
it('distinguishes two identically-named rate plans by their room type', function () {
    $f = ratePlanFixture();

    $this->actingAs($f['operator'])->get('/app/rates')
        ->assertOk()
        // Same plan name, same meta line — the room type is the ONLY thing telling them apart, and it is
        // now on the line rather than absent from the payload.
        ->assertSee('Std'.RATE_PLAN_BASE_SUB, false)
        ->assertSee('Suite'.RATE_PLAN_BASE_SUB, false);
});

// ── 2. Every plan row names a room type ──────────────────────────────────────────────────────────
it('names a room type on every rate plan row', function () {
    $f = ratePlanFixture();

    $html = $this->actingAs($f['operator'])->get('/app/rates')->assertOk()->getContent();

    // One composed line per active plan in scope — none left bare.
    $plans = DB::table('rate_plans')
        ->join('room_types', 'room_types.id', '=', 'rate_plans.room_type_id')
        ->where('room_types.property_id', $f['s']['property']->id)
        ->where('rate_plans.is_active', true)
        ->select('room_types.name as room_type')
        ->get();

    expect($plans)->not->toBeEmpty();

    foreach ($plans as $plan) {
        expect($html)->toContain($plan->room_type.RATE_PLAN_BASE_SUB);
    }
});

// ── 3. ⚠️ THE LABEL CANNOT BE EMPTY, BECAUSE THE PLAN CANNOT BE LISTED WITHOUT ITS TYPE ──────────
// ⚠️ AN EDGE TEST THAT WAS RETIRED FOR PRETENDING. The first version deleted a room type and asserted no
// dangling "·" — but a plan is only listed when its room type is in the grid's loaded set, so deleting the
// type removes the PLAN too and the empty-label branch never renders. A mutation that printed a lone
// separator passed that test happily. The reachable truth is the one below: the listed set is exactly the
// plans whose type is in scope, so every row has a name to show. (The `?? ''` in the payload stays as cheap
// null-safety, not as a guarded behaviour.)
it('lists only plans whose room type is in scope, so no row can lack a label', function () {
    $f = ratePlanFixture();

    $before = substr_count($this->actingAs($f['operator'])->get('/app/rates')->assertOk()->getContent(), RATE_PLAN_BASE_SUB);
    expect($before)->toBe(2);

    // Remove one room type: its plan leaves the list with it — it does not linger label-less.
    DB::table('room_types')->where('id', $f['second']->id)->delete();

    $html = $this->actingAs($f['operator'])->get('/app/rates')->assertOk()->getContent();

    expect(substr_count($html, RATE_PLAN_BASE_SUB))->toBe(1)
        ->and($html)->toContain('Std'.RATE_PLAN_BASE_SUB)
        ->and($html)->not->toContain('Suite'.RATE_PLAN_BASE_SUB);
});

// ── 4. ⚠️⚠️ MONEY UNTOUCHED — not one rate value moved ────────────────────────────────────────────
it('changes no rate value', function () {
    $f = ratePlanFixture();

    // A real priced night through the CERTIFIED writer. ⚠️ The range is HALF-OPEN: start === end writes
    // nothing at all, which reads exactly like a broken fixture.
    $plan = RatePlan::query()->where('room_type_id', $f['s']['roomType']->id)->firstOrFail();
    app(RateAndRestrictionService::class)->setRate($plan, '2026-09-02', '2026-09-03', Money::of(18500, 'EUR'));

    expect(DB::table('rate_calendar')->where('rate_plan_id', $plan->id)->value('price_minor'))->toBe(18500);

    $before = DB::table('rate_calendar')->orderBy('id')->get()->toJson();

    $this->actingAs($f['operator'])->get('/app/rates')->assertOk();

    // ⚠️ Byte-identical: rendering the grid reads prices, it never writes them.
    expect(DB::table('rate_calendar')->orderBy('id')->get()->toJson())->toBe($before);
});

// ── 5. The plan set is unchanged — this added no plan and hid none ───────────────────────────────
it('lists exactly the plans it listed before', function () {
    $f = ratePlanFixture();

    $expected = (int) DB::table('rate_plans')
        ->join('room_types', 'room_types.id', '=', 'rate_plans.room_type_id')
        ->where('room_types.property_id', $f['s']['property']->id)
        ->where('rate_plans.is_active', true)
        ->count();

    $html = $this->actingAs($f['operator'])->get('/app/rates')->assertOk()->getContent();

    // Count the plan list's own composed lines — one per active plan, no more and no fewer.
    expect(substr_count($html, RATE_PLAN_BASE_SUB))->toBe($expected);
});

// ── 6. ⚠️ The room type is READ, not invented — renaming the type renames the label ───────────────
it('reads the room type from the record rather than a copy', function () {
    $f = ratePlanFixture();

    DB::table('room_types')->where('id', $f['second']->id)->update(['name' => 'Penthouse']);

    $this->actingAs($f['operator'])->get('/app/rates')
        ->assertOk()
        ->assertSee('Penthouse'.RATE_PLAN_BASE_SUB, false)
        ->assertDontSee('Suite'.RATE_PLAN_BASE_SUB, false);
});
