<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerPayoutPreference;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\I18n\LocalDate;
use App\Support\I18n\Ordinal;
use App\Support\Money\MoneyFormatter;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Owner\OwnerListParams;
use App\Support\Owner\OwnerRows;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator OWNERS (rebuilt 2026-10-01 — the founder: "on Add owner page, modal design and buttons are mess … confirm with your
 * password and its box is not well aligned … make it more creative and operational and easy to use and fully functional").
 * The org's owners on the SAME certified statements/ledger the owner sees (reconciling #17), as a list like Guests and Units:
 * search, chips that count, sortable columns, a row menu, an export — and the invites waiting, which can be resent or
 * cancelled. Every row comes from {@see OwnerRows}; every link from {@see OwnerListParams}.
 *
 * The "Add owner" dialog posts to the #80 invite flow (InviteOwnerController → OwnerInviteService — NOT a second invite).
 * TENANT-scoped (OwnerScope inactive on /app). ⚠️ COMMISSION is the REAL agreement rate, never a hardcoded 15%.
 * ⚠️ This component exposes render() only — a public method here is callable over /livewire/update, where no route
 * middleware runs (OperatorLivewireMutationBoundaryTest).
 *
 * @phpstan-import-type OwnerRow from OwnerRows
 *
 * @phpstan-type PageState array{filter: ?string, q: string, sort: 'name'|'units'|'net'|'status', dir: 'asc'|'desc', per: int, page: int}
 */
#[Layout('components.layouts.operator')]
class OperatorOwners extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    /** The commission presets offered beside the organisation's own rate(s). */
    private const PRESETS = ['12', '15', '18'];

    public function render(): View
    {
        $state = $this->state();
        $rowsOf = app(OwnerRows::class);
        $all = $rowsOf->load();

        $user = auth()->user();
        $canManage = (bool) $user?->can('users.manage');
        $readOnly = app(OperatorWorkspace::class)->isReadOnly();
        // A write that would be refused is not offered: the invite, resend, cancel and pause routes all refuse a paused workspace.
        $canWrite = $canManage && ! $readOnly;

        $filtered = array_values(array_filter($all, fn (array $r): bool => $rowsOf->matches($r, $state['filter'], $state['q'])));
        $rows = $this->page(array_map(
            fn (array $r): array => $r + ['actions' => $this->actions($r, $canWrite)],
            $rowsOf->sort($filtered, $state['sort'], $state['dir']),
        ), $state);

        $rates = $this->orgRates();

        return view('livewire.operator.owners', [
            'viewer' => $this->viewer(),
            'rows' => $rows,
            'rowsById' => collect($rows->items())->keyBy('id')->all(),
            'total' => count($all),
            'kpis' => $this->kpis($all, $this->fallbackCurrency($all)),
            'query' => $state['q'],
            // What a search keeps when submitted without script — every other setting, and page 1.
            'keep' => array_diff_key(OwnerListParams::clean($this->params($state)), ['q' => true]),
            'chips' => $this->chips($all, $state, $rowsOf),
            'sorts' => $this->sortLinks($state),
            'sizes' => $this->sizeLinks($state),
            'pages' => $this->pageLinks($state, $rows),
            'sortedBy' => $this->sortedBy($state),
            'exportUrl' => route('app.owners.export', OwnerListParams::clean($this->params($state))),
            'invites' => $this->invites(),
            'payout' => $this->payoutCard($all, $this->fallbackCurrency($all)),
            'howPaid' => $this->howPaid($rates),
            'portal' => [
                // ⚠️ DEC-o — "with portal access" is an activated owner whose portal an operator has NOT switched off
                'active' => count(array_filter($all, fn (array $r): bool => $r['active'] && ! $r['portal_off'])),
                'total' => count($all),
            ],
            'inviteUnits' => $canManage ? $this->inviteUnits() : [],
            'commissionChoices' => $this->commissionChoices($rates),
            'commissionDefault' => count($rates) === 1 ? $rates[0] : '18',
            'canManage' => $canManage,
            'canWrite' => $canWrite,
            'readOnly' => $readOnly,
            'boot' => [
                'urls' => [
                    'show' => route('app.owners.show', ['owner' => '__ID__']),
                    'pause' => route('app.owners.pause-payouts', ['owner' => '__ID__']),
                    'resend' => route('app.owners.invites.resend', ['invite' => '__ID__']),
                ],
                'q' => $state['q'],
            ],
        ]);
    }

    // ── The page's settings ──────────────────────────────────────────────────────────────────────

    /** @return PageState */
    private function state(): array
    {
        $clean = OwnerListParams::clean((array) request()->query());
        $sort = $clean['sort'] ?? 'name';

        return [
            'filter' => $clean['filter'] ?? null,
            'q' => $clean['q'] ?? '',
            'sort' => $sort,
            'dir' => $clean['dir'] ?? OwnerListParams::SORTS[$sort],
            'per' => $clean['per'] ?? OwnerListParams::PER_PAGE,
            'page' => $clean['page'] ?? 1,
        ];
    }

    /**
     * @param  PageState  $state
     * @return array<string, string|int|null>
     */
    private function params(array $state): array
    {
        return ['filter' => $state['filter'], 'q' => $state['q'], 'sort' => $state['sort'], 'dir' => $state['dir'], 'per' => $state['per']];
    }

    /**
     * @param  PageState  $state
     * @param  array<string, string|int|null>  $changes
     */
    private function href(array $state, array $changes): string
    {
        return OwnerListParams::url(array_merge($this->params($state), $changes));
    }

    /**
     * What this viewer may do to THIS owner, in a fixed order — each only where its route would accept it.
     *
     * @param  OwnerRow  $row
     * @return list<string>
     */
    private function actions(array $row, bool $canWrite): array
    {
        $actions = ['open', 'email'];
        if ($canWrite && $row['active']) {
            // the preference itself — the pay status says "paused" only while money is due
            $actions[] = $row['paused'] ? 'resume' : 'pause';
        }
        if ($canWrite && $row['invite'] !== null && in_array($row['pay_key'], ['invited', 'inactive'], true)) {
            $actions[] = 'resend';
        }

        return $actions;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  PageState  $state
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function page(array $rows, array $state): LengthAwarePaginator
    {
        $total = count($rows);
        $page = min($state['page'], max(1, (int) ceil($total / $state['per'])));

        return new LengthAwarePaginator(array_slice($rows, ($page - 1) * $state['per'], $state['per']), $total, $state['per'], $page,
            ['path' => route('app.owners')]);
    }

    /**
     * @param  list<OwnerRow>  $all
     * @param  PageState  $state
     * @return list<array{key: string, label: string, count: int, active: bool, href: string}>
     */
    private function chips(array $all, array $state, OwnerRows $rowsOf): array
    {
        $chips = [];
        foreach ([null, 'ready', 'hold', 'review', 'paused', 'invited'] as $filter) {
            $chips[] = [
                'key' => $filter ?? 'all',
                'label' => match ($filter) {
                    null => __('common.all'),
                    'ready' => __('php/livewire/operator/operator_owners.chip_ready'),
                    'hold' => __('php/livewire/operator/operator_owners.chip_hold'),
                    'review' => __('php/livewire/operator/operator_owners.chip_review'),
                    'paused' => __('php/livewire/operator/operator_owners.chip_paused'),
                    'invited' => __('php/livewire/operator/operator_owners.chip_invited'),
                },
                // the REAL count of what clicking it shows, the search included
                'count' => count(array_filter($all, fn (array $r): bool => $rowsOf->matches($r, $filter, $state['q']))),
                'active' => $state['filter'] === $filter,
                'href' => $this->href($state, ['filter' => $filter]),
            ];
        }

        return $chips;
    }

    /**
     * @param  PageState  $state
     * @return array<string, array{href: string, active: bool, dir: string, aria: string}>
     */
    private function sortLinks(array $state): array
    {
        $links = [];
        foreach (OwnerListParams::SORTS as $column => $start) {
            $active = $state['sort'] === $column;
            $links[$column] = [
                'href' => $this->href($state, ['sort' => $column, 'dir' => $active ? ($state['dir'] === 'asc' ? 'desc' : 'asc') : $start]),
                'active' => $active,
                'dir' => $active ? $state['dir'] : '',
                'aria' => $active ? ($state['dir'] === 'asc' ? 'ascending' : 'descending') : 'none',
            ];
        }

        return $links;
    }

    /**
     * @param  PageState  $state
     * @return list<array{size: int, active: bool, href: string}>
     */
    private function sizeLinks(array $state): array
    {
        return array_map(fn (int $size): array => ['size' => $size, 'active' => $state['per'] === $size, 'href' => $this->href($state, ['per' => $size])],
            OwnerListParams::PER_PAGE_OPTIONS);
    }

    /**
     * @param  PageState  $state
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $rows
     * @return array{prev: ?string, next: ?string}
     */
    private function pageLinks(array $state, LengthAwarePaginator $rows): array
    {
        $page = $rows->currentPage();

        return [
            'prev' => $page > 1 ? OwnerListParams::url($this->params($state) + ['page' => $page - 1]) : null,
            'next' => $rows->hasMorePages() ? OwnerListParams::url($this->params($state) + ['page' => $page + 1]) : null,
        ];
    }

    /** @param  PageState  $state */
    private function sortedBy(array $state): string
    {
        return match ($state['sort'].'.'.$state['dir']) {
            'name.asc' => __('php/livewire/operator/operator_owners.sorted_name_asc'),
            'name.desc' => __('php/livewire/operator/operator_owners.sorted_name_desc'),
            'units.desc' => __('php/livewire/operator/operator_owners.sorted_units_desc'),
            'units.asc' => __('php/livewire/operator/operator_owners.sorted_units_asc'),
            'net.desc' => __('php/livewire/operator/operator_owners.sorted_net_desc'),
            'net.asc' => __('php/livewire/operator/operator_owners.sorted_net_asc'),
            'status.asc' => __('php/livewire/operator/operator_owners.sorted_status_asc'),
            'status.desc' => __('php/livewire/operator/operator_owners.sorted_status_desc'),
        };
    }

    // ── The figures and the panels ───────────────────────────────────────────────────────────────

    /**
     * Four figures that agree with the rows: active owners, the money ready for the next run, owners on hold (and what they
     * are waiting for), and statements to review. Money is summed PER CURRENCY, never across (#17).
     *
     * @param  list<OwnerRow>  $all
     * @return array<string, mixed>
     */
    private function kpis(array $all, string $currency): array
    {
        $ready = array_values(array_filter($all, fn (array $r): bool => $r['pay_key'] === 'due'));
        $hold = array_values(array_filter($all, fn (array $r): bool => $r['pay_key'] === 'hold'));
        $review = count(array_filter($all, fn (array $r): bool => $r['pay_key'] === 'draft'));
        $invited = count(array_filter($all, fn (array $r): bool => in_array($r['pay_key'], ['invited', 'inactive'], true)));
        $paused = count(array_filter($all, fn (array $r): bool => $r['pay_key'] === 'paused'));
        $units = array_sum(array_column($all, 'units'));

        return [
            'owners' => count(array_filter($all, fn (array $r): bool => $r['active'])),
            'owners_sub' => __('php/livewire/operator/operator_owners.kpi_invited', ['number' => $invited]).' · '
                .trans_choice('php/livewire/operator/operator_owners.kpi_units', $units, ['count' => $units]),
            'ready' => $this->sumLines($ready, $currency),
            'ready_sub' => trans_choice('php/livewire/operator/operator_owners.kpi_owners_ready', count($ready), ['count' => count($ready)])
                .($paused > 0 ? ' · '.__('php/livewire/operator/operator_owners.kpi_paused_not_counted', ['number' => $paused]) : ''),
            'hold' => count($hold),
            'hold_sub' => count($hold) === 0 ? __('php/livewire/operator/operator_owners.kpi_hold_none')
                : __('php/livewire/operator/operator_owners.kpi_hold_waiting', ['amounts' => implode(' · ', $this->sumLines($hold, $currency))]),
            'review' => $review,
            'review_sub' => __('php/livewire/operator/operator_owners.kpi_review_sub', ['number' => $review]),
        ];
    }

    /**
     * @param  list<OwnerRow>  $all
     * @return array{ready: list<string>, due: int, hold: int, paused: int}
     */
    private function payoutCard(array $all, string $currency): array
    {
        $ready = array_values(array_filter($all, fn (array $r): bool => $r['pay_key'] === 'due'));

        return [
            'ready' => $this->sumLines($ready, $currency),
            'due' => count($ready),
            'hold' => count(array_filter($all, fn (array $r): bool => $r['pay_key'] === 'hold')),
            // money due to a paused owner is left out of the figure above — the card says how many, as the KPI does
            'paused' => count(array_filter($all, fn (array $r): bool => $r['pay_key'] === 'paused')),
        ];
    }

    /**
     * HOW OWNERS ARE PAID — only what the system does. ⚠️ This card said "Statements finalize 1st of the month": nothing does;
     * a statement closes when someone presses Close period on the owner (`OwnerManageController::closePeriod`, the only
     * caller of `finalize()`). And "Payouts sent 3rd business day" is true only while the automatic run is ON
     * (`services.payouts.engine_enabled`, off by default) — otherwise payouts go from the payout run, by hand.
     *
     * @param  list<string>  $rates
     * @return array{engine: bool, schedule: string, own: int, commission: string}
     */
    private function howPaid(array $rates): array
    {
        $defaults = OwnerPayoutPreference::defaults();

        return [
            'engine' => (bool) config('services.payouts.engine_enabled', false),
            'schedule' => __('php/livewire/operator/operator_owners.schedule', ['day' => $this->ordinal((int) $defaults['payout_day'])]),
            'own' => OwnerPayoutPreference::query()->where(fn ($q) => $q->where('frequency', '!=', $defaults['frequency'])
                ->orWhere('payout_day', '!=', $defaults['payout_day']))->count(),
            'commission' => match (count($rates)) {
                0 => '—',
                1 => $rates[0].'%',
                default => 'varies',
            },
        ];
    }

    /**
     * Every invite still waiting to be accepted — owners AND staff, since this dialog sends both.
     *
     * @return list<array{id: string, email: string, name: string, initials: string, role: string, sent: string, expires: ?string, expired: bool, units: int, commission: ?string, cancel: string}>
     */
    private function invites(): array
    {
        $invites = OwnerInvite::query()->where('status', 'pending')
            // ADMIN 04 — a console-created organisation's FOUNDER link is not an invite this operator sent
            ->where('role', '!=', OwnerInviteService::FOUNDER_ROLE)
            ->orderByDesc('created_at')->get();
        $owners = Owner::query()->whereIn('id', $invites->pluck('owner_id')->filter())->pluck('name', 'id');
        $users = User::query()->whereIn('email', $invites->pluck('email'))->pluck('name', 'email');

        return $invites->map(fn (OwnerInvite $i): array => [
            'id' => (string) $i->id,
            'email' => (string) $i->email,
            'name' => $name = (string) ($owners[$i->owner_id] ?? $users[$i->email] ?? $i->email),
            'initials' => OwnerRows::initials($name),
            'role' => match ((string) $i->role) {
                'manager' => __('php/livewire/operator/operator_owners.role_manager'),
                'accountant' => __('php/livewire/operator/operator_owners.role_accountant'),
                default => __('php/livewire/operator/operator_owners.role_owner'),
            },
            'sent' => $i->created_at?->translatedFormat(LocalDate::pattern('j M')) ?? '',
            'expires' => $i->expires_at?->translatedFormat(LocalDate::pattern('j M Y')),
            'expired' => $i->expires_at !== null && $i->expires_at->isPast(),
            'units' => count($i->assigned_unit_ids ?? []),
            'commission' => $i->commission_pct !== null ? rtrim(rtrim((string) $i->commission_pct, '0'), '.').'%' : null,
            'cancel' => route('app.owners.invites.cancel', $i->id),
        ])->all();
    }

    /**
     * The units the dialog may give — inside the person's properties (DEC-k/DEC-l), each with its owner if it has one, so a
     * taken unit can say whose it is. ⚠️ A unit that has an owner is shown, never offered: its owner decides who is paid.
     *
     * @return list<array{id: string, label: string, owner: ?string}>
     */
    private function inviteUnits(): array
    {
        $units = Unit::query()->where('is_active', true)
            ->when($this->currentReadIds !== [], fn ($q) => $q->whereIn('property_id', $this->currentReadIds))
            ->get(['id', 'code', 'name', 'owner_id']);
        $owners = Owner::query()->whereIn('id', $units->pluck('owner_id')->filter())->pluck('name', 'id');

        return $units->sortBy(fn (Unit $u): string => (string) $u->code, SORT_NATURAL)->values()
            ->map(fn (Unit $u): array => [
                'id' => (string) $u->id,
                'label' => (string) ($u->name ?: $u->code),
                'owner' => $u->owner_id !== null ? (string) ($owners[$u->owner_id] ?? __('php/livewire/operator/operator_owners.another_owner')) : null,
            ])->all();
    }

    /**
     * The rates the dialog offers: the organisation's own beside the presets, in numeric order (its own is preselected).
     *
     * @param  list<string>  $rates
     * @return list<string>
     */
    private function commissionChoices(array $rates): array
    {
        $choices = array_values(array_unique(array_merge($rates, self::PRESETS)));
        usort($choices, fn (string $a, string $b): int => (float) $a <=> (float) $b);

        return $choices;
    }

    /** @return list<string> the organisation's agreement rates in force, e.g. ["18"] */
    private function orgRates(): array
    {
        return ManagementAgreement::query()->where('status', 'active')->pluck('commission_pct')
            ->filter(fn ($p): bool => $p !== null)
            ->map(fn ($p): string => rtrim(rtrim((string) $p, '0'), '.'))
            ->unique()->sort(SORT_NATURAL)->values()->all();
    }

    /**
     * @param  list<OwnerRow>  $rows
     * @return list<string>
     */
    private function sumLines(array $rows, string $currency): array
    {
        $totals = [];
        foreach ($rows as $r) {
            if ($r['net_minor'] !== null) {
                $totals[$r['currency']] = ($totals[$r['currency']] ?? 0) + $r['net_minor'];
            }
        }
        if ($totals === []) {
            return [MoneyFormatter::display(0, $currency)];
        }
        ksort($totals);

        return array_map(fn (int $minor, string $currency): string => MoneyFormatter::display($minor, $currency), array_values($totals), array_keys($totals));
    }

    /**
     * The currency a zero is shown in: an owner's own (what they are paid in), or — with no owner at all, so nothing in scope —
     * the ONE product default (F-44: never a literal repeated per file).
     *
     * @param  list<OwnerRow>  $all
     */
    private function fallbackCurrency(array $all): string
    {
        return $all !== [] ? $all[0]['currency'] : (string) config('billing.default_currency', 'EUR');
    }

    private function ordinal(int $n): string
    {
        // the choice of st / nd / rd / th is App\Support\I18n\Ordinal's (English's rule; Georgian's own for `ka`)
        return match (Ordinal::form($n)) {
            'st' => __('php/livewire/operator/operator_owners.ordinal_st', ['number' => $n]),
            'nd' => __('php/livewire/operator/operator_owners.ordinal_nd', ['number' => $n]),
            'rd' => __('php/livewire/operator/operator_owners.ordinal_rd', ['number' => $n]),
            default => __('php/livewire/operator/operator_owners.ordinal_th', ['number' => $n]),
        };
    }
}
