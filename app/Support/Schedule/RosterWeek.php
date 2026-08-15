<?php

declare(strict_types=1);

namespace App\Support\Schedule;

use App\Livewire\Operator\OperatorSchedule;
use App\Models\OpenShift;
use App\Models\Property;
use App\Models\SchedulePublication;
use App\Models\Shift;
use App\Models\User;
use App\Services\LeaveService;
use Carbon\CarbonImmutable;

/**
 * THE one builder of a week's roster (Phase 7d · SG2) — real staff × Mon–Sun for a property, each cell its
 * {@see ShiftType} with the REAL leave overlay + the REAL computed conflict. Read by BOTH the on-screen grid
 * ({@see OperatorSchedule}) AND the schedule PDF, so the PDF reconciles to the screen
 * character-for-character — two builders would drift.
 *
 * 🪤 REAL, not fabricated: a cell's `on_leave` is a real approved-leave record covering that date; `conflict`
 * is a real working-shift-on-a-leave-day (assign-on-leave is prevented at write time, so a conflict only
 * arises when leave is approved AFTER a shift was set — the grid surfaces it honestly).
 */
final class RosterWeek
{
    /**
     * @param  list<array{date: string, dow: string, label: string, today: bool}>  $days
     * @param  list<array{staff: array{id: string, name: string, initials: string, department: string, role_label: string}, cells: list<array<string, mixed>>}>  $rows
     */
    /** Hours per worked shift — Early/Day/Late are all 8h windows (07–15, 09–17, 15–23). */
    private const HOURS_PER_SHIFT = 8;

    public function __construct(
        public readonly array $days,
        public readonly array $rows,
        public readonly string $weekLabel,
        public readonly bool $isThisWeek,
        public readonly int $week,
        public readonly string $dept,
        public readonly int $conflictCount,
        /**
         * The property this week was built for (D34-1). ⚠️ Nullable AND defaulted so the signature stays
         * backward-compatible: `build()` is the only caller today, but a required 8th argument would break
         * any direct construction a test or future caller makes.
         */
        public readonly ?string $propertyId = null,
    ) {}

    /** Memoised so a Blade calling an accessor twice does not query twice. */
    private ?array $publicationCache = null;

    private ?array $openShiftCache = null;

    /** The number of team members on this rota (task #102 — the summary reconciles to THIS builder). */
    public function teamSize(): int
    {
        return count($this->rows);
    }

    /** The number of worked shifts across the week (a leave-conflicted cell is still a worked shift). */
    public function shiftCount(): int
    {
        $count = 0;
        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell['working']) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /** Total rostered hours across the week — derived from the same worked cells (never a re-query). */
    public function hours(): int
    {
        return $this->shiftCount() * self::HOURS_PER_SHIFT;
    }

    /**
     * GATE 9.2 — the DAY view's agenda for one day of the week: the focused day's staff grouped by working shift
     * type (Early/Day/Late), plus who is NOT working. Derived from the SAME rows the grid renders (dept-filtered),
     * so the two views reconcile — never a re-query.
     *
     * @return array{date: string, index: int, label: string, working_count: int, groups: list<array{type: string, label: string, hours: string, count: int, empty: bool, people: list<array{init: string, name: string, role: string, department: string, conflict: bool}>}>, not_working: list<array{init: string, name: string, reason: string}>}
     */
    public function dayView(int $focusIdx): array
    {
        $lastIdx = count($this->days) - 1;
        $focusIdx = max(0, min($focusIdx, $lastIdx));
        $day = $this->days[$focusIdx];

        $groups = [];
        foreach (ShiftType::WORKING as $type) {
            $people = [];
            foreach ($this->rows as $r) {
                $cell = $r['cells'][$focusIdx];
                if ($cell['working'] && $cell['type'] === $type) {
                    $people[] = [
                        'init' => $r['staff']['initials'],
                        'name' => $r['staff']['name'],
                        'role' => $r['staff']['role_label'],
                        'department' => $r['staff']['department'],
                        'conflict' => (bool) $cell['conflict'],
                    ];
                }
            }
            $groups[] = [
                'type' => $type,
                'label' => ShiftType::label($type),
                'hours' => ShiftType::hours($type),
                'count' => count($people),
                'empty' => $people === [],
                'people' => $people,
            ];
        }

        $notWorking = [];
        $workingCount = 0;
        foreach ($this->rows as $r) {
            $cell = $r['cells'][$focusIdx];
            if ($cell['working']) {
                $workingCount++;

                continue;
            }
            $notWorking[] = [
                'init' => $r['staff']['initials'],
                'name' => $r['staff']['name'],
                'reason' => $cell['on_leave'] ? 'On leave' : 'Day off',
            ];
        }

        return [
            'date' => $day['date'],
            'index' => $focusIdx,
            'label' => CarbonImmutable::parse($day['date'])->format('l j M'),
            'working_count' => $workingCount,
            'groups' => $groups,
            'not_working' => $notWorking,
        ];
    }

