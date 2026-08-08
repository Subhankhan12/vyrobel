<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\Unit;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator OWNERS list (#93 exact-match; the operator side of the owner financial system) — the org's owners with
 * their REAL net-payable, statement + pay-status, the payout-run summary, and the statement schedule, on the SAME
 * certified statements/ledger the OWNER sees (anti-drift, reconciling #17). The "Add owner" action REUSES the #80
 * invite flow (POST → InviteOwnerController → OwnerInviteService — NOT a second invite). TENANT-scoped (OwnerScope
 * inactive on /app; reads are explicit).
 *
 * ⚠️ COMMISSION is the REAL rate from `ManagementAgreement.commission_pct` (18% in the demo) — NEVER the
 * wireframe's hardcoded 15%. ⚠️ The net-payable + pay-status are the real `owner_statements.net_payable_minor` /
 * `payouts.status`; the payout-run summary counts the real due/held owners. No fabricated figures (#39).
 */
#[Layout('components.layouts.operator')]
class OperatorOwners extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $owners = Owner::query()->withCount('units')->orderBy('name')->get();
        $ownerIds = $owners->pluck('id')->all();

        // The latest FINALIZED statement per owner (the current period's net) + all payouts + payout methods —
        // batch-loaded so the list is O(1) queries, not N.
        $statements = OwnerStatement::query()
            ->whereIn('owner_id', $ownerIds)
            ->where('status', '!=', 'draft')
            ->orderByDesc('period_start')
            ->get()
            ->groupBy('owner_id');
        $payouts = Payout::query()->whereIn('owner_id', $ownerIds)->get()->groupBy('owner_id');
        $hasMethod = PayoutMethod::query()->whereIn('owner_id', $ownerIds)->pluck('owner_id')->unique()->flip();

        $rows = [];
        $netTotals = [];      // currency → minor (net payable, the current period)
        $needsAttention = 0;
        $holdCount = 0;
        $dueCount = 0;

        foreach ($owners as $owner) {
            /** @var Collection<int, OwnerStatement> $ownerStatements */
            $ownerStatements = $statements->get($owner->id) ?? collect();
            $latest = $ownerStatements->first();   // most recent finalized (or paid) statement
            /** @var Collection<int, Payout> $ownerPayouts */
            $ownerPayouts = $payouts->get($owner->id) ?? collect();

            $net = $latest !== null ? (int) $latest->net_payable_minor : 0;
            $currency = $latest !== null ? (string) $latest->currency : (string) ($owner->payout_currency ?? 'EUR');
            $status = $this->payStatus($owner, $latest, $ownerPayouts, $hasMethod->has($owner->id));

            // Net payable = the current period's finalized-but-not-yet-paid net (what a run would send).
            if ($status['due'] && $net > 0) {
                $netTotals[$currency] = ($netTotals[$currency] ?? 0) + $net;
                $dueCount++;
            }
            if ($status['key'] === 'hold') {
                $holdCount++;
            }
            if ($status['attention']) {
                $needsAttention++;
            }

            $rows[] = [
                'id' => (string) $owner->id,
                'name' => (string) $owner->name,
                'initials' => $this->initials((string) $owner->name),
                'sub' => (int) $owner->units_count.' '.((int) $owner->units_count === 1 ? 'unit' : 'units').' · '.($owner->status === 'active' ? 'active' : 'inactive'),
                'stmt' => $latest !== null ? CarbonImmutable::parse($latest->period_start)->format('M Y').' statement' : 'No statement yet',
                'net' => $latest !== null ? MoneyFormatter::display($net, $currency) : '—',
                'pay_label' => $status['label'],
                'pay_key' => $status['key'],
                'active' => $owner->status === 'active',
            ];
        }

        return view('livewire.operator.owners', [
            'viewer' => $this->viewer(),
            'owners' => $rows,
            'kpis' => [
                'owners' => $owners->count(),
                'units' => (int) $owners->sum('units_count'),
                'net' => $this->moneyLines($netTotals, 'EUR'),
                'needs_attention' => $needsAttention,
                'attention_sub' => $holdCount.' hold · '.max(0, $needsAttention - $holdCount).' draft',
            ],
            'payoutSummary' => [
                'due' => $dueCount,
                'hold' => $holdCount,
                'net_total' => $this->moneyLines($netTotals, 'EUR'),
            ],
            'schedule' => [
                'commission' => $this->orgCommission(),
                'portal_active' => $owners->where('status', 'active')->count(),
                'portal_total' => $owners->count(),
            ],
            'pending' => $this->pendingInvites(),
            'units' => $this->assignableUnits(),
            'commissionDefault' => $this->orgCommission(),
        ]);
    }

    /**
     * The owner's real pay-status for the current period. hold = the certified TAX-hold OR (honest) no payout
     * method on file — either way the run can't send. due = finalized-unpaid + payable. paid = a paid payout.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return array{key: string, label: string, due: bool, attention: bool}
     */
    private function payStatus(Owner $owner, ?OwnerStatement $latest, $payouts, bool $hasMethod): array
    {
        if ($latest === null) {
            return ['key' => 'none', 'label' => 'No statement', 'due' => false, 'attention' => false];
        }
        // ⚠️ Judge THIS statement's payout, not "any payout this owner has ever had". Testing the whole
        // collection meant one historical PAID payout short-circuited every branch below: a finalized-unpaid
        // statement read "Paid", `needs_attention` and the net-payable KPI collapsed to 0, and the payout run
        // reported "0 owners scheduled" — while Owner Detail showed the real pending figure on the same rows.
        // `payouts.owner_statement_id` is the link that makes this exact.
        $statementPayouts = $payouts->where('owner_statement_id', $latest->getKey());

        if ((string) $latest->status === 'paid' || $statementPayouts->contains(fn (Payout $p): bool => (string) $p->status === 'paid')) {
            return ['key' => 'paid', 'label' => 'Paid', 'due' => false, 'attention' => false];
        }
        if ($statementPayouts->contains(fn (Payout $p): bool => (string) $p->status === 'held')) {
            return ['key' => 'hold', 'label' => 'On hold', 'due' => false, 'attention' => true];
        }
        // Finalized-unpaid: on hold if the owner can't be paid yet (no tax details, or no payout method on file).
        if ((int) $latest->net_payable_minor > 0 && (! $owner->hasTaxDetails() || ! $hasMethod)) {
            return ['key' => 'hold', 'label' => 'On hold', 'due' => false, 'attention' => true];
        }
        if ((bool) $latest->has_unverified) {
            return ['key' => 'draft', 'label' => 'Needs review', 'due' => false, 'attention' => true];
        }
        if ((int) $latest->net_payable_minor > 0) {
            return ['key' => 'due', 'label' => 'Ready', 'due' => true, 'attention' => false];
        }

        return ['key' => 'zero', 'label' => 'Nothing due', 'due' => false, 'attention' => false];
    }

    /** The org's REAL management commission (18% in the demo) — the single effective rate, or "varies". Never 15%. */
    private function orgCommission(): string
    {
        $rates = ManagementAgreement::query()
            ->where('status', 'active')
            ->pluck('commission_pct')
            ->filter(fn ($p): bool => $p !== null)
            ->map(fn ($p): string => rtrim(rtrim((string) $p, '0'), '.'))
            ->unique()
            ->values();

        return match ($rates->count()) {
            0 => '—',
            1 => $rates->first().'%',
            default => 'varies',
        };
    }

    /**
     * @return list<array{email: string, expires: ?string}>
     */
    private function pendingInvites(): array
    {
        return OwnerInvite::query()
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OwnerInvite $i): array => ['email' => (string) $i->email, 'expires' => $i->expires_at?->format('j M Y')])
            ->all();
    }

    /**
     * @return list<array{id: string, code: string, name: ?string, owned: bool}>
     */
    private function assignableUnits(): array
    {
        return Unit::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Unit $u): array => [
                'id' => (string) $u->id,
                'code' => (string) $u->code,
                'name' => $u->name !== null ? (string) $u->name : null,
                'owned' => $u->owner_id !== null,
            ])->all();
    }

    /**
     * @param  array<string, int>  $totals
     * @return list<string>
     */
    private function moneyLines(array $totals, string $fallbackCurrency): array
    {
        if ($totals === []) {
            return [MoneyFormatter::display(0, $fallbackCurrency)];
        }

        ksort($totals);

        return array_map(fn (int $minor, string $currency): string => MoneyFormatter::display($minor, $currency), array_values($totals), array_keys($totals));
    }
}
