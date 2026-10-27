<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Jobs\SendBookingConfirmationEmail;
use App\Listeners\SendGuestTransactionalEmails;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Services\AssignmentService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ⚠️⚠️ TURNS A TENTATIVE GROUP HOLD INTO A REAL STAY — the ruling on R5 (F-60), built FIX 54.
 *
 * Until this existed there was **no confirm route in the product at all**: 🔬 sixteen operator POST routes
 * touched a reservation and none promoted one; `confirmed` was written only at CREATION. A front desk facing
 * a guest on a tentative group hold had nothing to press.
 *
 * ── ⚠️⚠️ THE HOLD IS ALREADY TAKEN. THIS CONVERTS IT; IT NEVER TAKES A SECOND ────────────────────────────
 *
 * ⚠️⚠️ **THIS IS A PURE STATE TRANSITION AND MUST STAY ONE.** 🔬 Measured: `createGroupBooking` occupies the
 * nights at CREATE — `availability.sold` goes 0 → 2 for a two-night hold, and the `reservation_unit` is
 * already `active`. The room is sold from the moment the hold exists. **Confirming changes what the booking
 * MEANS, not what it occupies**, exactly as {@see CheckInReservation} does at the other end of the ladder.
 *
 * ⚠️⚠️ SO IT NEVER GOES THROUGH {@see AssignmentService} AND NEVER TOUCHES AVAILABILITY. If it did, the
 * nights would be counted twice and the product would oversell the room — and an equal-and-opposite pair of
 * errors nets to zero and passes every instrument this project owns (F-62). The conversion is pinned by a
 * test that reads `availability.sold` before and after.
 *
 * ⚠️ AND IT DELIBERATELY DOES **NOT** RE-CHECK AVAILABILITY. The nights are already held by this very
 * booking, so a re-check would see the hold and could refuse the reservation for competing with itself.
 *
 * ── ⚠️⚠️ WHY A DISTINCT EVENT, AND NOT `reservation.confirmed` ───────────────────────────────────────────
 *
 * 🔬 MEASURED, NOT ASSUMED: `createGroupBooking` ALREADY records `reservation.confirmed` for tentative
 * members — deliberately, because the hold occupies inventory and must sync to channels. That event drives
 * three listeners: the ARI push, the OPERATOR new-booking notification, and **the guest booking-confirmation
 * email** — the third of which was firing for a HOLD, which is F-64, fixed in FIX 55 and described below.
 * ⚠️ Precisely: {@see SendBookingConfirmationEmail} still re-reads the reservation and sends
 * unconditionally; it is the LISTENER that now refuses to dispatch it for anything but a `confirmed` booking.
 * That placement is not incidental — see the ordering argument below.
 *
 * ⚠️⚠️ SO RECORDING `reservation.confirmed` HERE WOULD RE-FIRE THE ARI PUSH AND RE-NOTIFY THE OPERATOR, for a
 * booking whose inventory did not move. This records `reservation.hold_confirmed` instead: it satisfies the
 * invariant that every meaningful state change writes BOTH an audit row and a domain-events row, and 🔬 every
 * listener in `app/Listeners` is gated by event type with no catch-all, so a new type triggers only what is
 * deliberately taught to listen for it.
 *
 * ── ⚠️⚠️ F-64 — WHAT THIS EVENT NOW ALSO DOES, AND WHY THAT IS THE POINT ─────────────────────────────────
 *
 * ⚠️⚠️ **WHEN FIX 54 BUILT THIS ACTION IT REPORTED, AND DID NOT FIX, A PRE-EXISTING DEFECT: the tentative hold
 * itself emailed the guest "booking confirmed".** 🔬 `createGroupBooking` records `reservation.confirmed` for
 * tentative members, and {@see SendGuestTransactionalEmails} did not gate that send on status
 * — so a guest holding a room on a pickup deadline was told, in writing, that it was confirmed. **FIX 55 fixed
 * it as F-64**, and this event is where the repaired send now originates.
 *
 * ⚠️ So `reservation.hold_confirmed` is no longer inert: the email listener handles it, because **this is the
 * first moment at which "your booking is confirmed" is a true statement** about a held booking.
 *
 * ⚠️⚠️ THE GATE IS AT DISPATCH, NOT INSIDE THE JOB, AND THAT IS WHAT MAKES IT SAFE TO CONFIRM QUICKLY: 🔬 the
 * confirmation send carries a sixty-second delay, so a status check *inside* the job would let a hold-time job
 * and a confirm-time job both send when an operator confirms inside that window. Deciding at dispatch means a
 * hold never queues one at all. **Confirming a hold one second after taking it sends exactly one email.**
 *
 * ── ⚠️ NO INVERSE ───────────────────────────────────────────────────────────────────────────────────────
 *
 * ⚠️⚠️ There is deliberately **no un-confirm**. An undo that strands its side effects is exactly what produced
 * F-53 and F-54, which are CONTAINED rather than fixed — still in the code behind a `reverse()` that throws
 * before any write. The project has one quarantined undo already; building a second is a separate decision
 * with its own side-effect analysis, and it is the founder's to make.
 */
