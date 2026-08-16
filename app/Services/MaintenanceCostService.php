<?php

declare(strict_types=1);

namespace App\Services;

use App\Livewire\MaintenanceList;
use App\Models\MaintenanceTicket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Maintenance COST analytics (Phase 1 · Sub-gate 4) — every figure DERIVED from real resolved maintenance
 * tickets (`maintenance_tickets.cost_minor`, integer minor units). TENANT-scoped by construction
 * (MaintenanceTicket is BelongsToOrganization → the global scope restricts every query to the acting org).
 *
 * #89: the labor/parts split is now REAL (`labor_minor`/`parts_minor`, captured at resolve; labor + parts ===
 * cost_minor, #17), so the "Billable" line items (Labor|Parts|Total) + the labor-vs-parts split are genuine.
 * A ticket resolved before the split existed records as all-labour (COALESCE), so it still reconciles. The
 * billable totals equal what the owner is billed (the same `cost_minor` that posted to `owner_expense`). Also
 * real: total spend, orders closed, average, parts-on-order count, spend by category, top units, spend by
 * month, and the owner-unit vs operator-unit split (`units.owner_id`). An empty period is `null`/`—`, never a
 * fabricated €0.00.
 */
class MaintenanceCostService
{
    private const MONTHS = 6;

    private const TOP_UNITS = 8;

    /** The screen's line-item preview depth (a workbook asks for the complete set instead). */
    public const BILLABLE_PREVIEW = 60;

    /**
     * @return array{currency: string, total_minor: int, labor_minor: int, parts_minor: int, orders_closed: int, avg_minor: ?int, parts_on_order: int, owner_units_minor: int, operator_units_minor: int, by_category: list<array{category: string, total_minor: int, count: int}>, top_units: list<array{code: string, name: string, total_minor: int, count: int}>, by_month: list<array{month: string, total_minor: int, count: int}>, billable: list<array{id: string, date: string, date_iso: ?string, title: string, unit: string, category: string, labor_minor: int, parts_minor: int, total_minor: int}>}
     */
    /** @var list<string> the property read-set for this report — set by report(), read by every helper. */
    private array $propertyIds = [];

    private ?string $from = null;

    private ?string $to = null;

    private ?int $billableLimit = self::BILLABLE_PREVIEW;

    /**
     * @param  ?int  $billableLimit  how many line items to return — the default keeps the SCREEN's 60-row
     *                               preview; NULL returns the complete set. ⚠️ D12b-1: `total_minor` sums
     *                               EVERY resolved ticket in the window while `billable` was capped, so above
     *                               60 tickets the totals legitimately exceed the rows beneath them. A CSV can
     *                               live with a preview; a WORKBOOK is exported precisely to hold the whole
     *                               detail, and its summary tab must reconcile to its own line-item tab. Same
     *                               resolver, same scope, same period — only the detail depth differs.
     */
    public function report(array $propertyIds, ?string $from = null, ?string $to = null, ?int $billableLimit = self::BILLABLE_PREVIEW): array
    {
        $this->propertyIds = array_values(array_map('strval', $propertyIds));
        $this->from = $from;
        $this->to = $to;
        $this->billableLimit = $billableLimit;

        $ordersClosed = $this->resolved()->count();
        $totalMinor = (int) $this->resolved()->sum('cost_minor');

        // #89 — the REAL labor/parts split. A legacy ticket (resolved before the split existed) has null
        // labor/parts → COALESCE records it as all-labour, so labor + parts === total to the cent (#17).
        $laborMinor = (int) $this->resolved()->selectRaw('SUM(COALESCE(labor_minor, cost_minor, 0)) AS s')->value('s');
        $partsMinor = (int) $this->resolved()->selectRaw('SUM(COALESCE(parts_minor, 0)) AS s')->value('s');

        return [
            'currency' => (string) ($this->resolved()->whereNotNull('currency')->value('currency') ?? 'EUR'),
            'total_minor' => $totalMinor,
            'labor_minor' => $laborMinor,
            'parts_minor' => $partsMinor,
            'orders_closed' => $ordersClosed,
            'avg_minor' => $ordersClosed > 0 ? intdiv($totalMinor, $ordersClosed) : null,
            // Real count of active tickets waiting on a part (the "Parts on order" KPI). A part on order has no
            // cost yet (the ticket isn't resolved), so a count is the honest figure — never a fabricated €.
            // Property-scoped like everything else, but deliberately NOT period-filtered: a part on order is a
            // CURRENT state, not something that happened inside the selected window.
            'parts_on_order' => MaintenanceTicket::query()
                ->where('awaiting_parts', true)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereHas('unit', fn (Builder $q) => $q->whereIn('property_id', $this->propertyIds))
                ->count(),
            'owner_units_minor' => $this->spendOnUnits(true),
            'operator_units_minor' => $this->spendOnUnits(false),
            'by_category' => $this->byCategory(),
            'top_units' => $this->topUnits(),
            'by_month' => $this->byMonth(),
            'billable' => $this->billable(),
        ];
    }

