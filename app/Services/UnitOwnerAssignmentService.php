<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Owner\UnitAssignmentRefused;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerLedgerEntry;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\I18n\ListJoin;
use App\Support\I18n\LocalDate;
use App\Support\Schedule\BoardDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️⚠️ MONEY PATH — DEC-p (2026-11-12): an operator gives an EXISTING owner a unit that has no owner, FROM A CHOSEN DAY.
 *
 * A unit's owner decides who is paid for its stays, so this is the narrowest thing that can be said to be true:
 *
 *   • ONLY A UNIT WITH NO OWNER. An owned unit is refused — its owner's income is never moved from under them. (Nothing here releases a
 *     unit; removing an owner already does.)
 *   • ONLY AN ACTIVE, NON-ARCHIVED OWNER. One who has not accepted their invitation, or was removed, is refused.
 *   • THE DAY IS TODAY AT THE PROPERTY, OR LATER (≤ 365 days). Never retroactive: a day that has passed would change earnings that are
 *     already worked out, and the one rule of this feature is that THE PAST IS NEVER RE-ATTRIBUTED.
 *   • A KNOWN AGREEMENT COVERS THE UNIT ON THAT DAY ({@see ManagementAgreement::resolveFor()}). Without one the attribution posts a €0 line
 *     flagged `no_agreement` and the owner is paid nothing, so "payouts go to <owner>" would be false. The operator is told to give the owner an
 *     agreement first — one is never made here.
 *   • NO LIVE STAY IS CUT IN TWO by the day (a stay is worked out whole, from its first night; nothing splits one at an ownership date), and no
 *     stay from that day on is already worked out for another owner (a removed owner's — the ledger keeps them, and this cannot move them).
 *
 * ⚠️ WHAT IT WRITES: `units.owner_id` + `units.owner_since` (the day), nothing else on money. The ledger is APPEND-ONLY and is never touched
 * except by the certified {@see OwnerAttributionService::attributeReservation()} — idempotent — for the reservations whose stay starts on or
 * after the day, which were booked while the unit had no owner and so have no earnings yet. A stay that started BEFORE the day stays operator
 * inventory: `OwnerAttributionService` cuts each stay segment at `owner_since` (via {@see Unit::ownerIdForStayStarting()}), which is also what keeps
 * `owners:backfill-attribution` and the move listener from ever crediting the owner for a past stay.
 *
 * ONE TRANSACTION: the unit row is locked, every check runs again under the lock, then the write, then the attribution, the audit row
 * (`unit.owner_assigned`: ids and the day only — no names, no figures) and the business event. SCOPE IS THE CALLER'S JOB: the controller resolves
 * the unit inside the person's properties; this adds none and removes none.
 */
final class UnitOwnerAssignmentService
{
    /** How far ahead the day may be, counted from today at the property. */
    public const MAX_DAYS_AHEAD = 365;

    public function __construct(
        private AuditEventRecorder $audit,
        private DomainEventRecorder $domainEvents,
        private OwnerAttributionService $attribution,
    ) {}

    /**
     * Give `$ownerId` the unit from `$date` (`Y-m-d`; null = today at the unit's property).
     *
     * @return array{unit: Unit, since: string, attributed: int, waiting: int} how many stays from that day were worked out for the owner now, and how many
     *                                                                         could not be yet (no room charge on the booking's folio yet — they are worked out when it is posted)
     *
     * @throws UnitAssignmentRefused with a plain sentence; nothing was written
     */
    public function assign(string $unitId, string $ownerId, ?string $date = null, ?string $actorId = null): array
    {
        return DB::transaction(function () use ($unitId, $ownerId, $date, $actorId): array {
            // the unit's row is locked FIRST, so two operators giving it at once cannot both pass the "no owner" check
            $unit = Unit::query()->lockForUpdate()->findOrFail($unitId);
            $owner = Owner::query()->lockForUpdate()->find($ownerId);

            $day = $this->refusalOrDay($unit, $owner, $date);

            $before = ['owner_id' => $unit->owner_id !== null ? (string) $unit->owner_id : null, 'owner_since' => $unit->owner_since?->toDateString()];
            $unit->forceFill(['owner_id' => (string) $owner->getKey(), 'owner_since' => $day->toDateString()])->save();

            [$attributed, $waiting] = $this->workOutStaysFrom($unit, $owner, $day);

            // ⚠️ IDS AND THE DAY ONLY — an audit row is not a second copy of the money, and holds no name (#28)
            $after = ['unit_id' => (string) $unit->getKey(), 'owner_id' => (string) $owner->getKey(), 'owner_since' => $day->toDateString(), 'stays_worked_out' => $attributed, 'stays_waiting' => $waiting];
            $this->audit->record('unit.owner_assigned', $unit, $before, $after, actorId: $actorId);
            $this->domainEvents->record('unit.owner_assigned', $unit, [
                'unit_id' => (string) $unit->getKey(),
                'owner_id' => (string) $owner->getKey(),
                'owner_since' => $day->toDateString(),
            ]);

            return ['unit' => $unit->refresh(), 'since' => $day->toDateString(), 'attributed' => $attributed, 'waiting' => $waiting];
        });
    }

    /**
     * The sentence that refuses this, or null when the unit may be given — the dialog's pre-check and the write's own check are this one rule.
     * Read-only (no lock): {@see assign()} asks again under the lock.
     */
    public function refusal(Unit $unit, ?Owner $owner, ?string $date = null): ?string
    {
        try {
            $this->refusalOrDay($unit, $owner, $date);
        } catch (UnitAssignmentRefused $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Every rule, in the order an operator would fix them. Returns the day when all hold.
     *
     * @phpstan-assert Owner $owner   (a missing or inactive owner is refused, so past this call there is one)
     *
     * @throws UnitAssignmentRefused
     */
    private function refusalOrDay(Unit $unit, ?Owner $owner, ?string $date): CarbonImmutable
    {
        $label = $this->unitLabel($unit);

        // 1 — an active owner who still exists (a soft-deleted one is not found by the model's own scope)
        if (! $owner instanceof Owner || (string) $owner->status !== 'active') {
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.owner_not_active', ['owner' => $owner instanceof Owner ? (string) $owner->name : __('php/services/unit_owner_assignment_service.unit_owned_by_another')]));
        }

        // 2 — only a unit with no owner
        if ($unit->owner_id !== null) {
            $current = Owner::withTrashed()->whereKey($unit->owner_id)->value('name');
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.unit_already_owned', ['unit' => $label, 'owner' => is_string($current) && $current !== '' ? $current : __('php/services/unit_owner_assignment_service.unit_owned_by_another')]));
        }

        // 2b — a unit a still-waiting owner invitation lists is promised to that invitee (accepting hands them every unit it lists that has no owner then):
        //      giving it away here would make that invitation quietly smaller. Cancel the invitation first, or wait for it to be accepted.
        if (in_array((string) $unit->getKey(), OwnerInvite::unitIdsOnPendingInvites(), true)) {
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.unit_on_pending_invitation', ['unit' => $label]));
        }

        // 3 — the day: a real date, today at the property or later, within a year
        $today = BoardDate::today(Property::query()->whereKey($unit->property_id)->value('timezone'));
        $day = $date === null || $date === '' ? $today : $this->parseDay($date);
        if ($day === null) {
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.date_invalid'));
        }
        if ($day->lessThan($today)) {
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.date_in_past'));
        }
        if ($day->greaterThan($today->addDays(self::MAX_DAYS_AHEAD))) {
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.date_too_far', ['days' => self::MAX_DAYS_AHEAD]));
        }

        $pretty = $day->translatedFormat(LocalDate::pattern('j M Y'));

        // 4 — an agreement that covers this unit on that day (owner-wide or for this unit); never made here
        if (ManagementAgreement::resolveFor((string) $owner->getKey(), (string) $unit->getKey(), $day) === null) {
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.no_agreement', ['owner' => (string) $owner->name, 'unit' => $label, 'date' => $pretty]));
        }

        // 5 — no live stay on the unit is cut in two by that day
        $across = ReservationUnit::query()
            ->where('unit_id', $unit->getKey())->where('status', 'active')
            ->whereDate('check_in', '<', $day->toDateString())->whereDate('check_out', '>', $day->toDateString())
            ->whereHas('reservation', fn ($q) => $q->whereNotIn('status', AssignmentService::TERMINAL_STATUSES))
            ->orderBy('check_in')->first();
        if ($across instanceof ReservationUnit) {
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.stay_across_date', [
                'unit' => $label, 'owner' => (string) $owner->name, 'date' => $pretty,
                'from' => CarbonImmutable::parse($across->check_in)->translatedFormat(LocalDate::pattern('j M Y')),
                'to' => CarbonImmutable::parse($across->check_out)->translatedFormat(LocalDate::pattern('j M Y')),
            ]));
        }

        // 6 — no stay from that day on is already worked out for a DIFFERENT owner (the ledger is append-only; this cannot move them)
        $others = OwnerLedgerEntry::withoutOwnerScope()
            ->where('unit_id', $unit->getKey())->where('owner_id', '!=', (string) $owner->getKey())
            ->whereIn('reservation_id', $this->liveStayReservationIds($unit, $day))
            ->distinct()->pluck('owner_id');
        if ($others->isNotEmpty()) {
            $names = Owner::withTrashed()->whereIn('id', $others->all())->orderBy('name')->pluck('name')->all();
            throw new UnitAssignmentRefused(__('php/services/unit_owner_assignment_service.future_stays_with_another_owner', ['unit' => $label, 'date' => $pretty, 'owners' => ListJoin::join($names)]));
        }

        return $day;
    }

    /**
     * The reservations whose stay on this unit STARTS on or after the day and that are not over (not cancelled, no-show or checked out) — the stays
     * that become the owner's. A stay that started earlier is the operator's and is never read here.
     *
     * @return list<string>
     */
    private function liveStayReservationIds(Unit $unit, CarbonImmutable $day): array
    {
        return ReservationUnit::query()
            ->where('unit_id', $unit->getKey())->where('status', 'active')
            ->whereDate('check_in', '>=', $day->toDateString())
            ->whereHas('reservation', fn ($q) => $q->whereNotIn('status', AssignmentService::TERMINAL_STATUSES))
            ->pluck('reservation_id')->map(fn ($id): string => (string) $id)->unique()->values()->all();
    }

    /**
     * Work out the owner's earnings for the stays that start on or after the day, through the CERTIFIED, idempotent, append-only entry point
     * (a booking already worked out is skipped; one whose folio has no room charge yet is left to be worked out when it is posted).
     *
     * @return array{0: int, 1: int} [worked out for the owner, still waiting]
     */
    private function workOutStaysFrom(Unit $unit, Owner $owner, CarbonImmutable $day): array
    {
        $attributed = 0;
        $waiting = 0;

        foreach (Reservation::query()->whereIn('id', $this->liveStayReservationIds($unit, $day))->orderBy('id')->get() as $reservation) {
            $this->attribution->attributeReservation($reservation->refresh());

            if (OwnerLedgerEntry::withoutOwnerScope()->where('reservation_id', $reservation->getKey())->where('owner_id', (string) $owner->getKey())->exists()) {
                $attributed++;
            } else {
                $waiting++;
            }
        }

        return [$attributed, $waiting];
    }

    private function parseDay(string $date): ?CarbonImmutable
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        return $day instanceof CarbonImmutable && $day->format('Y-m-d') === $date ? $day : null;
    }

    private function unitLabel(Unit $unit): string
    {
        return (string) ($unit->name !== null && (string) $unit->name !== '' ? $unit->name : $unit->code);
    }
}
