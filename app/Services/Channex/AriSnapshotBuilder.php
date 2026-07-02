<?php

declare(strict_types=1);

namespace App\Services\Channex;

use App\Jobs\Sync\AriPushJob;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds the CURRENT FULL STATE {@see AriSnapshot} for one channel + room_type + date range,
 * reading only already-committed data (convention: the push is a pure consumer — it never
 * mutates availability, never writes sold, never emits domain events).
 *
 * Only rate plans with an ACTIVE {@see ChannelMapping} on this channel are included; an
 * unmapped rate plan is silently excluded from `rate_plans` for a date (its availability still
 * pushes) rather than blocking the whole snapshot — only a missing ROOM_TYPE mapping blocks the
 * entire push (enforced by the caller, {@see AriPushJob}, via the mapping guard).
 */
class AriSnapshotBuilder
{
    public function __construct(
        private AvailabilityService $availability,
    ) {}

    public function build(
        Channel $channel,
        RoomType $roomType,
        string $externalRoomTypeId,
        string $startDate,
        string $endDate,
    ): AriSnapshot {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        $availableByDate = $this->availability->availabilityRange($roomType->getKey(), $start, $end);

        $mappedRatePlans = $this->activelyMappedRatePlans($channel, $roomType);
        $ratePlanIds = $mappedRatePlans->keys()->all();

        $pricesByPlanAndDate = $this->priceLookup($ratePlanIds, $start, $end);
        $restrictionsByPlanAndDate = $this->restrictionLookup($roomType->getKey(), $ratePlanIds, $start, $end);

        $dates = [];
        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            $dateKey = $date->toDateString();

            $ratePlanSnapshots = [];
            foreach ($mappedRatePlans as $ratePlanId => $mapping) {
                $price = $pricesByPlanAndDate[$ratePlanId][$dateKey] ?? null;
                $restriction = $restrictionsByPlanAndDate[$ratePlanId][$dateKey] ?? null;

                $ratePlanSnapshots[] = new AriRatePlanSnapshot(
                    ratePlanId: (string) $ratePlanId,
                    externalRatePlanId: $mapping->external_id,
                    priceMinor: $price?->price_minor,
                    currency: $price?->currency,
                    minStay: $restriction?->min_stay,
                    maxStay: $restriction?->max_stay,
                    closedToArrival: $restriction !== null && $restriction->closed_to_arrival,
                    closedToDeparture: $restriction !== null && $restriction->closed_to_departure,
                    stopSell: $restriction !== null && $restriction->stop_sell,
                );
            }

            $dates[] = new AriDateSnapshot(
                date: $dateKey,
                available: $availableByDate[$dateKey] ?? 0,
                ratePlans: $ratePlanSnapshots,
            );
        }

        return new AriSnapshot(
            channelId: (string) $channel->getKey(),
            externalPropertyId: (string) $channel->external_property_id,
            roomTypeId: (string) $roomType->getKey(),
            externalRoomTypeId: $externalRoomTypeId,
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            dates: $dates,
        );
    }

    /**
     * Active rate plans under this room_type that also have an ACTIVE channel_mapping on this
     * channel, keyed by rate_plan_id => the mapping (for its external_id).
     *
     * @return Collection<string, ChannelMapping>
     */
    private function activelyMappedRatePlans(Channel $channel, RoomType $roomType): Collection
    {
        $ratePlanIds = RatePlan::query()
            ->where('room_type_id', $roomType->getKey())
            ->where('is_active', true)
            ->pluck('id');

        if ($ratePlanIds->isEmpty()) {
            return collect();
        }

        return ChannelMapping::query()
            ->where('channel_id', $channel->getKey())
            ->where('kind', ChannelMapping::KIND_RATE_PLAN)
            ->whereIn('rate_plan_id', $ratePlanIds)
            ->where('status', ChannelMapping::STATUS_ACTIVE)
            ->get()
            ->keyBy('rate_plan_id');
    }

    /**
     * @param  list<string>  $ratePlanIds
     * @return array<string, array<string, RateCalendar>> rate_plan_id => date => row
     */
    private function priceLookup(array $ratePlanIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($ratePlanIds === []) {
            return [];
        }

        $rows = RateCalendar::query()
            ->whereIn('rate_plan_id', $ratePlanIds)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->get();

        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row->rate_plan_id][CarbonImmutable::parse($row->date)->toDateString()] = $row;
        }

        return $lookup;
    }

    /**
     * Rate-plan-scoped restrictions win over room-type-scoped ones for the same date (more
     * specific policy takes precedence).
     *
     * @param  list<string>  $ratePlanIds
     * @return array<string, array<string, Restriction>> rate_plan_id => date => row
     */
    private function restrictionLookup(string $roomTypeId, array $ratePlanIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = Restriction::query()
            ->where(function ($query) use ($roomTypeId, $ratePlanIds): void {
                $query->where('room_type_id', $roomTypeId);
                if ($ratePlanIds !== []) {
                    $query->orWhereIn('rate_plan_id', $ratePlanIds);
                }
            })
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->get();

        $roomTypeScoped = [];
        $ratePlanScoped = [];
        foreach ($rows as $row) {
            $dateKey = CarbonImmutable::parse($row->date)->toDateString();
            if ($row->rate_plan_id !== null) {
                $ratePlanScoped[$row->rate_plan_id][$dateKey] = $row;
            } else {
                $roomTypeScoped[$dateKey] = $row;
            }
        }

        $lookup = [];
        foreach ($ratePlanIds as $ratePlanId) {
            for ($date = $start; $date->lt($end); $date = $date->addDay()) {
                $dateKey = $date->toDateString();
                $resolved = $ratePlanScoped[$ratePlanId][$dateKey] ?? $roomTypeScoped[$dateKey] ?? null;
                if ($resolved !== null) {
                    $lookup[$ratePlanId][$dateKey] = $resolved;
                }
            }
        }

        return $lookup;
    }
}
