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
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
use App\Support\I18n\LocalText;
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
                'frequencies' => OwnerBlockService::FREQUENCIES,
                'maxOccurrences' => OwnerBlockService::MAX_OCCURRENCES,
                'statLine' => '',
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
                'label' => $monthStart->translatedFormat(LocalDate::pattern('F Y')),
                'value' => $monthStart->format('Y-m'),
                'first_weekday' => $monthStart->dayOfWeekIso,   // 1=Mon … 7=Sun
                'prev' => $monthStart->copy()->subMonthNoOverflow()->format('Y-m'),
                'next' => $monthStart->copy()->addMonthNoOverflow()->format('Y-m'),
                'today' => Carbon::today()->format('Y-m'),
            ],
            'grid' => $grid,
            'blocks' => $this->blocksList($unit),
            'reasons' => OwnerBlockService::REASONS,
            'reasonLabels' => OwnerBlockService::reasonLabels(),   // the ONE definition, keyed by the stored code
            'weekdays' => $this->weekdayHeads(),
            'frequencies' => OwnerBlockService::FREQUENCIES,
            'maxOccurrences' => OwnerBlockService::MAX_OCCURRENCES,
            'statLine' => $this->statLine($grid),
        ]);
    }

    /**
     * The legend's stat line — real counts for the month grid (open / booked / held), never a fabricated figure.
     *
     * @param  list<array<string, mixed>>  $grid
     */
    private function statLine(array $grid): string
    {
        $count = fn (string $state): int => count(array_filter($grid, fn (array $d): bool => $d['state'] === $state));

        return __('php/livewire/owner/owner_block_calendar.stat_line', ['held' => $count('blocked'), 'booked' => $count('booked'), 'open' => $count('open')]);
    }

    /**
     * The grid's weekday heads, Monday first, in the reader's language and upper case ("MON" … "SUN" in English).
     *
     * @return list<string>
     */
    private function weekdayHeads(): array
    {
        $monday = Carbon::parse('2024-01-01');   // a Monday

        return array_map(fn (int $i): string => LocalText::upper($monday->copy()->addDays($i)->translatedFormat(LocalDate::pattern('D'))), range(0, 6));
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
     * Day → the booking guest's NAME for each 'Y-m-d' in the month occupied by an ACTIVE reservation on this
     * unit — half-open `[check_in, check_out)`, clipped to the month. Reached THROUGH the owner-scoped unit id.
     * NAME ONLY (#28): the guest's display name, never any contact detail; an owner sees who holds the date, as
     * on the owner Bookings PDF. An anonymized/unknown guest resolves to the neutral "Guest".
     *
     * @return array<string, string>
     */
    private function bookedDays(Unit $unit, Carbon $monthStart, Carbon $monthEnd): array
    {
        $days = [];

        ReservationUnit::query()
            ->where('unit_id', $unit->id)
            ->where('status', 'active')
            ->whereDate('check_in', '<', $monthEnd->toDateString())
            ->whereDate('check_out', '>', $monthStart->toDateString())
            ->with('reservation.leadGuest')
            ->get()
            ->each(function (ReservationUnit $line) use (&$days, $monthStart, $monthEnd): void {
                $name = GuestName::display($line->reservation?->leadGuest);
                $marked = [];
                $this->markRange($marked, Carbon::parse($line->check_in), Carbon::parse($line->check_out), $monthStart, $monthEnd);
                foreach (array_keys($marked) as $day) {
                    $days[$day] = $name;
                }
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
     * The month grid: leading blank cells to align day 1 under its weekday (MON-based), then one entry per day
     * with its state (past / booked / blocked / open), the guest NAME on a booked day (#28), and the block id
     * when blocked. Precedence: past first (nothing to do), then booked (a real reservation), then blocked, else
     * open.
     *
     * @param  array<string, string>  $bookedDays  day → guest name
     * @param  array<string, string>  $blockDays  day → block id
     * @return list<array{day: ?int, date: ?string, state: string, block_id: ?string, guest: ?string}>
     */
    private function grid(Carbon $monthStart, array $bookedDays, array $blockDays): array
    {
        $today = Carbon::today();
        $days = (int) $monthStart->daysInMonth;
        $out = [];

        // Leading blanks so day 1 sits under its real weekday (dayOfWeekIso: 1=Mon … 7=Sun).
        for ($b = 1; $b < $monthStart->dayOfWeekIso; $b++) {
            $out[] = ['day' => null, 'date' => null, 'state' => 'blank', 'block_id' => null, 'guest' => null, 'aria' => null];
        }

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
                'guest' => $state === 'booked' ? $bookedDays[$key] : null,
                // The spoken date for the cell's accessible name. A day number alone ("14") is meaningless
                // out of the grid's visual context, and every day control needs a name that identifies it.
                'aria' => $date->translatedFormat(LocalDate::pattern('j F Y')),
            ];
        }

        return $out;
    }

    /**
     * The scheduled blocks on this unit (soft-deleted ones excluded by the model), most recent first. Blocks
     * that share a `series_id` (a repeat) collapse into ONE row spanning the whole series — real, derived from
     * the real member blocks, never a fabricated parent — so the list badges "Series · N" and Remove releases
     * them together. A standalone block is its own row. The reason (+ optional note) is split back out of `notes`.
     *
     * @return list<array{is_series: bool, series_id: ?string, block_id: ?string, count: int, range: string, nights: int, reason: string, note: ?string}>
     */
    private function blocksList(Unit $unit): array
    {
        return InventoryBlock::query()
            ->where('unit_id', $unit->id)
            ->orderByDesc('start_date')
            ->get()
            ->groupBy(fn (InventoryBlock $b): string => $b->series_id !== null ? 'series:'.$b->series_id : 'block:'.$b->id)
            ->map(function (Collection $group): array {
                /** @var InventoryBlock $first */
                $first = $group->sortBy('start_date')->first();
                /** @var InventoryBlock $last */
                $last = $group->sortByDesc('start_date')->first();
                $isSeries = $first->series_id !== null;

                $start = Carbon::parse($first->start_date);
                $lastNight = Carbon::parse($last->end_date)->subDay();   // end_date is exclusive
                $nights = (int) $group->sum(fn (InventoryBlock $b): int => (int) Carbon::parse($b->start_date)->diffInDays(Carbon::parse($b->end_date)));

                [$reason, $note] = $this->splitNote((string) ($first->notes ?? __('php/livewire/owner/owner_block_calendar.owner_hold')));

                return [
                    'is_series' => $isSeries,
                    'series_id' => $isSeries ? (string) $first->series_id : null,
                    'block_id' => $isSeries ? null : (string) $first->id,
                    'count' => $group->count(),
                    'range' => __('php/livewire/owner/owner_block_calendar.date_range', ['start' => $start->translatedFormat(LocalDate::pattern('j M')), 'end' => $lastNight->translatedFormat(LocalDate::pattern('j M Y'))]),
                    'nights' => $nights,
                    'reason' => $reason,
                    'note' => $note,
                    'first_start' => $start->toDateString(),
                ];
            })
            ->sortByDesc('first_start')
            ->values()
            ->map(function (array $row): array {
                unset($row['first_start']);   // internal sort key only

                return $row;
            })
            ->all();
    }

    /**
     * Split a stored `notes` value back into its reason (before ' — ') and optional free note (after).
     *
     * @return array{0: string, 1: string|null}
     */
    private function splitNote(string $notes): array
    {
        if (! str_contains($notes, ' — ')) {
            return [$notes, null];
        }

        [$reason, $note] = explode(' — ', $notes, 2);

        return [$reason, $note];
    }
}
