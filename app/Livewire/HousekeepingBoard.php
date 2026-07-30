<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\HousekeepingActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\Media;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Models\User;
use App\Services\HousekeepingService;
use App\Support\Reservations\ReservationStage;
use App\Support\Schedule\StaffRoster;
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

    /** Forward status moves offered per column (label + target), all through the real enum. */
    private const NEXT_ACTIONS = [
        'pending' => [['label' => 'Start', 'to' => 'in_progress']],
        'in_progress' => [['label' => 'Mark clean', 'to' => 'clean']],
        'clean' => [['label' => 'Inspect', 'to' => 'inspected']],
        'inspected' => [],
    ];

    /** Expected clean minutes per turnover type (#88) — the board's elapsed bar denominator. A documented
     * operational constant (not a fabricated per-room estimate): departure turnover is the long one. */
    private const EXPECTED_MINUTES = ['departure' => 45, 'deep' => 90, 'stayover' => 30, 'arrival_prep' => 20];

    public function render(): View
    {
        $readIds = $this->currentReadIds;
        $now = CarbonImmutable::now();
        $today = CarbonImmutable::today();

        $crewFilter = is_string(request()->query('crew')) ? (string) request()->query('crew') : null;
        $search = mb_substr(trim((string) request()->query('q', '')), 0, 80);

        $tasks = HousekeepingTask::query()
            ->whereIn('property_id', $readIds)
            ->with(['unit', 'assignedTo', 'media'])
            ->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('date')
            ->get();

        $arrivalGuests = $this->arrivalGuestsForTasks($tasks);   // #87 prefs/VIP
        $todayArrivals = $this->todayArrivals($readIds, $today);  // unit_id → arriving reservation status (urgency)
        $photos = $this->photosForTasks($tasks);                  // taskId → list of proof photos

        // Every card (unfiltered) — the tallies + crew load must read the whole workspace, not the filtered view.
        $allCards = $tasks->map(fn (HousekeepingTask $t): array => $this->card($t, $arrivalGuests, $todayArrivals, $photos, $now))->all();

        $outOfOrder = $this->outOfOrderCount($readIds);
        $crew = $this->crew($tasks, $crewFilter);

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
            'outOfOrder' => $outOfOrder,
            'crewFilter' => $crewFilter,
            'query' => $search,
            'detail' => $detail,
            'unassignedCount' => count(array_filter($allCards, fn (array $c): bool => $c['assigned_to'] === null && in_array($c['status'], ['pending', 'in_progress'], true))),
            'housekeepers' => app(StaffRoster::class)->housekeepers(),
            'canAssign' => auth()->user()?->can('housekeeping.manage') ?? false,
        ]);
    }

    /**
     * @param  array<string, Guest>  $arrivalGuests  keyed "unitId|YYYY-MM-DD" → the guest arriving that day
     * @param  array<string, string>  $todayArrivals  unit_id → the arriving reservation's status (for urgency)
     * @param  array<string, list<array{id: string, url: string}>>  $photos  taskId → proof photos
     * @return array<string, mixed>
     */
    private function card(HousekeepingTask $task, array $arrivalGuests, array $todayArrivals, array $photos, CarbonImmutable $now): array
    {
        $assignee = $task->assignedTo;
        $priority = $this->priorityMeta((string) $task->priority);
        $status = (string) $task->status;
        $type = (string) $task->type;

        $guest = $arrivalGuests[$this->arrivalKey((string) $task->unit_id, (string) $task->date)] ?? null;

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
                ? ['tone' => 'red', 'label' => 'Guest waiting']
                : ['tone' => 'amber', 'label' => 'Guest arriving today'];
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
            'date' => CarbonImmutable::parse($task->date)->format('j M'),
            'assignee' => $assignee instanceof User ? (string) $assignee->name : null,
            'assignee_initials' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
            'assigned_to' => $assignee instanceof User ? (string) $assignee->getKey() : null,
            'urgency' => $urgency,
            'elapsed' => $elapsed,
            'started_label' => $task->started_at !== null ? CarbonImmutable::parse($task->started_at)->format('H:i') : null,
            'completed_label' => $task->completed_at !== null ? CarbonImmutable::parse($task->completed_at)->format('H:i') : null,
            'photos' => $photos[(string) $task->id] ?? [],
            'next' => self::NEXT_ACTIONS[$status],
            // #87 — the incoming guest's VIP flag + preferences, so the room is prepped right. Name-free.
            'guest_vip' => $guest !== null && (bool) $guest->is_vip,
            'guest_preferences' => $guest !== null ? $guest->preferenceLabels() : [],
        ];
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
     * @return list<array<string, mixed>>
     */
    private function crew($tasks, ?string $crewFilter): array
    {
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
            'active' => $crewFilter === $hk['id'],
        ], app(StaffRoster::class)->housekeepers());
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
    private function todayArrivals(array $readIds, CarbonImmutable $today): array
    {
        $lines = ReservationUnit::query()
            ->where('status', 'active')
            ->whereNotNull('unit_id')
            ->whereDate('check_in', $today->toDateString())
            ->whereHas('reservation', fn ($q) => $q->whereIn('property_id', $readIds)->whereNotIn('status', ['cancelled', 'no_show']))
            ->with('reservation:id,status')
            ->get(['id', 'unit_id', 'reservation_id', 'check_in', 'status']);

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
     * @return array<string, Guest>
     */
    private function arrivalGuestsForTasks($tasks): array
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
            $guest = $reservation?->leadGuest;
            if ($reservation === null || $guest === null) {
                continue;
            }
            if (in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true)) {
                continue;
            }
            $map[$this->arrivalKey((string) $line->unit_id, (string) $reservation->check_in)] = $guest;
        }

        return $map;
    }

    private function arrivalKey(string $unitId, string $date): string
    {
        return $unitId.'|'.CarbonImmutable::parse($date)->toDateString();
    }
}
