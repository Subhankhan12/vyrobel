<?php

use App\Models\Property;
use App\Models\Shift;
use App\Support\Schedule\ShiftType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A one-property org + a manager (schedule.manage) + a front-desk clerk (schedule.view) + an owner (non-staff).
 * A uniquely-named local scenario (not the OperatorScheduleTest global) so this file runs in isolation too.
 */
function moveScenario(): array
{
    $org = newDemoOrg();
    $propertyA = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);

    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
    $desk = operatorUser($s, ['name' => 'Frank Desk'], 'Front Desk');
    $owner = operatorUser($s, ['name' => 'Olivia Owner'], 'External Owner');

    return compact('org', 'propertyA', 'manager', 'desk', 'owner');
}

// GATE 9.1 — DRAG-TO-MOVE / SWAP a shift. The grid drags a real working shift to another cell; the drop posts the
// SERVER-rendered cell date strings (never a client toISOString) to schedule.move → ShiftRosterService::moveShift.
// A MOVE clears the source; a SWAP exchanges the two cells' types; landing on approved leave is refused; the write
// is manager-gated + read-only-guarded + real-staff-scoped. Reuses the global moveScenario() helper.

/** Assign a shift the in-context way (a real POST as the manager) so tenant/property context is set. */
function moveAssign(array $s, $property, $user, string $date, string $type): void
{
    test()->actingAs($s['manager'])->withSession(['current_property_id' => $property->id])
        ->post('/app/schedule/shift', ['user_id' => $user->id, 'date' => $date, 'shift_type' => $type, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect();
}

function moveDays(): array
{
    $mon = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);

    return [$mon->toDateString(), $mon->addDay()->toDateString(), $mon->addDays(2)->toDateString()];
}

// ── MOVE — a shift moves to an empty cell; the source is cleared to off ───────────────────────────────
it('moves a shift to an empty cell and clears the source', function () {
    $s = moveScenario();
    [$d1, $d2] = moveDays();
    moveAssign($s, $s['propertyA'], $s['desk'], $d1, ShiftType::DAY);

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), [
            'from_user' => $s['desk']->id, 'from_date' => $d1,
            'to_user' => $s['manager']->id, 'to_date' => $d2,
            'week' => 0, 'dept' => 'all',
        ])->assertRedirect();

    expect(Shift::where('user_id', $s['manager']->id)->whereDate('date', $d2)->where('shift_type', 'day')->exists())->toBeTrue();
    // Source cleared (off = no working row).
    expect(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d1)->exists())->toBeFalse();
});

// ── SWAP — two working shifts exchange types ─────────────────────────────────────────────────────────
it('swaps two shifts when the target cell already holds one', function () {
    $s = moveScenario();
    [$d1] = moveDays();
    moveAssign($s, $s['propertyA'], $s['desk'], $d1, ShiftType::DAY);
    moveAssign($s, $s['propertyA'], $s['manager'], $d1, ShiftType::LATE);

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), [
            'from_user' => $s['desk']->id, 'from_date' => $d1,
            'to_user' => $s['manager']->id, 'to_date' => $d1,
        ])->assertRedirect();

    expect(Shift::where('user_id', $s['manager']->id)->whereDate('date', $d1)->where('shift_type', 'day')->exists())->toBeTrue()
        ->and(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d1)->where('shift_type', 'late')->exists())->toBeTrue();
});

// ── LEAVE GUARD — a shift can't land on an approved-leave day; the whole move rolls back ──────────────
it('refuses moving a shift onto an approved-leave day and leaves the source intact', function () {
    $s = moveScenario();
    [$d1, $d2] = moveDays();
    moveAssign($s, $s['propertyA'], $s['desk'], $d1, ShiftType::DAY);

    // Grant the desk clerk approved leave on d2, then try to move their own d1 shift onto that leave day.
    // (Granting is manager→other, so it isn't blocked by the self-approval separation.)
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.leave.grant'), ['user_id' => $s['desk']->id, 'start_date' => $d2, 'end_date' => $d2, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect();

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), [
            'from_user' => $s['desk']->id, 'from_date' => $d1,
            'to_user' => $s['desk']->id, 'to_date' => $d2,
        ])->assertRedirect();

    // Nothing moved: the source shift is intact, the leave day has no working shift.
    expect(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d1)->where('shift_type', 'day')->exists())->toBeTrue()
        ->and(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d2)->whereIn('shift_type', ShiftType::WORKING)->exists())->toBeFalse();
});

