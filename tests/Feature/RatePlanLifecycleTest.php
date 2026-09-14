<?php

use App\Models\AuditEvent;
use App\Models\RatePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// ── F-6's SIBLING — A RATE PLAN COULD BE CREATED AND NEVER CHANGED ──────────────────────────────
// ⚠️ A UNIT IS A ROOM; A RATE PLAN IS A PRICE. Every assertion reads the ROW, and `rate_calendar` is
// asserted unchanged wherever a write happens — it is this pass's primary pin.

function rpActor(array $s, string $role = 'Hotel Manager'): User
{
    return operatorUser($s, [], $role);
}

/** A second plan on the same room type, so the first is not the last one on sale. */
function rpSecondPlan(array $s): RatePlan
{
    $first = $s['ratePlan'];

    return RatePlan::create([
        'property_id' => $first->property_id,
        'room_type_id' => $first->room_type_id,
        'name' => 'Non-refundable', 'code' => 'NONREF-T',
        'currency' => (string) $first->currency,
        'is_refundable' => false, 'is_active' => true,
    ]);
}

it('⚠️ a rate plan can be renamed, and the trail names who did it with the previous value', function () {
    $s = seedScenario(1);
    $actor = rpActor($s);
    $actor->forceFill(['name' => 'Mara Silva'])->save();
    $plan = $s['ratePlan'];
    $oldName = (string) $plan->name;
    $ratesBefore = DB::table('rate_calendar')->count();
    $sumBefore = (int) DB::table('rate_calendar')->sum('price_minor');

    $this->actingAs($actor)->put(route('app.inventory.rate-plans.update', $plan->id), [
        'name' => 'Flexible (renamed)', 'code' => 'FLEX-R',
    ])->assertRedirect(route('app.rates'));

    expect((string) $plan->fresh()->name)->toBe('Flexible (renamed)');

    // ⚠️ THE PRIMARY PIN. An edit must not touch a single nightly price.
    expect(DB::table('rate_calendar')->count())->toBe($ratesBefore)
        ->and((int) DB::table('rate_calendar')->sum('price_minor'))->toBe($sumBefore);

    $row = AuditEvent::query()->where('action', 'rate_plan.updated')->latest('created_at')->firstOrFail();
    expect(User::find($row->actor_id)?->name)->toBe('Mara Silva')
        ->and($row->before['name'])->toBe($oldName)
        ->and($row->after['name'])->toBe('Flexible (renamed)');
});

it('⚠️ renaming a plan CANNOT reach a stay already sold', function () {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $r->reservationUnits()->firstOrFail();
    $priceBefore = (int) $line->price_minor;

    $this->actingAs(rpActor($s))->put(route('app.inventory.rate-plans.update', $s['ratePlan']->id), [
        'name' => 'Anything At All', 'code' => 'XYZ',
    ])->assertRedirect(route('app.rates'));

    // 🔬 The price is snapshotted at booking and the line references the plan by ID.
    expect((int) $line->fresh()->price_minor)->toBe($priceBefore);
});

it('⚠️⚠️ TAKING A PLAN OFF SALE MEANS SOMETHING — a stranger stops being offered it', function () {
    $s = seedScenario(1);
    $plan = $s['ratePlan'];
    rpSecondPlan($s);                       // so `plan` is not the last one on sale

    $roomTypeId = $plan->room_type_id;
    $offeredBefore = RatePlan::query()->where('room_type_id', $roomTypeId)->where('is_active', true)->count();
    expect($offeredBefore)->toBe(2);        // ⚠️ NON-VACUITY: the control case could have failed

    $this->actingAs(rpActor($s))
        ->post(route('app.inventory.rate-plans.active', $plan->id), ['active' => '0'])
        ->assertRedirect(route('app.rates'));

    // ⚠️ THE WRONG-FIX MUTATION'S TARGET: a flag that flips while the plan is still offered would pass a
    // column check and fail this. This is the SAME filter the storefront's offer loop and the booking
    // engine apply (`->where('is_active', true)` on a room type's plans).
    expect((bool) $plan->fresh()->is_active)->toBeFalse()
        ->and(RatePlan::query()->where('room_type_id', $roomTypeId)->where('is_active', true)->count())->toBe(1)
        ->and(RatePlan::query()->where('room_type_id', $roomTypeId)->where('is_active', true)->pluck('id')->all())
        ->not->toContain($plan->id);
});

it('⚠️⚠️ THE LAST PLAN ON SALE IS REFUSED — a silently unsellable room type is the expensive error', function () {
    $s = seedScenario(1);
    $plan = $s['ratePlan'];

    // The seeded scenario has exactly one plan for this room type.
    expect(RatePlan::query()->where('room_type_id', $plan->room_type_id)->where('is_active', true)->count())->toBe(1);

    $this->actingAs(rpActor($s))
        ->post(route('app.inventory.rate-plans.active', $plan->id), ['active' => '0'])
        ->assertRedirect(route('app.rates'))
        ->assertSessionHas('rates_error');

    // ⚠️ READ THE ROW, not the redirect. Nothing moved.
    expect((bool) $plan->fresh()->is_active)->toBeTrue();
});

it('⚠️ taking a plan off sale is REVERSIBLE and writes no nightly price', function () {
    $s = seedScenario(1);
    $plan = $s['ratePlan'];
    rpSecondPlan($s);
    $actor = rpActor($s);
    $sumBefore = (int) DB::table('rate_calendar')->sum('price_minor');

    $this->actingAs($actor)->post(route('app.inventory.rate-plans.active', $plan->id), ['active' => '0']);
    $this->actingAs($actor)->post(route('app.inventory.rate-plans.active', $plan->id), ['active' => '1']);

    expect((bool) $plan->fresh()->is_active)->toBeTrue()
        ->and((int) DB::table('rate_calendar')->sum('price_minor'))->toBe($sumBefore);
});

it('⚠️ both new routes are gated on rates.manage, and a role without it is refused', function (string $route, string $method) {
    $s = seedScenario(1);
    $viewer = rpActor($s, 'Reservation Agent');   // holds rates.view, NOT rates.manage
    $plan = $s['ratePlan'];

    $res = $method === 'put'
        ? $this->actingAs($viewer)->put(route($route, $plan->id), ['name' => 'NOPE'])
        : $this->actingAs($viewer)->post(route($route, $plan->id), ['active' => '0']);

    $res->assertForbidden();
    expect((string) $plan->fresh()->name)->not->toBe('NOPE')
        ->and((bool) $plan->fresh()->is_active)->toBeTrue();
})->with([['app.inventory.rate-plans.update', 'put'], ['app.inventory.rate-plans.active', 'post']]);

it('⚠️⚠️ THERE IS NO DELETE ROUTE, deliberately — it would destroy prices nothing can restore', function () {
    // 🔬 Measured at the schema: rate_calendar CASCADES (929 rows), and NOTHING records what a rate row
    // replaced — no prior-value column, no snapshot table, `before` null at all eleven rate-path audit
    // sites. reservation_units / room_types.default_rate_plan_id / rate_plans.derived_from_rate_plan_id
    // all SET NULL, leaving sold stays, room types and derived plans pointing at nothing.
    // ⚠️ Asserted so that adding one becomes a deliberate act that breaks a test.
    expect(Route::has('app.inventory.rate-plans.destroy'))->toBeFalse();
});
