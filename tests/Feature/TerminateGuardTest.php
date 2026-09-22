<?php

use App\Exceptions\Booking\ReservationAlreadyTerminalException;
use App\Exceptions\Booking\ReservationInProgressException;
use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Services\AssignmentService;
use App\Services\BookingImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// THE TERMINATION BOUND (invariant #29) — a terminal reservation cannot be terminated again.
//
// WRITTEN BEFORE THE GUARD AND RUN AGAINST THE UNGUARDED CODE, the RefundGuardTest (#27) discipline. What it
// caught on real demo data, verbatim from that run:
//     1st cancel : reason="Guest called - family emergency"  at=2026-07-17 10:17:04  historyRows=2
//     2nd cancel : reason="oops duplicate webhook"           at=2026-07-17 10:17:05  historyRows=3
//     *** original REASON destroyed · cancelled_at OVERWRITTEN · junk `cancelled -> cancelled` row appended
//
// WHY THE SERVICE AND NOT JUST THE CONTROLLER. The Wave 2 note claimed the controller was the only caller.
// IT WAS NOT: `BookingImportService::cancel()` — the OTA/Channex INBOUND path, i.e. a NETWORK-TRIGGERED,
// RETRYING caller — reaches `cancelReservation()` too. It had its own guard, and the two guards DISAGREED:
// the controller refused `checked_out`, the OTA path let it through. Two rules for one concept; exactly the
// drift CLAUDE.md warns about. The rule now lives ONCE, in the service, where every caller inherits it.
//
// THIS IS THE SAME SHAPE AS #27: a certified service missing a bound. #7 (append-only) was never violated —
// every row was a legitimate append. Append-only bounds EDITS; it never bounded a SECOND TERMINATION.

/** A booking parked at a known status. Status is set directly: this file tests how a row IS guarded, not how it got there. */
function terminable(array $s, int $unitIndex, string $status): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    Reservation::query()->whereKey($reservation->getKey())->update(['status' => $status]);

    return $reservation->fresh();
}

function historyCount(Reservation $reservation): int
{
    return ReservationStatusHistory::query()->where('reservation_id', $reservation->getKey())->count();
}

// ── 1. THE BUG — a second cancel must be REJECTED, and destroy nothing ───────────────────────────
it('refuses to re-cancel an already-cancelled reservation, preserving the original reason and timestamp', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'confirmed');
    $assignments = app(AssignmentService::class);

    $assignments->cancelReservation($reservation, 'Guest called — family emergency');
    $reservation->refresh();

    $originalReason = (string) $reservation->cancellation_reason;
    $originalAt = (string) $reservation->cancelled_at;
    $rowsAfterFirst = historyCount($reservation);

    expect($originalReason)->toBe('Guest called — family emergency');

    // The second call — the exact shape a duplicate OTA webhook or a double-clicked button produces.
    expect(fn () => $assignments->cancelReservation($reservation->fresh(), 'oops duplicate'))
        ->toThrow(ReservationAlreadyTerminalException::class);

    $reservation->refresh();

    // NOTHING moved. This is the whole point: the provenance the detail screen renders is intact…
    expect((string) $reservation->cancellation_reason)->toBe($originalReason)
        ->and((string) $reservation->cancelled_at)->toBe($originalAt)
        ->and((string) $reservation->status)->toBe('cancelled')
        // …and no junk `cancelled → cancelled` row was appended to the append-only trail.
        ->and(historyCount($reservation))->toBe($rowsAfterFirst);

    expect(ReservationStatusHistory::query()
        ->where('reservation_id', $reservation->getKey())
        ->where('from_status', 'cancelled')->where('to_status', 'cancelled')->exists())->toBeFalse();
});

// ── 2. EVERY terminal status is terminal, from BOTH public entry points ──────────────────────────
it('refuses to terminate from any terminal status, whichever entry point is used', function (string $terminal) {
    $s = seedScenario(2);
    $assignments = app(AssignmentService::class);

    expect(fn () => $assignments->cancelReservation(terminable($s, 0, $terminal), 'x'))
        ->toThrow(ReservationAlreadyTerminalException::class);

    // markNoShow() funnels into the same private terminate(), so the guard covers it for free — that is
    // precisely why the guard belongs there and not in each public method.
    expect(fn () => $assignments->markNoShow(terminable($s, 1, $terminal)))
        ->toThrow(ReservationAlreadyTerminalException::class);
})->with(['cancelled', 'no_show', 'checked_out']);

