<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Property;
use App\Models\Shift;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonInterface;
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
    public function __construct(private readonly AuditEventRecorder $audit) {}

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
}
