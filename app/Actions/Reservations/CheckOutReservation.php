<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Exceptions\Folio\OutstandingBalanceException;
use App\Models\Folio;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;
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

        $this->assertFolioSettledOrBilled($reservation);

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

    /**
     * ⚠️⚠️ CHECKOUT IS REFUSED WHILE MONEY IS OWED — unless the balance is billed to a company account.
     *
     * ⚠️ THE GUARD SITS AT THE CONVERGENCE POINT, not in a screen. `FrontDeskService::checkOut()` is this
     * action's only caller and the controller is that method's only caller, so guarding here covers every
     * future caller too; a UI-only block would be bypassable by all of them while looking like protection.
     *
     * ⚠️ IT READS `FolioTotals` — THE SAME COMPUTATION THE FOLIO SCREEN RENDERS (§N.8f), never a second
     * one. A block that fires while the desk reads "€0 due" is indefensible, and the agent routes around it.
     *
     * ⚠️ ONLY AN AMOUNT OWED BLOCKS. A CREDIT balance (the guest overpaid) and a ZERO both pass — a sign
     * error here would block the guests least at fault. Integer minor units; no float comes near it.
     *
     * ⚠️⚠️ THE EXEMPTION IS WHAT MAKES THIS DEFENSIBLE RATHER THAN A BAN ON CORPORATE BUSINESS. A guest can
     * legitimately owe at departure when a company settles later, so a folio carrying a keyed
     * `billed_to_account_id` checks out with the balance still standing.
     *
     * ⚠️⚠️ AND NOTE WHAT IS *NOT* DONE HERE: billing posts NOTHING, so the balance is still owed and this
     * guard must test the BILLED STATE rather than a zeroed balance. Zeroing it to make one guard "just
     * work" would make `OwnerCollection::reservationIsCollected()` — literally `balance_minor <= 0` — read
     * the booking as COLLECTED, and the payout engine would release owner money for cash nobody has
     * received.
     */
    private function assertFolioSettledOrBilled(Reservation $reservation): void
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();

        if (! $folio instanceof Folio) {
            return;   // nothing was ever charged — nothing can be owed
        }

        if ($folio->billed_to_account_id !== null) {
            return;   // ⚠️ owed, but owed by the account — see the note above
        }

        $folio->loadMissing(['items', 'payments']);
        $balance = (int) FolioTotals::for($folio)['balance_minor'];

        if ($balance <= 0) {
            return;   // settled, or in credit
        }

        throw new OutstandingBalanceException(
            $balance,
            (string) $folio->currency,
            MoneyFormatter::display($balance, (string) $folio->currency),
        );
    }
}
