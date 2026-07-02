<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Checks a reservation out. This is a pure reservation STATE transition — it does NOT touch
 * inventory (the nights were consumed, not freed), so it never goes through AssignmentService
 * and never mutates availability. It emits `reservation.checked_out` post-commit; downstream
 * (housekeeping) reacts to that event as a one-directional consumer.
 */
class CheckOutReservation
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    public function handle(Reservation $reservation, ?User $by = null): Reservation
    {
        if ($reservation->status === 'checked_out') {
            return $reservation; // idempotent
        }

        $fromStatus = (string) $reservation->status;

        DB::transaction(function () use ($reservation, $fromStatus, $by): void {
            $reservation->update(['status' => 'checked_out']);

            ReservationStatusHistory::create([
                'reservation_id' => $reservation->getKey(),
                'from_status' => $fromStatus,
                'to_status' => 'checked_out',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'created_at' => now(),
            ]);
        });

        // Post-commit. Payload carries what the housekeeping listener needs (no re-query, no
        // inventory reference). This event is NOT an availability-recompute trigger.
        $unitIds = $reservation->reservationUnits()
            ->where('status', 'active')
            ->whereNotNull('unit_id')
            ->pluck('unit_id')
            ->all();

        $this->domainEvents->record('reservation.checked_out', $reservation, [
            'property_id' => $reservation->property_id,
            'unit_ids' => $unitIds,
            'date' => CarbonImmutable::parse($reservation->check_out)->toDateString(),
        ]);
        $this->audit->record('reservation.checked_out', $reservation, ['status' => $fromStatus], ['status' => 'checked_out']);

        return $reservation->refresh();
    }
}
