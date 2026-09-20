<?php

declare(strict_types=1);

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\ConfirmTentativeReservation;
use App\Models\Reservation;
use App\Services\GroupBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ R5 (F-60) — A TENTATIVE GROUP HOLD BECOMES A REAL STAY. Built FIX 54.
 *
 * ⚠️⚠️ THE DANGEROUS OUTCOME THIS FILE EXISTS TO PREVENT IS A DOUBLE-COUNT. 🔬 The hold occupies its nights
 * at CREATE — `availability.sold` 0 → 2 for a two-night hold. **Confirming must CONVERT that hold, never
 * take a second one.** If `sold` rose at confirm the product would sell the same room twice, and an
 * equal-and-opposite pair of errors nets to zero and passes every instrument this project owns (F-62).
 * So the inventory is read at FOUR points, not two, and each step says what it means.
 */

/** ⚠️ THE FIXTURE COMES FROM THE REAL PRODUCER — never from writing a status. */
function tentativeHold(array $scenario): Reservation
{
    $group = app(GroupBookingService::class)->create([
        'property_id' => $scenario['property']->id,
        'name' => 'Held party',
        'occasion' => null,
        'billing_mode' => 'individual',
        'status' => 'tentative',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'currency' => 'EUR',
        'adults' => 2,
        'children' => 0,
        'organiser_guest_id' => $scenario['guest']->id,
        'pickup_deadline' => '2026-09-01',
        'members' => [[
            'room_type_id' => $scenario['roomType']->id,
            'unit_id' => $scenario['units'][0]->id,
            'rate_plan_id' => $scenario['ratePlan']->id,
            'price_minor' => 10000,
        ]],
    ]);

    return Reservation::query()->where('group_id', $group->getKey())->firstOrFail();
}

function soldOver(Reservation $reservation): int
{
    $line = $reservation->reservationUnits()->first();

    return (int) DB::table('availability')
        ->where('room_type_id', $line->room_type_id)
        ->whereBetween('date', [$reservation->check_in, $reservation->check_out])
        ->sum('sold');
}

// ── 1. ⚠️⚠️ THE FOUR INVENTORY READINGS ───────────────────────────────────────────────────────────
it('⚠️⚠️ confirming CONVERTS the hold — it never takes a second one', function () {
    $s = seedScenario(2);
    $roomType = $s['roomType']->id;

    // (a) BEFORE create — nothing held yet.
    $before = (int) DB::table('availability')->where('room_type_id', $roomType)
        ->whereBetween('date', ['2026-09-01', '2026-09-02'])->sum('sold');

    $reservation = tentativeHold($s);

    // (b) AFTER create — ⚠️ THIS RISE IS CORRECT: the hold occupies the nights.
    $afterCreate = soldOver($reservation);
    expect($afterCreate)->toBeGreaterThan($before, 'a tentative hold must occupy its nights at CREATE');

    app(ConfirmTentativeReservation::class)->handle($reservation);
    $reservation->refresh();

    // (c) AFTER confirm — ⚠️⚠️ MUST NOT MOVE. A rise here is a double-count and the product oversells.
    expect(soldOver($reservation))->toBe($afterCreate,
        '⚠️⚠️ availability.sold MOVED AT CONFIRM — the hold was counted twice and the room can be oversold. '
        .'Do NOT balance this with a compensating decrement: a paired error nets to zero and passes every gate.');

    expect((string) $reservation->status)->toBe('confirmed');

    // (d) AFTER check-in — check-in is occupancy, not availability; the nights were sold at booking.
    app(CheckInReservation::class)->handle($reservation);
    $reservation->refresh();

    expect(soldOver($reservation))->toBe($afterCreate,
        'availability.sold moved at CHECK-IN — check-in is occupancy and must not touch inventory');
    expect((string) $reservation->status)->toBe('checked_in');
});

