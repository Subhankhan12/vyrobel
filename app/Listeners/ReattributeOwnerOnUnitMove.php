<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\OwnerAttributionService;
use App\Support\Tenancy\TenantContext;

/**
 * ⚠️⚠️ MONEY PATH — THE OWNER FOLLOWS THE UNIT WHEN A BOOKING IS MOVED.
 *
 * ⚠️ THE DEFECT THIS CLOSES WAS SILENTLY WRONG IN BOTH DIRECTIONS AT ONCE. `attributeReservation()` ran
 * once at booking creation and nothing re-ran it, so moving a booking from one owner's unit to another's
 * left the earnings on the ORIGINAL owner's statement: one owner overpaid, the other underpaid, and
 * neither had any way to notice.
 *
 * ⚠️ NOTHING NEW IS INVENTED HERE. `OwnerAttributionService::reattributeReservation()` already existed,
 * already reverses the live version as a WHOLE TRANSACTION before attributing a fresh one, and already
 * stamps `prior_period_adjustment` when the reversal lands in a sealed period — it simply had NO CALLER.
 * This listener is that caller, and nothing else.
 *
 * ⚠️⚠️ THE SPLIT DATE IS NOT INVENTED. A mid-stay move needs to know WHEN the guest actually changed
 * rooms, and the move record carries no such fact: `assignUnit()` writes `unit_id` and records
 * from/to — there is no effective date anywhere. A `now()` or the audit row's timestamp standing in for
 * it would be a fabricated fact that lands on someone's payout, so an ALREADY-ARRIVED booking is left
 * alone and reported rather than guessed at. A move made BEFORE arrival has no split at all: the whole
 * stay belongs to the new unit's owner, which is unambiguous and is what this handles.
 */
class ReattributeOwnerOnUnitMove
{
    /** @var array<string, true> the double-fire guard (the bus can deliver an event more than once) */
    private static array $processed = [];

    public function __construct(
        private OwnerAttributionService $attribution,
        private TenantContext $tenant,
    ) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;
        if ($domainEvent->event_type !== 'reservation_unit.moved') {
            return;
        }

        $key = (string) $domainEvent->id;
        if (isset(self::$processed[$key])) {
            return;
        }
        self::$processed[$key] = true;

        $orgId = $domainEvent->organization_id;
        if (! is_string($orgId) || $orgId === '') {
            return;
        }

        $payload = is_array($domainEvent->payload) ? $domainEvent->payload : [];
        $from = $payload['from_unit_id'] ?? null;
        $to = $payload['to_unit_id'] ?? null;

        $this->tenant->forOrganization($orgId, function () use ($domainEvent, $from, $to): void {
            // ⚠️ SAME OWNER → NOTHING AT ALL. A reversal plus an identical re-post is churn on an
            //    append-only ledger, and the owner can SEE it on their statement. The guard is on the
            //    OWNER, not on the unit: moving between two of one owner's units changes no money.
            //    ⚠️ Ownership is read as `units.owner_id` — the SAME resolution the attribution itself
            //    uses (§N.8f); a second definition of "who owns this unit" would eventually disagree
            //    with the one that decides where the money goes.
            if ($this->ownerOf($from) === $this->ownerOf($to)) {
                return;
            }

            $line = ReservationUnit::query()->find($domainEvent->subject_id);
            if (! $line instanceof ReservationUnit) {
                return;
            }

            $reservation = Reservation::query()->with('reservationUnits')->find($line->reservation_id);
            if (! $reservation instanceof Reservation) {
                return;
            }

            // ⚠️⚠️ ARRIVED ALREADY → LEAVE IT ALONE. Splitting the stay by nights needs the date the guest
            //    actually moved, and no such fact is recorded. Re-attributing the WHOLE stay to the new
            //    owner would be as wrong as leaving it on the old one, just in the other direction.
            //    ⚠️ Reported as a gap rather than guessed — see DECISIONS.
            if ($this->hasArrived($reservation)) {
                return;
            }

            // The certified path: whole-transaction reversal of the live version, then a fresh
            // attribution from the CURRENT unit assignments — which is what makes every ownership
            // transition fall out correctly, including on and off an operator-owned unit.
            $this->attribution->reattributeReservation($reservation);
        });
    }

    /** ⚠️ null for an operator-owned unit — that is a real answer, not a missing one. */
    private function ownerOf(mixed $unitId): ?string
    {
        if (! is_string($unitId) || $unitId === '') {
            return null;
        }

        $unit = Unit::query()->find($unitId);

        return $unit === null ? null : ($unit->owner_id === null ? null : (string) $unit->owner_id);
    }

    /** Arrived = the operator's authoritative check-in, or the guest already in-house/departed. */
    private function hasArrived(Reservation $reservation): bool
    {
        // ⚠️ `in_house` is a STAGE, not a status — the enum's arrived values are exactly these two.
        return in_array((string) $reservation->status, ['checked_in', 'checked_out'], true);
    }
}