class ConfirmTentativeReservation
{
    /**
     * ⚠️⚠️ AN ALLOW-LIST OF EXACTLY ONE, AND THE SHAPE IS THE POINT.
     *
     * Only a `tentative` hold may be confirmed. **Not `enquiry`** — 🔬 no code path in the application writes
     * that status, so its refusal guards a path nothing can reach; **not `cancelled`, `no_show` or
     * `checked_out`**, which {@see AssignmentService::TERMINAL_STATUSES} declares END states; **not
     * `checked_in`**, which is already past confirmation; and **not `confirmed`**, handled idempotently above.
     *
     * ⚠️ Adding a status here later must be a DELIBERATE act, never a fallthrough — which is why this is an
     * allow-list rather than a deny-list, and why every forbidden source carries its own refusal assertion.
     */
    private const ALLOWED_FROM = ['tentative'];

    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    public function handle(Reservation $reservation, ?User $by = null): Reservation
    {
        if ($reservation->status === 'confirmed') {
            return $reservation;   // idempotent — a double-press confirms once
        }

        $this->assertTransition($reservation);

        $fromStatus = (string) $reservation->status;

        DB::transaction(function () use ($reservation, $fromStatus, $by): void {
            // ⚠️ THE STATUS, AND NOTHING ELSE. No availability write, no AssignmentService call, no re-check.
            $reservation->update(['status' => 'confirmed']);

            ReservationStatusHistory::create([
                'reservation_id' => $reservation->getKey(),
                'from_status' => $fromStatus,
                'to_status' => 'confirmed',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'created_at' => now(),
            ]);
        });

        $this->domainEvents->record('reservation.hold_confirmed', $reservation, [
            'property_id' => $reservation->property_id,
            'from_status' => $fromStatus,
        ]);
        $this->audit->record('reservation.hold_confirmed', $reservation, ['status' => $fromStatus], ['status' => 'confirmed']);

        return $reservation->refresh();
    }

    /**
     * ⚠️ THE BOUND LIVES IN THE LAYER THAT PERFORMS THE WRITE, not in the controller — the same doctrine as
     * {@see CheckInReservation::assertTransition()}: a route gate bounds the HTTP surface only, and services
     * are called by jobs and tests with no user.
     */
    private function assertTransition(Reservation $reservation): void
    {
        $from = (string) $reservation->status;

        if (in_array($from, self::ALLOWED_FROM, true)) {
            return;
        }

        throw new RuntimeException(match ($from) {
            'cancelled', 'no_show' => __('php/actions/reservations/confirm_tentative_reservation.refused_closed', ['status' => $from]),
            'checked_in' => __('php/actions/reservations/confirm_tentative_reservation.refused_checked_in'),
            'checked_out' => __('php/actions/reservations/confirm_tentative_reservation.refused_checked_out'),
            'enquiry' => __('php/actions/reservations/confirm_tentative_reservation.refused_enquiry'),
            default => __('php/actions/reservations/confirm_tentative_reservation.refused_other', ['status' => $from]),
        });
    }
}