// ── 2. ⚠️ THE SECOND, INDEPENDENT READING OF THE SAME FACT ────────────────────────────────────────
//
// ⚠️⚠️ 🔬 `createGroupBooking` ALREADY records `reservation.confirmed` for a tentative member — deliberately,
// because the hold occupies inventory and must sync to channels. That event drives the ARI push, the operator
// notification AND the guest booking-confirmation email, whose job does not gate on status. Recording it
// again at confirm would send a SECOND "booking confirmed" email for a booking whose inventory did not move.
// So confirming records `reservation.hold_confirmed`, and this asserts the distinction holds.
it('⚠️ confirming records its own event and does NOT re-fire the booking-confirmation side effects', function () {
    Queue::fake();
    $s = seedScenario(2);
    $reservation = tentativeHold($s);

    $jobsAfterCreate = count(Queue::pushedJobs());

    app(ConfirmTentativeReservation::class)->handle($reservation);

    $events = DB::table('domain_events')->where('subject_id', $reservation->getKey())
        ->pluck('event_type')->all();

    expect($events)->toContain('reservation.hold_confirmed');

    // ⚠️ Exactly ONE `reservation.confirmed` — the one the HOLD recorded at create, not a second from confirm.
    expect(count(array_filter($events, fn (string $e): bool => $e === 'reservation.confirmed')))->toBe(1,
        'confirming must not record a second reservation.confirmed — that would re-send the guest email');

    expect(count(Queue::pushedJobs()))->toBe($jobsAfterCreate,
        'confirming queued a job — no listener should fire, since inventory did not move');

    // The audit trail and the status history both record it.
    expect(DB::table('audit_events')->where('subject_id', $reservation->getKey())
        ->where('action', 'reservation.hold_confirmed')->count())->toBe(1);
    expect(DB::table('reservation_status_history')->where('reservation_id', $reservation->getKey())
        ->where('to_status', 'confirmed')->where('from_status', 'tentative')->count())->toBe(1);
});

// ── 3. ⚠️⚠️ THE ALLOW-LIST OF EXACTLY ONE — one assertion per forbidden source ────────────────────
//
// ⚠️ A route that permits MORE than it should is the defect nobody goes looking for, and it will never show
// up in a happy-path journey. ⚠️ These fixtures force the status because several of these states have no
// producer at all — 🔬 `enquiry` is written by NOTHING in the application. The SUCCESS fixture above is the
// one that must come from the real path, and it does.
it('⚠️⚠️ refuses every source status but tentative', function (string $from) {
    $s = seedScenario(2);
    $reservation = tentativeHold($s);
    Reservation::query()->whereKey($reservation->getKey())->update(['status' => $from]);
    $reservation->refresh();

    $soldBefore = soldOver($reservation);

    expect(fn () => app(ConfirmTentativeReservation::class)->handle($reservation))
        ->toThrow(RuntimeException::class);

    $reservation->refresh();
    expect((string) $reservation->status)->toBe($from, "a refused confirm must not move the row off {$from}");
    expect(soldOver($reservation))->toBe($soldBefore, 'a refused confirm must not touch inventory');
})->with(['enquiry', 'cancelled', 'no_show', 'checked_in', 'checked_out']);

it('⚠️ is idempotent — confirming an already-confirmed booking changes nothing', function () {
    $s = seedScenario(2);
    $reservation = tentativeHold($s);

    app(ConfirmTentativeReservation::class)->handle($reservation);
    $reservation->refresh();
    $sold = soldOver($reservation);
    $history = DB::table('reservation_status_history')->where('reservation_id', $reservation->getKey())->count();

    app(ConfirmTentativeReservation::class)->handle($reservation->refresh());

    expect((string) $reservation->refresh()->status)->toBe('confirmed')
        ->and(soldOver($reservation))->toBe($sold)
        ->and(DB::table('reservation_status_history')->where('reservation_id', $reservation->getKey())->count())
        ->toBe($history, 'a second press must not append another history row');
});

// ── 4. ⚠️⚠️ THE PERMISSION — SUCCESS AND REFUSAL IN THE SAME RUN (class N) ────────────────────────
it('⚠️⚠️ the route needs reservations.manage — proven by a refusal AND a success together', function () {
    $s = seedScenario(2);
    $reservation = tentativeHold($s);

    // ⚠️ MUST REFUSE — Housekeeping holds no `reservations.manage`.
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    $this->actingAs($housekeeper)
        ->post(route('app.reservations.confirm', $reservation->getKey()))
        ->assertForbidden();

    expect((string) $reservation->refresh()->status)->toBe('tentative',
        'a refused request must not move the row — the status is the proof, never the response code');

    // ⚠️ MUST SUCCEED — Hotel Manager holds it. A run of only refusals proves the instrument was pointed
    //    at nothing, which is INSTRUMENT-ERRORS class N.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($manager)
        ->post(route('app.reservations.confirm', $reservation->getKey()))
        ->assertRedirect();

    expect((string) $reservation->refresh()->status)->toBe('confirmed');
});