    /**
     * The billable work-order line items (#89) — every costed resolved ticket with its REAL labor/parts/total.
     * The totals reconcile to the owner Expenses (the same `cost_minor` that posted to `owner_expense`).
     *
     * @return list<array{id: string, date: string, title: string, unit: string, category: string, labor_minor: int, parts_minor: int, total_minor: int}>
     */
    private function billable(): array
    {
        return $this->resolved()
            ->whereNotNull('cost_minor')
            ->join('units', 'units.id', '=', 'maintenance_tickets.unit_id')
            ->orderByDesc('maintenance_tickets.resolved_at')
            ->when($this->billableLimit !== null, fn (Builder $q) => $q->limit((int) $this->billableLimit))
            ->get([
                'maintenance_tickets.id AS id', 'maintenance_tickets.title AS title', 'maintenance_tickets.category AS category',
                'maintenance_tickets.cost_minor AS total', 'maintenance_tickets.labor_minor AS labor', 'maintenance_tickets.parts_minor AS parts',
                'maintenance_tickets.resolved_at AS resolved_at', 'units.code AS unit_code',
            ])
            ->map(function (Model $row): array {
                $total = (int) $row->getAttribute('total');
                $parts = $row->getAttribute('parts') !== null ? (int) $row->getAttribute('parts') : 0;
                $labor = $row->getAttribute('labor') !== null ? (int) $row->getAttribute('labor') : $total - $parts;

                return [
                    'id' => (string) $row->getAttribute('id'),
                    'date' => $row->getAttribute('resolved_at') !== null ? CarbonImmutable::parse($row->getAttribute('resolved_at'))->format('j M') : '—',
                    // ⚠️ The display string above cannot be a spreadsheet DATE cell ("5 Sep" sorts before
                    // "9 Aug"). The raw date travels beside it; nothing that renders `date` is affected.
                    'date_iso' => $row->getAttribute('resolved_at') !== null ? CarbonImmutable::parse($row->getAttribute('resolved_at'))->toDateString() : null,
                    'title' => (string) $row->getAttribute('title'),
                    'unit' => (string) $row->getAttribute('unit_code'),
                    'category' => (string) $row->getAttribute('category'),
                    'labor_minor' => $labor,
                    'parts_minor' => $parts,
                    'total_minor' => $total,
                ];
            })->all();
    }

    /**
     * The base set: resolved tickets the acting user MAY SEE, within the selected period.
     *
     * ⚠️ THIS WAS TENANT-SCOPED ONLY (auth/isolation pass, 2026-08-09). It carried no property constraint at
     * all, so the whole report — totals, unit NAMES, billable lines — was org-wide while the sibling board
     * ({@see MaintenanceList}) scoped through `currentReadIds`. A user restricted by the
     * `property_user` pivot ("the set the user MAY WORK IN", per {@see PropertyContext}) therefore saw the
     * costs of properties they may not work in.
     *
     * The scope MIRRORS the board exactly — `whereHas('unit', property_id ∈ readIds)` — rather than inventing
     * a second mechanism, so the two surfaces cannot diverge.
     *
     * ⚠️ An EMPTY read-set means "no properties in scope" and yields NOTHING. That is deliberate and is NOT
     * the documented empty-pivot case: PropertyContext resolves an empty `property_user` pivot to the FULL
     * org before this is ever called, so an unrestricted user still sees everything.
     */
    private function resolved(): Builder
    {
        $query = MaintenanceTicket::query()
            ->where('status', 'resolved')
            ->whereHas('unit', fn (Builder $q) => $q->whereIn('property_id', $this->propertyIds));

        if ($this->from !== null) {
            $query->whereDate('resolved_at', '>=', $this->from);
        }
        if ($this->to !== null) {
            $query->whereDate('resolved_at', '<=', $this->to);
        }

        return $query;
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
