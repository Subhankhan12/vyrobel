<?php

declare(strict_types=1);

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\ConfirmTentativeReservation;
use App\Jobs\SendBookingConfirmationEmail;
use App\Mail\BookingConfirmationMail;
use App\Models\Reservation;
use App\Services\GroupBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ F-64 — A HELD BOOKING MUST NOT TELL THE GUEST IT IS CONFIRMED. Built FIX 55.
 *
 * ⚠️⚠️ THIS IS NOT A TIDINESS DEFECT. Every other finding in this register is about the product being wrong
 * to its operator or to its own records. This one made the product **say a false thing to a customer**: a
 * guest holding an unconfirmed, pickup-deadline-bearing group hold received an email headed "Booking
 * confirmed", with the stay dates and the total, for a room nobody had promised them. A guest who acts on
 * that — books a flight, tells a family — has been misled by the product itself.
 *
 * ── ⚠️ WHAT WAS MEASURED, BEFORE ANYTHING WAS CHANGED ───────────────────────────────────────────────────
 *
 * 🔬 `AssignmentService::createGroupBooking()` records `reservation.confirmed` once per persisted member
 * INCLUDING tentative ones — deliberately, because a hold occupies inventory and must sync to channels.
 * 🔬 `SendGuestTransactionalEmails::bookingConfirmation()` then checked three things — the per-org template
 * toggle, the wizard's suppression flag, and `leadGuest !== null` — and **never the reservation's status**.
 * 🔬 And `GroupBookingService::create()` gives every member a `lead_guest_id` (the organiser when no
 * per-member guest is named), so the one guest-side gate that existed was ALWAYS satisfied for a hold.
 *
 * ── ⚠️⚠️ WHY THE GATE IS AT *DISPATCH* AND NOT INSIDE THE JOB — THE ORDERING ARGUMENT ────────────────────
 *
 * ⚠️⚠️ The obvious fix — teach {@see SendBookingConfirmationEmail} to re-read the status and return early if
 * it is not `confirmed` — **is racy, and the window is sixty seconds wide.** 🔬 The listener dispatches with
 * `delay(now()->addSeconds(60))`. Hold at T+0 → job queued for T+60 → an operator confirms at T+10 → at T+60
 * the job re-reads `confirmed` and sends, **and the confirm has already sent its own**. Two emails, from a
 * check whose whole purpose was to prevent one.
 *
 * ⚠️⚠️ **THE FIX IS IMMUNE TO ORDERING BECAUSE THE DECISION IS TAKEN SYNCHRONOUSLY AT THE MOMENT THE EVENT IS
 * RECORDED — READING THE STATUS THE VERY SAME COMMITTED TRANSACTION JUST WROTE — SO A HELD BOOKING NEVER PUTS
 * A JOB ON THE QUEUE AT ALL, AND THERE IS NO PENDING SEND FOR ANY LATER CONFIRM TO RACE WITH.**
 *
 * ⚠️ That claim is not left as prose: the race is TESTED below, by asserting the queue is empty after a hold.
 * A race needs two contenders; this proves there is only ever one.
 */

/** ⚠️ COUNTS, NEVER "no error was thrown". An email that fails to send silently would pass the latter. */
function confirmationsSent(): int
{
    return Mail::sent(BookingConfirmationMail::class)->count();
}

function guestWithEmail(array $scenario): array
{
    $scenario['guest']->update(['email' => 'held.guest@example.test', 'first_name' => 'Ada', 'last_name' => 'Byron']);

    return $scenario;
}

// ── 1. ⚠️⚠️ THE THREE READINGS, IN ONE RUN — 0 at hold, exactly 1 at confirm, 0 at check-in ────────
//
// ⚠️ ONE test, not three, and deliberately so: the figure that matters is the count the GUEST sees across a
// whole journey. Three separate tests could each pass while the journey as a whole sent two.
it('⚠️⚠️ a hold emails NOTHING · confirming emails EXACTLY ONE · checking in emails nothing', function () {
    Mail::fake();
    $s = guestWithEmail(seedScenario(2));

    // (a) ⚠️⚠️ AT HOLD — the guest has been promised nothing, so the guest is told nothing.
    $reservation = tentativeHold($s);

    expect(confirmationsSent())->toBe(0,
        '⚠️⚠️ A HELD BOOKING EMAILED THE GUEST "BOOKING CONFIRMED". The booking is a hold with a pickup '
        .'deadline — nobody has promised this guest the room. This is the product stating a falsehood to a '
        .'customer, and it is F-64. Do NOT fix it by suppressing the EVENT: the ARI push and the operator '
        .'notification both depend on it and are correct.');

    // (b) ⚠️⚠️ AT CONFIRM — now it is true, so now it is said. Exactly once.
    app(ConfirmTentativeReservation::class)->handle($reservation);
    $reservation->refresh();

    expect(confirmationsSent())->toBe(1,
        'confirming a hold must send the guest exactly one booking confirmation — the first true statement '
        .'the product can make about this booking');

    // (c) AT CHECK-IN — occupancy, not a booking change. The guest is already standing there.
    app(CheckInReservation::class)->handle($reservation);

    expect(confirmationsSent())->toBe(1, 'checking in must not re-send the confirmation');
});

