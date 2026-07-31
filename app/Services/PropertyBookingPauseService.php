<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Property;
use App\Models\RoomType;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Carbon;

/**
 * Property-level BOOKING PAUSE (task #98 · ⚠️ affects availability). An operator PAUSES new bookings for a whole
 * property, or RESUMES them. A pause stamps `properties.bookings_paused_at` and RECOMPUTES every room type's
 * availability across the bookable window through the certified {@see AvailabilityService}, which zeroes `total`
 * for a paused property — so NEW bookings are refused while existing `sold` (in-house stays) keep running
 * (append-only #7). This is a REAL effect, not a label; resume restores it.
 *
 * Orthogonal to per-unit `is_active` (the owner unit pause) — it never touches unit rows, so an owner's
 * individually-paused unit stays paused after a property resume. Tenant-scoped: the caller resolves the property
 * under the org scope; every change is audited.
 */
class PropertyBookingPauseService
{
    /** How far forward to recompute availability when pausing/resuming (the bookable window). */
    private const WINDOW_DAYS = 365;

    public function __construct(
        private AvailabilityService $availability,
        private AuditEventRecorder $audit,
    ) {}

    /** Pause new bookings for the property (idempotent). The actor auto-resolves from auth() in the recorder. */
    public function pause(Property $property): void
    {
        if ($property->bookings_paused_at !== null) {
            return;
        }

        $property->forceFill(['bookings_paused_at' => Carbon::now()])->save();
        $this->recomputeAll($property);
        $this->audit->record('property.bookings_paused', $property, null, ['fields' => ['bookings_paused_at']]);
    }

    /** Resume new bookings for the property (idempotent). */
    public function resume(Property $property): void
    {
        if ($property->bookings_paused_at === null) {
            return;
        }

        $property->forceFill(['bookings_paused_at' => null])->save();
        $this->recomputeAll($property);
        $this->audit->record('property.bookings_resumed', $property, null, ['fields' => ['bookings_paused_at']]);
    }

    /** Recompute the forward window for every room type in the property so `total` reflects the pause now. */
    private function recomputeAll(Property $property): void
    {
        $today = Carbon::today();
        $start = $today->toDateString();
        $end = $today->copy()->addDays(self::WINDOW_DAYS)->toDateString();

        RoomType::query()
            ->where('property_id', $property->getKey())
            ->pluck('id')
            ->each(fn (string $roomTypeId) => $this->availability->recompute($roomTypeId, $start, $end));
    }
}