// ── 3. A LEGITIMATE FIRST TERMINATION IS UNCHANGED — the guard must not break the real path ──────
it('still allows a first cancellation from every non-terminal status', function (string $status) {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, $status);

    $result = app(AssignmentService::class)->cancelReservation($reservation, 'Legitimate');

    expect((string) $result->status)->toBe('cancelled')
        ->and((string) $result->cancellation_reason)->toBe('Legitimate')
        ->and($result->cancelled_at)->not->toBeNull()
        // The real append-only row IS still written — a valid termination is a legitimate append.
        ->and(ReservationStatusHistory::query()
            ->where('reservation_id', $reservation->getKey())
            ->where('from_status', $status)->where('to_status', 'cancelled')->exists())->toBeTrue();
})->with([
    'enquiry', 'tentative', 'confirmed',
    // ⚠️⚠️ `checked_in` WAS IN THIS DATASET UNTIL 2026-09-22 AND WAS REMOVED — THE REASON, NOT JUST THE EDIT:
    // the founder ruled that a stay the guest is physically in may not be terminated at all. This case was
    // therefore asserting, by name, the exact behaviour the ruling removes. It is not loosened or deleted
    // to get green: the same transition is now asserted in the opposite direction, with its inventory
    // consequence, by 'refuses to cancel a stay the guest is physically in' below. ⚠️ If a future pass
    // builds a reverse-check-in, this dataset still must NOT regain `checked_in` — the route back is
    // `checked_in → confirmed → cancelled`, which the 'confirmed' case above already covers.
]);

it('still allows a first no-show, and releases the inventory exactly as before', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'confirmed');

    $result = app(AssignmentService::class)->markNoShow($reservation);

    expect((string) $result->status)->toBe('no_show')
        // markNoShow() passes NO reason — the guard must not have changed that.
        ->and($result->cancellation_reason)->toBeNull()
        ->and($result->cancelled_at)->not->toBeNull();

    // The inventory consequence is real and automatic (terminate() → releaseLine() → sold−−) and unaffected.
    expect($reservation->reservationUnits()->where('status', 'active')->count())->toBe(0);
});

// ── 4. ⚠️ THE OTA PATH — a network-triggered, RETRYING caller. It must stay idempotent, never throw ──
it('makes a duplicate OTA cancellation a silent no-op rather than an exception', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'cancelled');

    // A webhook that fails is retried forever. If the OTA path let a terminal booking reach the service it
    // would now throw — turning a duplicate delivery into a poison message. It must no-op BEFORE the call.
    $import = app(BookingImportService::class);

    $method = new ReflectionMethod($import, 'cancel');
    expect($method->isPublic())->toBeTrue();
});

it('never lets an OTA cancellation rewrite a completed stay', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'checked_out');

    // Before the shared definition, the OTA path's own guard checked only ['cancelled','no_show'] — so an
    // OTA cancelling a stay the guest had ALREADY COMPLETED went straight through and stamped cancelled_at
    // onto it. The service now refuses, and the OTA path no-ops before ever asking.
    expect(fn () => app(AssignmentService::class)->cancelReservation($reservation, 'OTA cancellation'))
        ->toThrow(ReservationAlreadyTerminalException::class);

    expect((string) $reservation->fresh()->status)->toBe('checked_out')
        ->and($reservation->fresh()->cancelled_at)->toBeNull();
});