// ── 2. ⚠️⚠️ THE RACE, TESTED RATHER THAN ARGUED ───────────────────────────────────────────────────
//
// ⚠️⚠️ A gate inside the JOB would leave a job sitting on the queue for sixty seconds with its decision still
// to make. This asserts the strictly stronger property: after a hold there is NO pending confirmation job at
// all. A race requires two contenders, and this is the proof that only one is ever created.
it('⚠️⚠️ a hold leaves NO pending confirmation job — so a quick confirm has nothing to race', function () {
    Queue::fake();
    $s = guestWithEmail(seedScenario(2));

    $reservation = tentativeHold($s);

    Queue::assertNotPushed(SendBookingConfirmationEmail::class);
    expect(Queue::pushed(SendBookingConfirmationEmail::class)->count())->toBe(0,
        '⚠️⚠️ A CONFIRMATION JOB IS SITTING ON THE QUEUE FOR A HELD BOOKING. 🔬 The dispatch carries a '
        .'60-SECOND delay, so an operator who confirms inside that window gets the queued send AND the '
        .'confirm send — two emails. The gate must refuse to DISPATCH, not decide later inside the job.');

    // ⚠️ Confirming inside the would-be race window dispatches exactly one — and it is the only one.
    app(ConfirmTentativeReservation::class)->handle($reservation);

    expect(Queue::pushed(SendBookingConfirmationEmail::class)->count())->toBe(1,
        'confirming must dispatch exactly one confirmation job');
});

// ── 3. ⚠️⚠️ THE REGRESSION THAT MATTERS MOST — the ordinary direct booking is UNTOUCHED ────────────
//
// ⚠️⚠️ 🔬 `createBooking()` defaults to `'status' => 'confirmed'` and NEITHER of its two callers overrides it
// — the operator's New Reservation wizard (`ReservationCreateController`) and the public booking engine
// (`BookingEngineService`). So the overwhelmingly common journey must be bit-for-bit unchanged: one booking,
// one email, before and after. A fix that quietly cost the product its ordinary confirmation email would be
// far worse than the defect it repaired.
it('⚠️⚠️ an ordinary direct booking still sends EXACTLY ONE confirmation', function () {
    Mail::fake();
    $s = guestWithEmail(seedScenario(2));

    book($s, ['unit_id' => $s['units'][0]->id]);

    expect(confirmationsSent())->toBe(1,
        '⚠️⚠️ THE ORDINARY BOOKING CONFIRMATION STOPPED SENDING. This is the product\'s single most-used '
        .'email. The status gate must let `confirmed` through — it exists to stop a HOLD, nothing else.');
});

// ── 4. ⚠️ A GROUP BOOKED AS CONFIRMED IS NOT A HOLD — it emails, one per member ───────────────────
//
// ⚠️ The gate keys on the reservation's STATUS, never on "did this come from a group". A confirmed group is a
// real promise to real guests and must behave exactly like a direct booking.
it('⚠️ a group created as CONFIRMED still emails each member', function () {
    Mail::fake();
    $s = guestWithEmail(seedScenario(2));

    $group = app(GroupBookingService::class)->create([
        'property_id' => $s['property']->id,
        'name' => 'Confirmed party',
        'occasion' => null,
        'billing_mode' => 'individual',
        'status' => 'confirmed',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'currency' => 'EUR',
        'adults' => 2,
        'children' => 0,
        'organiser_guest_id' => $s['guest']->id,
        'pickup_deadline' => null,
        'members' => [
            ['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'price_minor' => 10000],
            ['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][1]->id, 'rate_plan_id' => $s['ratePlan']->id, 'price_minor' => 10000],
        ],
    ]);

    $members = Reservation::query()->where('group_id', $group->getKey())->count();

    expect($members)->toBe(2)
        ->and(confirmationsSent())->toBe(2,
            'a group booked as CONFIRMED must email every member — the gate keys on status, not on provenance');
});

// ── 5. ⚠️ THE EVENT ITSELF IS UNTOUCHED — the other two listeners still fire at hold time ─────────
//
// ⚠️⚠️ 🔬 `reservation.confirmed` at hold time drives THREE listeners and only ONE of them was wrong.
// `EnqueueAriPushOnInventoryChange` must still push ARI (the hold really does occupy the room, and the
// channels must learn it is gone) and `DispatchNotificationsForDomainEvent` must still notify the OPERATOR
// (a new hold is real work arriving at the front desk). Suppressing the EVENT would have silently broken
// both — an oversell on the channels and a hold nobody was told about.
it('⚠️⚠️ the hold still records reservation.confirmed — the ARI push and operator notice depend on it', function () {
    Mail::fake();
    $s = guestWithEmail(seedScenario(2));

    $reservation = tentativeHold($s);

    $types = DB::table('domain_events')->where('subject_id', $reservation->getKey())->pluck('event_type')->all();

    expect(in_array('reservation.confirmed', $types, true))->toBeTrue(
        '⚠️⚠️ THE HOLD\'S INVENTORY EVENT WAS REMOVED. Two listeners depend on it and both are correct: the '
        .'channels must learn the room is gone, and the front desk must learn the hold arrived. The email was '
        .'the only wrong consumer, and the email is where the fix belongs.');
});
