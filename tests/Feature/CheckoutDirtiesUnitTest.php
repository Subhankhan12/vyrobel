<?php

use App\Models\HousekeepingTask;
use App\Models\Membership;
use App\Models\Unit;
use App\Models\UnitStatusLog;
use App\Models\User;
use App\Services\FrontDeskService;
use App\Services\HousekeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Tier 1 finishing sweep · Step 2 — check-OUT dirties the unit. The departure clean task already
// generated via the Phase-1 event chain; this adds the unit-status transition (through
// HousekeepingService, on the same one-directional consumer) so the Units screen + the dashboard
// "to clean" KPI reflect reality, and a later dirty→clean cycle is a REAL transition (not a
// clean→clean no-op). Idempotent — a double check-out doesn't double-anything.

/** A real OPERATOR: an active member of the scenario's org holding a back-office role. */
function coMember(array $scenario): User
{
    $user = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org']);
}

// ── Check-out flips the unit dirty AND generates the departure task ───────────────────────────────
it('marks the unit dirty on check-out and still auto-generates the departure clean task', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $reservation = book($s, ['unit_id' => $unit->id]);
    app(FrontDeskService::class)->checkIn($reservation);

    // Precondition: a fresh unit is clean, and there's no housekeeping task yet.
    expect((string) Unit::query()->find($unit->id)->housekeeping_status)->toBe('clean')
        ->and(HousekeepingTask::count())->toBe(0);

    app(FrontDeskService::class)->checkOut($reservation);

    // Unit is now dirty (needs cleaning before the next guest) …
    expect((string) Unit::query()->find($unit->id)->housekeeping_status)->toBe('dirty')
        // … the departure task still generates (proves reuse, not a reimplementation) …
        ->and(HousekeepingTask::query()->where('type', 'departure')->where('unit_id', $unit->id)->where('status', 'pending')->count())->toBe(1)
        // … and the dirty transition is recorded in the shared append-only log (from clean).
        ->and(UnitStatusLog::query()->where('unit_id', $unit->id)->where('type', 'housekeeping')->where('to_status', 'dirty')->where('from_status', 'clean')->count())->toBe(1);
});

// ── The dashboard "to clean" KPI (= pending + in_progress tasks) reflects the checkout ───────────
it('surfaces the departure task on the board and in the dashboard to-clean count', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'DIRTYUNIT']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $member = coMember($s);
    app(FrontDeskService::class)->checkIn($reservation);
    app(FrontDeskService::class)->checkOut($reservation);

    // The dashboard "to clean" KPI is exactly pending + in_progress tasks for the property.
    $toClean = HousekeepingTask::query()
        ->where('property_id', $s['property']->id)
        ->whereIn('status', ['pending', 'in_progress'])
        ->count();
    expect($toClean)->toBe(1);

    // Both operator surfaces render it (the board shows the unit; the dashboard renders its KPIs).
    $this->actingAs($member)->get('/app/housekeeping')->assertOk()->assertSee('DIRTYUNIT');
    $this->actingAs($member)->get('/app/dashboard')->assertOk()->assertSee('To clean');
});

// ── The dirty→clean cycle is a REAL transition now (no more clean→clean no-op) ────────────────────
it('completes a real dirty→clean cycle when the departure task is marked clean', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $reservation = book($s, ['unit_id' => $unit->id]);
    app(FrontDeskService::class)->checkIn($reservation);
    app(FrontDeskService::class)->checkOut($reservation);

    expect((string) Unit::query()->find($unit->id)->housekeeping_status)->toBe('dirty');

    // Marking the departure task clean syncs the unit — a genuine dirty→clean transition.
    $task = HousekeepingTask::query()->where('unit_id', $unit->id)->where('type', 'departure')->firstOrFail();
    app(HousekeepingService::class)->updateTaskStatus($task, 'clean');

    expect((string) Unit::query()->find($unit->id)->housekeeping_status)->toBe('clean')
        // The log proves it was a real transition FROM dirty (not a clean→clean no-op).
        ->and(UnitStatusLog::query()->where('unit_id', $unit->id)->where('from_status', 'dirty')->where('to_status', 'clean')->count())->toBe(1);
});

// ── Idempotent: a double check-out doesn't double-anything ────────────────────────────────────────
it('is idempotent on a repeated check-out — the unit stays dirty, nothing doubles', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $reservation = book($s, ['unit_id' => $unit->id]);
    app(FrontDeskService::class)->checkIn($reservation);

    app(FrontDeskService::class)->checkOut($reservation);
    app(FrontDeskService::class)->checkOut($reservation);   // second checkout — no-op (already checked_out)

    expect((string) Unit::query()->find($unit->id)->housekeeping_status)->toBe('dirty')
        ->and(HousekeepingTask::query()->where('type', 'departure')->where('unit_id', $unit->id)->count())->toBe(1)   // one task
        ->and(UnitStatusLog::query()->where('unit_id', $unit->id)->where('to_status', 'dirty')->count())->toBe(1);    // one dirty log
});

// ── Already-dirty unit: check-out stays dirty without a redundant transition ─────────────────────
it('does not re-dirty a unit that is already dirty', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    // Pre-dirty the unit through the real service, then check a reservation out on it.
    app(HousekeepingService::class)->markUnitHousekeepingStatus(Unit::query()->find($unit->id), 'dirty');
    $reservation = book($s, ['unit_id' => $unit->id]);
    app(FrontDeskService::class)->checkIn($reservation);

    app(FrontDeskService::class)->checkOut($reservation);

    expect((string) Unit::query()->find($unit->id)->housekeeping_status)->toBe('dirty')
        // Only the ONE pre-dirty transition — checkout didn't write a redundant dirty→dirty log.
        ->and(UnitStatusLog::query()->where('unit_id', $unit->id)->where('to_status', 'dirty')->count())->toBe(1);
});
