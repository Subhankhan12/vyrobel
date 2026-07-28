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

        $this->audit->record($event, $unit, ['is_active' => ! $active], ['is_active' => $active]);
    }
}