    /**
     * GATE 9.2 — HONEST per-department coverage for the week: the number of the 7 days on which at least ONE
     * staffer of that department is working, out of 7. There is NO configured staffing target, so this is a real
     * "days with cover" count — never an invented ratio like the prototype's fabricated 14/16. Only departments
     * actually on the team appear (no 0/7 bar for a department you don't employ). Feed an UNFILTERED roster so all
     * departments show regardless of the grid's department filter.
     *
     * @return list<array{dept: string, label: string, filled: int, total: int, pct: int, full: bool}>
     */
    public function coverageByDepartment(): array
    {
        $dayCount = count($this->days);
        $out = [];
        foreach ([StaffDepartment::FRONTDESK, StaffDepartment::HOUSEKEEPING, StaffDepartment::MANAGER] as $dept) {
            $deptRows = array_values(array_filter($this->rows, fn (array $r): bool => $r['staff']['department'] === $dept));
            if ($deptRows === []) {
                continue;
            }

            $covered = 0;
            for ($i = 0; $i < $dayCount; $i++) {
                foreach ($deptRows as $r) {
                    if ($r['cells'][$i]['working']) {
                        $covered++;
                        break;
                    }
                }
            }

            $out[] = [
                'dept' => $dept,
                'label' => StaffDepartment::label($dept),
                'filled' => $covered,
                'total' => $dayCount,
                'pct' => $dayCount > 0 ? (int) round($covered / $dayCount * 100) : 0,
                'full' => $covered >= $dayCount,
            ];
        }

        return $out;
    }

    /**
     * D34-1 — the week's PUBLICATION state, as TRUTH rather than as a rendered phrase.
     *
     * ⚠️ WHY THIS RETURNS A TIMESTAMP AND NOT A STRING, and it is the whole reason the accessor exists rather
     * than the grid's method being lifted verbatim: {@see OperatorSchedule::publishedState()} returns
     * `diffForHumans()` — "2 hours ago". That is right for a screen and WRONG for a document. A PDF printed
     * on Monday and read on Thursday would still say "2 hours ago", which is precisely the class of claim
     * DO-CORRECTNESS-1 stripped out of this template. So the builder returns the real `published_at` and each
     * surface formats it for its own medium: the grid relatively, the PDF absolutely.
     *
     * ⚠️ `published` is THE PRESENCE OF THE ROW, full stop — and that is a schema fact, not an assumption:
     * `schedule_publications.published_at` is NOT NULL (migration 2026_07_31_150000, `timestamp()` with no
     * `->nullable()`), verified against the live column. A first draft here also null-checked the stamp;
     * PHPStan pointed out the comparison can never be true. ⚠️ The grid carries the same redundant check
     * (`$pub?->published_at !== null`) — harmless there, logged rather than changed from this pass.
     *
     * @return array{published: bool, published_at: ?CarbonImmutable, published_by: ?string}
     */
    public function publicationState(): array
    {
        if ($this->publicationCache !== null) {
            return $this->publicationCache;
        }

        $none = ['published' => false, 'published_at' => null, 'published_by' => null];

        $weekStart = (string) ($this->days[0]['date'] ?? '');
        if ($this->propertyId === null || $weekStart === '') {
            return $this->publicationCache = $none;
        }

        // ⚠️ THE SAME READ THE GRID MAKES (property + whereDate on the week's Monday), so the two cannot
        // disagree about whether a week is published. Parity is asserted in SchedulePdfPublicationTest.
        $publication = SchedulePublication::query()
            ->where('property_id', $this->propertyId)
            ->whereDate('week_starts_on', $weekStart)
            ->first();

        if ($publication === null) {
            return $this->publicationCache = $none;
        }

        return $this->publicationCache = [
            'published' => true,
            'published_at' => CarbonImmutable::parse($publication->published_at),
            // Real name or nothing — never "Unknown", which would read as a person (#39).
            'published_by' => $publication->published_by !== null
                ? User::query()->whereKey($publication->published_by)->value('name')
                : null,
        ];
    }

