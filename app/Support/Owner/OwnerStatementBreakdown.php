<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;

/**
 * THE ONE per-unit split of a finalized owner statement — shared by the owner Statement PAGE (net-oriented: each
 * unit's NET + % of net) and the owner Statement PDF ("Performance by unit": nights · occupancy · gross revenue).
 * Both read the SAME computation, so the screen and the document can never disagree (anti-drift).
 *
 * RECONCILES BY CONSTRUCTION (invariant #17):
 *   • Σ(per-unit net) + (unattributed net) == Σ signedMinor of the lines the statement CLAIMED == net_source_minor.
 *   • Σ(per-unit gross) == the statement's `gross_minor` bucket (room_revenue is always unit-attributed).
 * The split reads ONLY the exact ledger lines this statement stamped at finalize (`owner_ledger.statement_id`),
 * grouped by `unit_id` and netted through the shared {@see OwnerLedgerAttribution} primitive. Occupancy / nights
 * come from the shared {@see PerformanceMetrics::forOwnerUnits()} over the statement's own period (owner's OWN units
 * only, #28). A DRAFT has no stamped lines yet → an honest "available once finalized" state, never a fake split (#39).
 */
final class OwnerStatementBreakdown
{
    /**
     * @return array{
     *   available: bool,
     *   currency: string,
     *   currency_symbol: string,
     *   unit_count: int,
     *   period_label: string,
     *   units: list<array{unit_id: string, name: string, kind: string|null, nights: int, occupancy_pct: int, net_minor: int, net_display: string, gross_minor: int, gross_display: string, pct_of_net: int, bar_width: int}>,
     *   unattributed: array{net_minor: int, net_display: string, pct: int}|null,
     *   total_net_minor: int, total_net_display: string,
     *   total_gross_minor: int, total_gross_display: string,
     *   total_nights: int, total_occupancy_pct: int
     * }
     */
    public static function forStatement(OwnerStatement $s): array
    {
        $currency = (string) $s->source_currency;
        $symbol = MoneyFormatter::symbol($currency);

        // The exact lines this statement claims (stamped at finalize). Owner-scoped by the global scope.
        $entries = OwnerLedgerEntry::query()
            ->where('statement_id', $s->id)
            ->with('unit.roomType')
            ->get();

        if ($entries->isEmpty()) {
            return [
                'available' => false,
                'currency' => $currency,
                'currency_symbol' => $symbol,
                'unit_count' => 0,
                'period_label' => $s->period_start->translatedFormat('F Y'),
                'units' => [],
                'unattributed' => null,
                'total_net_minor' => 0,
                'total_net_display' => MoneyFormatter::display(0, $currency),
                'total_gross_minor' => 0,
                'total_gross_display' => MoneyFormatter::display(0, $currency),
                'total_nights' => 0,
                'total_occupancy_pct' => 0,
            ];
        }

        // Net in the ledger's SOURCE currency — the same per-currency basis the per-unit nets use (a statement is
        // single-currency by construction, so for the real case this equals Σ signedMinor / net_source_minor).
        $totalNet = OwnerLedgerAttribution::netPerCurrency($entries)[$currency] ?? 0;

        // Half-open [period_start, period_end + 1 day) so the last day of the period is included.
        $start = CarbonImmutable::parse($s->period_start->toDateString());
        $end = CarbonImmutable::parse($s->period_end->toDateString())->addDay();
        $periodStart = $s->period_start->toDateString();

        $units = [];
        $unattributed = null;
        $unitIds = [];

        foreach ($entries->groupBy(fn (OwnerLedgerEntry $e): string => (string) ($e->unit_id ?? '')) as $unitId => $rows) {
            $net = OwnerLedgerAttribution::netPerCurrency($rows)[$currency] ?? 0;
            $pct = $totalNet !== 0 ? (int) round($net / $totalNet * 100) : 0;

            if ((string) $unitId === '') {
                // Unit-less lines (e.g. a flat management fee): an honest remainder so the split still
                // reconciles to the whole. Absent whenever every line is unit-attributed (the demo case).
                $unattributed = [
                    'net_minor' => $net,
                    'net_display' => MoneyFormatter::display($net, $currency),
                    'pct' => $pct,
                ];

                continue;
            }

            $unitIds[] = (string) $unitId;

            // Per-unit gross = the unit's IN-PERIOD room_revenue credits; occupancy/nights from the shared metric
            // layer. A room_revenue line dated before period_start (or flagged prior_period_adjustment) is a
            // carry-forward the engine buckets into ADJUSTMENTS, not gross — exclude it here too, so Σ per-unit
            // gross == the statement's gross_minor by construction, even with a straggler.
            $gross = (int) $rows
                ->where('entry_type', 'room_revenue')
                ->reject(fn (OwnerLedgerEntry $e): bool => $e->prior_period_adjustment === true || $e->service_date->toDateString() < $periodStart)
                ->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
            $metrics = PerformanceMetrics::forOwnerUnits([(string) $unitId], $start, $end, $currency);
            // unit_id is set here (else-branch) and the FK is nullOnDelete, so the unit row exists.
            $unit = $rows->first()->unit;

            $units[] = [
                'unit_id' => (string) $unitId,
                'name' => (string) ($unit->name ?? $unit->code ?? __('php/support/owner/owner_statement_breakdown.unit_fallback')),
                'kind' => $unit->roomType?->name,
                'nights' => $metrics->roomNightsSold,
                'occupancy_pct' => $metrics->occupancyPct(),
                'net_minor' => $net,
                'net_display' => MoneyFormatter::display($net, $currency),
                'gross_minor' => $gross,
                'gross_display' => MoneyFormatter::display($gross, $currency),
                'pct_of_net' => $pct,
                'bar_width' => max(0, min(100, $pct)),
            ];
        }

        // Best-earning unit first — the wireframe's order.
        usort($units, fn (array $a, array $b): int => $b['net_minor'] <=> $a['net_minor']);

        // Portfolio totals for the PDF's "Total gross room revenue" row: gross = Σ per-unit gross (== gross_minor);
        // nights + occupancy from ONE metric over all the owner's units in this split (so the total row is consistent
        // with the per-unit rows and reconciles to the statement gross).
        $totalGross = (int) array_sum(array_map(fn (array $u): int => $u['gross_minor'], $units));
        // forOwnerUnits([]) returns an empty MetricSet (0 nights / 0% occ) — so no null branch is needed.
        $portfolio = PerformanceMetrics::forOwnerUnits($unitIds, $start, $end, $currency);

        return [
            'available' => true,
            'currency' => $currency,
            'currency_symbol' => $symbol,
            'unit_count' => count($units),
            'period_label' => $s->period_start->translatedFormat('F Y'),
            'units' => $units,
            'unattributed' => $unattributed,
            'total_net_minor' => $totalNet,
            'total_net_display' => MoneyFormatter::display($totalNet, $currency),
            'total_gross_minor' => $totalGross,
            'total_gross_display' => MoneyFormatter::display($totalGross, $currency),
            'total_nights' => $portfolio->roomNightsSold,
            'total_occupancy_pct' => $portfolio->occupancyPct(),
        ];
    }
}
