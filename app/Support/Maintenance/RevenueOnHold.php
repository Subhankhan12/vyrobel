<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Models\RateCalendar;
use App\Models\RoomType;
use App\Models\Unit;
use App\Support\Pricing\NightlyRate;

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

        // ⚠️ D18-5 — THIS IS HANDED UNIT IDS, so the revenue a blocked unit withholds is ITS revenue. Summing
        // the room-type price once per unit understated (or overstated) any unit carrying its own price. The
        // resolver answers per unit and falls back to the type price, so a unit nobody priced individually
        // contributes exactly what it always did.
        $unitRoomType = Unit::query()->whereIn('id', $unitIds)->pluck('room_type_id', 'id');

        $total = 0;
        foreach ($unitRoomType as $unitId => $rtId) {
            $planId = $plans[$rtId] ?? null;
            if ($planId === null) {
                continue;
            }

            $total += NightlyRate::forUnit((string) $planId, (string) $unitId, $date)
                ?? (int) ($planPrice[$planId] ?? 0);
        }

        return $total;
    }
}
