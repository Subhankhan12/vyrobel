<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\AuditEvent;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Media;
use App\Models\Membership;
use App\Models\Note;
use App\Models\OwnerExpense;
use App\Models\ReservationUnit;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Services\MaintenanceSettingsService;
use App\Support\Maintenance\MaintenanceSla;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Schedule\StaffRoster;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Work Order detail — the styled work order (#108 exact-match to "Operator Work Order.html"). The
 * detail of the SAME {@see MaintenanceTicket} model the Maintenance board lists (anti-drift; the board is
 * undisturbed). All facts, the activity timeline (real `audit_events` ∪ real ticket `notes`), the SLA clock
 * ({@see MaintenanceSla}, the ONE helper the board also reads), the out-of-order impact (real
 * `condition_status` + real affected bookings), and the cost destination (real `owner_expense`) are REAL and
 * tenant-scoped. The page READS; every mutation POSTs to the real {@see MaintenanceActionController} →
 * {@see MaintenanceService} (guarded, read-only-respecting, audited).
 *
 * RMB rule: `{ticket}` is a plain string prop resolved in render() via a tenant-scoped `findOrFail` (after
 * SetTenantForWebRequest) — a cross-org id 404s. Never a route-model-bound prop.
 *
 * Honesty (#39): NO fabricated fields. The wireframe's Area / sequential WO-#### / technician specialty /
 * "On site" presence / availability / resolution-outcome chips / "€X per night on hold" are NOT modelled and
 * are OMITTED (the reduced honest state), never invented. The affected-arrival, the SLA countdown, the OOS
 * block, and the cost are all real. Guest data on the OOS impact stays NAME-only-absent (a count, #28).
 */
#[Layout('components.layouts.operator')]
class MaintenanceTicketDetail extends Component
{
    use FormatsOperatorData;

    public string $ticketId;

    public function mount(string $ticket): void
    {
        $this->ticketId = $ticket;
    }

    public function render(): View
    {
        // Tenant-scoped: a cross-org (or unknown) id resolves to nothing → 404.
        $ticket = MaintenanceTicket::query()
            ->with(['unit.property', 'assignedTo', 'reportedBy'])
            ->findOrFail($this->ticketId);

        $unit = $ticket->unit;
        $status = (string) $ticket->status;
        $statusMeta = $this->maintenanceStatusMeta($status);
        $priorityMeta = $this->maintenancePriorityMeta((string) $ticket->priority);
        $now = CarbonImmutable::now();
        $openedAt = CarbonImmutable::parse((string) $ticket->opened_at);

        // The real active owner-expense for this ticket's cost (owned units only; absent = operator cost).
        $expense = OwnerExpense::query()
            ->with('owner')
            ->where('source_type', $ticket->getMorphClass())
            ->where('source_id', $ticket->getKey())
            ->whereNull('voided_at')
            ->first();

        // OOS integration — shown only when this ticket took the unit out AND it is genuinely OOS now.
        $isOos = $ticket->takes_out_of_service && $unit->condition_status === 'out_of_service';

        // Real affected bookings on the (blocked) unit: the total still physically assigned, and — for the
        // wireframe's "N arrival tonight needs a room" — the subset arriving today. Both are REAL counts.
        $bookingsToMove = $isOos ? ReservationUnit::query()->active()->where('unit_id', $unit->id)->count() : 0;
        $arrivalsToday = $isOos
            ? ReservationUnit::query()->active()->where('unit_id', $unit->id)->whereDate('check_in', $now->toDateString())->count()
            : 0;

        $currency = (string) $unit->property->currency;

        // #104 GATE 0 — the maintenance lifecycle routes are gated `maintenance.manage`; gate the CONTROLS on the
        // SAME permission so a role that can VIEW a ticket but not manage it never sees a 403-ing button.
        $canManage = (bool) auth()->user()?->can('maintenance.manage');

        // The SAME real SLA clock the board shows (MaintenanceSla over the property's configured targets).
        // ⚠️ WAVE 2 · #16 — via the SHARED accessor, which firstOrCreate()s the row so the schema defaults
        // (4/8/48/168h) actually apply. This used to be `first() ?? new MaintenanceSetting`, and an UNSAVED
        // Eloquent model carries no schema defaults — so every ticket on a fresh org read "No SLA target set"
        // while the org genuinely had targets. The signal was dead from install until someone opened Settings.
        $setting = app(MaintenanceSettingsService::class)->forOrg();
        $sla = MaintenanceSla::evaluate($ticket, $setting, $now);
        $deadline = $sla['deadline'];   // ?CarbonImmutable, from the shared SLA helper

        return view('livewire.maintenance-ticket-detail', [
            'viewer' => $this->viewer(),
            'canManage' => $canManage,
            // A paused/lapsed workspace is read-only — hide the write affordances (the controller enforces too).
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
            // translated here, at render time, keyed by the note kind CODE (the posted value)
            'noteKinds' => [
                ['key' => 'note', 'label' => __('php/livewire/maintenance_ticket_detail.kind_note')],
                ['key' => 'progress', 'label' => __('php/livewire/maintenance_ticket_detail.kind_progress')],
                ['key' => 'part', 'label' => __('php/livewire/maintenance_ticket_detail.kind_part')],
            ],
            'ticket' => [
                'id' => (string) $ticket->id,
                'title' => (string) $ticket->title,
                'description' => $ticket->description !== null ? (string) $ticket->description : null,
                'category' => $this->maintenanceCategoryLabel((string) $ticket->category),
                'priority_label' => $priorityMeta['label'],
                'priority_tone' => $priorityMeta['tone'],
                'status' => $status,
                'status_label' => $statusMeta['label'],
                'status_tone' => $statusMeta['tone'],
                'unit' => (string) $unit->code,
                'unit_name' => $unit->name !== null ? (string) $unit->name : null,
                'property_name' => (string) $unit->property->name,
                'reporter' => $ticket->reportedBy instanceof User ? (string) $ticket->reportedBy->name : null,
                'assignee' => $ticket->assignedTo instanceof User ? (string) $ticket->assignedTo->name : null,
                'assignee_id' => $ticket->assigned_to !== null ? (string) $ticket->assigned_to : null,
                'assignee_initials' => $ticket->assignedTo instanceof User ? $this->initials((string) $ticket->assignedTo->name) : null,
                'opened' => $openedAt->translatedFormat('j M Y · H:i'),
                'reported_day' => $openedAt->isToday() ? __('common.today') : $openedAt->translatedFormat('j M'),
                'reported_time' => $openedAt->format('H:i'),
                'open_for' => $this->elapsedLabel($openedAt, $now),
                'resolved' => $ticket->resolved_at?->translatedFormat('j M Y · H:i'),
                'labor_hours_label' => $ticket->labor_hours !== null ? $this->hoursLabel((float) $ticket->labor_hours) : null,
                'is_oos' => $isOos,
                'cost' => $ticket->cost_minor !== null ? $this->money((int) $ticket->cost_minor, (string) ($ticket->currency ?? $currency)) : null,
                // Real state → which transitions the service will accept (offer only what it allows).
                'can_start' => $canManage && $status === 'open',
                // open AND in progress resolve — as the board and MaintenanceService::resolve() allow (2026-10-05; this page alone
                // offered it only in progress)
                'can_resolve' => $canManage && in_array($status, ['open', 'in_progress'], true),
                'can_cancel' => $canManage && in_array($status, ['open', 'in_progress'], true),
                'can_assign' => $canManage && in_array($status, ['open', 'in_progress'], true),
                'is_active' => $ticket->isActive(),
                // #104 GATE 1d — awaiting-parts state (real columns; the editor below is its first UI).
                'awaiting_parts' => (bool) $ticket->awaiting_parts && $ticket->isActive(),
                'part_name' => $ticket->part_name !== null ? (string) $ticket->part_name : null,
                'part_eta' => $ticket->part_eta?->format('Y-m-d'),
                'part_eta_label' => $ticket->part_eta?->translatedFormat('j M Y'),
                'can_parts' => $canManage && $ticket->isActive(),
            ],
            'sla' => [
                'state' => $sla['state'],
                'has_target' => $sla['has_target'],
                'breached' => $sla['breached'],
                'due_soon' => $sla['due_soon'],
                'target_hours' => $sla['target_hours'],
                'label' => $this->slaLabel($sla),
                // "Same-day target" only for a short (≤12h) SLA — a 24h target spans into the next day.
                'same_day' => is_int($sla['target_hours']) && $sla['target_hours'] <= 12,
                'due_by' => $deadline?->isSameDay($now) === true ? $deadline->format('H:i') : $deadline?->translatedFormat('j M · H:i'),
                'reported_time' => $openedAt->format('H:i'),
                'progress_pct' => $this->slaProgress($openedAt, $now, is_int($sla['target_hours']) ? $sla['target_hours'] : null),
            ],
            'currencySymbol' => $this->currencySymbol($currency),
            'timeline' => $this->timeline($ticket),
            'photos' => $this->photos($ticket),
            'oos' => [
                'active' => $isOos,
                'bookings_to_move' => $bookingsToMove,
                'arrivals_today' => $arrivalsToday,
                'impact_label' => $this->oosImpactLabel($arrivalsToday, $bookingsToMove),
            ],
            'expense' => $expense !== null ? [
                'owner' => (string) ($expense->owner->name ?? __('php/livewire/maintenance_ticket_detail.the_owner')),
                'amount' => $this->money((int) $expense->amount_minor, (string) $expense->currency),
                'deductible' => (bool) $expense->deductible,
            ] : null,
            'staff' => $this->reassignStaff($ticket),
        ]);
    }

    /**
     * The ticket's activity timeline — REAL `audit_events` (lifecycle transitions) MERGED with the ticket's
     * real `notes` (the Activity composer), ordered by time. Each entry carries a per-type dot/ring colour
     * (a presentation map over the real action/kind) + an optional note body. Nothing is invented.
     *
     * @return list<array{label: string, time: string, actor: string, note: string|null, has_note: bool, dot: string, ring: string, show_line: bool}>
     */
    private function timeline(MaintenanceTicket $ticket): array
    {
        // Lifecycle events — the note_added action is excluded (the note itself renders from the notes store).
        $events = AuditEvent::query()
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->getKey())
            ->where('action', 'like', 'maintenance_ticket.%')
            ->where('action', '!=', 'maintenance_ticket.note_added')
            ->orderBy('created_at')
            ->get();

        $userIds = $events->pluck('actor_id')->filter()->all();
        foreach ($events as $event) {
            $after = is_array($event->after) ? $event->after : [];
            if (isset($after['assigned_to'])) {
                $userIds[] = (string) $after['assigned_to'];
            }
        }
        /** @var array<string, string> $names */
        $names = User::query()->whereIn('id', array_values(array_unique($userIds)))->pluck('name', 'id')->all();

        /** @var Collection<int, array{sort: string, label: string, time: string, actor: string, note: string|null, has_note: bool, dot: string, ring: string}> $rows */
        $rows = $events->map(function (AuditEvent $event) use ($names): array {
            $action = (string) $event->action;
            $after = is_array($event->after) ? $event->after : [];

            // translated here, at render time, keyed by the audit action CODE
            $label = match ($action) {
                'maintenance_ticket.opened' => __('php/livewire/maintenance_ticket_detail.timeline_opened'),
                'maintenance_ticket.assigned' => isset($after['assigned_to'])
                    ? __('php/livewire/maintenance_ticket_detail.timeline_assigned_to', ['name' => $names[(string) $after['assigned_to']] ?? __('php/livewire/maintenance_ticket_detail.a_technician')])
                    : __('php/livewire/maintenance_ticket_detail.timeline_unassigned'),
                'maintenance_ticket.started' => __('php/livewire/maintenance_ticket_detail.timeline_started'),
                'maintenance_ticket.resolved' => __('php/livewire/maintenance_ticket_detail.timeline_resolved'),
                'maintenance_ticket.cancelled' => __('php/livewire/maintenance_ticket_detail.timeline_cancelled'),
                'maintenance_ticket.parts_updated' => __('php/livewire/maintenance_ticket_detail.timeline_parts_updated'),
                'maintenance_ticket.cost_corrected' => __('php/livewire/maintenance_ticket_detail.timeline_cost_corrected'),
                'maintenance_ticket.updated' => __('php/livewire/maintenance_ticket_detail.timeline_updated'),
                default => ucwords(str_replace(['maintenance_ticket.', '_'], ['', ' '], $action)),
            };

            [$dot, $ring] = $this->eventColour($action);
            $at = $event->created_at;   // Illuminate\Support\Carbon|null

            return [
                'sort' => $at?->format('Y-m-d H:i:s.u') ?? '',
                'label' => $label,
                'time' => $at?->translatedFormat('j M · H:i') ?? '',
                'actor' => $event->actor_id !== null
                    ? ($names[(string) $event->actor_id] ?? __('php/livewire/maintenance_ticket_detail.a_team_member'))
                    : ($event->actor_type === 'system' ? __('php/livewire/maintenance_ticket_detail.system') : __('php/livewire/maintenance_ticket_detail.a_team_member')),
                'note' => null,
                'has_note' => false,
                'dot' => $dot,
                'ring' => $ring,
            ];
        });

        // Real composer notes on the ticket.
        $notes = $ticket->notes()->with('author')->orderBy('created_at')->get()
            ->map(function (Note $note): array {
                $kind = (string) ($note->kind ?? 'note');
                [$dot, $ring] = $this->noteColour($kind);
                $at = $note->created_at;   // Illuminate\Support\Carbon|null

                return [
                    'sort' => $at?->format('Y-m-d H:i:s.u') ?? '',
                    'label' => match ($kind) {
                        'progress' => __('php/livewire/maintenance_ticket_detail.note_progress'),
                        'part' => __('php/livewire/maintenance_ticket_detail.note_part'),
                        default => __('php/livewire/maintenance_ticket_detail.note_added'),
                    },
                    'time' => $at?->translatedFormat('j M · H:i') ?? '',
                    'actor' => (string) ($note->author->name ?? __('php/livewire/maintenance_ticket_detail.a_team_member')),
                    'note' => (string) $note->body,
                    'has_note' => true,
                    'dot' => $dot,
                    'ring' => $ring,
                ];
            });

        $merged = $rows->concat($notes)->sortBy('sort')->values();
        $last = $merged->count() - 1;

        return $merged->map(fn (array $row, int $i): array => [
            'label' => $row['label'],
            'time' => $row['time'],
            'actor' => $row['actor'],
            'note' => $row['note'],
            'has_note' => $row['has_note'],
            'dot' => $row['dot'],
            'ring' => $row['ring'],
            'show_line' => $i < $last,
        ])->all();
    }

    /**
     * The ticket's photos (real Media, `mt_photo` collection) — surfaced on the detail's Reported-issue card.
     *
     * @return list<array{id: string, url: string, alt: string}>
     */
    private function photos(MaintenanceTicket $ticket): array
    {
        return $ticket->media()
            ->where('collection', 'mt_photo')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Media $m): array => ['id' => (string) $m->id, 'url' => (string) $m->url, 'alt' => (string) ($m->alt ?? __('php/livewire/maintenance_ticket_detail.photo_alt'))])
            ->all();
    }

    /**
     * The active staff of the current org for the Reassign picker, each with a REAL open-work load (open /
     * in-progress tickets assigned to them, and how many are urgent) — never a fabricated availability.
     *
     * @return list<array{id: string, name: string, initials: string, load: string, is_current: bool}>
     */
    private function reassignStaff(MaintenanceTicket $ticket): array
    {
        $orgId = app(TenantContext::class)->id();
        if ($orgId === null) {
            return [];
        }

        // ⚠️ STAFF ONLY, via the ONE shared definition. This used to select by active Membership alone, which
        // offered External Owners in the Reassign picker — a Membership is NOT operator access (owners hold
        // one; it is how their org resolves). See {@see StaffRoster::staffUserIds()}.
        $memberIds = app(StaffRoster::class)->staffUserIds();

        // Real open-load per member: active tickets assigned to them + the urgent subset (one grouped query).
        $open = MaintenanceTicket::query()
            ->whereIn('assigned_to', $memberIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->get(['assigned_to', 'priority']);
        /** @var array<string, array{open: int, urgent: int}> $load */
        $load = [];
        foreach ($open as $row) {
            $id = (string) $row->assigned_to;
            $load[$id] ??= ['open' => 0, 'urgent' => 0];
            $load[$id]['open']++;
            if ((string) $row->priority === 'urgent') {
                $load[$id]['urgent']++;
            }
        }

        $currentId = $ticket->assigned_to !== null ? (string) $ticket->assigned_to : null;

        return User::query()
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($load, $currentId): array {
                $id = (string) $u->id;
                $l = $load[$id] ?? ['open' => 0, 'urgent' => 0];
                $label = $l['open'] === 0
                    ? __('php/livewire/maintenance_ticket_detail.load_none')
                    : ($l['urgent'] > 0
                        ? __('php/livewire/maintenance_ticket_detail.load_open_urgent', ['open' => $l['open'], 'urgent' => $l['urgent']])
                        : __('php/livewire/maintenance_ticket_detail.load_open', ['open' => $l['open']]));

                return [
                    'id' => $id,
                    'name' => (string) $u->name,
                    'initials' => $this->initials((string) $u->name),
                    'load' => $label,
                    'is_current' => $id === $currentId,
                ];
            })
            ->all();
    }

    /** The wireframe's OOS-impact headline, from REAL counts (arrivals tonight preferred, else bookings on the unit). */
    private function oosImpactLabel(int $arrivalsToday, int $bookingsToMove): string
    {
        if ($arrivalsToday > 0) {
            return trans_choice('php/livewire/maintenance_ticket_detail.impact_arrivals', $arrivalsToday, ['count' => $arrivalsToday]);
        }
        if ($bookingsToMove > 0) {
            return trans_choice('php/livewire/maintenance_ticket_detail.impact_bookings', $bookingsToMove, ['count' => $bookingsToMove]);
        }

        return __('php/livewire/maintenance_ticket_detail.impact_none');
    }

    /** Elapsed label "2h 03m" / "3d 4h" / "12m" between two instants. */
    private function elapsedLabel(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $minutes = max(0, (int) $from->diffInMinutes($to));
        if ($minutes < 60) {
            return __('php/livewire/maintenance_ticket_detail.elapsed_minutes', ['minutes' => $minutes]);
        }
        if ($minutes < 1440) {
            return __('php/livewire/maintenance_ticket_detail.elapsed_hours', ['hours' => intdiv($minutes, 60), 'minutes' => str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT)]);
        }

        return __('php/livewire/maintenance_ticket_detail.elapsed_days', ['days' => intdiv($minutes, 1440), 'hours' => intdiv($minutes % 1440, 60)]);
    }

    /** "1.5h" / "2h" — a labour-hours label (trailing zeros trimmed). */
    private function hoursLabel(float $hours): string
    {
        return __('php/livewire/maintenance_ticket_detail.hours_short', ['hours' => rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.')]);
    }

    /** SLA progress % (elapsed / target), clamped 0–100 — for the timing bar. */
    private function slaProgress(CarbonImmutable $openedAt, CarbonImmutable $now, ?int $targetHours): int
    {
        if ($targetHours === null || $targetHours <= 0) {
            return 0;
        }
        $elapsed = (float) $openedAt->diffInMinutes($now) / 60.0;

        return (int) round(max(0.0, min(100.0, $elapsed / $targetHours * 100.0)));
    }

    /** @return array{0: string, 1: string} dot + ring colour for a lifecycle action. */
    private function eventColour(string $action): array
    {
        return match ($action) {
            'maintenance_ticket.opened' => ['#C2410C', 'rgba(217,102,46,.14)'],
            'maintenance_ticket.started' => ['#E8703A', 'rgba(232,112,58,.16)'],
            'maintenance_ticket.resolved' => ['#3B9E6E', 'rgba(59,158,110,.16)'],
            'maintenance_ticket.parts_updated' => ['#C79A3E', 'rgba(217,154,61,.16)'],
            default => ['rgba(120,70,40,.40)', 'rgba(120,70,40,.10)'],
        };
    }

    /** @return array{0: string, 1: string} dot + ring colour for a composer note kind. */
    private function noteColour(string $kind): array
    {
        return match ($kind) {
            'progress' => ['#E8703A', 'rgba(232,112,58,.16)'],
            'part' => ['#C79A3E', 'rgba(217,154,61,.16)'],
            default => ['rgba(120,70,40,.40)', 'rgba(120,70,40,.10)'],
        };
    }
}
