<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * THE ONE per-unit analytics derivation (Batch B · GATE 2) — the shared rule the owner My Units cards and
 * the owner Unit Detail screen both read, so a unit's headline figures (revenue, nightly rate, occupancy,
 * "best performer") can never disagree between the two surfaces. Same anti-drift discipline as
 * {@see OwnerOccupancy} / {@see OwnerLedgerAttribution}.
 *
 * Every figure is a REAL aggregate through the shared {@see PerformanceMetrics} (owner-unit scope), never a
 * fabricated trend or a hand-picked winner (#39). The CALLER supplies the owner's OWN unit ids (owner-scoped
 * upstream, never a request id), so owner isolation (#28) lives in that query, not here. "Best performer" is
 * simply the real ranking of those figures — with NO winner claimed when there is no revenue at all.
 */
final class OwnerUnitAnalytics
{
    /**
     * Per-unit metrics for one calendar month, keyed by unit id: GROSS room revenue (the comparability
     * figure the cards show), ADR (= the achieved nightly rate), occupancy % and nights sold — each a real
     * aggregate for that single unit over [month, month+1).
     *
     * @param  list<string>  $unitIds  the owner's OWN unit ids
     * @return array<string, array{gross_minor: int, adr_minor: int, occupancy_pct: int, nights: int}>
     */
    public static function perUnitMonth(array $unitIds, Carbon $month, string $currency): array
    {
        $start = CarbonImmutable::parse($month->copy()->startOfMonth()->toDateString());
        $end = $start->addMonth();

        $out = [];
        foreach ($unitIds as $id) {
            $m = PerformanceMetrics::forOwnerUnits([$id], $start, $end, $currency);
            $out[$id] = [
                'gross_minor' => $m->roomRevenueMinor,
                'adr_minor' => $m->adrMinor(),
                'occupancy_pct' => $m->occupancyPct(),
                'nights' => $m->roomNightsSold,
            ];
        }

        return $out;
    }

    /**
     * The id of the best-performing unit this month by GROSS room revenue (ties broken by nights, then a
     * stable id order). Returns null when NO unit earned anything — an honest "no best performer yet",
     * never a fabricated winner (#39).
     *
     * @param  array<string, array{gross_minor: int, nights: int}>  $perUnit  a {@see perUnitMonth()} result
     */
    public static function bestUnitId(array $perUnit): ?string
    {
        $bestId = null;
        $bestRev = -1;
        $bestNights = -1;

        foreach ($perUnit as $id => $m) {
            $rev = $m['gross_minor'];
            $nights = $m['nights'];
            if ($rev > $bestRev || ($rev === $bestRev && $nights > $bestNights) || ($rev === $bestRev && $nights === $bestNights && ($bestId === null || $id < $bestId))) {
                $bestId = (string) $id;
                $bestRev = $rev;
                $bestNights = $nights;
            }
        }

        return $bestRev > 0 ? $bestId : null;   // no revenue anywhere → no winner claimed
    }
}
