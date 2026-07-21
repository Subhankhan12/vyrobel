<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerLedgerEntry;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Owner\OwnerLedgerAttribution;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner REPORTS (Phase 6b · SG3) — per-unit performance for the owner's OWN units (nights + occupancy over
 * a range) plus their real NET EARNINGS, OWNER-SCOPED via {@see OwnerPortalService::units()} and the
 * owner-scoped {@see OwnerLedgerEntry} (never a request id; an owner sees only their own).
 *
 * EARNINGS reconcile to the owner ledger BY CONSTRUCTION: net = {@see OwnerLedgerAttribution::netPerCurrency}
 * over the owner's ledger entries — the exact same figure the owner statements show (statements are a
 * period-locked projection of that ledger). Occupancy/nights are the SG1 metric layer over the owner's
 * units. Links to the real Phase-2 Statements + Tax Center. No fabricated figures; an owner with no data
 * sees honest zeros / "not yet attributed".
 */
#[Layout('components.layouts.owner')]
class OwnerReports extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed
        $currency = $this->currency($units);
        $range = $this->resolveRange();

        // Owner-scoped ledger entries (OwnerScope active on the portal) — the single source of "earnings".
        $entries = OwnerLedgerEntry::query()->get();
        $netByUnit = $this->netByUnit($entries);
        $totalNet = OwnerLedgerAttribution::netDisplays($entries);   // per-currency, reconciles to statements

        $rows = $units->map(function (Unit $unit) use ($range, $currency, $netByUnit): array {
            $m = PerformanceMetrics::forOwnerUnits([(string) $unit->id], $range['start'], $range['end'], $currency);
            $net = $netByUnit[(string) $unit->id] ?? [];

            return [
                'code' => (string) $unit->code,
                'name' => $unit->name !== null ? (string) $unit->name : (string) $unit->code,
                'nights' => $m->roomNightsSold,
                'occupancy_pct' => $m->occupancyPct(),
                'earnings' => $net === [] ? '—' : implode(' · ', array_map(fn (int $minor, string $c): string => $this->money($minor, $c), array_values($net), array_keys($net))),
            ];
        })->all();

        return view('livewire.owner.reports', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'ranges' => $this->rangeOptions($range['key']),
            'activeRange' => $range,
            'rows' => $rows,
            'totalNet' => $totalNet,   // list<string>, per currency; [] → not yet attributed
        ]);
    }

    /**
     * Net per currency PER UNIT — the owner's ledger grouped by unit, each group summed through the ONE
     * shared attribution rule so a per-unit figure and the total can never disagree.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array<string, array<string, int>> unitId → (currency → net minor)
     */
    private function netByUnit(Collection $entries): array
    {
        $out = [];

        foreach ($entries->groupBy('unit_id') as $unitId => $group) {
            if ((string) $unitId === '') {
                continue;   // org-level entries with no unit belong to no per-unit row
            }
            $out[(string) $unitId] = OwnerLedgerAttribution::netPerCurrency($group);
        }

        return $out;
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

    /**
     * @param  Collection<int, Unit>  $units
     */
    private function currency(Collection $units): string
    {
        $first = $units->first();

        return (string) ($first?->property->currency ?? 'EUR');
    }
}
