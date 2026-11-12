<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\ReservationUnit;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * THE NIGHTS A UNIT'S OWNER ACTUALLY HAS (D-OWNERSINCE-WINDOWS, 2026-11-12) — the half of the owner-since rule that is about TIME, beside
 * {@see ReservationUnit::scopeOwnerStays()} which is about STAYS.
 *
 * A unit given to an owner from a day (`units.owner_since`, DEC-p) is the owner's from that day. Earnings already follow it
 * ({@see Unit::ownerIdForStayStarting()}); the owner-facing PERFORMANCE figures did not — an owner given a unit in the middle of the year saw
 * occupancy, rate and revenue windows for nights before their day, which could not reconcile with their own ledger. Two things must be cut:
 *   • the STAYS counted (the scope: a stay that started before the day is not theirs), and
 *   • the NIGHTS AVAILABLE — the denominator of every occupancy figure: a unit the owner did not yet have offered them no nights, so 30 nights of a
 *     month in which the owner took the unit over on the 16th are 15 available nights, not 30. This class is the one place that counts them.
 *
 * ⚠️ A unit with no `owner_since` (every unit before DEC-p; every unit an invite or an approved request gives) has the whole period — the figures are
 *    exactly what they were. ⚠️ A read of `units` by id only (no model scope): the ids are the caller's already-owner-scoped set, the same trust the
 *    reservation-line scope has.
 */
final class OwnerStayWindow
{
    /**
     * The day each of these units became the owner's — only the units that have one.
     *
     * @param  list<string>  $unitIds
     * @return array<string, string> unit id => `Y-m-d`
     */
    public static function sinceByUnit(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        $out = [];
        foreach (DB::table((new Unit)->getTable())->whereIn('id', $unitIds)->whereNotNull('owner_since')->pluck('owner_since', 'id') as $id => $since) {
            $out[(string) $id] = substr((string) $since, 0, 10);
        }

        return $out;
    }

    /**
     * The nights of `[start, end)` the owner has on each unit — the whole span, or from the unit's `owner_since` when that is later.
     *
     * @param  list<string>  $unitIds
     * @param  array<string, string>|null  $since  {@see self::sinceByUnit()}, when the caller already holds it
     * @return array<string, int> unit id => nights
     */
    public static function nightsByUnit(array $unitIds, CarbonInterface $start, CarbonInterface $end, ?array $since = null): array
    {
        $since ??= self::sinceByUnit($unitIds);
        $to = CarbonImmutable::parse($end->toDateString());

        $out = [];
        foreach ($unitIds as $id) {
            $id = (string) $id;
            $startDay = $start->toDateString();
            $from = CarbonImmutable::parse(isset($since[$id]) && $since[$id] > $startDay ? $since[$id] : $startDay);
            $out[$id] = $from->lessThan($to) ? (int) $from->diffInDays($to) : 0;
        }

        return $out;
    }

    /**
     * The owner's available nights across these units over `[start, end)` — what `count($unitIds) × days` was, cut at each unit's day.
     *
     * @param  list<string>  $unitIds
     */
    public static function nights(array $unitIds, CarbonInterface $start, CarbonInterface $end): int
    {
        return array_sum(self::nightsByUnit($unitIds, $start, $end));
    }

    /**
     * How many of these units are the owner's on one date — the per-day denominator (a calendar shades a day by units booked out of units held).
     *
     * @param  list<string>  $unitIds
     * @param  array<string, string>  $since  {@see self::sinceByUnit()}
     */
    public static function unitsHeldOn(array $unitIds, array $since, string $date): int
    {
        $held = 0;
        foreach ($unitIds as $id) {
            if (! isset($since[(string) $id]) || $since[(string) $id] <= $date) {
                $held++;
            }
        }

        return $held;
    }
}
