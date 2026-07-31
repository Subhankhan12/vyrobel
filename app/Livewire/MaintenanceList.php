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
use App\Models\Membership;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Support\Maintenance\MaintenanceSla;
use App\Support\Maintenance\RevenueOnHold;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator MAINTENANCE board (#89 exact-match) — the work-order list over the real {@see MaintenanceTicket}
 * (the SAME model the Dashboard #84 reads for out-of-order + on-hold revenue; undisturbed). READ view; every
 * mutation is a guarded POST → {@see MaintenanceActionController} → the certified
 * {@see MaintenanceService}. Real KPIs (Open/Urgent/Awaiting-parts/Out-of-order/Resolved-7d), a
 * real SLA bar ({@see MaintenanceSla}), a technicians panel with real load, a parts-on-order panel, and the
 * out-of-order hero with real revenue-on-hold ({@see RevenueOnHold}).
 *
 * TENANT + property scoped (tickets hang off a unit → scoped through `unit.property_id ∈ currentReadIds`).
 */
#[Layout('components.layouts.operator')]
class MaintenanceList extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    private const CATEGORIES = ['plumbing', 'electrical', 'appliance', 'structural', 'other'];

    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    /** The board filter chips (each a real query over the real tickets). */
    private const FILTERS = ['active', 'urgent', 'parts', 'ooo', 'done'];

    public function render(): View
    {
        $readIds = $this->currentReadIds;
        $now = CarbonImmutable::now();
        $today = $now->toDateString();
        $setting = MaintenanceSetting::query()->first() ?? new MaintenanceSetting;

        $filterParam = request()->query('filter');
        $filter = is_string($filterParam) && in_array($filterParam, self::FILTERS, true) ? $filterParam : null;
        $search = mb_substr(trim((string) request()->query('q', '')), 0, 80);

        // The window the board shows: active tickets + those resolved in the last 7 days (older → the costs report).
        $tickets = MaintenanceTicket::query()
            ->whereHas('unit', fn (Builder $q) => $q->whereIn('property_id', $readIds))
            ->with(['unit', 'assignedTo'])
            ->where(function (Builder $q) use ($now): void {
                $q->whereIn('status', ['open', 'in_progress'])
                    ->orWhere(fn (Builder $r) => $r->where('status', 'resolved')->where('resolved_at', '>=', $now->subDays(7)));
            })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderByDesc('opened_at')
            ->get();

        $photos = $this->photosForTickets($tickets);
        $allCards = $tickets->map(fn (MaintenanceTicket $t): array => $this->card($t, $setting, $photos, $now))->all();

        // OOS units (real condition_status) + the real revenue on hold.
        $oosUnits = Unit::query()->whereIn('property_id', $readIds)->where('condition_status', 'out_of_service')->orderBy('code')->get();
        $onHold = RevenueOnHold::forUnits($oosUnits->pluck('id')->map(fn ($id): string => (string) $id)->all(), $today);
        $currency = $this->currency($readIds);

        $cards = array_values(array_filter($allCards, fn (array $c): bool => $this->matches($c, $filter, $search)));

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
            'rows' => $cards,
            'kpis' => $this->kpis($allCards, $oosUnits->count()),
            'technicians' => $this->technicians($allCards),
            'partsOnOrder' => array_values(array_filter($allCards, fn (array $c): bool => $c['awaiting_parts'])),
            'oosHero' => [
                'count' => $oosUnits->count(),
                'units' => $oosUnits->map(fn (Unit $u): string => (string) $u->code)->implode(' · '),
                'on_hold' => $onHold > 0 ? $this->money($onHold, $currency) : null,
            ],
            'filter' => $filter,
            'query' => $search,
            'detail' => $detail,
            'units' => $this->ownUnits($readIds),
            'staff' => $this->orgStaff(),
            'categories' => array_map(fn (string $c): array => ['value' => $c, 'label' => $this->maintenanceCategoryLabel($c)], self::CATEGORIES),
            'priorities' => array_map(fn (string $p): array => ['value' => $p, 'label' => $this->maintenancePriorityMeta($p)['label']], self::PRIORITIES),
            'canManage' => (bool) auth()->user()?->can('maintenance.manage'),
        ]);
    }

    /**
     * @param  array<string, list<array{id: string, url: string}>>  $photos
     * @return array<string, mixed>
     */
    private function card(MaintenanceTicket $ticket, MaintenanceSetting $setting, array $photos, CarbonImmutable $now): array
    {
        $assignee = $ticket->assignedTo;
        $priority = $this->maintenancePriorityMeta((string) $ticket->priority);
        $status = (string) $ticket->status;
        $unit = $ticket->unit;
        $oos = $ticket->takes_out_of_service && $unit !== null && $unit->condition_status === 'out_of_service';
        $sla = MaintenanceSla::evaluate($ticket, $setting, $now);
        $currency = (string) ($ticket->currency ?? 'EUR');

        return [
            'id' => (string) $ticket->id,
            'title' => (string) $ticket->title,
            'description' => (string) ($ticket->description ?? ''),
            'unit' => (string) ($unit->code ?? '—'),
            'category' => $this->maintenanceCategoryLabel((string) $ticket->category),
            'priority' => (string) $ticket->priority,
            'priority_label' => $priority['label'],
            'priority_tone' => $priority['tone'],
            'status' => $status,
            'status_meta' => $this->maintenanceStatusMeta($status),
            'assignee' => $assignee instanceof User ? (string) $assignee->name : null,
            'assignee_initials' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
            'assignee_id' => $ticket->assigned_to !== null ? (string) $ticket->assigned_to : null,
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
            'reported_ago' => CarbonImmutable::parse($ticket->opened_at)->diffForHumans(),
            'cost' => $ticket->cost_minor !== null ? $this->money((int) $ticket->cost_minor, $currency) : null,
            'labor' => $ticket->labor_minor !== null ? $this->money((int) $ticket->labor_minor, $currency) : null,
            'parts_cost' => $ticket->parts_minor !== null ? $this->money((int) $ticket->parts_minor, $currency) : null,
            'resolved' => $ticket->resolved_at?->format('j M'),
            'photos' => $photos[(string) $ticket->id] ?? [],
        ];
    }

    private function matches(array $card, ?string $filter, string $search): bool
    {
        $active = in_array($card['status'], ['open', 'in_progress'], true);
        $ok = match ($filter) {
            'active' => $active,
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
     * @param  list<string>  $readIds
     * @return list<array{id: string, label: string}>
     */
    private function ownUnits(array $readIds): array
    {
        return Unit::query()->whereIn('property_id', $readIds)->orderBy('code')->get()
            ->map(fn (Unit $u): array => ['id' => (string) $u->id, 'label' => $u->name !== null ? $u->code.' · '.$u->name : (string) $u->code])->all();
    }

    /** @return list<array{id: string, name: string}> */
    private function orgStaff(): array
    {
        $orgId = app(TenantContext::class)->id();
        if ($orgId === null) {
            return [];
        }
        $memberIds = Membership::query()->where('organization_id', $orgId)->where('status', 'active')->pluck('user_id');

        return User::query()->whereIn('id', $memberIds)->orderBy('name')->get()
            ->map(fn (User $u): array => ['id' => (string) $u->id, 'name' => (string) $u->name])->all();
    }

    /** @param  list<string>  $readIds */
    private function currency(array $readIds): string
    {
        $id = $readIds[0] ?? null;
        $property = $id !== null ? Property::query()->whereKey($id)->first() : null;

        return (string) ($property->currency ?? 'EUR');
    }
}
