<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Unit;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Carbon;

/**
 * OWNER UNIT-STATUS control (Wave C · GATE 3 · ⚠️ affects availability). The owner PAUSES (snoozes) or RESUMES
 * one of their OWN units. A pause sets `units.is_active = false` and RECOMPUTES the room type's availability
 * through the certified {@see AvailabilityService} — which counts only `is_active = true` units into `total`, so a
 * paused unit's capacity is genuinely removed and NEW bookings for it are refused (existing reservations stay).
 * This is a REAL effect on availability, not a label; resume restores it. Owner-scoped: the caller resolves the
 * unit under OwnerScope (it's the owner's OWN unit), and every change is audited.
 *
 * A pause NEVER touches another party — it only removes the owner's own physical unit's capacity (the operator's /
 * another owner's units in the same room type keep theirs). It does not cancel existing bookings (append-only #7).
 */
class OwnerUnitStatusService
{
    /** How far forward to recompute availability when pausing/resuming (the bookable window). */
    private const WINDOW_DAYS = 365;

    public function __construct(
        private AvailabilityService $availability,
        private AuditEventRecorder $audit,
    ) {}

    /** Pause the unit — un-list it + recompute so its capacity is removed (new bookings refused). */
    public function pause(Unit $unit, ?string $actorId = null): void
    {
        $this->setActive($unit, false, 'owner.unit_paused', $actorId);
    }

    /** Resume the unit — re-list it + recompute so its capacity is restored. */
    public function resume(Unit $unit, ?string $actorId = null): void
    {
        $this->setActive($unit, true, 'owner.unit_resumed', $actorId);
    }

    /**
     * ⚠️ THE OPERATOR'S ENTRANCE TO THE SAME MECHANISM (F-6). An operator could create a unit and never take
     * it out of service: no update route, no delete route, no Livewire action — while this service already
     * removed a unit's capacity correctly for OWNERS. Rather than write a second `is_active` writer, both
     * realms now share this one: the availability recompute, the idempotent no-op and the audit row are
     * defined once and cannot drift (§N.8f).
     *
     * ⚠️ THE EVENT NAME IS THE ONLY DIFFERENCE, and it is not cosmetic — recording an operator's action as
     * `owner.unit_paused` would put the wrong realm in the trail that FIX 05 built to answer "who did this".
     *
     * ⚠️ SCOPE IS THE CALLER'S JOB, unchanged: the owner path resolves the unit under OwnerScope, the
     * operator path under the tenant scope. This method adds no scoping of its own and removes none.
     */
    public function deactivateByOperator(Unit $unit, ?string $actorId = null): void
    {
        $this->setActive($unit, false, 'unit.deactivated', $actorId);
    }

    /** The operator's resume — see {@see deactivateByOperator()}. */
    public function reactivateByOperator(Unit $unit, ?string $actorId = null): void
    {
        $this->setActive($unit, true, 'unit.reactivated', $actorId);
    }

    private function setActive(Unit $unit, bool $active, string $event, ?string $actorId): void
    {
        if ((bool) $unit->is_active === $active) {
            return;   // already in the desired state — nothing to do (idempotent)
        }

        $unit->forceFill(['is_active' => $active])->save();

        // Recompute the room type's availability across the bookable window so `total` reflects the change now.
        $today = Carbon::today();
        $this->availability->recompute(
            (string) $unit->room_type_id,
            $today->toDateString(),
            $today->copy()->addDays(self::WINDOW_DAYS)->toDateString(),
        );

        // ⚠️ `$actorId` WAS ACCEPTED AND THEN DROPPED — found while wiring the operator entrance (F-6). The
        // recorder falls back to auth()->user(), which resolves over HTTP, so the trail was correct for both
        // realms' real callers; but a job or console caller passing an actor explicitly would have written a
        // NULL actor, which is precisely what FIX 05 established closes nothing. Passing it through costs a
        // line and makes the parameter mean what its name says.
        $this->audit->record(
            $event,
            $unit,
            ['is_active' => ! $active],
            ['is_active' => $active],
            actorType: $actorId !== null ? 'user' : null,
            actorId: $actorId,
        );
    }
}
