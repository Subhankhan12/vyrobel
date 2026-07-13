<?php

use App\Exceptions\Booking\BookingException;
use App\Models\Availability;
use App\Models\MaintenanceTicket;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\BookingEngineService;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Maintenance module · Step 2 — out-of-service ↔ availability. A takes_out_of_service ticket flips its
// unit to out_of_service (via setUnitCondition → unit.condition_changed → the EXISTING recompute listener),
// so the unit is excluded from availability. AssignmentService/AvailabilityService are UNTOUCHED; sold is
// never touched. Two-tickets, cross-unit isolation, never-auto-cancel, and the negative over-committed
// signal are all asserted; the concurrency suite is re-run in the gate.

function oosTicket(array $s, int $unitIndex = 0): MaintenanceTicket
{
    return app(MaintenanceService::class)->open([
        'unit_id' => $s['units'][$unitIndex]->id,
        'title' => 'Burst pipe',
        'category' => 'plumbing',
        'priority' => 'urgent',
        'takes_out_of_service' => true,
    ]);
}

function rawAvailable(string $roomTypeId, string $date): int
{
    $row = Availability::query()->where('room_type_id', $roomTypeId)->whereDate('date', $date)->firstOrFail();

    return $row->total - $row->sold - $row->out_of_order - $row->blocked;   // unclamped over-commit signal
}

// ── OOS ticket → unit out_of_service, excluded from availability; resolve → back in service ──────
it('takes a unit out of service and excludes it from availability, then returns it on resolve', function () {
    $s = seedScenario(3);   // 3 units in one room-type; availability published
    $av = app(AvailabilityService::class);
    expect($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(3);

    $ticket = oosTicket($s);
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service')
        ->and($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(2);   // one fewer offered

    app(MaintenanceService::class)->resolve($ticket);
    expect($s['units'][0]->refresh()->condition_status)->toBe('in_service')
        ->and($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(3);   // sellable again
});

// ── Two OOS tickets on one unit → stays out until BOTH close ─────────────────────────────────────
it('keeps a unit out of service until every OOS ticket holding it is closed', function () {
    $s = seedScenario(3);
    $svc = app(MaintenanceService::class);
    $av = app(AvailabilityService::class);

    $t1 = oosTicket($s);
    $t2 = oosTicket($s);   // second OOS ticket on the SAME unit
    expect($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(2);   // out once (idempotent)

    $svc->resolve($t1);   // one closed — the other still holds it
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service')
        ->and($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(2);

    $svc->cancel($t2);    // both closed → back in service
    expect($s['units'][0]->refresh()->condition_status)->toBe('in_service')
        ->and($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(3);
});

// ── Public: an out-of-service unit is NOT offered and cannot be booked (no oversell) ─────────────
it('does not offer or book an out-of-service unit publicly', function () {
    $a = publicPropertyScenario(units: 1, nightlyMinor: 12000);   // single-unit room-type
    $engine = app(BookingEngineService::class);

    // Sellable before.
    expect($engine->search($a['property'], '2026-09-01', '2026-09-03', 2))->toHaveCount(1);

    app(MaintenanceService::class)->open([
        'unit_id' => $a['units'][0]->id, 'title' => 'No power', 'takes_out_of_service' => true,
    ]);

    // Public search no longer offers it (available < 1 → filtered from the storefront) and the sellable
    // count is 0. (The guarded-write rejection — book() throwing when a unit is OOS-and-full — is asserted
    // in the over-committed test below, proving no oversell at the AssignmentService path public checkout uses.)
    expect($engine->search($a['property'], '2026-09-01', '2026-09-03', 2))->toHaveCount(0)
        ->and(app(AvailabilityService::class)->availableOn($a['roomType']->id, '2026-09-01'))->toBe(0);
});

// ── Cross-unit / cross-room-type isolation: OOS one unit corrupts nothing else, sold untouched ──
it('does not alter another room-type’s availability or any sold count', function () {
    $s = seedScenario(3);
    $av = app(AvailabilityService::class);

    // A second, independent room-type + unit.
    $rtB = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Suite', 'kind' => 'room', 'max_adults' => 2]);
    Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $rtB->id, 'code' => 'B1']);
    $av->recompute($rtB->id, '2026-09-01', '2026-09-10');
    expect($av->availableOn($rtB->id, '2026-09-05'))->toBe(1);

    $soldBefore = soldOn($s['roomType']->id, '2026-09-05');
    oosTicket($s);   // OOS a unit in room-type A

    expect($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(2)   // A drops by EXACTLY one
        ->and($av->availableOn($rtB->id, '2026-09-05'))->toBe(1)          // B untouched
        ->and(soldOn($s['roomType']->id, '2026-09-05'))->toBe($soldBefore); // sold never touched
});

// ── Over-committed: OOS a unit in a fully-sold room-type → negative raw available, still blocks ──
it('surfaces a negative over-committed signal without overselling or cancelling the booking', function () {
    $s = seedScenario(1);   // one unit
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // sold = 1 on 2026-09-01..03
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);

    oosTicket($s);   // out-of-service the only (already fully-sold) unit

    // The row shows the honest over-commit: total 1 − sold 1 − out_of_order 1 = −1 (unclamped)…
    expect(rawAvailable($s['roomType']->id, '2026-09-01'))->toBe(-1)
        // …while the clamped sellable count is 0, so a NEW booking is cleanly rejected (no oversell).
        ->and(app(AvailabilityService::class)->availableOn($s['roomType']->id, '2026-09-01'))->toBe(0);
    expect(fn () => book($s))->toThrow(BookingException::class);

    // The EXISTING booking is intact — never auto-cancelled (flagged for manual handling).
    expect($reservation->refresh()->status)->not->toBe('cancelled')
        ->and(ReservationUnit::query()->where('reservation_id', $reservation->id)->where('status', 'active')->count())->toBe(1);
});

// ── Never auto-cancel: a physically-assigned reservation on a now-OOS unit stays active ──────────
it('never auto-cancels a reservation physically assigned to a unit that goes out of service', function () {
    $s = seedScenario(2);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // physically assigned to unit 0
    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    expect((string) $line->unit_id)->toBe((string) $s['units'][0]->id);

    oosTicket($s, 0);   // that same unit goes out of service

    $line->refresh();
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service')
        ->and($line->status)->toBe('active')                    // line intact
        ->and((string) $line->unit_id)->toBe((string) $s['units'][0]->id)   // still assigned (not voided)
        ->and($reservation->refresh()->status)->not->toBe('cancelled');
});
