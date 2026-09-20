<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\HousekeepingService;
use App\Services\MaintenanceService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Checks a reservation in. Like {@see CheckOutReservation}, this is a pure reservation STATE
 * transition — the nights were already occupied at booking time, so check-in frees/consumes no
 * inventory: it NEVER goes through AssignmentService and never touches availability. Emits
 * `reservation.checked_in` post-commit (no listener acts on it today — it's semantic-layer /
 * audit context). Idempotent.
 */
class CheckInReservation
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    public function handle(Reservation $reservation, ?User $by = null): Reservation
    {
        if ($reservation->status === 'checked_in') {
            return $reservation; // idempotent
        }

        $this->assertTransition($reservation);

        $fromStatus = (string) $reservation->status;

        DB::transaction(function () use ($reservation, $fromStatus, $by): void {
            $reservation->update(['status' => 'checked_in']);

            ReservationStatusHistory::create([
                'reservation_id' => $reservation->getKey(),
                'from_status' => $fromStatus,
                'to_status' => 'checked_in',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'created_at' => now(),
            ]);
        });

        $this->domainEvents->record('reservation.checked_in', $reservation, [
            'property_id' => $reservation->property_id,
        ]);
        $this->audit->record('reservation.checked_in', $reservation, ['status' => $fromStatus], ['status' => 'checked_in']);

        return $reservation->refresh();
    }

    /**
     * ⚠️ THE LADDER'S BOUND (F-18) — the SOURCE-state check this action never had. Same shape as
     * {@see MaintenanceService::assertTransition()} and {@see HousekeepingService},
     * placed in the layer that performs the WRITE: the controller's own check bounds the HTTP surface only,
     * and this project's doctrine is that a data bound belongs here "because services are called by jobs/tests
     * with no user". 🔬 Measured before the fix: a **cancelled** reservation was driven to `checked_in`.
     *
     * ⚠️ THE MAP IS DERIVED, NOT INVENTED. `confirmed` is the only source both live callers permit
     * (FrontDeskActionController and the walk-in path each require exactly `confirmed`), and
     * {@see AssignmentService::TERMINAL_STATUSES} is the codebase's own declaration that
     * `cancelled`, `no_show` and `checked_out` are END states — so walking back out of one is refused here
     * on the product's existing semantics rather than on a policy invented in this guard.
     * ⚠️ `checked_in → checked_in` never reaches this: the idempotent return above handles it, unchanged.
     * ⚠️ Whether a terminal state should EVER be reversible is a founder ruling — see docs/STATUS-LADDERS.md.
     */
    private const ALLOWED_FROM = ['confirmed'];

    /**
     * ⚠️⚠️ EXTENDED BY THE FOUNDER, 2026-09-19 — R1 COVERS `no_show` TOO (F-60). The ruling's reasoning was
     * always an AVAILABILITY argument, and 🔬 it is literally true of a no-show: `markNoShow()` funnels
     * through `AssignmentService::terminate()`, which RELEASES THE LINE — measured, `availability.sold` over
     * the stay went 2 → 0 — and records a domain event `EnqueueAriPushOnInventoryChange` subscribes to, so
     * the freed nights may already have been pushed to the channels. **Permitting the transition would be a
     * re-booking against inventory that may no longer exist.** Only the ruling's TEXT named `cancelled`; the
     * refusal itself was already correct and is unchanged.
     *
     * ⚠️⚠️ THE MESSAGES ARE NOW SPLIT, AND THE `no_show` ARM NAMES A ROUTE THAT WAS DRIVEN BEFORE IT WAS
     * NAMED. 🔬 Measured 2026-09-19: **reschedule is NOT a way out** — `AssignmentService::reschedule()`
     * refuses with *"Only an active reservation line can be rescheduled"* because the line is no longer
     * active. 🔬 **A new reservation IS** — booking the same nights for the same guest succeeded and took
     * `availability.sold` back 0 → 2. 🔬 The cost was measured too: the no-show keeps its own folio (a 5,000
     * minor-unit fee survived it) and the guest ends with two reservations.
     *
     * ⚠️ **A refusal that names a route which does not exist is worse than one that names none** — it sends a
     * night receptionist hunting for a button nobody built, at 2am, with a guest at the desk. "New
     * reservation" is the front desk's own label for the control (`app.reservations.store`).
     *
     * ⚠️⚠️ RULED BY THE FOUNDER, 2026-09-19 — R1: `cancelled` → `checked_in` STAYS REFUSED. A cancelled
     * booking cannot be revived into an occupied stay: the room may already have been resold, and reinstating
     * one is an AVAILABILITY question, not a status write. ⚠️ The refusal was already correct — this ruling
     * changed no behaviour. What it changed is the MESSAGE: a front desk facing a guest who is standing there
     * needs to be told what to do, not only what they may not do.
     *
     * ⚠️ `checked_out` → `checked_in` is R3 and is NOT built: the undo cannot honestly reverse the review
     * email the checkout already sent. See docs/STATUS-LADDERS.md.
     */
    private function assertTransition(Reservation $reservation): void
    {
        $from = (string) $reservation->status;

        if (in_array($from, self::ALLOWED_FROM, true)) {
            return;
        }

        throw new RuntimeException(match ($from) {
            // ⚠️ R1 — F-18's exact shape: a rolled-back probe once revived a cancelled booking this way.
            'cancelled' => 'Cannot check in a cancelled reservation: the booking is closed and the '
                .'room may already have been resold. A returning guest needs a new booking.',

            // ⚠️⚠️ R1 EXTENDED TO `no_show` (F-60). The route below was DRIVEN before it was named.
            'no_show' => 'Cannot check in a no-show reservation: the booking is closed and its nights were '
                .'released back to sale. To put this guest in a room, take a New reservation on the front '
                .'desk — it re-checks availability before it books. The no-show and any fee already charged '
                .'on it stay on the guest record.',
            'checked_out' => 'Cannot check in a checked-out reservation: the departure has already been '
                .'recorded and acted on. Reversing a checkout is not available.',

            // ⚠️⚠️ R5 (F-60) — REFUSE, AND NAME THE WAY OUT, exactly as R1's arms do. ⚠️ THE ROUTE WAS BUILT
            // AND DRIVEN BEFORE THIS SENTENCE NAMED IT (FIX 54): when the ruling was first put, the proposed
            // copy pointed at a confirm route that 🔬 DID NOT EXIST — sixteen operator POST routes touched a
            // reservation and not one promoted it. Naming it then would have shipped a dead end.
            //
            // ⚠️ "Confirm hold" is the SHIPPED LABEL — read out of the reservation-detail view, not a
            // description of the control invented here. If that button is ever renamed, this sentence
            // becomes a lie about the product's own UI; ReservationLadderTest reads the label from the
            // blade and pins the two together, so that cannot happen silently.
            //
            // ⚠️⚠️ AND THE COPY STATES WHY IT CANNOT FAIL, which is the part a receptionist needs: 🔬 the hold
            // already occupies the nights (`availability.sold` 0 → 2 at create, unchanged at confirm), so
            // confirming converts what is held and takes nothing further. Unlike the `no_show` arm — where the
            // nights were RELEASED and a new booking must re-check availability — there is nothing here that
            // can be lost to another guest in between.
            'tentative' => 'Cannot check in a tentative booking: the room is held but not confirmed, so '
                .'nobody has promised this guest the stay yet. Press Confirm hold on this reservation first, '
                .'then check them in. The nights are already held by this booking, so confirming takes '
                .'nothing further and cannot fail for availability.',

            // ⚠️ `enquiry` DELIBERATELY GETS NO ARM AND NO COPY. 🔬 Nothing in this application writes that
            // status — it exists in the enum and in display labels and no code path produces it — so a
            // sentence naming a route out of it would describe a journey no guest can be on. Recorded as a
            // ruling in docs/STATUS-LADDERS.md: do not "fix" the missing enquiry flow.
            default => "Cannot check in a {$from} reservation.",
        });
    }
}
