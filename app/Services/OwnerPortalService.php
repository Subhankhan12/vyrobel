<?php

namespace App\Services;

use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Support\Billing\BillableUnits;
use App\Support\Owner\OwnerBookingsReader;
use Illuminate\Support\Collection;

/**
 * Read-only assembly for the owner portal (Phase 3 · Module 1 Step 6). Every query runs through the
 * owner-scoped models, so an authenticated owner only ever sees their own rows — the isolation lives
 * in the {@see OwnerScope} global scope, not in ad-hoc where clauses here. Reuses
 * {@see AvailabilityService} for occupancy rather than re-deriving it.
 */
class OwnerPortalService
{
    public function __construct(
        private AvailabilityService $availability,
    ) {}

    /**
     * ⚠️⚠️ THE ATTRIBUTION SET — every unit ever attributed to this owner, INCLUDING units whose property has
     * since been ARCHIVED. Owner-scoped. **This is not a list of inventory. Do not filter it.**
     *
     * ⚠️ WHY IT MUST KEEP THE ARCHIVED ONES. `OwnerBookingsReader` derives the owner's reservations AND their
     * `owner_ledger` rows from these ids ({@see OwnerBookingsReader::ledgerRows()}), and
     * so do Reports, Insights and the occupancy trend. MEASURED: adding a live-property filter here removed
     * €300.00 of real, earned owner revenue and a real completed stay from the owner's own Bookings page. The
     * property was archived; the money was not. Money that was earned does not stop having been earned.
     * `ArchivedPropertyGhostUnitsTest` pins exactly that figure, and a mutation that filters this method turns
     * it red on purpose.
     *
     * ⚠️ CHOOSING BETWEEN THE TWO ACCESSORS. Ask what the caller is answering:
     *   • "what happened / what is owed?" → **units()** — history, money, reports, statements, reviews.
     *   • "what can be managed or sold today?" → {@see unitsAtLiveProperties()} — listings, editors, writes.
     * Pick units() for a management surface and the owner sees a unit at a property that no longer exists.
     * Pick unitsAtLiveProperties() for a history surface and you delete their earnings. Neither is the safer
     * default; they answer different questions.
     *
     * @return Collection<int, Unit>
     */
    public function units(): Collection
    {
        return Unit::query()->with('roomType')->orderBy('code')->get();
    }

    /**
     * ⚠️ THE LIVE-INVENTORY SET — the owner's units at a property that still EXISTS. Owner-scoped, and the set
     * a management or listing surface must use: My Units, Unit Detail, Welcome, the units CSV, the OAuth API,
     * and the two owner WRITE paths (blocks and rates).
     *
     * ⚠️ THE NAME IS DELIBERATE, AND IT IS NOT `activeUnits()`. `units.is_active` already means something else
     * entirely — the OWNER paused this unit, took it off sale (#69) — and conflating the two cost this project
     * two full passes of confusion. This method says nothing about `is_active`: an owner-paused unit at a live
     * property IS returned, because the owner must still be able to see and resume it. The only thing filtered
     * is a unit whose PROPERTY has been archived out from under it.
     *
     * ⚠️ `whereHas('property')` IS THE SOFT-DELETE TEST — the same expression, for the same reason, as
     * {@see BillableUnits::count()}. `Property` uses SoftDeletes, so the relation's own
     * global scope excludes an archived parent; it tests EXISTENCE only and therefore cannot touch
     * `condition_status` or `is_active`.
     *
     * @return Collection<int, Unit>
     */
    public function unitsAtLiveProperties(): Collection
    {
        return Unit::query()->whereHas('property')->with('roomType')->orderBy('code')->get();
    }

    /**
     * The authenticated owner's statements, most recent first (owner-scoped).
     *
     * @return Collection<int, OwnerStatement>
     */
    public function statements(): Collection
    {
        return OwnerStatement::query()->orderByDesc('period_end')->get();
    }

    /**
     * The authenticated owner's payouts, most recent first (owner-scoped).
     *
     * @return Collection<int, Payout>
     */
    public function payouts(): Collection
    {
        return Payout::query()->orderByDesc('created_at')->get();
    }

    /**
     * Per-night availability for a room type over a window (reuses AvailabilityService).
     *
     * @return array<string, int>
     */
    public function occupancy(string $roomTypeId, string $start, string $end): array
    {
        return $this->availability->availabilityRange($roomTypeId, $start, $end);
    }
}