// ── CONFLICT SOURCE — a shift on an approved-leave day MOVES to a free day (clearing it) but won't SWAP ──
it('moves a conflicted shift to a free day to clear it, but refuses swapping it onto a working day', function () {
    $s = moveScenario();
    [$d1, $d2, $d3] = moveDays();

    // Desk works d1; then leave is approved for the desk ON d1 → d1 becomes a conflict (working + on leave).
    moveAssign($s, $s['propertyA'], $s['desk'], $d1, ShiftType::DAY);
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.leave.grant'), ['user_id' => $s['desk']->id, 'start_date' => $d1, 'end_date' => $d1])->assertRedirect();

    // SWAP the conflicted d1 onto the manager's working d3 → refused (the leave day would keep a working shift).
    moveAssign($s, $s['propertyA'], $s['manager'], $d3, ShiftType::LATE);
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), ['from_user' => $s['desk']->id, 'from_date' => $d1, 'to_user' => $s['manager']->id, 'to_date' => $d3])
        ->assertRedirect();
    expect(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d1)->where('shift_type', 'day')->exists())->toBeTrue()   // untouched
        ->and(Shift::where('user_id', $s['manager']->id)->whereDate('date', $d3)->where('shift_type', 'late')->exists())->toBeTrue();

    // MOVE the conflicted d1 onto the desk's own FREE d2 → succeeds, and d1 is cleared (conflict resolved).
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), ['from_user' => $s['desk']->id, 'from_date' => $d1, 'to_user' => $s['desk']->id, 'to_date' => $d2])
        ->assertRedirect();
    expect(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d2)->where('shift_type', 'day')->exists())->toBeTrue()
        ->and(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d1)->exists())->toBeFalse();
});

// ── NO SOURCE — moving from an empty cell errors, nothing created ────────────────────────────────────
it('refuses a move when the source cell has no shift', function () {
    $s = moveScenario();
    [$d1, $d2] = moveDays();

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), [
            'from_user' => $s['desk']->id, 'from_date' => $d1,
            'to_user' => $s['manager']->id, 'to_date' => $d2,
        ])->assertRedirect();

    expect(Shift::query()->count())->toBe(0);
});

// ── GATED — Front Desk (schedule.view only, NOT .manage) can't move ──────────────────────────────────
it('forbids a move from a user without schedule.manage', function () {
    $s = moveScenario();
    [$d1, $d2] = moveDays();
    moveAssign($s, $s['propertyA'], $s['desk'], $d1, ShiftType::DAY);

    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), [
            'from_user' => $s['desk']->id, 'from_date' => $d1,
            'to_user' => $s['manager']->id, 'to_date' => $d2,
        ])->assertForbidden();

    expect(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d1)->where('shift_type', 'day')->exists())->toBeTrue();  // unchanged
});

// ── READ-ONLY — a paused workspace refuses the move ──────────────────────────────────────────────────
it('refuses a move while the workspace is paused (read-only)', function () {
    $s = moveScenario();
    [$d1, $d2] = moveDays();
    moveAssign($s, $s['propertyA'], $s['desk'], $d1, ShiftType::DAY);
    $s['org']->update(['paused_until' => now()->addMonth()]);

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), [
            'from_user' => $s['desk']->id, 'from_date' => $d1,
            'to_user' => $s['manager']->id, 'to_date' => $d2,
        ])->assertRedirect();

    expect(Shift::where('user_id', $s['manager']->id)->count())->toBe(0)  // nothing moved
        ->and(Shift::where('user_id', $s['desk']->id)->whereDate('date', $d1)->where('shift_type', 'day')->exists())->toBeTrue();
});

// ── REAL STAFF — a foreign / non-staff to_user is rejected before the service runs ───────────────────
it('rejects a move targeting a user who is not on the roster', function () {
    $s = moveScenario();
    [$d1, $d2] = moveDays();
    moveAssign($s, $s['propertyA'], $s['desk'], $d1, ShiftType::DAY);

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post(route('app.schedule.move'), [
            'from_user' => $s['desk']->id, 'from_date' => $d1,
            'to_user' => $s['owner']->id, 'to_date' => $d2,   // External Owner is NOT staff
        ])->assertSessionHasErrors('to_user');

    expect(Shift::where('user_id', $s['owner']->id)->count())->toBe(0);
});
