<?php

use App\Exceptions\Booking\UnitUnavailableException;
use App\Models\Availability;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// AssignmentService::reschedule() — the operator Calendar drag-to-reschedule. A single guarded inventory
// move: release old nights' sold, claim new nights' sold, atomically, with Level A + Level B re-checks and
// the reservation aggregate dates resynced. These lock its behaviour (the concurrency guarantee is proven
// separately in ConcurrentRescheduleTest across two OS processes).

function soldNight(string $roomTypeId, string $date): int
{
    return (int) Availability::query()->where('room_type_id', $roomTypeId)->whereDate('date', $date)->value('sold');
}

it('moves a line to new dates + unit, releasing old sold and claiming new, and resyncs reservation dates', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);   // U1, 2026-09-01..03
    $line = $res->reservationUnits()->first();

    expect(soldNight($s['roomType']->id, '2026-09-01'))->toBe(1)
        ->and(soldNight($s['roomType']->id, '2026-09-05'))->toBe(0);

    app(AssignmentService::class)->reschedule($line, '2026-09-05', '2026-09-07', $s['units'][1]->id);

    $line->refresh();
    expect((string) $line->unit_id)->toBe((string) $s['units'][1]->id)
        ->and($line->check_in->toDateString())->toBe('2026-09-05')
        ->and($line->check_out->toDateString())->toBe('2026-09-07');

    // Old nights released, new nights claimed.
    expect(soldNight($s['roomType']->id, '2026-09-01'))->toBe(0)
        ->and(soldNight($s['roomType']->id, '2026-09-02'))->toBe(0)
        ->and(soldNight($s['roomType']->id, '2026-09-05'))->toBe(1)
        ->and(soldNight($s['roomType']->id, '2026-09-06'))->toBe(1);

    // Reservation aggregate dates follow the line.
    $reservation = Reservation::query()->whereKey($res->id)->first();
    expect($reservation->check_in->toDateString())->toBe('2026-09-05')
        ->and($reservation->check_out->toDateString())->toBe('2026-09-07');
});

it('nets sold to zero on nights the old and new ranges share (an overlapping move never self-blocks)', function () {
    $s = seedScenario(1);   // ONE unit — an overlapping self-move must not trip Level A/B on itself
    $res = book($s, ['unit_id' => $s['units'][0]->id]);   // U1, 2026-09-01..03
    $line = $res->reservationUnits()->first();

    // Shift by one night: 09-01..03 -> 09-02..04. Night 09-02 is shared (net 0); 09-01 freed; 09-03 claimed.
    app(AssignmentService::class)->reschedule($line, '2026-09-02', '2026-09-04', $s['units'][0]->id);

    expect(soldNight($s['roomType']->id, '2026-09-01'))->toBe(0)   // released
        ->and(soldNight($s['roomType']->id, '2026-09-02'))->toBe(1)   // shared — still exactly one
        ->and(soldNight($s['roomType']->id, '2026-09-03'))->toBe(1);  // claimed
});

it('refuses a move onto a unit already occupied on the target nights', function () {
    $s = seedScenario(2);
    $a = book($s, ['unit_id' => $s['units'][0]->id]);                                   // U1, 09-01..03
    book($s, ['unit_id' => $s['units'][1]->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-07']); // U2 taken 05..07
    $lineA = $a->reservationUnits()->first();

    // Move A onto U2 across 05..07 → occupied.
    expect(fn () => app(AssignmentService::class)->reschedule($lineA, '2026-09-05', '2026-09-07', $s['units'][1]->id))
        ->toThrow(UnitUnavailableException::class);

    // Nothing changed — A is still on U1 at its original dates (atomic rollback).
    $lineA->refresh();
    expect((string) $lineA->unit_id)->toBe((string) $s['units'][0]->id)
        ->and($lineA->check_in->toDateString())->toBe('2026-09-01');
});

it('refuses to reschedule a non-active (cancelled) line', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();
    app(AssignmentService::class)->cancelReservationUnit($line);

    expect(fn () => app(AssignmentService::class)->reschedule($line->refresh(), '2026-09-05', '2026-09-07', $s['units'][0]->id))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses a target unit of a different room type', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();

    // A DIFFERENT room type + unit in the SAME property/org (a reschedule stays within the room type).
    $otherType = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Suite', 'kind' => 'room']);
    $otherUnit = Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $otherType->id, 'code' => 'S1']);

    expect(fn () => app(AssignmentService::class)->reschedule($line, '2026-09-05', '2026-09-07', $otherUnit->id))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses a move onto a paused (is_active=false) unit', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();
    $s['units'][1]->update(['is_active' => false]);   // owner-paused target (#69)

    expect(fn () => app(AssignmentService::class)->reschedule($line, '2026-09-05', '2026-09-07', $s['units'][1]->id))
        ->toThrow(UnitUnavailableException::class);

    $line->refresh();
    expect((string) $line->unit_id)->toBe((string) $s['units'][0]->id);   // unchanged
});

it('refuses a move onto an out-of-service unit', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();
    $s['units'][1]->update(['condition_status' => 'out_of_service']);   // maintenance OOS target

    expect(fn () => app(AssignmentService::class)->reschedule($line, '2026-09-05', '2026-09-07', $s['units'][1]->id))
        ->toThrow(UnitUnavailableException::class);
});

it('rejects check_out on or before check_in', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();

    expect(fn () => app(AssignmentService::class)->reschedule($line, '2026-09-05', '2026-09-05', $s['units'][0]->id))
        ->toThrow(InvalidArgumentException::class);
});
