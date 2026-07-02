<?php

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Exceptions\Booking\UnitUnavailableException;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Obligation 3 (type oversell / Level A) + Obligation 5 (move/extend/cancel/no-show).

it('creates a booking and increments sold inline', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    expect($reservation->status)->toBe('confirmed')
        ->and($reservation->reservationUnits()->first()->status)->toBe('active')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(1);
});

it('rejects an oversell beyond the room_type total (Level A) and leaves sold unchanged', function () {
    $s = seedScenario(1); // total = 1
    book($s);             // type-level booking → sold = 1

    expect(fn () => book($s))->toThrow(InsufficientAvailabilityException::class);
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);
});

it('moves a unit: rejects an occupied target (Level B), allows a free target, sold unchanged', function () {
    $s = seedScenario(3);
    $res1 = book($s, ['unit_id' => $s['units'][0]->id]);
    book($s, ['unit_id' => $s['units'][1]->id]);
    $ru1 = $res1->reservationUnits()->first();

    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(2);

    expect(fn () => app(AssignmentService::class)->assignUnit($ru1, $s['units'][1]->id))
        ->toThrow(UnitUnavailableException::class);

    $moved = app(AssignmentService::class)->assignUnit($ru1, $s['units'][2]->id);
    expect($moved->unit_id)->toBe($s['units'][2]->id)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(2); // move never changes sold
});

it('extends a reservation_unit, adding sold only for the new nights', function () {
    $s = seedScenario(1);
    $ru = book($s, ['unit_id' => $s['units'][0]->id])->reservationUnits()->first();

    app(AssignmentService::class)->extend($ru, '2026-09-04'); // add night 09-03

    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-03'))->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-04'))->toBe(0) // check-out night, unoccupied
        ->and($ru->refresh()->check_out->toDateString())->toBe('2026-09-04');
});

it('cancels a reservation and frees sold', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    app(AssignmentService::class)->cancelReservation($reservation, 'guest request');

    expect($reservation->refresh()->status)->toBe('cancelled')
        ->and($reservation->reservationUnits()->first()->status)->toBe('cancelled')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
});

it('marks a reservation no-show and frees sold', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    app(AssignmentService::class)->markNoShow($reservation);

    expect($reservation->refresh()->status)->toBe('no_show')
        ->and($reservation->reservationUnits()->first()->status)->toBe('cancelled')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
});
