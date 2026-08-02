<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Livewire\Operator\OperatorReports;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;

/**
 * GATE 10.2 — THE one assembler of the operator Reports payload. The screen ({@see OperatorReports})
 * AND the PDF export both build their figures here, so the printed report can never disagree with the page it
 * mirrors (the two-paths principle — the same rule that keeps the schedule PDF and grid in sync).
 *
 * 🪤 REAL only, mixed bases (each labelled by the surface): the P&L (revenue / costs / net) is the folio·arrival
 * basis (Σ real folio items for reservations arriving in the window); the KPIs (occupancy · ADR · RevPAR) are the
 * stay-night basis via {@see PerformanceMetrics}. Returns RAW integer minor units + raw metric values — NO display
 * formatting — so each surface formats with its own money helper. The only real cost captured today is
 * MAINTENANCE; the prototype's other expense lines have no data source and are returned as {@see UNTRACKED} for the
 * surface to render honestly as "not tracked yet", never fabricated (#39).
 */
final class OperatorReportData
{
    /** Expense categories the prototype shows but the product captures no data for (honestly deferred). */
    public const UNTRACKED = ['Channel commissions', 'Cleaning', 'Supplies', 'Linen'];

    /**
     * @param  list<string>  $readIds  property ids in the current context scope
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return array{
     *     reservationIds: list<string>, arrivals: int,
     *     room_minor: int, ancillary_minor: int, tax_minor: int, gross_minor: int,
     *     maintenance_minor: int, untracked: list<string>, net_minor: int,
     *     occupancy_pct: int, adr_minor: int, revpar_minor: int,
     *     occupancy_delta: ?int, adr_delta: ?int, revpar_delta: ?int,
     *     by_channel: list<array{source: string, bookings: int, revenue_minor: int, share: int}>,
     *     by_month: list<array{month: string, label: string, revenue_minor: int}>,
     *     top_units: list<array{code: string, name: string, nights: int, revenue_minor: int}>,
     *     empty: bool
     * }
     */
    public static function build(array $readIds, array $range, string $currency): array
    {
        $reservationIds = $readIds === []
            ? []
            : Reservation::query()
                ->whereIn('property_id', $readIds)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->whereDate('check_in', '>=', $range['start']->toDateString())
                ->whereDate('check_in', '<', $range['end']->toDateString())
                ->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $revenue = self::revenue($reservationIds, $currency);
        $maintenanceMinor = self::maintenanceCost($readIds, $range);
        $grossMinor = $revenue['room_minor'] + $revenue['ancillary_minor'];

        // Performance metrics (stay-night basis) + the same period last year, for HONEST deltas only where LY has data.
        $m = $readIds === [] ? null : PerformanceMetrics::forProperties($readIds, $range['start'], $range['end'], $currency);
        $ly = $readIds === [] ? null : PerformanceMetrics::forProperties($readIds, $range['start']->subYear(), $range['end']->subYear(), $currency);

        $occ = $m?->occupancyPct() ?? 0;
        $adr = $m?->adrMinor() ?? 0;
        $revpar = $m?->revparMinor() ?? 0;

        $occDelta = $adrDelta = $revparDelta = null;
        if ($ly !== null && ! $ly->isEmpty()) {
            // Occupancy + RevPAR are per AVAILABLE room-night, so a prior-year 0 (inventory present, nothing sold) is
            // a GENUINE baseline. ADR is per SOLD room-night — undefined when nothing sold (adrMinor() returns 0) —
            // so only present an ADR delta when BOTH periods actually sold nights, else a 0 baseline masquerades as
            // real growth/decline (#39).
            $occDelta = $occ - $ly->occupancyPct();
            $revparDelta = $revpar - $ly->revparMinor();
            if ($m !== null && $m->roomNightsSold > 0 && $ly->roomNightsSold > 0) {
                $adrDelta = $adr - $ly->adrMinor();
            }
        }

        return [
            'reservationIds' => $reservationIds,
            'arrivals' => count($reservationIds),
            'room_minor' => $revenue['room_minor'],
            'ancillary_minor' => $revenue['ancillary_minor'],
            'tax_minor' => $revenue['tax_minor'],
            'gross_minor' => $grossMinor,
            'maintenance_minor' => $maintenanceMinor,
            'untracked' => self::UNTRACKED,
            'net_minor' => $grossMinor - $maintenanceMinor,
            'occupancy_pct' => $occ,
            'adr_minor' => $adr,
            'revpar_minor' => $revpar,
            'occupancy_delta' => $occDelta,
            'adr_delta' => $adrDelta,
            'revpar_delta' => $revparDelta,
            'by_channel' => OperatorRevenueBreakdown::byChannel($reservationIds, $currency),
            'by_month' => OperatorRevenueBreakdown::byMonth($reservationIds, $currency, $range['start'], $range['end']),
            'top_units' => self::topUnits($reservationIds, $currency),
            'empty' => $reservationIds === [] && $maintenanceMinor === 0,
        ];
    }

