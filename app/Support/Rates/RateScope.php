<?php

declare(strict_types=1);

namespace App\Support\Rates;

use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Models\RoomType;
use App\Support\Property\PropertyContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * WHAT A RATE WRITE MAY TOUCH — the ONE rule, read by every writer behind the Rates page (the editor, bulk edit, per-unit
 * prices, seasons, rate plans).
 *
 * ⚠️⚠️ DEC-l, HIS RULING (2026-09-30: "Yes, all of it" to the Rates plan, which named it): a price changes only in the
 * properties the person works in — the same {@see PropertyContext::readIds()} rule every unit write already uses (DEC-k).
 * Before, each writer found its room type, plan or season through the TENANT scope only, so staff limited to one property
 * could reprice another by posting its id; and "All room types" meant every room type in the ORGANISATION — a bulk change
 * made while looking at one property silently repriced the others. A room type, plan or season outside that set is NOT
 * FOUND — a 404, never a 403 that would confirm it exists.
 *
 * ⚠️ `readIds()` is the property on screen, or every accessible one under "All properties" — exactly what the page lists,
 * so every control offers only what its write accepts. Outside a web request (no property context) it is empty and only
 * the tenant scope applies, as before.
 */
final class RateScope
{
    /** @return Builder<RoomType> */
    public function roomTypes(): Builder
    {
        $ids = app(PropertyContext::class)->readIds();

        return RoomType::query()->when($ids !== [], fn (Builder $q) => $q->whereIn('property_id', $ids));
    }

    public function roomType(string $id): RoomType
    {
        return $this->roomTypes()->findOrFail($id);
    }

    public function ratePlan(string $id): RatePlan
    {
        $ids = app(PropertyContext::class)->readIds();

        return RatePlan::query()->when($ids !== [], fn (Builder $q) => $q->whereIn('property_id', $ids))->findOrFail($id);
    }

    public function season(string $id): RateSeason
    {
        $ids = app(PropertyContext::class)->readIds();

        return RateSeason::query()
            ->when($ids !== [], fn (Builder $q) => $q->whereHas('ratePlan', fn (Builder $p) => $p->whereIn('property_id', $ids)))
            ->findOrFail($id);
    }
}
