<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Models\RateCalendar;
use App\Models\RoomType;
use App\Models\Unit;

/**
 * The REAL "revenue on hold" for out-of-order units (#89) — the sum of tonight's rate for each blocked unit's
 * room type (via the room type's default rate plan → {@see RateCalendar}). DERIVED from the real rate calendar,
 * never fabricated: a unit whose room type has no rate tonight contributes 0 (not an invented figure). The
 * operator Maintenance board's "revenue on hold" reads this; the Dashboard #84 derives the same figure inline.
 */
final class RevenueOnHold
{
    /**
     * @param  list<string>  $unitIds  the blocked (out-of-order) unit ids
     */
    public static function forUnits(array $unitIds, string $date): int
    {
        if ($unitIds === []) {
            return 0;
        }

        $roomTypeIds = Unit::query()->whereIn('id', $unitIds)->pluck('room_type_id')->filter()->values();
        if ($roomTypeIds->isEmpty()) {
            return 0;
        }

        $plans = RoomType::query()->whereIn('id', $roomTypeIds->unique()->all())->pluck('default_rate_plan_id', 'id');
        $planIds = collect($plans)->filter()->unique()->values();

        $planPrice = [];
        if ($planIds->isNotEmpty()) {
            $planPrice = RateCalendar::query()
                ->whereIn('rate_plan_id', $planIds->all())
                ->whereDate('date', $date)
                ->pluck('price_minor', 'rate_plan_id')
                ->all();
        }

        $total = 0;
        foreach ($roomTypeIds as $rtId) {
            $planId = $plans[$rtId] ?? null;
            $total += $planId !== null ? (int) ($planPrice[$planId] ?? 0) : 0;
        }

        return $total;
    }
}
