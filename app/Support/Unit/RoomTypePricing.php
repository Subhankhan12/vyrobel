<?php

declare(strict_types=1);

namespace App\Support\Unit;

use App\Http\Controllers\Operator\InventoryActionController;
use App\Livewire\UnitsList;
use App\Models\RatePlan;
use Carbon\CarbonImmutable;

/**
 * WHICH ROOM TYPES HAVE A PRICE TO SELL WITH (2026-10-05) — read by the Units page's setup guide and room type cards
 * ({@see UnitsList}) and by the add notices ({@see InventoryActionController}), so "it can be booked now" and the card's
 * "Units and prices set" can never disagree.
 *
 * 📖 The storefront quotes a stay from an ACTIVE rate plan's `rate_calendar` nights (a derived plan from its base plan's),
 * so a room type counts as priced when such a plan has a price on some night from today on. ⚠️ This answers "has it been
 * priced at all", not "is every night priced" — the Rates and Calendar screens mark the individual nights they cannot sell.
 */
final class RoomTypePricing
{
    /**
     * @param  list<string>  $roomTypeIds
     * @return list<string>
     */
    public static function pricedIds(array $roomTypeIds): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        $today = CarbonImmutable::today()->toDateString();
        $priced = fn ($q) => $q->where('date', '>=', $today)->where('price_minor', '>', 0);

        return RatePlan::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereHas('calendar', $priced)->orWhereHas('derivedFrom.calendar', $priced))
            ->distinct()
            ->pluck('room_type_id')
            ->map(fn ($id): string => (string) $id)
            ->values()
            ->all();
    }
}
