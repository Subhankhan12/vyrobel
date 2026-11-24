<?php

declare(strict_types=1);

use App\Actions\Reservations\CheckInReservation;
use App\Exceptions\Reservations\RoomNotReadyException;
use App\Models\AuditEvent;
use App\Models\Reservation;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-13 (audit of 2026-11-24, reproduced) — A GUEST IS CHECKED IN ONLY INTO A ROOM, AND ONLY INTO A ROOM THAT IS IN SERVICE.
//
// The only condition for check-in was that the booking was confirmed. Both of these succeeded: a booking with NO room assigned, and a
// booking whose room was out of service. The board then showed a guest "in house" in no room, or in a room maintenance had closed.
//
// The check now lives in the action that writes the status (every caller is covered), and the front desk shows a page that NAMES the
// problem and offers the way out — there is no "check in anyway".
//
// ⚠️ EACH REFUSAL HAS ITS CONTROL: the same booking with a room in service checks in.

uses(DatabaseTransactions::class);

function vy13CheckIn(array $s, Reservation $reservation)
{
    return test()->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post(route('app.front-desk.check-in', $reservation->id), ['date' => '2026-09-01', 'confirm_early' => 1]);
}

it('⚠️⚠️ VY-13 (as reproduced): a booking with NO room is not checked in — the page names the problem and links to the reservation', function () {
    $s = seedScenario(1);
    $reservation = book($s);   // confirmed, no unit on its line

    vy13CheckIn($s, $reservation)
        ->assertOk()
        ->assertSee(__('operator/check-in-blocked.heading'))
        ->assertSee(__('operator/check-in-blocked.no_room'))
        ->assertSee(route('app.reservations.show', $reservation->id), false)
        ->assertDontSee(__('operator/early-check-in.check_in_anyway'));

    expect($reservation->refresh()->status)->toBe('confirmed');
});

it('⚠️⚠️ VY-13 (as reproduced): a booking whose room is OUT OF SERVICE is not checked in — the page names the room', function () {
    $s = seedScenario(2);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $s['units'][0]->forceFill(['condition_status' => 'out_of_service', 'name' => 'Lakeview 12'])->save();

    vy13CheckIn($s, $reservation)
        ->assertOk()
        ->assertSee(__('operator/check-in-blocked.heading'))
        ->assertSee(__('operator/check-in-blocked.out_of_service', ['room' => 'Lakeview 12']));

    expect($reservation->refresh()->status)->toBe('confirmed');
});

it('⚠️ VY-13: an OUT OF ORDER room is refused the same way', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $s['units'][0]->forceFill(['condition_status' => 'out_of_order'])->save();

    vy13CheckIn($s, $reservation)->assertOk()->assertSee(__('operator/check-in-blocked.heading'));

    expect($reservation->refresh()->status)->toBe('confirmed');
});

it('⚠️⚠️ VY-13: the guard is in the ACTION — a caller with no screen is refused too, and nothing is written', function () {
    $s = seedScenario(1);
    $reservation = book($s);

    expect(fn () => app(CheckInReservation::class)->handle($reservation))->toThrow(RoomNotReadyException::class);

    expect($reservation->refresh()->status)->toBe('confirmed')
        ->and(AuditEvent::query()->where('action', 'reservation.checked_in')->where('subject_id', $reservation->id)->count())->toBe(0);
});

it('⚠️ VY-13: a stay of TWO rooms is refused while either one is missing or closed', function () {
    $s = seedScenario(2);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    // a second line on the same booking, with no room
    $line = $reservation->reservationUnits()->firstOrFail();
    $second = $line->replicate();
    $second->unit_id = null;
    $second->save();

    expect(fn () => app(CheckInReservation::class)->handle($reservation->refresh()))->toThrow(RoomNotReadyException::class);
    expect($reservation->refresh()->status)->toBe('confirmed');
});

it('⚠️ VY-13: a booking whose only line was cancelled has no room either — it is refused', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    app(AssignmentService::class)->cancelReservationUnit($reservation->reservationUnits()->firstOrFail());

    expect($reservation->refresh()->status)->toBe('confirmed');   // the premise: the booking itself is still confirmed
    expect(fn () => app(CheckInReservation::class)->handle($reservation))->toThrow(RoomNotReadyException::class);
    expect($reservation->refresh()->status)->toBe('confirmed');
});

it('VY-13 CONTROL: a booking with a room in service checks in', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    vy13CheckIn($s, $reservation)->assertRedirect();

    expect($reservation->refresh()->status)->toBe('checked_in');
});

it('VY-13 CONTROL: a DIRTY room is not a refusal — housekeeping state is the desk\'s call, not a closed room', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $s['units'][0]->forceFill(['housekeeping_status' => 'dirty'])->save();

    vy13CheckIn($s, $reservation)->assertRedirect();

    expect($reservation->refresh()->status)->toBe('checked_in');
});
