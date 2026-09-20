<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

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
 * email**, whose job 🔬 does not gate on status and sends unconditionally.
 *
 * ⚠️⚠️ SO RECORDING `reservation.confirmed` HERE WOULD SEND THE GUEST A **SECOND** "BOOKING CONFIRMED"
 * EMAIL AND RE-NOTIFY THE OPERATOR, for a booking whose inventory did not move. This records
 * `reservation.hold_confirmed` instead: it satisfies the invariant that every meaningful state change writes
 * BOTH an audit row and a domain-events row, and 🔬 every listener in `app/Listeners` is gated by event type
 * with no catch-all, so a new type triggers nothing.
 *
 * ⚠️ **THAT A TENTATIVE HOLD EMAILS THE GUEST "BOOKING CONFIRMED" AT ALL IS A PRE-EXISTING DEFECT, REPORTED
 * SEPARATELY AND NOT FIXED HERE.** This action neither causes it nor compounds it.
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
            'cancelled', 'no_show' => "Cannot confirm a {$from} reservation: the booking is closed and its "
                .'nights were released back to sale. A returning guest needs a new booking.',
            'checked_in' => 'Cannot confirm a checked-in reservation: the guest has already arrived, so the '
                .'booking is past confirmation.',
            'checked_out' => 'Cannot confirm a checked-out reservation: the stay is already over.',
            'enquiry' => 'Cannot confirm an enquiry: only a tentative hold can be confirmed.',
            default => "Cannot confirm a {$from} reservation: only a tentative hold can be confirmed.",
        });
    }
}
