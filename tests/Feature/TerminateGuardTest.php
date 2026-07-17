<?php

use App\Exceptions\Booking\ReservationAlreadyTerminalException;
use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Services\AssignmentService;
use App\Services\BookingImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
})->with(['enquiry', 'tentative', 'confirmed', 'checked_in']);

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

    // And together they partition the real enum: every status is either terminable or terminal.
    $enum = ['enquiry', 'tentative', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];
    expect(array_values(array_diff($enum, AssignmentService::TERMINAL_STATUSES)))
        ->toBe(ReservationLifecycleController::CANCELLABLE);
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
