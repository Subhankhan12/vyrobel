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

    private function assertTransition(Reservation $reservation): void
    {
        $from = (string) $reservation->status;

        if (! in_array($from, self::ALLOWED_FROM, true)) {
            throw new RuntimeException("Cannot check in a {$from} reservation.");
        }
    }
}
