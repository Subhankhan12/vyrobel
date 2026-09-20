<?php

declare(strict_types=1);

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\ConfirmTentativeReservation;
use App\Jobs\SendBookingConfirmationEmail;
use App\Models\Reservation;
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

/**
 * ⚠️ THE FIXTURES `tentativeHold()` AND `soldOver()` LIVE IN `tests/Support/booking_helpers.php`.
 * They are shared with {@see tests/Feature/HoldDoesNotEmailTheGuestTest.php} — the hold's CONVERSION and the
 * hold's SEND are two readings of one fixture, and two copies of it would drift.
 */

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
// notification AND the guest booking-confirmation email. Recording it AGAIN at confirm would re-fire the ARI
// push and re-notify the operator for a booking whose inventory did not move. So confirming records
// `reservation.hold_confirmed`, and this asserts the distinction holds.
//
// ⚠️⚠️ UPDATED BY FIX 55 (F-64), AND THE CHANGE IS DELIBERATE — READ THIS BEFORE "RESTORING" THE OLD ASSERTION.
// This test used to assert that confirming queued NO job at all. That was true only while the guest
// confirmation email was being sent at the WRONG time: 🔬 the hold fired it, so by the time an operator
// confirmed, the guest had already been told — falsely — that their booking was confirmed (F-64).
// ⚠️ Now the email fires HERE, which is the first moment the sentence is true. So confirming queues EXACTLY
// ONE job and it is the confirmation email. The assertion below is stricter than the one it replaced: it
// names the job class rather than counting anonymous jobs, so a DIFFERENT listener waking up still fails.
it('⚠️ confirming records its own event and does NOT re-fire the booking-confirmation side effects', function () {
    Queue::fake();
    $s = seedScenario(2);
    $reservation = tentativeHold($s);

    // ⚠️ F-64: a HOLD must queue no confirmation email — the guest has been promised nothing yet.
    expect(Queue::pushed(SendBookingConfirmationEmail::class)->count())->toBe(0,
        'a tentative hold queued a guest booking-confirmation email — that is F-64');
    $otherJobsAfterCreate = count(Queue::pushedJobs());

    app(ConfirmTentativeReservation::class)->handle($reservation);

    $events = DB::table('domain_events')->where('subject_id', $reservation->getKey())
        ->pluck('event_type')->all();

    expect($events)->toContain('reservation.hold_confirmed');

    // ⚠️ Exactly ONE `reservation.confirmed` — the one the HOLD recorded at create, not a second from confirm.
    expect(count(array_filter($events, fn (string $e): bool => $e === 'reservation.confirmed')))->toBe(1,
        'confirming must not record a second reservation.confirmed — that would re-send the guest email');

    // ⚠️⚠️ EXACTLY ONE, AND IT IS NAMED. The confirmation email is the ONLY side effect confirming may have:
    //    inventory did not move, so nothing else has anything to react to.
    expect(Queue::pushed(SendBookingConfirmationEmail::class)->count())->toBe(1,
        'confirming must queue exactly one guest booking-confirmation email — the first true one (F-64)');

    expect(count(Queue::pushedJobs()))->toBe($otherJobsAfterCreate + 1,
        '⚠️ confirming woke a listener other than the guest confirmation email. Inventory did not move, so '
        .'nothing else should have anything to react to — find out what fired before relaxing this.');

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
