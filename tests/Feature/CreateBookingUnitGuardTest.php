<?php

use App\Exceptions\Booking\UnitUnavailableException;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GATE 12 — CLOSE the carried-over write-path gap: the availability READ (Unit::bookable()) never OFFERS a
// paused / out-of-service unit, but a CRAFTED create request could still NAME one. The single write path
// (AssignmentService::occupy, shared by createBooking + createGroupBooking) now refuses it, the SAME isBookable
// rule the move (assignUnit) + reschedule paths already enforce. A guest can never land on an unbookable unit.

it('refuses createBooking onto an OUT-OF-SERVICE unit and leaves no trace', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['condition_status' => 'out_of_service']);   // genuinely unbookable

    expect(fn () => book($s, ['unit_id' => $s['units'][0]->id]))
        ->toThrow(UnitUnavailableException::class);

    // Atomic refusal: no reservation, no reservation_unit, no sold increment.
    expect(Reservation::query()->count())->toBe(0)
        ->and(ReservationUnit::query()->count())->toBe(0)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
});

it('refuses createBooking onto a PAUSED (is_active=false) unit', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['is_active' => false]);   // owner-paused unit (#69)

    expect(fn () => book($s, ['unit_id' => $s['units'][0]->id]))
        ->toThrow(UnitUnavailableException::class);

    expect(ReservationUnit::query()->count())->toBe(0);
});

it('still books normally onto a bookable unit (the guard only refuses unbookable ones)', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['condition_status' => 'out_of_service']);   // #0 unbookable…

    $res = book($s, ['unit_id' => $s['units'][1]->id]);                 // …but #1 is fine

    expect($res->exists)->toBeTrue()
        ->and(ReservationUnit::query()->where('unit_id', $s['units'][1]->id)->where('status', 'active')->exists())->toBeTrue()
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);
});
