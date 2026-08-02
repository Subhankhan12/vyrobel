<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Reports\OperatorReportData;
use App\Support\Reports\ReportPeriod;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator REPORTS (Phase 6b · SG2; GATE 10) — a period report scoped by the Phase-6a property context. EVERY
 * figure is real and reconciles: the figures are assembled ONCE by the shared {@see OperatorReportData} (so the
 * page and the PDF/CSV exports can never drift), and this component only maps that raw data to display.
 *
 *   • KPIs — occupancy · ADR · RevPAR (stay-night basis, {@see PerformanceMetrics}) with an
 *     HONEST vs-last-year delta shown only where the prior-year period has data (never a fabricated trend, #39).
 *   • Revenue P&L — room / ancillary / tax is Σ real `folio_items` (folio·arrival basis), reconciling to the folios.
 *   • The only REAL operator cost captured today is MAINTENANCE; the prototype's other expense lines have no data
 *     source and are HONESTLY listed as "not tracked yet", never fabricated.
 *   • Top performers + by-channel + by-month = real aggregates. No "best month on record" narrative.
 *
 * TENANT-scoped; `/app` behind #26; the route carries `can:reports.view` (NOT Front Desk, #30).
 */
#[Layout('components.layouts.operator')]
class OperatorReports extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    public function render(): View
    {
        $range = ReportPeriod::resolve(request()->query('range'), request()->query('month'));
        $currency = $this->currency();
        $data = OperatorReportData::build($this->currentReadIds, $range, $currency);

        $exportParams = str_starts_with($range['key'], 'month:')
            ? ['month' => substr($range['key'], 6)]
            : ['range' => $range['key']];

        return view('livewire.operator.reports', [
            'viewer' => $this->viewer(),
            'scopeLabel' => $this->viewingAllProperties ? 'All properties' : $this->currentPropertyName(),
            'ranges' => ReportPeriod::rangeOptions($range['key']),
            'months' => ReportPeriod::monthOptions($range['key']),
            'activeRange' => $range,
            'arrivals' => $data['arrivals'],
            // GATE 10.1 — the KPI lead: real occupancy · ADR · RevPAR + gross, with honest vs-LY deltas (#39).
            'kpis' => $this->kpiCards($data, $currency),
            'revenue' => [
                'room' => $this->money($data['room_minor'], $currency),
                'ancillary' => $this->money($data['ancillary_minor'], $currency),
                'tax' => $this->money($data['tax_minor'], $currency),
                'gross' => $this->money($data['gross_minor'], $currency),
            ],
            'expenses' => [
                'maintenance' => $this->money($data['maintenance_minor'], $currency),
                'untracked' => $data['untracked'],
            ],
            'net' => $this->money($data['net_minor'], $currency),
            'topUnits' => array_map(fn (array $u): array => [
                'code' => $u['code'],
                'name' => $u['name'],
                'nights' => $u['nights'],
                'revenue' => $this->money($u['revenue_minor'], $currency),
            ], $data['top_units']),
            'byChannel' => array_map(fn (array $c): array => [
                'label' => $this->sourceLabel($c['source']),
                'bookings' => $c['bookings'],
                'revenue' => $this->money($c['revenue_minor'], $currency),
                'share' => $c['share'],
            ], $data['by_channel']),
            'byMonth' => $this->monthBars($data['by_month'], $currency),
            // GATE 10.2 — both exports carry the SAME period (roll-up range or specific month).
            'exportUrl' => route('app.reports.export', $exportParams),
            'pdfUrl' => route('app.reports.export.pdf', $exportParams),
            'empty' => $data['empty'],
        ]);
    }

    /**
     * Map the shared raw metrics to the KPI card display (occupancy · ADR · RevPAR · gross), formatting the
     * honest vs-last-year deltas here (presentation) — the delta is null when the builder found no prior-year data.
     *
     * @param  array<string, mixed>  $d
     * @return list<array{key: string, label: string, value: string, sub: string, delta: ?string, delta_dir: ?string}>
     */
    private function kpiCards(array $d, string $currency): array
    {
        return [
            ['key' => 'occupancy', 'label' => 'Occupancy', 'value' => $d['occupancy_pct'].'%', 'sub' => 'room-nights sold',
                'delta' => $d['occupancy_delta'] !== null ? $this->signed($d['occupancy_delta']).'pp' : null, 'delta_dir' => $this->dir($d['occupancy_delta'])],
            ['key' => 'adr', 'label' => 'ADR', 'value' => $this->money($d['adr_minor'], $currency), 'sub' => 'average daily rate',
                'delta' => $d['adr_delta'] !== null ? $this->signedMoney($d['adr_delta'], $currency) : null, 'delta_dir' => $this->dir($d['adr_delta'])],
            ['key' => 'revpar', 'label' => 'RevPAR', 'value' => $this->money($d['revpar_minor'], $currency), 'sub' => 'per available room',
                'delta' => $d['revpar_delta'] !== null ? $this->signedMoney($d['revpar_delta'], $currency) : null, 'delta_dir' => $this->dir($d['revpar_delta'])],
            ['key' => 'gross', 'label' => 'Gross revenue', 'value' => $this->money($d['gross_minor'], $currency), 'sub' => 'room + ancillary · folio basis',
                'delta' => null, 'delta_dir' => null],
        ];
    }

    /**
     * The revenue-by-month bars — scaled to the tallest bar here for the chart component.
     *
     * @param  list<array{month: string, label: string, revenue_minor: int}>  $buckets
     * @return list<array{label: string, display: string, pct: int}>
     */
    private function monthBars(array $buckets, string $currency): array
    {
        $max = max(1, ...array_map(fn (array $b): int => $b['revenue_minor'], $buckets ?: [['revenue_minor' => 0]]));

        return array_map(fn (array $b): array => [
            'label' => $b['label'],
            'display' => $this->money($b['revenue_minor'], $currency),
            'pct' => (int) round($b['revenue_minor'] * 100 / $max),
        ], $buckets);
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
