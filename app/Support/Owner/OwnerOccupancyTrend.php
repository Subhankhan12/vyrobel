<?php

declare(strict_types=1);

namespace App\Support\Owner;

use Illuminate\Support\Carbon;

/**
 * THE ONE owner occupancy-TREND derivation — the current % + a month-over-month delta + an SVG sparkline over the
 * N months ENDING at (and including) an anchor month — shared by the owner Overview occupancy KPI and the owner
 * Statement page occupancy card, so the two cannot draw a different trend for the same units. Built on the shared
 * {@see OwnerOccupancy::forMonth()} (owner-unit-scoped by physical unit id — the room-type-wide trap is already
 * fixed there), so a non-owned unit in the same room type is excluded BY CONSTRUCTION and an empty id set fails
 * closed (zeroed). Pure derivation — never a fabricated %, an all-zero series shows the honest zero (#39).
 */
final class OwnerOccupancyTrend
{
    /**
     * Occupancy for the anchor month + a delta vs the prior month + a sparkline over the trailing `count` months.
     *
     * @param  list<string>  $unitIds  the owner's OWN unit ids (owner-scoped upstream); [] → zeroed
     * @return array{has_data: bool, pct: int, pct_display: string, delta: array{label: string, positive: bool}|null, spark: array{has: bool, line: string, area: string, last_x: float, last_y: float}, months: list<array{label: string, pct: int}>, per_unit_pct: array<string, int>}
     */
    public static function ending(array $unitIds, Carbon $anchorMonth, int $count = 6): array
    {
        $anchor = $anchorMonth->copy()->startOfMonth();

        /** @var list<Carbon> $months */
        $months = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $months[] = $anchor->copy()->subMonthsNoOverflow($i);
        }

        $results = array_map(fn (Carbon $m): array => OwnerOccupancy::forMonth($unitIds, $m), $months);
        $pcts = array_map(fn (array $r): int => $r['pct'], $results);
        $current = $results[count($results) - 1];

        $days = $current['days'];
        $perUnitPct = [];
        foreach ($current['per_unit'] as $id => $nights) {
            $perUnitPct[(string) $id] = $days > 0 ? (int) round($nights / $days * 100) : 0;
        }

        $pct = $current['pct'];
        $prevPct = $pcts[count($pcts) - 2] ?? 0;
        $diff = $pct - $prevPct;
        $delta = $diff !== 0
            ? ['label' => ($diff > 0 ? '+' : '−').abs($diff).'%', 'positive' => $diff > 0]
            : null;

        // Per-month bars (oldest → newest, label + %) for the Statement page's mini bar chart; the Overview
        // uses `spark` (the SVG line) — both are the SAME real monthly series, drawn two ways.
        $monthBars = [];
        foreach ($months as $i => $m) {
            $monthBars[] = ['label' => $m->translatedFormat('M'), 'pct' => $pcts[$i]];
        }

        return [
            'has_data' => array_sum($pcts) > 0,
            'pct' => $pct,
            'pct_display' => $pct.'%',
            'delta' => $delta,
            'spark' => self::sparkline($pcts),
            'months' => $monthBars,
            'per_unit_pct' => $perUnitPct,
        ];
    }

    /**
     * A monthly-occupancy trend as an SVG path over a 0 0 100 34 viewBox (the wireframe's sparkline). `has` is
     * false when every month is 0 → the card shows the % alone rather than a flat line at the floor.
     *
     * @param  list<int>  $pcts  oldest → newest
     * @return array{has: bool, line: string, area: string, last_x: float, last_y: float}
     */
    private static function sparkline(array $pcts): array
    {
        $n = count($pcts);
        $points = [];
        for ($i = 0; $i < $n; $i++) {
            $x = $n <= 1 ? 96.0 : round(2 + ($i / ($n - 1)) * 94, 1);
            $v = max(0, min(100, $pcts[$i]));
            $y = round(30 - $v / 100 * 26, 1);
            $points[] = [$x, $y];
        }

        $line = 'M'.implode(' L', array_map(fn (array $p): string => $p[0].','.$p[1], $points));
        $first = $points[0];
        $last = $points[$n - 1];

        return [
            'has' => $pcts !== [] && max($pcts) > 0,
            'line' => $line,
            'area' => $line.' L'.$last[0].',34 L'.$first[0].',34 Z',
            'last_x' => $last[0],
            'last_y' => $last[1],
        ];
    }
}
