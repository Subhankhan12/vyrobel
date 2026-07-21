<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Support\Folio\FolioTotals;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator REPORTS (Phase 6b · SG2) — a period report on the ARRIVAL basis (reservations arriving in the
 * range), scoped by the Phase-6a property context. EVERY figure is real and reconciles to the folios:
 *   • revenue breakdown — room / ancillary (add-ons·products·fees) / tax — is Σ real `folio_items`, so it
 *     reconciles to {@see FolioTotals} by construction.
 *   • the only REAL operator cost captured today is MAINTENANCE (resolved `maintenance_tickets.cost_minor`
 *     on the scope's units in the range). The prototype's other expense lines — channel commissions,
 *     cleaning, supplies, linen — have NO real data source, so they are HONESTLY OMITTED (listed as "not
 *     tracked yet"), never fabricated.
 *   • top performers = the scope's units ranked by REAL room revenue. No "best month on record" narrative.
 *
 * TENANT-scoped; `/app` behind #26; the route carries `can:reports.view` (NOT Front Desk, #30).
 */
#[Layout('components.layouts.operator')]
class OperatorReports extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    /** Expense categories the prototype shows for which the product captures NO real data (honestly deferred). */
    private const UNTRACKED_EXPENSES = ['Channel commissions', 'Cleaning', 'Supplies', 'Linen'];

    public function render(): View
    {
        $range = $this->resolveRange();
        $currency = $this->currency();
        $ids = $this->currentReadIds;

        // Active reservations ARRIVING in the range, within scope — the report's population.
        $reservationIds = $ids === []
            ? []
            : Reservation::query()
                ->whereIn('property_id', $ids)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->whereDate('check_in', '>=', $range['start']->toDateString())
                ->whereDate('check_in', '<', $range['end']->toDateString())
                ->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $revenue = $this->revenue($reservationIds, $currency);
        $maintenanceMinor = $this->maintenanceCost($ids, $range);
        $grossMinor = $revenue['room_minor'] + $revenue['ancillary_minor'];

        return view('livewire.operator.reports', [
            'viewer' => $this->viewer(),
            'scopeLabel' => $this->viewingAllProperties ? 'All properties' : $this->currentPropertyName(),
            'ranges' => $this->rangeOptions($range['key']),
            'activeRange' => $range,
            'arrivals' => count($reservationIds),
            'revenue' => [
                'room' => $this->money($revenue['room_minor'], $currency),
                'ancillary' => $this->money($revenue['ancillary_minor'], $currency),
                'tax' => $this->money($revenue['tax_minor'], $currency),
                'gross' => $this->money($grossMinor, $currency),
                'room_minor' => $revenue['room_minor'],
                'ancillary_minor' => $revenue['ancillary_minor'],
            ],
            'expenses' => [
                'maintenance' => $this->money($maintenanceMinor, $currency),
                'maintenance_minor' => $maintenanceMinor,
                'untracked' => self::UNTRACKED_EXPENSES,
            ],
            'net' => $this->money($grossMinor - $maintenanceMinor, $currency),
            'topUnits' => $this->topUnits($reservationIds, $currency),
            'empty' => $reservationIds === [] && $maintenanceMinor === 0,
        ]);
    }

    /**
     * Revenue split from REAL folio items (arrival-attributed) — reconciles to the folios.
     *
     * @param  list<string>  $reservationIds
     * @return array{room_minor: int, ancillary_minor: int, tax_minor: int}
     */
    private function revenue(array $reservationIds, string $currency): array
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
    private function maintenanceCost(array $propertyIds, array $range): int
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
     * @return list<array{code: string, name: string, revenue: string, nights: int}>
     */
    private function topUnits(array $reservationIds, string $currency): array
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
                'revenue' => $this->money((int) $row->getAttribute('revenue'), $currency),
                'nights' => (int) $row->getAttribute('nights'),
            ])
            ->all();
    }

    /**
     * @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}
     */
    private function resolveRange(): array
    {
        $today = CarbonImmutable::today();
        $key = (string) request()->query('range', 'this-year');

        return match ($key) {
            'ytd' => ['key' => 'ytd', 'label' => 'Year to date', 'start' => $today->startOfYear(), 'end' => $today->addDay()],
            '6m' => ['key' => '6m', 'label' => 'Last 6 months', 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay()],
            default => ['key' => 'this-year', 'label' => $today->format('Y'), 'start' => $today->startOfYear(), 'end' => $today->startOfYear()->addYear()],
        };
    }

    /**
     * @return list<array{key: string, label: string, active: bool}>
     */
    private function rangeOptions(string $active): array
    {
        $year = CarbonImmutable::today()->format('Y');

        return [
            ['key' => 'this-year', 'label' => $year, 'active' => $active === 'this-year'],
            ['key' => 'ytd', 'label' => 'Year to date', 'active' => $active === 'ytd'],
            ['key' => '6m', 'label' => 'Last 6 months', 'active' => $active === '6m'],
        ];
    }

    private function currency(): string
    {
        $id = $this->currentReadIds[0] ?? null;
        $property = $id !== null ? Property::query()->whereKey($id)->first() : null;

        return (string) ($property->currency ?? 'EUR');
    }

    private function currentPropertyName(): string
    {
        $property = $this->currentProperty();

        return $property !== null ? (string) $property->name : 'No property';
    }
}