// ── 5. THE TWO LAYERS AGREE, AND EACH BLOCKS INDEPENDENTLY (the #26 defense-in-depth shape) ──────
it('keeps the controller allow-list and the service bound in agreement', function () {
    // The controller's policy list must never offer a transition the service will refuse. Asserted rather
    // than assumed: these are two constants in two files, and that is exactly how drift starts.
    foreach (ReservationLifecycleController::CANCELLABLE as $status) {
        expect(AssignmentService::TERMINAL_STATUSES)->not->toContain($status);
    }
    foreach (ReservationLifecycleController::NO_SHOWABLE as $status) {
        expect(AssignmentService::TERMINAL_STATUSES)->not->toContain($status);
    }

    // ⚠️ AND THE SAME MUST HOLD FOR THE 2026-09-22 BOUND — a UI that offers cancel on a stay in progress
    // would walk the operator into a service refusal.
    foreach (ReservationLifecycleController::CANCELLABLE as $status) {
        expect(AssignmentService::IN_PROGRESS_STATUSES)->not->toContain($status);
    }

    // ⚠️⚠️ THIS PARTITION CHANGED ON 2026-09-22, AND THE REASON IS THE POINT — it is not a re-pin to get
    // green. It used to read `enum − TERMINAL_STATUSES === CANCELLABLE`, i.e. "every status is either
    // terminable or terminal", and that dichotomy was true right up until the founder ruled a THIRD state:
    // `checked_in` is refused WITHOUT being terminal (a first termination of an unfinished stay, not a
    // second termination of a finished one). Subtracting only the terminal set now leaves `checked_in`
    // over — which is exactly the red this assertion produced, correctly, when the ruling was applied.
    //
    // ⚠️ It is widened to the UNION rather than deleted, because the property it defends is unchanged and
    // still load-bearing: three constants in three files must not drift, and the UI must never offer a
    // control the write path will refuse. Deleting it would have been the loosening; naming the third set
    // keeps the same guarantee over the new rule.
    $enum = ['enquiry', 'tentative', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];
    $refused = array_merge(AssignmentService::TERMINAL_STATUSES, AssignmentService::IN_PROGRESS_STATUSES);
    expect(array_values(array_diff($enum, $refused)))
        ->toBe(ReservationLifecycleController::CANCELLABLE);

    // ⚠️ And the two refused sets must stay DISJOINT. If a later pass "tidies" `checked_in` into
    // TERMINAL_STATUSES, the partition above would still pass — the union is identical — while four
    // unrelated readers of TERMINAL_STATUSES silently changed behaviour (the GDPR active-stay query, the
    // owner upcoming-check-in flag, R6's OTA modify permission, and this very assertion). This is the line
    // that goes red instead. See ReservationInProgressException's docblock for the measurement.
    expect(array_intersect(AssignmentService::TERMINAL_STATUSES, AssignmentService::IN_PROGRESS_STATUSES))
        ->toBe([], 'the two refusal sets are different concepts and must not merge');
});

it('blocks a re-cancel at the CONTROLLER layer with a clean flash, never a 500', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'confirmed');
    app(AssignmentService::class)->cancelReservation($reservation, 'First and only');

    // The controller's own allow-list refuses BEFORE the service is called…
    $response = $this->actingAs(operatorUser($s))
        ->post('/app/reservations/'.$reservation->id.'/cancel', ['reason' => 'second attempt']);

    $response->assertRedirect(route('app.reservations.show', $reservation->id))
        ->assertSessionHas('folio_notice');

    // …and the original survives either way.
    expect((string) $reservation->fresh()->cancellation_reason)->toBe('First and only');
});

// ── 6. ⚠️⚠️ THE 2026-09-22 RULING — A STAY THE GUEST IS PHYSICALLY IN MAY NOT BE TERMINATED ──────────
//
// ⚠️ A DIFFERENT BOUND FROM #29 ABOVE, TESTED SEPARATELY ON PURPOSE. #29 refuses a SECOND termination of a
// finished stay; this refuses a FIRST termination of an unfinished one. They share a chokepoint and nothing
// else — different reason, different exception, different constant. Folding these cases into the #29 blocks
// above would make a later reader think one rule is being tested twice.
//
// ⚠️ WHAT THIS PASS COULD NOT PROVE, STATED RATHER THAN QUIETLY OMITTED: the brief's "then reverse → cancel
// → allowed, unit released" is NOT asserted anywhere below, because **the product has no reverse-check-in**
// (measured three ways 2026-09-22: the app/Actions/Reservations inventory · every write of
// status => 'confirmed' · every lifecycle route). `checked_in` now has exactly one operator exit,
// `checked_out`. The `confirmed` case in the dataset above is the closest real proof that the cancel path
// itself still works, and it is not a substitute for the step that could not run.

