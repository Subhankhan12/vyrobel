<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Schedule\ScheduleException;
use App\Models\Property;
use App\Models\Shift;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * THE single write path for the staff shift roster (Phase 7d · Team Schedule · SG1). Every shift assign/clear
 * goes through here, so the property + tenant scoping and the real-staff guard live in ONE place.
 *
 * 🔒 SCOPING: a shift is stamped with the PROPERTY passed in (resolved by the caller through `PropertyContext`,
 * never a request id) and the active org (auto-filled by BelongsToOrganization). So a shift always lands on the
 * SELECTED property and can never be cross-org. 🪤 REAL STAFF: the caller resolves a real {@see User} (validated
 * against {@see StaffRoster}) — this never invents a person.
 */
class ShiftRosterService
{
    public function __construct(
        private readonly AuditEventRecorder $audit,
        private readonly LeaveService $leave,
    ) {}

    /**
     * Set a staff member's shift for a date on a property. A WORKING type upserts the shift; `off` clears it
     * (an off day is the absence of a working shift). Returns the shift, or null when cleared.
     */
    public function assign(Property $property, User $staff, CarbonInterface $date, string $type): ?Shift
    {
        if (! ShiftType::isValid($type)) {
            throw new RuntimeException("Unknown shift type [{$type}].");
        }

        $dateString = $date->toDateString();

        // 🪤 REAL CONFLICT — a working shift can't land on a day the staff member is on APPROVED leave (a
        // computed check against the real leave record, never a fabricated warning). Clearing (`off`) is fine.
        if (ShiftType::isWorking($type) && $this->leave->isOnApprovedLeave((string) $staff->getKey(), $dateString)) {
            throw new ScheduleException($staff->name.' is on approved leave that day — clear the leave first.');
        }

        $existing = Shift::query()
            ->where('property_id', $property->getKey())
            ->where('user_id', $staff->getKey())
            ->whereDate('date', $dateString)
            ->first();

        // `off` = no working shift → clear any existing row.
        if ($type === ShiftType::OFF) {
            if ($existing !== null) {
                $existing->delete();
                $this->audit->record('schedule.shift.cleared', $existing, ['shift_type' => $existing->shift_type], null);
            }

            return null;
        }

        if ($existing !== null) {
            $before = ['shift_type' => $existing->shift_type];
            $existing->update(['shift_type' => $type]);
            $this->audit->record('schedule.shift.assigned', $existing, $before, ['shift_type' => $type]);

            return $existing;
        }

        $shift = Shift::create([
            'property_id' => $property->getKey(),
            'user_id' => $staff->getKey(),
            'date' => $dateString,
            'shift_type' => $type,
            'created_by' => auth()->id(),
        ]);
        $this->audit->record('schedule.shift.assigned', $shift, null, ['shift_type' => $type]);

        return $shift;
    }

    /**
     * MOVE or SWAP a shift by drag-and-drop (GATE 9.1). The source cell MUST hold a REAL working shift; the target
     * cell may be empty (a plain MOVE — source becomes off) or hold its OWN working shift (a SWAP — the two cells
     * exchange types). Composed entirely from {@see assign} so the approved-leave guard, the property/tenant
     * scoping and the audit trail are inherited (never re-implemented), inside ONE transaction so a half-move can
     * never persist.
     *
     * ⚠️ `$fromDate`/`$toDate` are the SERVER-rendered cell date strings the drag handed back (never a client
     * `toISOString()`), so there is no timezone day-roll. A drop on the same cell is a no-op. If the landing cell
     * is on approved leave the composed assign throws {@see ScheduleException} and the whole move rolls back.
     */
    public function moveShift(Property $property, User $fromStaff, CarbonInterface $fromDate, User $toStaff, CarbonInterface $toDate): void
    {
        $fromDateString = $fromDate->toDateString();
        $toDateString = $toDate->toDateString();

        if ((string) $fromStaff->getKey() === (string) $toStaff->getKey() && $fromDateString === $toDateString) {
            return;   // dropped on itself
        }

        $source = Shift::query()
            ->where('property_id', $property->getKey())
            ->where('user_id', $fromStaff->getKey())
            ->whereDate('date', $fromDateString)
            ->first();

        if ($source === null || ! ShiftType::isWorking((string) $source->shift_type)) {
            throw new ScheduleException('There is no shift to move from that day.');
        }
        $sourceType = (string) $source->shift_type;

        // The target's own working type, if any — the source will take it back (a swap). An empty/off target means
        // the source is cleared to off (a plain move).
        $target = Shift::query()
            ->where('property_id', $property->getKey())
            ->where('user_id', $toStaff->getKey())
            ->whereDate('date', $toDateString)
            ->first();
        $targetType = $target !== null && ShiftType::isWorking((string) $target->shift_type)
            ? (string) $target->shift_type
            : ShiftType::OFF;

        // A CONFLICTED source (a working shift already sitting on the staffer's approved-leave day) can be MOVED to
        // a free day to CLEAR the conflict — but it can't be SWAPPED onto another working day, since the swap-back
        // would just re-place a working shift onto the same leave day. Refuse that one gesture with a clear message
        // (rather than let the composed assign() throw the generic leave error, which would name the wrong day).
        if ($targetType !== ShiftType::OFF && $this->leave->isOnApprovedLeave((string) $fromStaff->getKey(), $fromDateString)) {
            throw new ScheduleException('That shift is on an approved-leave day — move it to a free day to clear it, don’t swap it.');
        }

        DB::transaction(function () use ($property, $fromStaff, $fromDate, $toStaff, $toDate, $sourceType, $targetType): void {
            $this->assign($property, $toStaff, $toDate, $sourceType);       // landing takes the moved shift (guard runs here)
            $this->assign($property, $fromStaff, $fromDate, $targetType);   // source takes the target's type, or clears
        });
    }
}
