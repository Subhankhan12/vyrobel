<?php

use App\Models\AuditEvent;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// ── F-6 — A UNIT COULD BE CREATED AND NEVER CHANGED ─────────────────────────────────────────────
// ⚠️ THE FIRST BUILD IN THE PROGRAMME, on an object reservations, rates, housekeeping, availability and
// the owner ledger all hang from. Every assertion reads the ROW or the RENDERED page.

function ulActor(array $s, string $role = 'Hotel Manager'): User
{
    return operatorUser($s, [], $role);
}

it('⚠️ a unit can be RENAMED, and the trail names who did it with the previous value', function () {
    $s = seedScenario(1);
    $actor = ulActor($s);
    $actor->forceFill(['name' => 'Paulo Nunes'])->save();
    $unit = $s['units'][0];
    $oldCode = (string) $unit->code;

    $this->actingAs($actor)->put(route('app.inventory.units.update', $unit->id), [
        'code' => 'A1-RENAMED', 'name' => 'Sea view corner', 'max_occupancy' => 3,
    ])->assertRedirect(route('app.units'));

    $fresh = $unit->fresh();
    expect((string) $fresh->code)->toBe('A1-RENAMED')
        ->and((string) $fresh->name)->toBe('Sea view corner')
        ->and((int) $fresh->max_occupancy)->toBe(3);

    $row = AuditEvent::query()->where('action', 'unit.updated')->latest('created_at')->firstOrFail();
    expect(User::find($row->actor_id)?->name)->toBe('Paulo Nunes')
        ->and($row->before['code'])->toBe($oldCode)          // ⚠️ a real `before`, not null
        ->and($row->after['code'])->toBe('A1-RENAMED');
});

it('⚠️ renaming a unit CANNOT reach a stay already sold', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $r = book($s, ['unit_id' => $unit->id]);
    $line = $r->reservationUnits()->firstOrFail();
    $priceBefore = (int) $line->price_minor;

    $this->actingAs(ulActor($s))->put(route('app.inventory.units.update', $unit->id), [
        'code' => 'RENAMED-2', 'name' => 'Anything', 'max_occupancy' => 4,
    ])->assertRedirect(route('app.units'));

    // 🔬 The price is snapshotted at booking and the line points at the unit by ID — so the rename is
    // structurally unable to reprice or re-point it. This bounds the whole risk of the edit path.
    expect((int) $line->fresh()->price_minor)->toBe($priceBefore)
        ->and((string) $line->fresh()->unit_id)->toBe((string) $unit->id);
});

it('⚠️⚠️ DEACTIVATION MEANS SOMETHING — the unit stops being offered, not just flagged', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];

    $sellableBefore = Unit::query()->bookable()->where('id', $unit->id)->count();
    expect($sellableBefore)->toBe(1);

    $totalBefore = (int) DB::table('availability')->where('room_type_id', $unit->room_type_id)->sum('total');
    expect($totalBefore)->toBeGreaterThan(0);   // non-vacuity: there is capacity to remove

    $this->actingAs(ulActor($s))
        ->post(route('app.inventory.units.active', $unit->id), ['active' => '0'])
        ->assertRedirect(route('app.units'));

    // ⚠️ THE WRONG-FIX MUTATION'S TARGET, AND MY FIRST VERSION OF IT WAS TOO WEAK. `scopeBookable()` reads
    // `is_active` directly, so a naive flag flip would have satisfied it. What actually stops a stranger
    // being offered the unit is the AVAILABILITY RECOMPUTE — `total` counts only active units — so that is
    // what is asserted. A deactivation that does not move `total` is a label, not an effect.
    expect((bool) $unit->fresh()->is_active)->toBeFalse()
        ->and(Unit::query()->bookable()->where('id', $unit->id)->count())->toBe(0)
        ->and(DB::table('availability')->where('room_type_id', $unit->room_type_id)->sum('total'))
        ->toBeLessThan($totalBefore, 'deactivation did not remove the unit from availability');
});

it('⚠️ deactivation is REVERSIBLE and leaves existing bookings untouched', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $r = book($s, ['unit_id' => $unit->id]);
    $actor = ulActor($s);

    $this->actingAs($actor)->post(route('app.inventory.units.active', $unit->id), ['active' => '0']);
    expect((string) $r->fresh()->status)->toBe('confirmed')          // the stay is untouched (#7)
        ->and($r->reservationUnits()->count())->toBe(1);

    $this->actingAs($actor)->post(route('app.inventory.units.active', $unit->id), ['active' => '1']);
    expect((bool) $unit->fresh()->is_active)->toBeTrue()
        ->and(Unit::query()->bookable()->where('id', $unit->id)->count())->toBe(1);
});

it('⚠️ both new routes are gated on units.manage, and a role without it is refused', function (string $route, string $method) {
    $s = seedScenario(1);
    $viewer = ulActor($s, 'Front Desk');   // holds units.view, NOT units.manage

    $unit = $s['units'][0];
    $res = $method === 'put'
        ? $this->actingAs($viewer)->put(route($route, $unit->id), ['code' => 'NOPE'])
        : $this->actingAs($viewer)->post(route($route, $unit->id), ['active' => '0']);

    $res->assertForbidden();

    // NON-VACUITY: nothing moved.
    expect((string) $unit->fresh()->code)->not->toBe('NOPE')
        ->and((bool) $unit->fresh()->is_active)->toBeTrue();
})->with([['app.inventory.units.update', 'put'], ['app.inventory.units.active', 'post']]);

it('⚠️ THERE IS NO DELETE ROUTE, deliberately — a hard delete would orphan money and destroy history', function () {
    // 🔬 Measured at the schema: unit_status_logs / housekeeping_tasks / maintenance_tickets /
    // management_agreements / unit_rate_overrides CASCADE, and reservation_units / owner_ledger /
    // owner_expenses / inventory_blocks / reviews SET NULL. Deleting a unit would therefore destroy
    // append-only history and orphan a sold stay's frozen price and the owner's ledger lines.
    // ⚠️ Asserted so that adding one becomes a deliberate act that breaks a test — see docs/UNIT-LIFECYCLE.md.
    expect(Route::has('app.inventory.units.destroy'))->toBeFalse();
});
