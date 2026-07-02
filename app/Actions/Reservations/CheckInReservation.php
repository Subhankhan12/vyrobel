<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
}