    /**
     * D34-1 — the week's OPEN (unassigned) shift slots, in the shape both surfaces need.
     *
     * ⚠️ DELIBERATELY NOT DEPARTMENT-FILTERED, because {@see OperatorSchedule::openShiftsFor()} is not: an
     * open slot is shown whatever the grid's `dept` filter is set to. Matching that exactly is the point —
     * an accessor that filtered "more correctly" than the screen would make the PDF disagree with the grid,
     * which is the drift this builder exists to prevent. ⚠️ Whether the grid SHOULD filter is a real open
     * question, logged rather than decided here.
     *
     * @return list<array{id: string, date: string, day_label: string, shift_label: string, department: ?string}>
     */
    public function openShifts(): array
    {
        if ($this->openShiftCache !== null) {
            return $this->openShiftCache;
        }

        $first = (string) ($this->days[0]['date'] ?? '');
        $last = (string) ($this->days[count($this->days) - 1]['date'] ?? '');
        if ($this->propertyId === null || $first === '') {
            return $this->openShiftCache = [];
        }

        return $this->openShiftCache = OpenShift::query()
            ->where('property_id', $this->propertyId)
            ->whereBetween('date', [$first, $last])
            ->orderBy('date')
            ->get()
            ->map(fn (OpenShift $o): array => [
                'id' => (string) $o->getKey(),
                'date' => $o->date->toDateString(),
                'day_label' => $o->date->format('D j M'),
                'shift_label' => ShiftType::label((string) $o->shift_type),
                'department' => $o->department,
            ])->all();
    }

    /** How many slots on this week's rota are unfilled — derived from {@see openShifts()}, never re-queried. */
    public function openShiftCount(): int
    {
        return count($this->openShifts());
    }

    public static function build(?Property $property, int $week, string $dept): self
    {
        $tz = $property !== null ? (string) ($property->timezone ?? 'UTC') : 'UTC';
        $today = CarbonImmutable::now($tz);
        $monday = $today->startOfWeek(CarbonImmutable::MONDAY)->addWeeks($week);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $monday->addDays($i);
            $days[] = ['date' => $d->toDateString(), 'dow' => $d->format('D'), 'label' => $d->format('j M'), 'today' => $d->isSameDay($today)];
        }
        $from = $days[0]['date'];
        $to = $days[6]['date'];

        $staff = app(StaffRoster::class)->staff();
        if ($dept !== 'all') {
            $staff = array_values(array_filter($staff, fn (array $s): bool => $s['department'] === $dept));
        }
        $staffIds = array_map(fn (array $s): string => $s['id'], $staff);

        // Shifts for THIS property + week, and approved-leave days for these staff — both keyed "user_id|date".
        $shiftMap = [];
        if ($property !== null && $staffIds !== []) {
            Shift::query()
                ->where('property_id', $property->getKey())
                ->whereIn('user_id', $staffIds)
                ->whereBetween('date', [$from, $to])
                ->get()
                ->each(function (Shift $s) use (&$shiftMap): void {
                    $shiftMap[$s->user_id.'|'.$s->date->toDateString()] = $s->shift_type;
                });
        }
        $leaveDays = $staffIds === [] ? [] : app(LeaveService::class)->approvedLeaveDays($staffIds, $from, $to);

        $conflictCount = 0;
        $rows = array_map(function (array $s) use ($days, $shiftMap, $leaveDays, &$conflictCount): array {
            $cells = array_map(function (array $day) use ($s, $shiftMap, $leaveDays, &$conflictCount): array {
                $key = $s['id'].'|'.$day['date'];
                $type = $shiftMap[$key] ?? ShiftType::OFF;
                $onLeave = isset($leaveDays[$key]);
                $working = ShiftType::isWorking($type);
                $conflict = $working && $onLeave;
                if ($conflict) {
                    $conflictCount++;
                }

                return [
                    'date' => $day['date'],
                    'type' => $type,
                    'label' => ShiftType::label($type),
                    'hours' => ShiftType::hours($type),
                    'working' => $working,
                    'on_leave' => $onLeave,
                    'conflict' => $conflict,
                    'display' => $onLeave ? 'Leave' : ($working ? ShiftType::label($type) : '—'),
                ];
            }, $days);

            return ['staff' => $s, 'cells' => $cells];
        }, $staff);

        return new self(
            days: $days,
            rows: $rows,
            weekLabel: $monday->format('j M').' – '.$monday->addDays(6)->format('j M Y'),
            isThisWeek: $week === 0,
            week: $week,
            dept: $dept,
            conflictCount: $conflictCount,
            propertyId: $property?->getKey() !== null ? (string) $property->getKey() : null,
        );
    }
}
