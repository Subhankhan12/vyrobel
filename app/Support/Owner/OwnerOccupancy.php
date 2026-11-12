<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\ReservationUnit;
use App\Support\Money\MoneyFormatter;
use App\Support\Payouts\RailLabel;
use Illuminate\Support\Carbon;

/**
 * The ONE owner-scoped occupancy derivation, shared by the owner My Units screen and the owner Unit
 * Detail screen (Wave 1 · Sub-gate 3). Extracted verbatim from the certified `OwnerMyUnits::occupancy()`
 * so the two surfaces cannot drift apart on the single number an owner is most likely to dispute — the
 * same discipline as {@see MoneyFormatter} and {@see RailLabel}.
 *
 * WHY THIS EXISTS — the room-type-wide trap
 * -----------------------------------------
 * `AvailabilityService::availabilityRange()` / `OwnerPortalService::occupancy()` are keyed by ROOM TYPE.
 * A room type is shared: a hotel room, an operator-owned unit and ANOTHER owner's unit can all sit under
 * the same `room_type_id`. Reading occupancy that way would show an owner a number driven by units they
 * do not own — a cross-owner leak dressed up as a metric.
 *
 * This derivation is keyed by **physical unit id** instead: it reads `reservation_units` for the caller's
 * OWN unit ids only (always sourced from the owner-scoped `OwnerPortalService::units()`), so a non-owner
 * unit in the same room type is excluded BY CONSTRUCTION — it carries a different `unit_id` and is never
 * in the id set. Pass an empty id set and you get a zeroed result: it fails closed, like {@see OwnerScope}.
 *
 * Presentation/derivation only — a pure read. It never mutates availability, never touches
 * `AssignmentService`, and is not a second inventory write path (invariant #1).
 *
 * Counting rules (unchanged from the certified original):
 *   • `status = 'active'` physical-unit lines only — an `overbooked` line carries no unit_id and never
 *     consumed inventory, so it is not a night sold.
 *   • Stays are half-open `[check_in, check_out)`; a stay straddling the month is CLIPPED to the window.
 *   • No double-count: the no-double-booking guarantee (invariant #2) means one physical unit's active
 *     lines never overlap, so a night can be counted at most once per unit.
 */
final class OwnerOccupancy
{
    /**
     * Occupancy for a calendar month across the given OWNER-OWNED unit ids.
     *
     * ⚠️ D-OWNERSINCE-WINDOWS (2026-11-12): a unit given to the owner from a day (`units.owner_since`) counts only from that day — its stays are the ones that
     * began on or after it ({@see ReservationUnit::scopeOwnerStays()}) and its available nights start there ({@see OwnerStayWindow}). `held[d]` is how many of
     * the units were the owner's on day `d` (every unit, every day, when none has an `owner_since` — then `available` is `unit_count × days`, as before).
     *
     * @param  list<string>  $unitIds  the owner's OWN unit ids (owner-scoped upstream); [] → zeroed
     * @return array{days: int, unit_count: int, heat: array<int, int>, held: array<int, int>, per_unit: array<string, int>, per_unit_available: array<string, int>, available: int, nights: int, pct: int}
     */
    public static function forMonth(array $unitIds, Carbon $month): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();   // exclusive upper bound
        $days = (int) $monthStart->daysInMonth;

        $heat = array_fill(1, $days, 0);
        $held = array_fill(1, $days, 0);
        $perUnit = array_fill_keys($unitIds, 0);
        $perUnitAvailable = array_fill_keys($unitIds, 0);

        if ($unitIds !== []) {
            $since = OwnerStayWindow::sinceByUnit($unitIds);
            $perUnitAvailable = OwnerStayWindow::nightsByUnit($unitIds, $monthStart, $monthEnd, $since);
            for ($d = 1; $d <= $days; $d++) {
                $held[$d] = OwnerStayWindow::unitsHeldOn($unitIds, $since, $monthStart->copy()->day($d)->toDateString());
            }

            $lines = ReservationUnit::query()
                ->ownerStays()                          // ← only the stays that are the owner's (began on or after owner_since)
                ->whereIn('unit_id', $unitIds)          // ← the owner boundary: own units ONLY
                ->where('status', 'active')
                ->whereDate('check_in', '<', $monthEnd->toDateString())
                ->whereDate('check_out', '>', $monthStart->toDateString())
                ->get(['unit_id', 'check_in', 'check_out']);

            foreach ($lines as $line) {
                $unitId = (string) $line->unit_id;
                $checkIn = Carbon::parse($line->check_in);
                $checkOut = Carbon::parse($line->check_out);
                $start = $checkIn->greaterThan($monthStart) ? $checkIn : $monthStart->copy();
                $end = $checkOut->lessThan($monthEnd) ? $checkOut : $monthEnd->copy();

                for ($d = $start->copy(); $d->lessThan($end); $d->addDay()) {
                    $heat[(int) $d->format('j')]++;
                    $perUnit[$unitId] = ($perUnit[$unitId] ?? 0) + 1;
                }
            }
        }

        $unitCount = count($unitIds);
        $nights = array_sum($perUnit);
        $available = array_sum($perUnitAvailable);

        return [
            'days' => $days,
            'unit_count' => $unitCount,
            'heat' => $heat,
            'held' => $held,
            'per_unit' => $perUnit,
            'per_unit_available' => $perUnitAvailable,
            'available' => $available,
            'nights' => $nights,
            'pct' => $available > 0 ? (int) round($nights / $available * 100) : 0,
        ];
    }
}
