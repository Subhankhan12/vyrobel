<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\HousekeepingActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\Media;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Models\User;
use App\Services\HousekeepingService;
use App\Support\I18n\LocalDate;
use App\Support\Property\PropertyDay;
use App\Support\Reservations\ReservationStage;
use App\Support\Schedule\StaffRoster;
use App\Support\StayTime;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Housekeeping — the cleaning KANBAN board (design pass · Operator Screen 3). Styled from the
 * catalogued "Operator Housekeeping" design onto the SAME operator design system as Screens 1–2 (the
 * operator layout, <x-operator.shell>, FormatsOperatorData — reused, never redefined). Presentation +
 * a thin read: the board only READS; the status-advance MUTATION runs through the real
 * {@see HousekeepingService} via a POST route ({@see HousekeepingActionController}),
 * NOT from here — so no schema/guarantee is touched by this component.
 *
 * TENANT-scoped (BelongsToOrganization) — one org never sees another's tasks.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL       — every task grouped by its real `status` (the 4 kanban columns are the real enum
 *                  pending/in_progress/clean/inspected — no invented columns); per card the real unit,
 *                  turnover `type`, `priority`, `date`, and assigned housekeeper (or "Unassigned").
 *                  Status advance (Start / Mark clean / Mark inspected) is REAL and persists.
 *   • DERIVED    — the per-column + high-priority summary counts, from the real rows only.
 *   • DEFERRED   — drag-to-move: the mutation is offered via a safe POST status button instead (a drag
 *                  that persisted would need a wire-action/JS fetch this pass doesn't ship); flagged.
 *                  Assign-housekeeper: there is NO existing HousekeepingService method for it, so the
 *                  assignee is DISPLAYED (real) but the "Assign" affordance is a flagged "Soon" — never
 *                  a faked assignment.
 *   • NO BACKEND — filters / export: disabled "Soon".
 *   • PII        — the board shows the staff housekeeper's name (not a guest). #87 adds the incoming guest's
 *                  VIP flag + stay preferences (operational flags, not contact PII — no name/email/phone),
 *                  so the room is prepped right; the name-only perimeter is unaffected.
 *
 * The view receives:
 *   viewer: array{name: string, initials: string}
 *   columns: list<array{key: string, label: string, tone: string, tasks: list<array<string, mixed>>}>
 *   summary: array{pending: int, in_progress: int, clean: int, inspected: int, high: int, total: int}
 */
#[Layout('components.layouts.operator')]
class HousekeepingBoard extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    /** The kanban columns, in workflow order — exactly the real status enum. */
    private const STATUSES = ['pending', 'in_progress', 'clean', 'inspected'];

    /** Forward status moves offered per column (action code + target), all through the real enum. The label is
     * translated at render time ({@see nextActions()}) — a constant runs before the request's language is set. */
    private const NEXT_ACTIONS = [
        'pending' => [['action' => 'start', 'to' => 'in_progress']],
        'in_progress' => [['action' => 'mark_clean', 'to' => 'clean']],
        'clean' => [['action' => 'inspect', 'to' => 'inspected']],
        'inspected' => [],
    ];

    /** Expected clean minutes per turnover type (#88) — the board's elapsed bar denominator. A documented
     * operational constant (not a fabricated per-room estimate): departure turnover is the long one. */
    private const EXPECTED_MINUTES = ['departure' => 45, 'deep' => 90, 'stayover' => 30, 'arrival_prep' => 20];

    public function render(): View
    {
        $readIds = $this->currentReadIds;
        $now = CarbonImmutable::now();
        // ⚠️ D-DAY-ELSEWHERE (2026-11-11): "today" is the day it is AT EACH ROOM'S PROPERTY — Today / Tomorrow / Overdue and the "guest arriving
        //    today" urgency read it (the app's day is UTC, hours off elsewhere); "all properties" can span timezones, so it is per property
        $todays = PropertyDay::todays($readIds);
        $defaultToday = $todays[$this->currentPropertyId] ?? ($todays !== [] ? reset($todays) : CarbonImmutable::today()->toDateString());

        $crewFilter = is_string(request()->query('crew')) ? (string) request()->query('crew') : null;
        $search = mb_substr(trim((string) request()->query('q', '')), 0, 80);

        $tasks = HousekeepingTask::query()
            ->whereIn('property_id', $readIds)
            ->with(['unit', 'assignedTo', 'media'])
            ->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('date')
            ->get();

        $arrivals = $this->arrivalsForTasks($tasks);         // #87 prefs/VIP + D8-2 the ready-by deadline
        $todayArrivals = $this->todayArrivals($readIds);  // unit_id → arriving reservation status (urgency), each against its own property's day
        $photos = $this->photosForTasks($tasks);                  // taskId → list of proof photos
        $checkInTimes = $this->checkInTimes($readIds);             // D8-2 — property id → its own check-in time

        // Every card (unfiltered) — the tallies + crew load must read the whole workspace, not the filtered view.
        $allCards = $tasks->map(fn (HousekeepingTask $t): array => $this->card($t, $arrivals, $todayArrivals, $photos, $now, $checkInTimes, CarbonImmutable::parse($todays[(string) $t->property_id] ?? $defaultToday)))->all();

        $outOfOrder = $this->outOfOrderCount($readIds);
        // ⚠️ 2026-10-05 — each filter chip counts exactly what clicking it shows: the rooms that pass the search (the chips carry it),
        //    every stage — the chip used to print a person's OPEN load, while clicking them listed their clean rooms too
        $searched = array_values(array_filter($allCards, fn (array $c): bool => $this->matches($c, null, $search)));
        $crew = $this->crew($tasks, $crewFilter, $searched);

        // The displayed cards — narrowed by the crew filter + the search.
        $cards = array_values(array_filter($allCards, fn (array $c): bool => $this->matches($c, $crewFilter, $search)));

        // Board lanes (the 4 real statuses).
        $columns = [];
        foreach (self::STATUSES as $status) {
            $meta = $this->housekeepingStatusMeta($status);
            $columns[] = [
                'key' => $status,
                'label' => $meta['label'],
                'tone' => $meta['tone'],
                'tasks' => array_values(array_filter($cards, fn (array $c): bool => $c['status'] === $status)),
            ];
        }

        // List rows — waiting-guest first, then the stage order.
        $stageOrder = array_flip(self::STATUSES);
        usort($cards, function (array $a, array $b) use ($stageOrder): int {
            $aw = $a['urgency'] !== null ? 0 : 1;
            $bw = $b['urgency'] !== null ? 0 : 1;

            return $aw <=> $bw ?: ($stageOrder[$a['status']] ?? 9) <=> ($stageOrder[$b['status']] ?? 9);
        });

        // The detail drawer target (?task=), if any — from the UNFILTERED set so a deep-link always resolves.
        $detailId = is_string(request()->query('task')) ? (string) request()->query('task') : null;
        $detail = $detailId !== null ? (collect($allCards)->firstWhere('id', $detailId) ?: null) : null;

        return view('livewire.housekeeping-board', [
            'viewer' => $this->viewer(),
            'columns' => $columns,
            'rows' => $cards,
            'tallies' => $this->tallies($allCards, $outOfOrder),
            'crew' => $crew,
            'suggestedCrewId' => $this->suggestedHousekeeperId($crew),
            'outOfOrder' => $outOfOrder,
            'crewFilter' => $crewFilter,
            'query' => $search,
            'detail' => $detail,
            'chipCounts' => [
                'all' => count($searched),
                'unassigned' => count(array_filter($searched, fn (array $c): bool => $c['assigned_to'] === null)),
            ],
            'unassignedCount' => count(array_filter($allCards, fn (array $c): bool => $c['assigned_to'] === null && in_array($c['status'], ['pending', 'in_progress'], true))),
            'housekeepers' => app(StaffRoster::class)->housekeepers(),
            // ⚠️ NAMED FOR THE PERMISSION IT CARRIES, renamed from `canAssign` 2026-09-23. It gates the
            //    status advance, the assign, the photo upload AND the photo delete — every one of them
            //    `housekeeping.manage` — so "canAssign" understated it by three controls. ⚠️ The view
            //    used to read `$canManage ?? $canAssign`, where `$canManage` was NEVER PROVIDED: the
            //    fallback was permanent, and the expression read as though two permissions were in play
            //    when there has only ever been one.
            'canManage' => auth()->user()?->can('housekeeping.manage') ?? false,
            // who may give a team member the Housekeeping role — the empty-roster hint links to Roles & access only for them (#30)
            'canStaff' => auth()->user()?->can('users.manage') ?? false,
        ]);
    }

    /**
     * @param  array<string, Reservation>  $arrivals  keyed "unitId|YYYY-MM-DD" → the reservation arriving that day
     * @param  array<string, string>  $todayArrivals  unit_id → the arriving reservation's status (for urgency)
     * @param  array<string, list<array{id: string, url: string}>>  $photos  taskId → proof photos
     * @return array<string, mixed>
     */
    private function card(HousekeepingTask $task, array $arrivals, array $todayArrivals, array $photos, CarbonImmutable $now, array $checkInTimes = [], ?CarbonImmutable $today = null): array
    {
        $assignee = $task->assignedTo;
        $priority = $this->priorityMeta((string) $task->priority);
        $status = (string) $task->status;
        $type = (string) $task->type;

        $arrival = $arrivals[$this->arrivalKey((string) $task->unit_id, (string) $task->date)] ?? null;
        $guest = $arrival?->leadGuest;

        // ⚠️ D8-2 — THE READY-BY DEADLINE IS A FACT OR IT IS NOTHING. It exists only when a real, non-cancelled
        // reservation arrives at THIS room on THIS date, and it is that property's own published check-in time
        // — the hour a guest may actually turn up. No arrival, or no published time, means no deadline, and
        // the row keeps its "—". Nothing here estimates, rounds or defaults an hour (#39, D1-3).
        $readyBy = $arrival !== null ? ($checkInTimes[(string) $task->property_id] ?? null) : null;

        // Elapsed is REAL — from started_at (set on the in_progress transition), against the expected minutes.
        $elapsed = null;
        if ($status === 'in_progress' && $task->started_at !== null) {
            $mins = max(0, (int) CarbonImmutable::parse($task->started_at)->diffInMinutes($now));
            $expected = self::EXPECTED_MINUTES[$type];   // $type is the real enum → the key always exists
            $elapsed = ['minutes' => $mins, 'expected' => $expected, 'over' => $mins > $expected, 'remaining' => max(0, $expected - $mins)];
        }

        // Urgency — a guest arriving today for this unit while the room isn't ready. Name-free (operational).
        $urgency = null;
        $arriving = $todayArrivals[(string) $task->unit_id] ?? null;
        if ($arriving !== null && ! in_array($status, ['clean', 'inspected'], true)) {
            $urgency = $arriving === 'checked_in'
                ? ['tone' => 'red', 'label' => __('php/livewire/housekeeping_board.urgency_guest_waiting')]
                : ['tone' => 'amber', 'label' => __('php/livewire/housekeeping_board.urgency_guest_arriving_today')];
        }

        return [
            'id' => (string) $task->id,
            'unit' => (string) ($task->unit->name ?? $task->unit->code),
            'unit_code' => (string) ($task->unit->code ?? ''),
            'status' => $status,
            'status_meta' => $this->housekeepingStatusMeta($status),
            'type' => $this->housekeepingTypeLabel($type),
            'turnover' => $type === 'departure',
            'priority_label' => $priority['label'],
            'priority_tone' => $priority['tone'],
            'is_priority' => $task->priority === 'high',
            'date' => CarbonImmutable::parse($task->date)->translatedFormat(LocalDate::pattern('j M')),
            // ⚠️ 2026-10-05 — which DAY this room is for. The page lists every day's cleaning (no date filter), and said "Today's":
            //    tomorrow's rooms read as today's. Today · Tomorrow · Overdue · <date> (not done, its day passed) · <weekday date>
            ...$this->day((string) $task->date, $status, $today ?? $now->startOfDay()),
            'assignee' => $assignee instanceof User ? (string) $assignee->name : null,
            'assignee_initials' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
            'assigned_to' => $assignee instanceof User ? (string) $assignee->getKey() : null,
            'urgency' => $urgency,
            'elapsed' => $elapsed,
            'started_label' => $task->started_at !== null ? CarbonImmutable::parse($task->started_at)->format('H:i') : null,
            'completed_label' => $task->completed_at !== null ? CarbonImmutable::parse($task->completed_at)->format('H:i') : null,
            'photos' => $photos[(string) $task->id] ?? [],
            'next' => $this->nextActions($status),
            // #87 — the incoming guest's VIP flag + preferences, so the room is prepped right. Name-free.
            'guest_vip' => $guest !== null && (bool) $guest->is_vip,
            'guest_preferences' => $guest !== null ? $guest->preferenceLabels() : [],
            // D8-2 — the real turnover deadline, or null. Never a placeholder time.
            'ready_by' => $readyBy,
        ];
    }

    /**
     * The task's day in words, and a tone for it — Today · Tomorrow · Overdue · 30 Jul (its day passed, not clean yet) · Wed 7 Oct.
     *
     * @return array{day: string, day_tone: string}
     */
    private function day(string $date, string $status, CarbonImmutable $today): array
    {
        $d = CarbonImmutable::parse($date)->startOfDay();
        $done = in_array($status, ['clean', 'inspected'], true);

        return match (true) {
            $d->isSameDay($today) => ['day' => __('common.today'), 'day_tone' => 'today'],
            $d->isSameDay($today->addDay()) => ['day' => __('php/livewire/housekeeping_board.day_tomorrow'), 'day_tone' => 'later'],
            $d->lessThan($today) && ! $done => ['day' => __('php/livewire/housekeeping_board.day_overdue', ['date' => $d->translatedFormat(LocalDate::pattern('j M'))]), 'day_tone' => 'overdue'],
            default => ['day' => $d->translatedFormat(LocalDate::pattern('D j M')), 'day_tone' => $d->lessThan($today) ? 'past' : 'later'],
        };
    }

    /**
     * The forward step(s) offered on a room at this stage, labelled in the request's language. The view (and the
     * room detail's script) read `label` to show and `to` to post — never the label to decide anything.
     *
     * @return list<array{label: string, to: string}>
     */
    private function nextActions(string $status): array
    {
        return array_map(fn (array $a): array => [
            'label' => match ($a['action']) {
                'start' => __('php/livewire/housekeeping_board.next_start'),
                'mark_clean' => __('php/livewire/housekeeping_board.next_mark_clean'),
                'inspect' => __('php/livewire/housekeeping_board.next_inspect'),
            },
            'to' => $a['to'],
        ], self::NEXT_ACTIONS[$status]);
    }

    /** Whether a card passes the active crew filter + search (unit / type / housekeeper name). */
    private function matches(array $card, ?string $crewFilter, string $search): bool
    {
        if ($crewFilter === 'unassigned' && $card['assigned_to'] !== null) {
            return false;
        }
        if ($crewFilter !== null && $crewFilter !== 'unassigned' && $card['assigned_to'] !== $crewFilter) {
            return false;
        }
        if ($search !== '') {
            $hay = mb_strtolower($card['unit'].' '.$card['type'].' '.((string) $card['assignee']));
            if (! str_contains($hay, mb_strtolower($search))) {
                return false;
            }
        }

        return true;
    }

    /**
     * The 5 status tallies (#88) — the 4 real HK statuses + out-of-order (real `condition_status` units).
     *
     * @param  list<array<string, mixed>>  $cards
     * @return array<string, int>
     */
    private function tallies(array $cards, int $outOfOrder): array
    {
        $t = array_fill_keys(self::STATUSES, 0);
        foreach ($cards as $c) {
            $t[$c['status']] = ($t[$c['status']] ?? 0) + 1;
        }
        $t['out_of_order'] = $outOfOrder;

        return $t;
    }

    /**
     * The real crew (Housekeeping dept via StaffRoster) with each one's LIVE open-task load — the count of
     * their pending/in_progress tasks in scope. No fabricated availability; the load is the real queue depth.
     *
     * @param  Collection<int, HousekeepingTask>|iterable<HousekeepingTask>  $tasks
     * @param  list<array<string, mixed>>  $searched  the cards that pass the search — what each person's chip will show
     * @return list<array<string, mixed>>
     */
    private function crew($tasks, ?string $crewFilter, array $searched = []): array
    {
        $shown = [];
        foreach ($searched as $c) {
            if ($c['assigned_to'] !== null) {
                $shown[(string) $c['assigned_to']] = ($shown[(string) $c['assigned_to']] ?? 0) + 1;
            }
        }

        $load = [];
        foreach ($tasks as $task) {
            if (in_array((string) $task->status, ['pending', 'in_progress'], true) && $task->assigned_to !== null) {
                $load[(string) $task->assigned_to] = ($load[(string) $task->assigned_to] ?? 0) + 1;
            }
        }

        return array_map(fn (array $hk): array => [
            'id' => $hk['id'],
            'name' => $hk['name'],
            'initials' => $hk['initials'],
            'load' => (int) ($load[$hk['id']] ?? 0),
            'count' => (int) ($shown[$hk['id']] ?? 0),
            'active' => $crewFilter === $hk['id'],
        ], app(StaffRoster::class)->housekeepers());
    }

    /**
     * The genuinely LEAST-LOADED housekeeper — what "Suggested" claims to mean (WAVE 3 tail).
     *
     * ⚠️ The assign picker badged and pre-selected `$i === 0`, i.e. whoever `StaffRoster::housekeepers()`
     * happened to return first (name order) — while the SAME row displayed that person's real open-task
     * load right beside it. A "suggestion" that ignores the number printed next to it is a false claim (#39).
     *
     * Computed rather than re-sorting `$crew`, because that list also drives the filter chips and the crew
     * panel: ordering those by load would make the filters shuffle as work is assigned. Ties break on the
     * existing order, so the pick is stable between renders.
     *
     * @param  list<array{id: string, name: string, initials: string, load: int, count: int, active: bool}>  $crew
     */
    private function suggestedHousekeeperId(array $crew): ?string
    {
        if ($crew === []) {
            return null;
        }

        $best = $crew[0];
        foreach ($crew as $member) {
            if ($member['load'] < $best['load']) {
                $best = $member;
            }
        }

        return (string) $best['id'];
    }

    /** Real out-of-order unit count in scope — the certified availability source (`condition_status`). */
    private function outOfOrderCount(array $readIds): int
    {
        return Unit::query()
            ->whereIn('property_id', $readIds)
            ->whereIn('condition_status', ['out_of_order', 'out_of_service'])
            ->count();
    }

    /**
     * unit_id → the status of a reservation ARRIVING TODAY on that unit (for the "guest waiting/arriving"
     * urgency). A reservation has no direct unit — the assigned unit lives on the `reservation_units` line — so
     * this reads the active lines checking in today. Cancelled/no-show excluded; checked_in beats confirmed.
     *
     * @return array<string, string>
     */
    private function todayArrivals(array $readIds): array
    {
        $lines = PropertyDay::arrivingToday($readIds);

        $map = [];
        foreach ($lines as $line) {
            // whereHas guarantees the reservation exists + it's eager-loaded.
            $status = (string) $line->reservation->status;
            $id = (string) $line->unit_id;
            // checked_in (guest physically here) outranks a not-yet-arrived confirmed booking.
            if (($map[$id] ?? null) !== 'checked_in') {
                $map[$id] = $status;
            }
        }

        return $map;
    }

    /**
     * taskId → the proof photos on the task (#88), batch-loaded once through the typed Media model (avoids an
     * N+1 and gives PHPStan the real type, unlike the generic `->media` relation).
     *
     * @param  Collection<int, HousekeepingTask>|iterable<HousekeepingTask>  $tasks
     * @return array<string, list<array{id: string, url: string}>>
     */
    private function photosForTasks($tasks): array
    {
        $ids = collect($tasks)->pluck('id')->all();
        if ($ids === []) {
            return [];
        }

        $media = Media::query()
            ->where('mediable_type', (new HousekeepingTask)->getMorphClass())
            ->whereIn('mediable_id', $ids)
            ->where('collection', 'hk_proof')
            ->orderBy('created_at')
            ->get();

        $map = [];
        foreach ($media as $m) {
            $map[(string) $m->getAttribute('mediable_id')][] = ['id' => (string) $m->getKey(), 'url' => (string) $m->url];
        }

        return $map;
    }

    /**
     * Map each task's unit+date to the guest ARRIVING on that unit that day (the stay the clean prepares for).
     * One batched query over the active reservation lines; a cancelled/no-show stay never sources a guest.
     *
     * @param  Collection<int, HousekeepingTask>|iterable<HousekeepingTask>  $tasks
     * @return array<string, Reservation> keyed "unitId|YYYY-MM-DD"
     */
    private function arrivalsForTasks($tasks): array
    {
        $unitIds = collect($tasks)->pluck('unit_id')->filter()->unique()->values()->all();
        if ($unitIds === []) {
            return [];
        }

        $lines = ReservationUnit::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->with(['reservation.leadGuest'])
            ->get();

        $map = [];
        foreach ($lines as $line) {
            $reservation = $line->reservation;
            if ($reservation === null) {
                continue;
            }
            if (in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true)) {
                continue;
            }
            // ⚠️ D8-2 — the RESERVATION is kept, not just its guest. This map is the ONE answer to "who
            // arrives at this unit on this date", and two things now read it: the guest's prefs/VIP (which
            // need a lead guest) and the ready-by deadline (which does not). Dropping guest-less rows here,
            // as this method used to, would have made a second reader disagree with the first — a booking
            // with no lead guest is still a real arrival with a real deadline (§N.8f).
            $map[$this->arrivalKey((string) $line->unit_id, (string) $reservation->check_in)] = $reservation;
        }

        return $map;
    }

    /**
     * D8-2 — when each property expects a guest at the door, keyed by property id. The board can span several
     * properties, so the deadline must come from the room's OWN property, never one shared figure.
     *
     * ⚠️ A NULL `check_in_time` STAYS NULL. An unset column means the property has not published a check-in
     * time, so there is no deadline to state — the row keeps its honest "—" rather than inheriting an
     * invented hour ({@see StayTime}, and D1-3's lesson on defaulted times).
     *
     * @param  list<string>  $propertyIds
     * @return array<string, string|null>
     */
    private function checkInTimes(array $propertyIds): array
    {
        if ($propertyIds === []) {
            return [];
        }

        return Property::query()
            ->whereIn('id', $propertyIds)
            ->pluck('check_in_time', 'id')
            ->map(fn ($time): ?string => StayTime::display($time, 'H:i'))   // the board's own 24h idiom
            ->all();
    }

    private function arrivalKey(string $unitId, string $date): string
    {
        return $unitId.'|'.CarbonImmutable::parse($date)->toDateString();
    }
}