    /**
     * Revenue split from REAL folio items (arrival-attributed) — reconciles to the folios.
     *
     * @param  list<string>  $reservationIds
     * @return array{room_minor: int, ancillary_minor: int, tax_minor: int}
     */
    private static function revenue(array $reservationIds, string $currency): array
    {
        if ($reservationIds === []) {
            return ['room_minor' => 0, 'ancillary_minor' => 0, 'tax_minor' => 0];
        }

        $folioIds = Folio::query()->whereIn('reservation_id', $reservationIds)->pluck('id');

        $rows = FolioItem::query()
            ->whereIn('folio_id', $folioIds)
            ->where('currency', $currency)   // never sum across currencies (#17)
            ->selectRaw('type, SUM(amount_minor) AS total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $room = (int) ($rows['room'] ?? 0);
        $tax = (int) ($rows['tax'] ?? 0);
        $ancillary = (int) ($rows['add_on'] ?? 0) + (int) ($rows['product'] ?? 0) + (int) ($rows['fee'] ?? 0)
            + (int) ($rows['manual'] ?? 0) + (int) ($rows['discount'] ?? 0);

        return ['room_minor' => $room, 'ancillary_minor' => $ancillary, 'tax_minor' => $tax];
    }

    /**
     * REAL maintenance spend: resolved tickets on the scope's units, resolved inside the range.
     *
     * @param  list<string>  $propertyIds
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    private static function maintenanceCost(array $propertyIds, array $range): int
    {
        if ($propertyIds === []) {
            return 0;
        }

        $unitIds = Unit::query()->whereIn('property_id', $propertyIds)->pluck('id');
        if ($unitIds->isEmpty()) {
            return 0;
        }

        return (int) MaintenanceTicket::query()
            ->where('status', 'resolved')
            ->whereIn('unit_id', $unitIds)
            ->whereNotNull('resolved_at')
            ->whereDate('resolved_at', '>=', $range['start']->toDateString())
            ->whereDate('resolved_at', '<', $range['end']->toDateString())
            ->sum('cost_minor');
    }

    /**
     * Top units by REAL room revenue among the arriving reservations.
     *
     * @param  list<string>  $reservationIds
     * @return list<array{code: string, name: string, nights: int, revenue_minor: int}>
     */
    private static function topUnits(array $reservationIds, string $currency): array
    {
        if ($reservationIds === []) {
            return [];
        }

        return ReservationUnit::query()
            ->join('units', 'units.id', '=', 'reservation_units.unit_id')
            ->whereIn('reservation_units.reservation_id', $reservationIds)
            ->where('reservation_units.status', 'active')
            ->where('reservation_units.currency', $currency)
            ->selectRaw('units.code AS code, units.name AS name, SUM(reservation_units.price_minor) AS revenue, SUM(DATEDIFF(reservation_units.check_out, reservation_units.check_in)) AS nights')
            ->groupBy('units.id', 'units.code', 'units.name')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get()
            ->map(fn ($row): array => [
                'code' => (string) $row->getAttribute('code'),
                'name' => (string) ($row->getAttribute('name') ?? $row->getAttribute('code')),
                'nights' => (int) $row->getAttribute('nights'),
                'revenue_minor' => (int) $row->getAttribute('revenue'),
            ])
            ->all();
    }
}
