<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\MaintenanceActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\AuditEvent;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Media;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Services\MaintenanceSettingsService;
use App\Support\Maintenance\MaintenanceListParams;
use App\Support\Maintenance\MaintenanceSla;
use App\Support\Maintenance\RevenueOnHold;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Schedule\StaffRoster;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator MAINTENANCE board (#89 exact-match; the table rebuilt 2026-09-29) — the work-order list over the real
 * {@see MaintenanceTicket} (the SAME model the Dashboard #84 reads for out-of-order + on-hold revenue; undisturbed). READ
 * view; every mutation is a guarded POST → {@see MaintenanceActionController} → the certified {@see MaintenanceService}.
 * Real KPIs (Open/Urgent/Awaiting-parts/Out-of-order/Resolved-7d), a real SLA target per row ({@see MaintenanceSla}), a
 * technicians panel with real load, a parts-on-order panel, and the out-of-order hero with real revenue-on-hold
 * ({@see RevenueOnHold}).
 *
 * TENANT + property scoped (tickets hang off a unit → scoped through `unit.property_id ∈ currentReadIds`).
 *
 * THE WINDOW. The board shows every OPEN work order (open or in progress, whatever its age) and those RESOLVED in the
 * last 7 days — older history lives in the costs report, and a cancelled one is off the board. The footer says exactly
 * that (it used to say "from the last 7 days" while listing open work of any age).
 *
 * THE TABLE (2026-09-29). The window is small and already loaded whole, so the filter, the search, the SORT and the PAGE
 * are applied here in PHP — which is what lets "time to target" sort by the very deadline {@see MaintenanceSla} computes
 * for the column, so the sorted column cannot read out of order. Every setting rides the URL (#20), read through the ONE
 * {@see MaintenanceListParams}.
 *
 * @phpstan-type BoardState array{filter: ?string, q: string, sort: 'priority'|'due'|'reported'|'unit', dir: 'asc'|'desc', per: int, page: int}
 */
#[Layout('components.layouts.operator')]
class MaintenanceList extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    private const CATEGORIES = ['plumbing', 'electrical', 'appliance', 'structural', 'other'];

    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    /** Priority, most pressing first — the order the board has always shown by default. */
    private const PRIORITY_RANK = ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];

    /** One tile icon per category (inner SVG paths for the choice tiles). */
    private const CATEGORY_ICONS = [
        'plumbing' => '<path d="M12 3s6 6.3 6 10.5A6 6 0 0 1 6 13.5C6 9.3 12 3 12 3z"/>',
        'electrical' => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
        'appliance' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M5 9h14M9 6h.01"/>',
        'structural' => '<path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>',
        'other' => '<circle cx="6" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="18" cy="12" r="1.3"/>',
    ];

    public function render(): View
    {
        $readIds = $this->currentReadIds;
        $now = CarbonImmutable::now();
        $today = $now->toDateString();
        $state = $this->state();
        // ⚠️ WAVE 2 · #16 — via the SHARED accessor, which firstOrCreate()s the row so the schema defaults
        // (4/8/48/168h) actually apply. An UNSAVED model carries no schema defaults — every ticket on a fresh org
        // read "No SLA target set" while the org genuinely had targets.
        $setting = app(MaintenanceSettingsService::class)->forOrg();

        // The window the board shows: active tickets + those resolved in the last 7 days (older → the costs report).
        $tickets = MaintenanceTicket::query()
            ->whereHas('unit', fn (Builder $q) => $q->whereIn('property_id', $readIds))
            ->with(['unit', 'assignedTo', 'reportedBy'])
            ->where(function (Builder $q) use ($now): void {
                $q->whereIn('status', ['open', 'in_progress'])
                    ->orWhere(fn (Builder $r) => $r->where('status', 'resolved')->where('resolved_at', '>=', $now->subDays(7)));
            })
            ->get();

        $photos = $this->photosForTickets($tickets);
        // ⚠️ RESOLVED BEFORE THE CARDS: `maintenance_tickets.currency` is filled at resolve, so an unresolved ticket has
        // none — the property's currency is the real answer (never a hardcoded 'EUR').
        $pageCurrency = $this->currency($readIds);
        $canManage = (bool) auth()->user()?->can('maintenance.manage');
        $canWrite = $canManage && ! app(OperatorWorkspace::class)->isReadOnly();
        $allCards = $tickets->map(fn (MaintenanceTicket $t): array => $this->card($t, $setting, $photos, $now, $pageCurrency, $canWrite))->all();

        // OOS units (real condition_status) + the real revenue on hold.
        $oosUnits = Unit::query()->whereIn('property_id', $readIds)->where('condition_status', 'out_of_service')->orderBy('code')->get();
        $onHold = RevenueOnHold::forUnits($oosUnits->pluck('id')->map(fn ($id): string => (string) $id)->all(), $today);

        $filtered = array_values(array_filter($allCards, fn (array $c): bool => $this->matches($c, $state['filter'], $state['q'])));
        $rows = $this->page($this->sorted($filtered, $state['sort'], $state['dir']), $state);

        // The detail drawer target (?ticket=), from the UNFILTERED set, with its real activity timeline.
        $detailId = is_string(request()->query('ticket')) ? (string) request()->query('ticket') : null;
        $detail = null;
        if ($detailId !== null) {
            $found = collect($allCards)->firstWhere('id', $detailId);
            if ($found !== null) {
                $found['activity'] = $this->activity($detailId);
                $detail = $found;
            }
        }

        return view('livewire.maintenance-list', [
            'viewer' => $this->viewer(),
            'rows' => $rows,
            // each row on this page, once, by id — the row menu and the drawer read it (the page script's `row()`)
            'rowsById' => collect($rows->items())->keyBy('id')->all(),
            'windowTotal' => count($allCards),
            'kpis' => $this->kpis($allCards, $oosUnits->count()),
            'technicians' => $this->technicians($allCards),
            'partsOnOrder' => array_values(array_filter($allCards, fn (array $c): bool => $c['awaiting_parts'])),
            'oosHero' => [
                'count' => $oosUnits->count(),
                'units' => $oosUnits->map(fn (Unit $u): string => (string) $u->code)->implode(' · '),
                'on_hold' => $onHold > 0 ? $this->money($onHold, $pageCurrency) : null,
            ],
            'filter' => $state['filter'],
            'query' => $state['q'],
            'keep' => array_diff_key(MaintenanceListParams::clean($this->params($state)), ['q' => true]),
            'chips' => $this->chips($allCards, $state),
            'sorts' => $this->sortLinks($state),
            'sizes' => $this->sizeLinks($state),
            'pages' => $this->pageLinks($state, $rows),
            'sortedBy' => $this->sortedBy($state),
            'unitOptions' => $this->unitOptions($readIds),
            'staff' => array_map(fn (array $m): array => $m + ['initials' => $this->initials($m['name'])], $this->orgStaff()),
            'categoryChoices' => array_map(fn (string $c): array => ['value' => $c, 'label' => $this->maintenanceCategoryLabel($c), 'icon' => self::CATEGORY_ICONS[$c]], self::CATEGORIES),
            'priorityChoices' => array_map(fn (string $p): array => ['value' => $p, 'label' => $this->maintenancePriorityMeta($p)['label']], self::PRIORITIES),
            'canManage' => $canManage,
            'canWrite' => $canWrite,
            // The page script's start: the action URLs (the id fills `__ID__`), the search as loaded, the drawer to open,
            // and an Edit the server just refused, to reopen as it was typed.
            'boot' => [
                'urls' => [
                    'show' => route('app.maintenance.show', ['ticket' => '__ID__']),
                    'edit' => route('app.maintenance.update', ['ticket' => '__ID__']),
                    'assign' => route('app.maintenance.assign', ['ticket' => '__ID__']),
                    'start' => route('app.maintenance.start', ['ticket' => '__ID__']),
                    'resolve' => route('app.maintenance.resolve', ['ticket' => '__ID__']),
                    'cancel' => route('app.maintenance.cancel', ['ticket' => '__ID__']),
                    'photo' => route('app.maintenance.photos.store', ['ticket' => '__ID__']),
                    'bulkAssign' => route('app.maintenance.bulk.assign'),
                    'bulkResolve' => route('app.maintenance.bulk.resolve'),
                ],
                'q' => $state['q'],
                'detail' => $detail,
                'retry' => $canWrite ? $this->editRetry() : null,
            ],
        ]);
    }

    // ── The board's settings ─────────────────────────────────────────────────────────────────────

    /** @return BoardState */
    private function state(): array
    {
        $clean = MaintenanceListParams::clean((array) request()->query());
        $sort = $clean['sort'] ?? 'priority';

        return [
            'filter' => $clean['filter'] ?? null,
            'q' => $clean['q'] ?? '',
            'sort' => $sort,
            'dir' => $clean['dir'] ?? MaintenanceListParams::SORTS[$sort],
            'per' => $clean['per'] ?? MaintenanceListParams::PER_PAGE,
            'page' => $clean['page'] ?? 1,
        ];
    }

    /**
     * @param  BoardState  $state
     * @return array<string, string|int|null>
     */
    private function params(array $state): array
    {
        return ['filter' => $state['filter'], 'q' => $state['q'], 'sort' => $state['sort'], 'dir' => $state['dir'], 'per' => $state['per']];
    }

    /**
     * A link to the board with some settings changed and every other one kept — and back to page 1.
     *
     * @param  BoardState  $state
     * @param  array<string, string|int|null>  $changes
     */
    private function href(array $state, array $changes): string
    {
        return MaintenanceListParams::url(array_merge($this->params($state), $changes));
    }

    // ── One row ──────────────────────────────────────────────────────────────────────────────────

    /**
     * @param  array<string, list<array{id: string, url: string}>>  $photos
     * @return array<string, mixed>
     */
    private function card(MaintenanceTicket $ticket, MaintenanceSetting $setting, array $photos, CarbonImmutable $now, string $pageCurrency, bool $canWrite): array
    {
        $assignee = $ticket->assignedTo;
        $priority = $this->maintenancePriorityMeta((string) $ticket->priority);
        $status = (string) $ticket->status;
        $unit = $ticket->unit;
        $oos = $ticket->takes_out_of_service && $unit !== null && $unit->condition_status === 'out_of_service';
        $sla = MaintenanceSla::evaluate($ticket, $setting, $now);
        $currency = (string) ($ticket->currency ?? $pageCurrency);
        $opened = CarbonImmutable::parse($ticket->opened_at);

        return [
            'id' => (string) $ticket->id,
            'title' => (string) $ticket->title,
            'description' => (string) ($ticket->description ?? ''),
            'unit' => (string) ($unit->code ?? '—'),
            'category' => $this->maintenanceCategoryLabel((string) $ticket->category),
            'category_value' => (string) $ticket->category,
            'priority' => (string) $ticket->priority,
            'priority_label' => $priority['label'],
            'priority_tone' => $priority['tone'],
            'status' => $status,
            'status_meta' => $this->maintenanceStatusMeta($status),
            'assignee' => $assignee instanceof User ? (string) $assignee->name : null,
            'assignee_initials' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
            'assignee_id' => $ticket->assigned_to !== null ? (string) $ticket->assigned_to : null,
            // who reported it (a member of staff, never a guest) — Mark resolved offers to tell them, as the work order page does
            'reporter' => $ticket->reportedBy instanceof User ? (string) $ticket->reportedBy->name : null,
            'oos' => $oos,
            'is_urgent' => $ticket->priority === 'urgent',
            'awaiting_parts' => (bool) $ticket->awaiting_parts && $ticket->isActive(),
            'part_name' => $ticket->part_name !== null ? (string) $ticket->part_name : null,
            'part_eta' => $ticket->part_eta?->format('j M'),
            'sla' => [
                'state' => $sla['state'],
                'breached' => $sla['breached'],
                'due_soon' => $sla['due_soon'],
                'label' => $this->slaLabel($sla),
            ],
            // The sort keys — the SAME values the row shows, so a sorted column cannot read out of order.
            'deadline_ts' => $sla['deadline'] !== null ? $sla['deadline']->getTimestamp() : null,
            'opened_ts' => $opened->getTimestamp(),
            'reported_ago' => $opened->diffForHumans(),
            'currency' => $currency,
            'cost' => $ticket->cost_minor !== null ? $this->money((int) $ticket->cost_minor, $currency) : null,
            'labor' => $ticket->labor_minor !== null ? $this->money((int) $ticket->labor_minor, $currency) : null,
            'parts_cost' => $ticket->parts_minor !== null ? $this->money((int) $ticket->parts_minor, $currency) : null,
            'resolved' => $ticket->resolved_at?->format('j M'),
            'photos' => $photos[(string) $ticket->id] ?? [],
            // What this viewer may do to THIS ticket, in a fixed order. Nothing that writes on a closed ticket — the old
            // board offered Assign on a resolved one, which the service refuses.
            'actions' => $this->actions($status, $canWrite),
        ];
    }

    /** @return list<string> */
    private function actions(string $status, bool $canWrite): array
    {
        if (! $canWrite || ! in_array($status, ['open', 'in_progress'], true)) {
            return [];
        }

        return $status === 'open'
            ? ['edit', 'assign', 'start', 'resolve', 'cancel']
            : ['edit', 'assign', 'resolve', 'cancel'];
    }

    private function matches(array $card, ?string $filter, string $search): bool
    {
        $active = in_array($card['status'], ['open', 'in_progress'], true);
        $ok = match ($filter) {
            'active' => $active,
            'open' => $card['status'] === 'open',
            'inprog' => $card['status'] === 'in_progress',
            'urgent' => $active && $card['is_urgent'],
            'parts' => $card['awaiting_parts'],
            'ooo' => $card['oos'],
            'done' => $card['status'] === 'resolved',
            default => true,
        };
        if (! $ok) {
            return false;
        }
        if ($search !== '') {
            $hay = mb_strtolower($card['title'].' '.$card['unit'].' '.$card['category'].' '.((string) $card['assignee']));
            if (! str_contains($hay, mb_strtolower($search))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Order the rows (2026-09-29). Each sort key is a value the row SHOWS; ties always fall back to priority and then the
     * newest, and finally the id, so the order is total and a row can never appear on two pages. "Time to target": a
     * ticket with no deadline (a closed one, or no target set) goes last in both directions.
     *
     * @param  list<array<string, mixed>>  $cards
     * @param  'priority'|'due'|'reported'|'unit'  $sort
     * @param  'asc'|'desc'  $dir
     * @return list<array<string, mixed>>
     */
    private function sorted(array $cards, string $sort, string $dir): array
    {
        $sign = $dir === 'asc' ? 1 : -1;
        $rank = fn (array $c): int => self::PRIORITY_RANK[$c['priority']] ?? 9;

        usort($cards, function (array $a, array $b) use ($sort, $sign, $rank): int {
            $primary = match ($sort) {
                'priority' => $sign * ($rank($a) <=> $rank($b)),
                'due' => match (true) {
                    $a['deadline_ts'] === null && $b['deadline_ts'] === null => 0,
                    $a['deadline_ts'] === null => 1,
                    $b['deadline_ts'] === null => -1,
                    default => $sign * ($a['deadline_ts'] <=> $b['deadline_ts']),
                },
                'reported' => $sign * ($a['opened_ts'] <=> $b['opened_ts']),
                'unit' => $sign * strnatcasecmp((string) $a['unit'], (string) $b['unit']),
            };

            return $primary !== 0 ? $primary
                : ([$rank($a), $b['opened_ts'], $a['id']] <=> [$rank($b), $a['opened_ts'], $b['id']]);
        });

        return $cards;
    }

    /**
     * One page of the sorted rows. A page past the end shows the last one rather than an empty table.
     *
     * @param  list<array<string, mixed>>  $cards
     * @param  BoardState  $state
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function page(array $cards, array $state): LengthAwarePaginator
    {
        $total = count($cards);
        $last = max(1, (int) ceil($total / $state['per']));
        $page = min($state['page'], $last);

        return new LengthAwarePaginator(
            array_slice($cards, ($page - 1) * $state['per'], $state['per']),
            $total,
            $state['per'],
            $page,
            ['path' => route('app.maintenance')],
        );
    }

    // ── The controls — each link keeps every other setting ───────────────────────────────────────

    /**
     * @param  list<array<string, mixed>>  $cards
     * @param  BoardState  $state
     * @return list<array{key: string, label: string, count: int, active: bool, href: string}>
     */
    private function chips(array $cards, array $state): array
    {
        $chips = [];
        foreach ([[null, 'All'], ['active', 'Active'], ['open', 'Not started'], ['inprog', 'In progress'], ['urgent', 'Urgent'],
            ['parts', 'Awaiting parts'], ['ooo', 'Out of order'], ['done', 'Resolved']] as [$filter, $label]) {
            $chips[] = [
                'key' => $filter ?? 'all',
                'label' => $label,
                // the REAL count of what clicking it shows, the search included
                'count' => count(array_filter($cards, fn (array $c): bool => $this->matches($c, $filter, $state['q']))),
                'active' => $state['filter'] === $filter,
                'href' => $this->href($state, ['filter' => $filter]),
            ];
        }

        return $chips;
    }

    /**
     * @param  BoardState  $state
     * @return array<string, array{href: string, active: bool, dir: string, aria: string}>
     */
    private function sortLinks(array $state): array
    {
        $links = [];
        foreach (MaintenanceListParams::SORTS as $column => $start) {
            $active = $state['sort'] === $column;
            $next = $active ? ($state['dir'] === 'asc' ? 'desc' : 'asc') : $start;
            $links[$column] = [
                'href' => $this->href($state, ['sort' => $column, 'dir' => $next]),
                'active' => $active,
                'dir' => $active ? $state['dir'] : '',
                'aria' => $active ? ($state['dir'] === 'asc' ? 'ascending' : 'descending') : 'none',
            ];
        }

        return $links;
    }

    /**
     * @param  BoardState  $state
     * @return list<array{size: int, active: bool, href: string}>
     */
    private function sizeLinks(array $state): array
    {
        return array_map(fn (int $size): array => [
            'size' => $size,
            'active' => $state['per'] === $size,
            'href' => $this->href($state, ['per' => $size]),
        ], MaintenanceListParams::PER_PAGE_OPTIONS);
    }

    /**
     * @param  BoardState  $state
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $rows
     * @return array{prev: ?string, next: ?string}
     */
    private function pageLinks(array $state, LengthAwarePaginator $rows): array
    {
        $page = $rows->currentPage();

        return [
            'prev' => $page > 1 ? MaintenanceListParams::url($this->params($state) + ['page' => $page - 1]) : null,
            'next' => $rows->hasMorePages() ? MaintenanceListParams::url($this->params($state) + ['page' => $page + 1]) : null,
        ];
    }

    /** @param  BoardState  $state */
    private function sortedBy(array $state): string
    {
        return match ($state['sort'].'.'.$state['dir']) {
            'priority.asc' => 'most urgent first',
            'priority.desc' => 'least urgent first',
            'due.asc' => 'soonest target first',
            'due.desc' => 'latest target first',
            'reported.desc' => 'newest first',
            'reported.asc' => 'oldest first',
            'unit.asc' => 'by unit, A–Z',
            'unit.desc' => 'by unit, Z–A',
        };
    }

    /**
     * The Edit dialog, reopened with what was typed when the server refused it — only the board's own Edit form carries
     * `_ticket`, so no other refusal opens this.
     *
     * @return array<string, string>|null
     */
    private function editRetry(): ?array
    {
        $id = old('_ticket');
        if (! is_string($id) || preg_match('/^[0-9A-Za-z]{26}$/', $id) !== 1 || ! session()->has('errors')) {
            return null;
        }

        $text = fn (string $key): string => is_string(old($key)) ? (string) old($key) : '';

        return ['id' => $id, 'title' => $text('title'), 'description' => $text('description'),
            'category' => $text('category'), 'priority' => $text('priority')];
    }

    // ── The panels ───────────────────────────────────────────────────────────────────────────────

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return array<string, int>
     */
    private function kpis(array $cards, int $oosCount): array
    {
        $active = array_filter($cards, fn (array $c): bool => in_array($c['status'], ['open', 'in_progress'], true));

        return [
            'open' => count($active),
            'urgent' => count(array_filter($active, fn (array $c): bool => $c['is_urgent'])),
            'parts' => count(array_filter($cards, fn (array $c): bool => $c['awaiting_parts'])),
            'ooo' => $oosCount,
            'done' => count(array_filter($cards, fn (array $c): bool => $c['status'] === 'resolved')),
        ];
    }

    /**
     * The technicians panel — the org members who currently hold OPEN tickets, with a real open-ticket load +
     * a busy/available status (derived from the load). No fabricated roster: only members with real work show.
     *
     * @param  list<array<string, mixed>>  $cards
     * @return list<array{name: string, initials: string, load: int, urgent: int, status: string}>
     */
    private function technicians(array $cards): array
    {
        $by = [];
        foreach ($cards as $c) {
            if ($c['assignee'] === null || ! in_array($c['status'], ['open', 'in_progress'], true)) {
                continue;
            }
            $key = (string) $c['assignee'];
            $by[$key] ??= ['name' => $key, 'initials' => $this->initials($key), 'load' => 0, 'urgent' => 0, 'status' => 'available'];
            $by[$key]['load']++;
            if ($c['is_urgent']) {
                $by[$key]['urgent']++;
            }
        }

        $out = array_values($by);
        usort($out, fn (array $a, array $b): int => $b['load'] <=> $a['load']);
        foreach ($out as $i => $t) {
            $out[$i]['status'] = $t['load'] >= 3 ? 'busy' : 'available';
        }

        return $out;
    }

    /**
     * The ticket's real activity timeline (#89) — from the append-only `audit_events` for THIS ticket, the same
     * source the detail page uses. Chronological.
     *
     * @return list<array{label: string, when: string}>
     */
    private function activity(string $ticketId): array
    {
        $events = AuditEvent::query()
            ->where('subject_type', (new MaintenanceTicket)->getMorphClass())
            ->where('subject_id', $ticketId)
            ->where('action', 'like', 'maintenance_ticket.%')
            ->orderBy('created_at')
            ->get(['action', 'created_at']);

        return $events->map(fn (AuditEvent $e): array => [
            'label' => ucfirst(str_replace(['maintenance_ticket.', '_'], ['', ' '], (string) $e->action)),
            'when' => $e->created_at !== null ? CarbonImmutable::parse($e->created_at)->diffForHumans() : '',
        ])->all();
    }

    /**
     * @param  Collection<int, MaintenanceTicket>|iterable<MaintenanceTicket>  $tickets
     * @return array<string, list<array{id: string, url: string}>>
     */
    private function photosForTickets($tickets): array
    {
        $ids = collect($tickets)->pluck('id')->all();
        if ($ids === []) {
            return [];
        }

        $media = Media::query()
            ->where('mediable_type', (new MaintenanceTicket)->getMorphClass())
            ->whereIn('mediable_id', $ids)
            ->where('collection', 'mt_photo')
            ->orderBy('created_at')
            ->get();

        $map = [];
        foreach ($media as $m) {
            $map[(string) $m->getAttribute('mediable_id')][] = ['id' => (string) $m->getKey(), 'url' => (string) $m->url];
        }

        return $map;
    }

    /**
     * The units a new work order may be opened on — the properties in view, for the searchable unit list.
     *
     * @param  list<string>  $readIds
     * @return list<array{value: string, label: string, hint: string}>
     */
    private function unitOptions(array $readIds): array
    {
        $properties = Property::query()->whereIn('id', $readIds)->pluck('name', 'id');

        return Unit::query()->whereIn('property_id', $readIds)->orderBy('code')->get()
            ->map(fn (Unit $u): array => [
                'value' => (string) $u->id,
                'label' => $u->name !== null ? $u->code.' · '.$u->name : (string) $u->code,
                'hint' => count($readIds) > 1 ? (string) ($properties[(string) $u->property_id] ?? '') : '',
            ])->all();
    }

    /**
     * The assignable pool — real STAFF only, via the ONE shared definition.
     *
     * ⚠️ This used to select by ACTIVE MEMBERSHIP alone, which offered External Owners as technicians: a
     * Membership is NOT operator access (owners hold one — it is how their org resolves). See
     * {@see StaffRoster::staffUserIds()} for the full account.
     *
     * @return list<array{id: string, name: string}>
     */
    private function orgStaff(): array
    {
        if (app(TenantContext::class)->id() === null) {
            return [];
        }

        return app(StaffRoster::class)->assignableStaff();
    }

    /** @param  list<string>  $readIds */
    private function currency(array $readIds): string
    {
        $id = $readIds[0] ?? null;
        $property = $id !== null ? Property::query()->whereKey($id)->first() : null;

        // ⚠️⚠️ F-44 (FIX 46) — FOUND BY THE GUARD, NOT BY THE REGISTER. A `->first()` query that finds NOTHING when no
        //    property is accessible, so the literal fires. ⚠️ Nothing is in scope to derive from here, so the literal
        //    becomes the ONE product default.
        return (string) ($property->currency ?? config('billing.default_currency', 'EUR'));
    }
}
