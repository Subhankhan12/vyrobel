<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Property;
use App\Models\Reservation;
use App\Support\Events\AuditEventRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Property removal — a careful, scheduled ARCHIVE-NOT-DESTROY (task #98 GATE 2 · ⚠️ never destructive).
 *
 * Removal is SCHEDULED, not immediate: the property stays live until the last active reservation checks out
 * ({@see schedule} computes the effective date), then a scheduled pass ({@see processDue}) SOFT-DELETES it and
 * disconnects its channels. It is CANCELLABLE ({@see cancel}) any time before it runs. Reservations, folios and
 * statements are NEVER destroyed — they live in their own tables (a soft-delete doesn't cascade), so they stay
 * queryable/exportable. A hard `delete()` is NEVER used (it would fire `property_user`'s `cascadeOnDelete`).
 *
 * Tenant-scoped: the caller resolves the property under the org scope; every transition is audited.
 */
class PropertyRemovalService
{
    public function __construct(private AuditEventRecorder $audit) {}

    /**
     * Schedule removal. Effective date = the last checkout among non-cancelled future/current stays (so the
     * property stays live until then); immediate (today) when there are none. Records who requested it.
     */
    public function schedule(Property $property, string $requestedBy): void
    {
        $today = CarbonImmutable::today();

        $lastCheckout = Reservation::query()
            ->where('property_id', $property->getKey())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereDate('check_out', '>=', $today->toDateString())
            ->max('check_out');

        $effective = $lastCheckout !== null
            ? CarbonImmutable::parse($lastCheckout)->startOfDay()
            : $today;

        $property->forceFill([
            'removal_scheduled_at' => Carbon::now(),
            'removal_effective_at' => $effective->toDateTimeString(),
            'removal_requested_by' => $requestedBy,
        ])->save();

        $this->audit->record('property.removal_scheduled', $property, null, ['fields' => ['removal_effective_at']]);
    }

    /** Cancel a scheduled removal before it runs — clears the schedule; the property is untouched. */
    public function cancel(Property $property): void
    {
        if ($property->removal_scheduled_at === null) {
            return;
        }

        $property->forceFill([
            'removal_scheduled_at' => null,
            'removal_effective_at' => null,
            'removal_requested_by' => null,
        ])->save();

        $this->audit->record('property.removal_cancelled', $property, null, ['fields' => ['removal_scheduled_at']]);
    }

    /**
     * Archive every property whose scheduled effective date has passed (run daily). Returns the count archived.
     * Each: disconnect channels (certified {@see ChannelConnectionService}), then SOFT-DELETE (records retained).
     */
    public function processDue(ChannelConnectionService $channels): int
    {
        $due = Property::query()
            ->whereNotNull('removal_effective_at')
            ->where('removal_effective_at', '<=', Carbon::now())
            ->get();

        foreach ($due as $property) {
            $this->archive($property, $channels);
        }

        return $due->count();
    }

    /** Archive one property NOW (used by processDue and available for an immediate effective date). */
    public function archive(Property $property, ChannelConnectionService $channels): void
    {
        Channel::query()->where('property_id', $property->getKey())->get()
            ->each(fn (Channel $channel) => $channels->disconnect($channel));

        $this->audit->record('property.archived', $property, null, ['fields' => ['deleted_at']]);

        $property->delete();   // SoftDeletes — sets deleted_at; NEVER a hard delete (no FK cascade fires).
    }
}
