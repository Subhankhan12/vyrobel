<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Maintenance COST analytics (Phase 1 · Sub-gate 4) — every figure DERIVED from real resolved maintenance
 * tickets (`maintenance_tickets.cost_minor`, integer minor units). TENANT-scoped by construction
 * (MaintenanceTicket is BelongsToOrganization → the global scope restricts every query to the acting org).
 *
 * ⚠️ HONESTY: the prototype draws a "Labor vs Parts" split and per-order Labor/Parts columns — but the schema
 * holds ONE `cost_minor` per ticket, not a labour/parts breakdown. That split is FABRICATED and is NOT rendered
 * (the Wave-1 rule: reconcile to real data or omit). What IS real: total spend, orders closed, average, spend by
 * category, top units, spend by month, and the owner-unit vs operator-unit split (from `units.owner_id` — the
 * same fact that decides owner-rechargeability via the certified maintenance→owner-expense hook). An empty
 * period is `null`/`—`, never a fabricated €0.00.
 */
class MaintenanceCostService
{
    private const MONTHS = 6;

    private const TOP_UNITS = 8;

    /**
     * @return array{currency: string, total_minor: int, orders_closed: int, avg_minor: ?int, owner_units_minor: int, operator_units_minor: int, by_category: list<array{category: string, total_minor: int, count: int}>, top_units: list<array{code: string, name: string, total_minor: int, count: int}>, by_month: list<array{month: string, total_minor: int, count: int}>}
     */
    public function report(): array
    {
        $ordersClosed = $this->resolved()->count();
        $totalMinor = (int) $this->resolved()->sum('cost_minor');

        return [
            'currency' => (string) ($this->resolved()->whereNotNull('currency')->value('currency') ?? 'EUR'),
            'total_minor' => $totalMinor,
            'orders_closed' => $ordersClosed,
            'avg_minor' => $ordersClosed > 0 ? intdiv($totalMinor, $ordersClosed) : null,
            'owner_units_minor' => $this->spendOnUnits(true),
            'operator_units_minor' => $this->spendOnUnits(false),
            'by_category' => $this->byCategory(),
            'top_units' => $this->topUnits(),
            'by_month' => $this->byMonth(),
        ];
    }

    /** The base set: resolved tickets in the acting org. */
    private function resolved(): Builder
    {
        return MaintenanceTicket::query()->where('status', 'resolved');
    }

    /** @return list<array{category: string, total_minor: int, count: int}> */
    private function byCategory(): array
    {
        return $this->resolved()
            ->selectRaw('category, SUM(cost_minor) AS total, COUNT(*) AS cnt')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Model $row): array => [
                'category' => (string) $row->getAttribute('category'),
                'total_minor' => (int) $row->getAttribute('total'),
                'count' => (int) $row->getAttribute('cnt'),
            ])
            ->all();
    }

    /** @return list<array{code: string, name: string, total_minor: int, count: int}> */
    private function topUnits(): array
    {
        return $this->resolved()
            ->join('units', 'units.id', '=', 'maintenance_tickets.unit_id')
            ->selectRaw('units.code AS code, units.name AS name, SUM(maintenance_tickets.cost_minor) AS total, COUNT(*) AS cnt')
            ->groupBy('units.id', 'units.code', 'units.name')
            ->orderByDesc('total')
            ->limit(self::TOP_UNITS)
            ->get()
            ->map(fn (Model $row): array => [
                'code' => (string) $row->getAttribute('code'),
                'name' => (string) ($row->getAttribute('name') ?? $row->getAttribute('code')),
                'total_minor' => (int) $row->getAttribute('total'),
                'count' => (int) $row->getAttribute('cnt'),
            ])
            ->all();
    }

    /** @return list<array{month: string, total_minor: int, count: int}> */
    private function byMonth(): array
    {
        return $this->resolved()
            ->whereNotNull('resolved_at')
            ->selectRaw("DATE_FORMAT(resolved_at, '%Y-%m') AS ym, SUM(cost_minor) AS total, COUNT(*) AS cnt")
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->limit(self::MONTHS)
            ->get()
            ->map(fn (Model $row): array => [
                'month' => (string) $row->getAttribute('ym'),
                'total_minor' => (int) $row->getAttribute('total'),
                'count' => (int) $row->getAttribute('cnt'),
            ])
            ->all();
    }

    /** Total spend on owner-owned units (`owner_id` set) vs operator units — the two reconcile to the total. */
    private function spendOnUnits(bool $owned): int
    {
        return (int) $this->resolved()
            ->join('units', 'units.id', '=', 'maintenance_tickets.unit_id')
            ->when($owned, fn (Builder $q) => $q->whereNotNull('units.owner_id'))
            ->when(! $owned, fn (Builder $q) => $q->whereNull('units.owner_id'))
            ->sum('maintenance_tickets.cost_minor');
    }
}
