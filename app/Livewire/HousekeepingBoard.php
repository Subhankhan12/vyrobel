<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\HousekeepingActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\ReservationUnit;
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
        'pending' => [['label' => 'Start', 'to' => 'in_progress'], ['label' => 'Mark clean', 'to' => 'clean']],
        'in_progress' => [['label' => 'Mark clean', 'to' => 'clean']],
        'clean' => [['label' => 'Mark inspected', 'to' => 'inspected']],
        'inspected' => [],
    ];

    public function render(): View
    {
        /** @var array<string, list<array<string, mixed>>> $grouped */
        $grouped = array_fill_keys(self::STATUSES, []);
        $summary = array_fill_keys(self::STATUSES, 0);
        $high = 0;
        $total = 0;

        // Phase 6a · multi-property: the board shows the current property (or every accessible property
        // when "all"). Pre-6a it listed housekeeping org-wide, blending properties in a multi-property org.
        $tasks = HousekeepingTask::query()
            ->whereIn('property_id', $this->currentReadIds)
            ->with(['unit', 'assignedTo'])
            ->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('date')
            ->get();

        // #87 — the guest each task is prepping for (the reservation ARRIVING on the task's unit that day), so
        // housekeeping sees the incoming guest's VIP flag + stay preferences. One batched read; name-only is
        // irrelevant here (VIP/prefs are operational flags, not contact PII — nothing decrypts).
        $arrivalGuests = $this->arrivalGuestsForTasks($tasks);

        foreach ($tasks as $task) {
            $status = (string) $task->status;
            if (! array_key_exists($status, $grouped)) {
                continue;   // ignore any out-of-enum row rather than invent a column
            }

            $grouped[$status][] = $this->card($task, $arrivalGuests);
            $summary[$status]++;
            $total++;
            if ($task->priority === 'high') {
                $high++;
            }
        }

        $columns = [];
        foreach (self::STATUSES as $status) {
            $meta = $this->housekeepingStatusMeta($status);
            $columns[] = [
                'key' => $status,
                'label' => $meta['label'],
                'tone' => $meta['tone'],
                'tasks' => $grouped[$status],
            ];
        }

        return view('livewire.housekeeping-board', [
            'viewer' => $this->viewer(),
            'columns' => $columns,
            'summary' => $summary + ['high' => $high, 'total' => $total],
            // Batch C · GATE 1 — the REAL assignable housekeepers (Housekeeping dept only, StaffRoster) for
            // the row picker + whether the viewer may assign (the route is `housekeeping.manage`-gated too).
            'housekeepers' => app(StaffRoster::class)->housekeepers(),
            'canAssign' => auth()->user()?->can('housekeeping.manage') ?? false,
        ]);
    }

    /**
     * @param  array<string, Guest>  $arrivalGuests  keyed "unitId|YYYY-MM-DD" → the guest arriving that day
     * @return array<string, mixed>
     */
    private function card(HousekeepingTask $task, array $arrivalGuests = []): array
    {
        $assignee = $task->assignedTo;
        $priority = $this->priorityMeta((string) $task->priority);

        $guest = $arrivalGuests[$this->arrivalKey((string) $task->unit_id, (string) $task->date)] ?? null;

        return [
            'id' => $task->id,
            'unit' => (string) ($task->unit->name ?? $task->unit->code),
            'type' => $this->housekeepingTypeLabel((string) $task->type),
            'priority_label' => $priority['label'],
            'priority_tone' => $priority['tone'],
            'date' => CarbonImmutable::parse($task->date)->format('j M'),
            'assignee' => $assignee instanceof User ? (string) $assignee->name : null,
            'next' => self::NEXT_ACTIONS[$task->status],
            // #87 — the incoming guest's VIP flag + preferences, so the room is prepped right. Name-free.
            'guest_vip' => $guest !== null && (bool) $guest->is_vip,
            'guest_preferences' => $guest !== null ? $guest->preferenceLabels() : [],
        ];
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
