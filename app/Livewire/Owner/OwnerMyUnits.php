<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\ReservationUnit;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
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

        $month = $this->resolveMonth($units);
        $occupancy = $this->occupancy($units, $month);

        return view('livewire.owner.units', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'units' => $this->unitCards($units, $occupancy),
            'occupancy' => $occupancy,
            'month' => [
                'label' => $month->format('F Y'),
                'first_weekday' => $month->copy()->startOfMonth()->dayOfWeekIso,   // 1=Mon … 7=Sun
                'prev' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
                'next' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            ],
        ]);
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
     * OWNER-SCOPED occupancy for the month — active physical-unit bookings on the owner's OWN units
     * only. Excludes non-owner units in the same room type by construction (they carry a different
     * unit_id and are never in $unitIds). No double-count: the no-double-booking guarantee means a
     * physical unit's lines never overlap.
     *
     * @param  Collection<int, Unit>  $units
     * @return array{days: int, unit_count: int, heat: array<int, int>, per_unit: array<string, int>, nights: int, pct: int}
     */
    private function occupancy(Collection $units, Carbon $month): array
    {
        $unitIds = array_map('strval', $units->pluck('id')->all());
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();   // exclusive upper bound
        $days = (int) $monthStart->daysInMonth;

        $heat = array_fill(1, $days, 0);
        $perUnit = array_fill_keys($unitIds, 0);

        if ($unitIds !== []) {
            $lines = ReservationUnit::query()
                ->whereIn('unit_id', $unitIds)
                ->where('status', 'active')
                ->whereDate('check_in', '<', $monthEnd->toDateString())
                ->whereDate('check_out', '>', $monthStart->toDateString())
                ->get(['unit_id', 'check_in', 'check_out']);

            foreach ($lines as $line) {
                $unitId = (string) $line->unit_id;
                $checkIn = Carbon::parse($line->check_in);
                $checkOut = Carbon::parse($line->check_out);
                $start = $checkIn->greaterThan($monthStart) ? $checkIn : $monthStart->copy();
                $end = $checkOut->lessThan($monthEnd) ? $checkOut : $monthEnd->copy();

                for ($d = $start->copy(); $d->lessThan($end); $d->addDay()) {
                    $heat[(int) $d->format('j')]++;
                    $perUnit[$unitId] = ($perUnit[$unitId] ?? 0) + 1;
                }
            }
        }

        $unitCount = count($unitIds);
        $nights = array_sum($perUnit);

        return [
            'days' => $days,
            'unit_count' => $unitCount,
            'heat' => $heat,
            'per_unit' => $perUnit,
            'nights' => $nights,
            'pct' => $unitCount > 0 && $days > 0 ? (int) round($nights / ($unitCount * $days) * 100) : 0,
        ];
    }

    /**
     * @param  Collection<int, Unit>  $units
     * @param  array{days: int, per_unit: array<string, int>}  $occupancy
     * @return list<array<string, mixed>>
     */
    private function unitCards(Collection $units, array $occupancy): array
    {
        $days = $occupancy['days'];

        return $units->map(function (Unit $u) use ($occupancy, $days): array {
            $nights = $occupancy['per_unit'][(string) $u->id] ?? 0;

            return [
                'id' => $u->id,
                'name' => (string) ($u->name ?? $u->code),
                'code' => (string) $u->code,
                'type' => (string) $u->roomType->name,   // units always belong to a room type
                'active' => $u->is_active !== false,
                'nights' => $nights,
                'occupancy_pct' => $days > 0 ? (int) round($nights / $days * 100) : 0,
            ];
        })->values()->all();
    }
}
