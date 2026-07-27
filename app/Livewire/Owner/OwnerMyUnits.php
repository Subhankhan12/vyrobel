<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerLedgerEntry;
use App\Models\ReservationUnit;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerAttention;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Owner\OwnerOccupancy;
use App\Support\Owner\OwnerUnitAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner My Units (design pass · Screen 4 — completes the owner portal) — a NEW owner-scoped page for
 * the owner's units + an occupancy calendar. Styled from the catalogued "Owner My Units" design, wired
 * to LIVE data via the read-only {@see OwnerPortalService} (called, never modified). Presentation +
 * thin page only: NO change to Unit/availability logic, schema, or guarantees. Owner isolation
 * ({@see OwnerScope}) makes the unit list the owner's own; a non-linked user fails
 * closed. No route-model-bound props; month navigation is via plain GET links (`?month=`) so every
 * request re-runs the owner middleware (a Livewire wire-action would bypass it → isolation leak).
 *
 * OCCUPANCY — built OWNER-SCOPED, not room-type-wide. {@see occupancy()} reads `reservation_units` for
 * the owner's OWN unit ids only (from the owner-scoped units()), active physical-unit lines — so a
 * non-owner unit in the SAME room type (a different unit_id, e.g. a hotel room or another owner's unit)
 * is excluded. This is the fix for the room-type-wide `availabilityRange` trap flagged on the dashboard:
 * the number shown is genuinely THIS owner's occupancy.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — unit list + count, per-unit name/type/status, per-unit + portfolio occupancy, nights
 *                sold, and the occupancy calendar heatmap (all owner-scoped).
 *   • DEFERRED — per-unit revenue + nightly rate + best-performer: derivable but not built this pass →
 *                stubbed "not yet available".
 *   • NO BACKEND — "Add unit", edit-pricing / message-guest, block-dates, export, the "Needs attention"
 *                approvals: disabled "Soon".
 *   • Guest PII — the calendar shows booking COUNTS only; no guest names anywhere.
 */
#[Layout('components.layouts.owner')]
class OwnerMyUnits extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $portal = app(OwnerPortalService::class);
        $units = $portal->units();   // owner-scoped, fail-closed when empty
        $currency = $this->currency($units);

        $month = $this->resolveMonth($units);
        $occupancy = $this->occupancy($units, $month);

        // Batch B — REAL per-unit month metrics (gross revenue · ADR · occupancy) + the best-performer
        // ranking, both through the SHARED OwnerUnitAnalytics so this screen and Unit Detail cannot drift.
        $unitIds = array_map('strval', $units->pluck('id')->all());
        $perUnit = OwnerUnitAnalytics::perUnitMonth($unitIds, $month, $currency);
        $bestUnitId = OwnerUnitAnalytics::bestUnitId($perUnit);

        return view('livewire.owner.units', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'units' => $this->unitCards($units, $occupancy, $perUnit, $currency, $bestUnitId),
            'occupancy' => $occupancy,
            'earned' => $this->earnedThisMonth($month, $currency),
            'best' => $this->bestPerformer($units, $perUnit, $bestUnitId, $month, $currency),
            'attention' => $this->attentionItems(),
            'month' => [
                'label' => $month->format('F Y'),
                'first_weekday' => $month->copy()->startOfMonth()->dayOfWeekIso,   // 1=Mon … 7=Sun
                'prev' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
                'next' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            ],
        ]);
    }

    /** The display currency — the units' property currency (matches OwnerInsights); EUR fallback. */
    private function currency(Collection $units): string
    {
        return (string) ($units->first()?->property->currency ?? 'EUR');
    }

    /**
     * The calendar month: `?month=YYYY-MM` if valid, else the month of the owner's most recent active
     * booking, else the current month.
     *
     * @param  Collection<int, Unit>  $units
     */
    private function resolveMonth(Collection $units): Carbon
    {
        $param = request()->query('month');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}$/', $param) === 1) {
            return Carbon::parse($param.'-01')->startOfMonth();
        }

        $unitIds = $units->pluck('id')->all();
        $latest = $unitIds === [] ? null : ReservationUnit::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->max('check_in');

        return $latest !== null ? Carbon::parse($latest)->startOfMonth() : Carbon::now()->startOfMonth();
    }

    /**
     * OWNER-SCOPED occupancy for the month — delegated to the SHARED {@see OwnerOccupancy} derivation so
     * this screen and the Unit Detail screen can never disagree about the owner's occupancy. The rule it
     * enforces is unchanged: active physical-unit bookings on the owner's OWN units only, so a non-owner
     * unit in the same room type is excluded by construction (it carries a different unit_id).
     *
     * @param  Collection<int, Unit>  $units
     * @return array{days: int, unit_count: int, heat: array<int, int>, per_unit: array<string, int>, nights: int, pct: int}
     */
    private function occupancy(Collection $units, Carbon $month): array
    {
        return OwnerOccupancy::forMonth(array_map('strval', $units->pluck('id')->all()), $month);
    }

    /**
     * @param  Collection<int, Unit>  $units
     * @param  array{days: int, per_unit: array<string, int>}  $occupancy
     * @param  array<string, array{gross_minor: int, adr_minor: int, occupancy_pct: int, nights: int}>  $perUnit
     * @return list<array<string, mixed>>
     */
    private function unitCards(Collection $units, array $occupancy, array $perUnit, string $currency, ?string $bestUnitId): array
    {
        $days = $occupancy['days'];

        return $units->map(function (Unit $u) use ($occupancy, $days, $perUnit, $currency, $bestUnitId): array {
            $id = (string) $u->id;
            $nights = $occupancy['per_unit'][$id] ?? 0;
            $m = $perUnit[$id] ?? ['gross_minor' => 0, 'adr_minor' => 0, 'nights' => 0];

            return [
                'id' => $u->id,
                'name' => (string) ($u->name ?? $u->code),
                'code' => (string) $u->code,
                'type' => (string) $u->roomType->name,   // units always belong to a room type
                'active' => $u->is_active !== false,
                'nights' => $nights,
                'occupancy_pct' => $days > 0 ? (int) round($nights / $days * 100) : 0,
                // Batch B — REAL per-unit revenue (GROSS room revenue, for comparability) + nightly rate
                // (the achieved ADR). No nights sold → no ADR figure ("—"), never a fake €0.
                'revenue' => $this->money($m['gross_minor'], $currency),
                'nightly_rate' => $m['nights'] > 0 ? $this->money($m['adr_minor'], $currency) : null,
                'is_best' => $bestUnitId !== null && $id === $bestUnitId,
                // Cover derives from the room-type (the sellable entity); null → gradient fallback.
                'cover' => $u->roomType->coverPhoto()?->url,
            ];
        })->values()->all();
    }

    /**
     * "Earned this month" — the owner's REAL net over the month (Σ signedMinor of the owner-scoped ledger
     * rows dated in the month), in the display currency. A month with no rows shows a clean zero, never a
     * fabricated figure. Owner-scoped by OwnerLedgerEntry's BelongsToOwner (#28).
     *
     * @return array{display: string, minor: int}
     */
    private function earnedThisMonth(Carbon $month, string $currency): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        $entries = OwnerLedgerEntry::query()
            ->whereDate('service_date', '>=', $start->toDateString())
            ->whereDate('service_date', '<', $end->toDateString())
            ->get();

        $net = OwnerLedgerAttribution::netPerCurrency($entries)[$currency] ?? 0;

        return ['display' => $this->money($net, $currency), 'minor' => $net];
    }

    /**
     * The best-performing unit this month, from the SHARED ranking (real gross revenue). null → an honest
     * "no best performer yet" when nothing has earned (#39 — never a fabricated winner).
     *
     * @param  Collection<int, Unit>  $units
     * @param  array<string, array{gross_minor: int, occupancy_pct: int, nights: int}>  $perUnit
     * @return array<string, mixed>|null
     */
    private function bestPerformer(Collection $units, array $perUnit, ?string $bestUnitId, Carbon $month, string $currency): ?array
    {
        if ($bestUnitId === null) {
            return null;
        }

        $unit = $units->firstWhere('id', $bestUnitId);
        if (! $unit instanceof Unit) {
            return null;
        }

        $m = $perUnit[$bestUnitId];

        return [
            'name' => (string) ($unit->name ?? $unit->code),
            'occupancy_pct' => $m['occupancy_pct'],
            'nights' => $m['nights'],
            'revenue' => $this->money($m['gross_minor'], $currency),
            'count' => $units->count(),
            'month' => $month->format('F Y'),
        ];
    }

    /**
     * "Needs your attention" — REAL items only, via the ONE shared {@see OwnerAttention} feed (extracted so
     * this screen and the owner Overview cannot drift on what needs the owner). Owner-resolved via
     * OwnerContext (#28); no real item → the view shows an honest "caught up".
     *
     * @return list<array{tone: string, title: string, body: string, cta: array{label: string, route: string}}>
     */
    private function attentionItems(): array
    {
        return OwnerAttention::items(app(OwnerContext::class));
    }
}
