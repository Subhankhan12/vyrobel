<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerBlockController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\InventoryBlock;
use App\Models\ReservationUnit;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\OwnerBlockService;
use App\Services\OwnerPortalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Block Calendar (Phase 2 · Sub-gate 2) — the owner holds dates on their OWN units (personal use,
 * maintenance, seasonal closures). This component is the READ side (unit picker, month grid of day states, and
 * the scheduled-blocks list); the WRITE goes through {@see OwnerBlockController} →
 * {@see OwnerBlockService} → the certified {@see AssignmentService} (single inventory write path).
 *
 * ISOLATION — {@see Unit} carries the owner {@see OwnerScope}, so the unit picker is the owner's own units and a
 * tampered `?unit=` that isn't owned silently falls back to the first owned unit (a READ never 403s; the WRITE
 * guard lives in the service). Every day-state read is keyed on that owned unit id, so bookings/blocks on other
 * owners' units can't surface. Unit + month ride GET so the owner middleware re-runs (a wire-action would bypass
 * the owner scope — invariant #20).
 *
 * Honesty: a Booked day shows "Booked" WITHOUT the guest name (the owner needs only to know the date is taken,
 * not who — less incidental PII than a name on a cell). The block reasons are the real ones the schema supports
 * ({@see OwnerBlockService::REASONS}, stored in `notes`; the `reason` column is the real enum `'owner'`). No
 * fabricated states.
 */
#[Layout('components.layouts.owner')]
class OwnerBlockCalendar extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed when empty

        if ($units->isEmpty()) {
            return view('livewire.owner.block-calendar', [
                'viewer' => $this->viewer(),
                'units' => [],
                'unit' => null,
                'month' => null,
                'grid' => [],
                'blocks' => [],
                'reasons' => OwnerBlockService::REASONS,
                'blockedThisMonth' => 0,
            ]);
        }

        $unit = $this->resolveUnit($units);
        $month = $this->resolveMonth();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();   // exclusive

        $bookedDays = $this->bookedDays($unit, $monthStart, $monthEnd);
        $blockDays = $this->blockDays($unit, $monthStart, $monthEnd);
        $grid = $this->grid($monthStart, $bookedDays, $blockDays);

        return view('livewire.owner.block-calendar', [
            'viewer' => $this->viewer(),
            'units' => $units->map(fn (Unit $u): array => [
                'id' => (string) $u->id,
                'label' => (string) ($u->name ?? $u->code),
            ])->values()->all(),
            'unit' => ['id' => (string) $unit->id, 'label' => (string) ($unit->name ?? $unit->code)],
            'month' => [
                'label' => $monthStart->format('F Y'),
                'value' => $monthStart->format('Y-m'),
                'first_weekday' => $monthStart->dayOfWeekIso,   // 1=Mon … 7=Sun
                'prev' => $monthStart->copy()->subMonthNoOverflow()->format('Y-m'),
                'next' => $monthStart->copy()->addMonthNoOverflow()->format('Y-m'),
                'today' => Carbon::today()->format('Y-m'),
            ],
            'grid' => $grid,
            'blocks' => $this->blocksList($unit),
            'reasons' => OwnerBlockService::REASONS,
            'blockedThisMonth' => count(array_filter($grid, fn (array $d): bool => $d['state'] === 'blocked')),
        ]);
    }

    /**
     * The selected unit: `?unit=` if it is one the owner owns, else the first owned unit. A foreign/unknown id
     * is IGNORED (never trusted) — the collection is already the owner-scoped set.
     *
     * @param  Collection<int, Unit>  $units
     */
    private function resolveUnit(Collection $units): Unit
    {
        $requested = request()->query('unit');
        if (is_string($requested)) {
            $match = $units->first(fn (Unit $u): bool => (string) $u->id === $requested);
            if ($match instanceof Unit) {
                return $match;
            }
        }

        /** @var Unit $first */
        $first = $units->first();

        return $first;
    }

    private function resolveMonth(): Carbon
    {
        $param = request()->query('month');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}$/', $param) === 1) {
            return Carbon::parse($param.'-01')->startOfMonth();
        }

        return Carbon::now()->startOfMonth();
    }

    /**
     * The set of 'Y-m-d' days in the month occupied by an ACTIVE reservation on this unit — half-open
     * `[check_in, check_out)`, clipped to the month. Reached THROUGH the owner-scoped unit id.
     *
     * @return array<string, true>
     */
    private function bookedDays(Unit $unit, Carbon $monthStart, Carbon $monthEnd): array
    {
        $days = [];

        ReservationUnit::query()
            ->where('unit_id', $unit->id)
            ->where('status', 'active')
            ->whereDate('check_in', '<', $monthEnd->toDateString())
            ->whereDate('check_out', '>', $monthStart->toDateString())
            ->get(['check_in', 'check_out'])
            ->each(function (ReservationUnit $line) use (&$days, $monthStart, $monthEnd): void {
                $this->markRange($days, Carbon::parse($line->check_in), Carbon::parse($line->check_out), $monthStart, $monthEnd);
            });

        return $days;
    }

    /**
     * Day → block id for the owner's blocks on this unit that touch the month (so a day can be released).
     *
     * @return array<string, string>
     */
    private function blockDays(Unit $unit, Carbon $monthStart, Carbon $monthEnd): array
    {
        $days = [];

        InventoryBlock::query()
            ->where('unit_id', $unit->id)
            ->whereDate('start_date', '<', $monthEnd->toDateString())
            ->whereDate('end_date', '>', $monthStart->toDateString())
            ->get(['id', 'start_date', 'end_date'])
            ->each(function (InventoryBlock $block) use (&$days, $monthStart, $monthEnd): void {
                $marked = [];
                $this->markRange($marked, Carbon::parse($block->start_date), Carbon::parse($block->end_date), $monthStart, $monthEnd);
                foreach (array_keys($marked) as $day) {
                    $days[$day] = (string) $block->id;
                }
            });

        return $days;
    }

    /**
     * Fill $days with each 'Y-m-d' in `[from, to)` ∩ `[monthStart, monthEnd)`.
     *
     * @param  array<string, true>  $days
     */
    private function markRange(array &$days, Carbon $from, Carbon $to, Carbon $monthStart, Carbon $monthEnd): void
    {
        $start = $from->greaterThan($monthStart) ? $from->copy() : $monthStart->copy();
        $end = $to->lessThan($monthEnd) ? $to->copy() : $monthEnd->copy();

        for ($d = $start->copy(); $d->lessThan($end); $d->addDay()) {
            $days[$d->toDateString()] = true;
        }
    }

    /**
     * The month grid: one entry per day with its state (past / booked / blocked / open) + the block id when
     * blocked. Precedence: past first (nothing to do), then booked (a real reservation), then blocked, else open.
     *
     * @param  array<string, true>  $bookedDays
     * @param  array<string, string>  $blockDays
     * @return list<array{day: int, date: string, state: string, block_id: ?string}>
     */
    private function grid(Carbon $monthStart, array $bookedDays, array $blockDays): array
    {
        $today = Carbon::today();
        $days = (int) $monthStart->daysInMonth;
        $out = [];

        for ($n = 1; $n <= $days; $n++) {
            $date = $monthStart->copy()->day($n);
            $key = $date->toDateString();

            $state = match (true) {
                $date->lessThan($today) => 'past',
                isset($bookedDays[$key]) => 'booked',
                isset($blockDays[$key]) => 'blocked',
                default => 'open',
            };

            $out[] = [
                'day' => $n,
                'date' => $key,
                'state' => $state,
                'block_id' => $state === 'blocked' ? $blockDays[$key] : null,
            ];
        }

        return $out;
    }

    /**
     * The scheduled blocks on this unit (soft-deleted ones excluded by the model), most recent first.
     *
     * @return list<array{id: string, range: string, nights: int, reason: string}>
     */
    private function blocksList(Unit $unit): array
    {
        return InventoryBlock::query()
            ->where('unit_id', $unit->id)
            ->orderByDesc('start_date')
            ->get()
            ->map(function (InventoryBlock $block): array {
                $start = Carbon::parse($block->start_date);
                $end = Carbon::parse($block->end_date);   // exclusive
                $lastNight = $end->copy()->subDay();

                return [
                    'id' => (string) $block->id,
                    'range' => $start->format('j M').' – '.$lastNight->format('j M Y'),
                    'nights' => (int) $start->diffInDays($end),
                    'reason' => (string) ($block->notes ?? 'Owner hold'),
                ];
            })
            ->values()
            ->all();
    }
}