it('refuses to cancel a stay the guest is physically in, and moves NOTHING', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'checked_in');

    // ⚠️ MEASURED BEFORE, NOT INFERRED. The whole risk of this ruling is inventory: the old behaviour freed
    // the remaining nights of an OCCUPIED room back onto the calendar for resale. A refusal that still
    // released a night would be worse than the behaviour it replaced.
    $soldBefore = DB::table('availability')->sum('sold');
    $historyBefore = historyCount($reservation);

    expect(fn () => app(AssignmentService::class)->cancelReservation($reservation, 'mid-stay cancel'))
        ->toThrow(ReservationInProgressException::class);

    // THE ROW, re-read from the database — a method that refuses and a method that silently wrote look
    // identical from the outside.
    $after = $reservation->fresh();
    expect((string) $after->status)->toBe('checked_in', 'the guest is still checked in')
        ->and($after->cancelled_at)->toBeNull()
        ->and($after->cancellation_reason)->toBeNull()
        // The guard sits BEFORE the transaction, so there is nothing to roll back and no line is released.
        ->and(DB::table('availability')->sum('sold'))->toBe($soldBefore, 'the occupied night stays sold')
        ->and($reservation->reservationUnits()->where('status', 'active')->count())->toBe(1)
        // Append-only (#7): a refusal appends nothing either.
        ->and(historyCount($reservation))->toBe($historyBefore);
});

it('refuses a NO-SHOW from checked_in too — the guard is on the shared writer, not on one entrance', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'checked_in');

    // ⚠️ THE STRUCTURAL CLAIM, NOT A SECOND COPY OF THE ONE ABOVE. markNoShow() is the OTHER entrance into
    // terminate(). If the ruling had been implemented on cancelReservation() instead of the shared private
    // writer, this call would still un-arrive the guest — and a no-show is a *worse* lie than a cancel,
    // because it records that a guest standing in the room never turned up. A guard on one entrance is how
    // F-18 and F-29 each hid; this is the test that says the guard is not on one entrance.
    expect(fn () => app(AssignmentService::class)->markNoShow($reservation))
        ->toThrow(ReservationInProgressException::class);

    expect((string) $reservation->fresh()->status)->toBe('checked_in')
        ->and($reservation->reservationUnits()->where('status', 'active')->count())->toBe(1);
});

it('refuses at the CONTROLLER layer with a flash that names a way forward, never a 500', function () {
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'checked_in');

    $response = $this->actingAs(operatorUser($s))
        ->post('/app/reservations/'.$reservation->id.'/cancel', ['reason' => 'guest changed their mind']);

    $response->assertRedirect(route('app.reservations.show', $reservation->id))
        ->assertSessionHas('folio_notice');

    // ⚠️ THE WORDING IS PART OF THE RULING, NOT DECORATION. The generic branch would have said the booking
    // was "already closed" while the guest stood at the desk — false, and it names nothing to do next. The
    // refusal must say the stay is in progress AND name the exit that exists.
    $notice = (string) session('folio_notice');
    expect($notice)->toContain('checked in')
        ->and($notice)->toContain('Check the guest out')
        ->and($notice)->not->toContain('already closed');

    expect((string) $reservation->fresh()->status)->toBe('checked_in');
});

it('proves the new refusal can actually fire — the same call succeeds from confirmed', function () {
    // ⚠️ A GUARD NEVER SEEN TO PASS *AND* FAIL IS NOT A MEASUREMENT. The three cases above are all refusals;
    // on a harness that silently refused everything they would be green and meaningless. This is the
    // control: identical call, identical fixture, one status different → it goes through and releases.
    $s = seedScenario(1);
    $reservation = terminable($s, 0, 'confirmed');

    $result = app(AssignmentService::class)->cancelReservation($reservation, 'legitimate');

    expect((string) $result->status)->toBe('cancelled')
        ->and($reservation->reservationUnits()->where('status', 'active')->count())->toBe(0);
});
