<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Livewire\Owner\OwnerAgreement;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\Payouts\RailLabel;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Owner Detail (Wave 1 · Sub-gate 3) — the OPERATOR's read-only lens on ONE owner they manage:
 * the owner's real record, their units, the commercial terms, the statement + payout history, and the
 * ledger balance. Styled from the catalogued "Operator Owner Detail" design onto the SAME operator design
 * system as every other /app screen (operator layout, <x-operator.shell>, FormatsOperatorData — reused,
 * never redefined). Presentation + wiring of EXISTING certified reads ONLY: no schema, service, guarantee
 * or write path is touched, and this screen never mutates anything.
 *
 * ⚠️ THIS SURFACE IS TENANT-SCOPED, **NOT** OWNER-SCOPED — the exact inverse of the owner portal, and the
 * isolation-critical fact about this file. The `/app` routes do NOT run SetOwnerScopeForPortal, so
 * {@see OwnerScope} is INACTIVE here (by design: "operator/CLI see all, subject to the
 * tenant scope"). That is CORRECT — an operator must see the owners they manage — but it means:
 *   • {@see Owner} carries BelongsToOrganization, so `Owner::query()->findOrFail($id)` gives a cross-org
 *     404 for free. That, plus `SetTenantForWebRequest`, IS the tenant boundary for this page.
 *   • EVERY other read (Unit / ManagementAgreement / OwnerStatement / Payout / OwnerLedgerEntry /
 *     OwnerInvite) is tenant-scoped ONLY on this surface — their BelongsToOwner scope does nothing here.
 *     So each one is filtered EXPLICITLY with `->where('owner_id', $record->id)`. Dropping any one of
 *     those filters would silently mix a DIFFERENT owner of the same org into this owner's page; the
 *     "no cross-owner mix" test in OperatorOwnerDetailViewTest is what locks that.
 * OwnerContext/OwnerScope is deliberately NOT activated here, and BelongsToOwner is added to nothing.
 *
 * RMB rule (invariant #20): `{owner}` is a plain string prop that Livewire fills from the route param and
 * that render() resolves via a tenant-scoped findOrFail — AFTER SetTenantForWebRequest. It is never a
 * route-model-bound typed prop (RMB resolves during SubstituteBindings, i.e. BEFORE the scope middleware).
 * This screen has no navigation state, so there is no wire-action and nothing re-renders over
 * /livewire/update.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11; this gate's standing order is OMIT AGGRESSIVELY
 * — a control implying state with no schema is not rendered even disabled):
 *   • REAL     — name, legal_name, type + status (the real enums), payout_currency, notes; the owner's
 *                units (code/name/kind/sleeps/room type/active); the agreement terms verbatim
 *                (commission_pct, management fee + interval, payout schedule + currency, scope,
 *                effective dates, status); statement rows (sequence_no, period, gross, commission, net,
 *                the real status enum, has_unverified, locked); payout rows (amount, rail via the shared
 *                {@see RailLabel}, the real status enum, paid_at, failed_at, failure_reason, external_ref).
 *   • DERIVED  — the KPI band: balance = Σ signedMinor() over the owner's real `owner_ledger` rows
 *                (reversals are ordinary rows in that sum); Σ paid payouts; Σ pending payouts; the unit
 *                count. All computed PER CURRENCY and never summed across currencies (invariant #17) —
 *                with no ledger/payout rows at all the honest zero is rendered in the owner's own real
 *                `payout_currency`. "In effect today" comes from the model's CERTIFIED `effectiveOn`
 *                scope — the effective-dating predicate is never re-implemented here.
 *   • NULLS    — every nullable column renders honestly: no commission → "Not set" (NEVER "0%"), no fee →
 *                "Not set", effective_to null → "Open-ended", no sequence_no → "—". Nothing is invented
 *                to fill a slot.
 *   • PORTAL   — the REAL access state, and this is subtler than it looks: {@see OwnerInviteService}
 *                links `owners.user_id` to an INACTIVE (`status = 'invited'`) login the moment an invite is
 *                sent, so `user_id !== null` on its own does NOT mean the owner can log in. Reading it that
 *                way would print "Portal access active" for someone who has never accepted — a fabricated
 *                status. So: a live invite is reported as the pending invite it really is; "Portal access
 *                active" requires user_id AND the linked login genuinely being `active`; otherwise the real
 *                OwnerInvite enum state, or "No portal access". Invite expiry is rendered as a factual DATE
 *                ("Expires …" / "Expired …"), never as a status the column does not carry — the same
 *                discipline as {@see OwnerAgreement}'s timing note.
 *   • PII      — the owner's name, email and phone ARE rendered: this is the operator's own CRM record for
 *                a business counterparty THEY invited (the operator typed that email; the operator Owners
 *                list already renders the pending-invite email). `tax_id` (encrypted, most sensitive, not
 *                needed to manage the relationship) and the raw `address` JSON are OMITTED — never read,
 *                never rendered. This is NOT the guest rule: guests' encrypted identity is still never
 *                rendered on any surface, and that rule is unchanged.
 *   • NOT LINKED — statement rows deliberately carry NO link. The statement detail lives on the
 *                OWNER-scoped route (`/owner/statements/{id}`), which an operator's session cannot pass —
 *                it would resolve to an empty owner scope and 404. A link that always 404s is worse than
 *                no link, so the rows render as facts only.
 *   • OMITTED (no backend — reported, not greyed out):
 *       – the design's "Edit owner" modal: the commission stepper is a WRITE to a certified agreement
 *         (out of scope for a presentation gate), and its three toggles — "Owner portal access",
 *         "Statement PDF by email", "Pause payouts" — have NO columns and no backend at all. The whole
 *         modal is omitted. (The design's own modal is also broken: it opens on the destructive REMOVE
 *         view and its "Remove owner permanently" button has no handler.)
 *       – "Remove owner" + its type-to-confirm: no backend, and the design's button is inert.
 *       – IBAN / "Revolut Business · IBAN ····4821" / "verified": NO bank-account field exists anywhere
 *         in the schema. Never rendered.
 *       – "Next payout · est. 3 Nov" / any estimated payout date: no such column. A payout date is real
 *         only once the payout is genuinely paid (`paid_at`).
 *       – the "Workspace paused — read-only until 5 Oct" billing banner: subscription/billing has no
 *         backend; porting it would fabricate a system status.
 *       – "Scheduled" as a statement/payout status: NOT in either real enum ('draft','finalized','paid' /
 *         'pending','paid','failed','reversed'). Only the real words are rendered.
 *       – the design's "Add note" write (no path exists) — `owners.notes` IS a real nullable text column,
 *         so the real note is rendered READ-ONLY when set; the design's invented note CHIPS are not.
 *       – the design's Email/Message buttons (no operator→owner messaging surface exists), its demo
 *         figures (€10,370 / 74,830 / "owner since 2023") and its localStorage refund netting.
 *       – a re-invite WRITE: the real invite POST lives on the Owners list screen (a new write is out of
 *         scope for this gate), so this screen reports invite state and links back to that list.
 */
#[Layout('components.layouts.operator')]
class OperatorOwnerDetail extends Component
{
    use FormatsOperatorData;

    /**
     * The `{owner}` route param — a plain STRING id (never a route-model-bound model).
     *
     * NB the `Id` suffix is load-bearing, not styling: Livewire injects public props into the view
     * scope, so a prop named `$owner` would SHADOW the `'owner' => [...]` array this component passes
     * from render() and the view's `$owner['name']` would fatal on a string offset. Same reason
     * OwnerStatementView/ReservationDetail/MaintenanceTicketDetail all use `$xxxId`.
     */
    public string $ownerId;

    public function mount(string $owner): void
    {
        $this->ownerId = $owner;
    }

    public function render(): View
    {
        // TENANT-scoped (BelongsToOrganization): a cross-org or unknown id resolves to nothing → 404.
        // This is the whole tenant boundary for the page; every read below hangs off the resolved record.
        $record = Owner::query()->findOrFail($this->ownerId);
        $ownerId = (string) $record->id;
        $today = Carbon::today();

        // ⚠️ OwnerScope is INACTIVE on /app — every read below is filtered EXPLICITLY by owner_id, or it
        // would return every owner in the org. See the class docblock.
        /** @var Collection<int, Unit> $units */
        $units = Unit::query()
            ->with('roomType')
            ->where('owner_id', $ownerId)
            ->orderBy('code')
            ->get();

        /** @var Collection<int, ManagementAgreement> $agreements */
        $agreements = ManagementAgreement::query()
            ->where('owner_id', $ownerId)
            ->orderByDesc('effective_from')
            ->orderByDesc('created_at')
            ->get();

        /** @var Collection<int, OwnerStatement> $statements */
        $statements = OwnerStatement::query()
            ->where('owner_id', $ownerId)
            ->orderByDesc('period_start')
            ->get();

        /** @var Collection<int, Payout> $payouts */
        $payouts = Payout::query()
            ->where('owner_id', $ownerId)
            ->orderByDesc('created_at')
            ->get();

        /** @var Collection<int, OwnerLedgerEntry> $ledger */
        $ledger = OwnerLedgerEntry::query()
            ->where('owner_id', $ownerId)
            ->get();

        $invite = OwnerInvite::query()
            ->where('owner_id', $ownerId)
            ->orderByDesc('created_at')
            ->first();

        // "In effect today" via the model's CERTIFIED effectiveOn scope (status=active AND
        // effective_from <= today <= effective_to|null) — never a re-implemented date predicate.
        $currentIds = ManagementAgreement::query()
            ->where('owner_id', $ownerId)
            ->effectiveOn($today)
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $current = $agreements->filter(
            fn (ManagementAgreement $a): bool => in_array((string) $a->id, $currentIds, true)
        )->values();

        // The portfolio-wide agreement in effect headlines; per-unit agreements are overrides on top of it
        // (most-specific-wins is the attribution engine's job, never this screen's).
        $primary = $current->first(fn (ManagementAgreement $a): bool => $a->unit_id === null) ?? $current->first();
        $primaryId = $primary instanceof ManagementAgreement ? (string) $primary->id : null;

        $unitLabels = $this->unitLabels($units);
        $payoutCurrency = (string) $record->payout_currency;
        $balance = $this->ledgerBalance($ledger);

        return view('livewire.operator.owner-detail', [
            'viewer' => $this->viewer(),
            'owner' => [
                'name' => (string) $record->name,
                'initials' => $this->initials((string) $record->name),
                'legal_name' => $this->nullableString($record->legal_name),
                'type_label' => $this->ownerTypeLabel((string) $record->type),
                'status_meta' => $this->ownerStatusMeta((string) $record->status),
                'payout_currency' => $payoutCurrency,
                // PII rendered by an explicit, recorded decision — operator's own CRM record (see docblock).
                // tax_id + the raw address JSON are NOT read and NOT rendered.
                'email' => $this->nullableString($record->email),
                'phone' => $this->nullableString($record->phone),
                'notes' => $this->nullableString($record->notes),
            ],
            'portal' => $this->portalAccess($record, $invite),
            'kpis' => [
                // Per currency, NEVER summed across currencies (invariant #17).
                'balance' => $this->moneyLines($balance, $payoutCurrency),
                'paid' => $this->moneyLines($this->payoutTotals($payouts, 'paid'), $payoutCurrency),
                'pending' => $this->moneyLines($this->payoutTotals($payouts, 'pending'), $payoutCurrency),
                'multi_currency' => count($balance) > 1,
                'units' => $units->count(),
            ],
            'units' => $this->unitRows($units),
            'agreement' => $primary instanceof ManagementAgreement
                ? $this->agreementCard($primary, $unitLabels, $payoutCurrency)
                : null,
            'agreementHistory' => $this->agreementRows(
                $agreements->reject(fn (ManagementAgreement $a): bool => (string) $a->id === $primaryId)->values(),
                $unitLabels,
                $currentIds,
                $today,
            ),
            'statements' => $this->statementRows($statements),
            'payouts' => $this->payoutRows($payouts),
        ]);
    }

    // ── KPI band — DERIVED, per currency ─────────────────────────────────────────────────────────

    /**
     * The owner's balance = Σ signedMinor() over EVERY real ledger row, bucketed BY CURRENCY. Reversal
     * rows are ordinary rows in the sum (the ledger is append-only, correct-by-reversal). Bucketing is
     * what keeps invariant #17: two currencies produce two figures, never one silently FX-ed total.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $ledger
     * @return array<string, int>
     */
    private function ledgerBalance(Collection $ledger): array
    {
        $totals = [];

        foreach ($ledger as $entry) {
            $currency = (string) $entry->currency;
            $totals[$currency] = ($totals[$currency] ?? 0) + $entry->signedMinor();
        }

        return $totals;
    }

    /**
     * Σ the owner's payouts in ONE real status ('paid' | 'pending'), bucketed BY CURRENCY.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return array<string, int>
     */
    private function payoutTotals(Collection $payouts, string $status): array
    {
        $totals = [];

        foreach ($payouts as $payout) {
            if ((string) $payout->status !== $status) {
                continue;
            }

            $currency = (string) $payout->currency;
            $totals[$currency] = ($totals[$currency] ?? 0) + (int) $payout->amount_minor;
        }

        return $totals;
    }

    /**
     * Format a currency→minor bucket for display, one line per currency. An EMPTY bucket is a genuine
     * zero (Σ over no rows), rendered in the owner's own REAL payout_currency — not a guessed one.
     *
     * @param  array<string, int>  $totals
     * @return list<string>
     */
    private function moneyLines(array $totals, string $fallbackCurrency): array
    {
        if ($totals === []) {
            return [$this->money(0, $fallbackCurrency)];
        }

        ksort($totals);

        $lines = [];

        foreach ($totals as $currency => $minor) {
            $lines[] = $this->money($minor, $currency);
        }

        return $lines;
    }

    // ── Units ────────────────────────────────────────────────────────────────────────────────────

    /**
     * @param  Collection<int, Unit>  $units
     * @return list<array<string, mixed>>
     */
    private function unitRows(Collection $units): array
    {
        return $units->map(fn (Unit $u): array => [
            'code' => (string) $u->code,
            'name' => $this->nullableString($u->name),
            'kind_label' => $this->unitKindLabel((string) $u->unit_kind),
            'room_type' => $u->roomType !== null ? (string) $u->roomType->name : null,
            'sleeps' => (int) $u->max_occupancy,
            'active' => (bool) $u->is_active,
        ])->values()->all();
    }

    /**
     * Labels for the owner's OWN units only — the collection is already filtered by owner_id, so an
     * agreement pointing at a unit outside this set is labelled generically rather than named.
     *
     * @param  Collection<int, Unit>  $units
     * @return array<string, string>
     */
    private function unitLabels(Collection $units): array
    {
        $labels = [];

        foreach ($units as $unit) {
            $name = (string) $unit->name;
            $labels[(string) $unit->id] = (string) $unit->code.($name !== '' ? ' · '.$name : '');
        }

        return $labels;
    }

    // ── Agreement ────────────────────────────────────────────────────────────────────────────────

    /**
     * The full presentation payload for the agreement in effect today.
     *
     * @param  array<string, string>  $unitLabels
     * @return array<string, mixed>
     */
    private function agreementCard(ManagementAgreement $a, array $unitLabels, string $ownerPayoutCurrency): array
    {
        $fee = $this->feeDisplay($a);
        $currency = $this->payoutCurrency($a, $ownerPayoutCurrency);

        return [
            'commission_display' => $this->commissionDisplay($a),
            'fee_display' => $fee,
            'fee_interval' => $fee === null ? null : $this->intervalLabel($a->management_fee_interval),
            'schedule' => $this->intervalLabel($a->payout_schedule),
            'payout_currency' => $currency['code'],
            'payout_currency_source' => $currency['source'],
            'scope_label' => $this->scopeLabel($a, $unitLabels),
            'from_display' => $this->dateDisplay($a->effective_from),
            'to_display' => $this->dateDisplay($a->effective_to),
            'status_meta' => $this->agreementStatusMeta((string) $a->status),
        ];
    }

    /**
     * Rows for every agreement that is not the hero — other terms in effect, plus history.
     *
     * @param  Collection<int, ManagementAgreement>  $set
     * @param  array<string, string>  $unitLabels
     * @param  list<string>  $currentIds
     * @return list<array<string, mixed>>
     */
    private function agreementRows(Collection $set, array $unitLabels, array $currentIds, Carbon $today): array
    {
        return $set->map(fn (ManagementAgreement $a): array => [
            'scope_label' => $this->scopeLabel($a, $unitLabels),
            'commission_display' => $this->commissionDisplay($a),
            'fee_display' => $this->feeDisplay($a),
            'from_display' => $this->dateDisplay($a->effective_from),
            'to_display' => $this->dateDisplay($a->effective_to),
            'in_effect' => in_array((string) $a->id, $currentIds, true),
            'timing_note' => $this->timingNote($a, $today),
            'status_meta' => $this->agreementStatusMeta((string) $a->status),
        ])->values()->all();
    }

    /**
     * The REAL commission, e.g. "15.00%" — the `decimal:2` cast already renders the stored scale, so the
     * figure is never re-rounded here. A null column → null → the view says "Not set", NEVER "0%".
     */
    private function commissionDisplay(ManagementAgreement $a): ?string
    {
        $pct = $a->commission_pct;

        return $pct === null ? null : ((string) $pct).'%';
    }

    /** The REAL fixed management fee in its OWN currency (money(), never a float). Null → "Not set". */
    private function feeDisplay(ManagementAgreement $a): ?string
    {
        $minor = $a->management_fee_minor;
        $currency = $a->management_fee_currency;

        if ($minor === null || ! is_string($currency) || strlen($currency) !== 3) {
            return null;
        }

        return $this->money((int) $minor, $currency);
    }

    /**
     * The REAL enum label for a fee interval / payout schedule — 'monthly'|'quarterly'|'yearly' only.
     * Anything else (or null) → null → "Not set"; a schedule is never invented to fill the row.
     */
    private function intervalLabel(mixed $value): ?string
    {
        return match ($value) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => null,
        };
    }

    /**
     * DERIVED: the currency the owner is paid in = the agreement override if set, else the owner's
     * profile default. The source is always labelled so the figure is never mistaken for the other.
     *
     * @return array{code: string|null, source: string|null}
     */
    private function payoutCurrency(ManagementAgreement $a, string $ownerPayoutCurrency): array
    {
        $onAgreement = is_string($a->payout_currency) && $a->payout_currency !== '' ? $a->payout_currency : null;

        if ($onAgreement !== null) {
            return ['code' => $onAgreement, 'source' => 'Set on this agreement'];
        }

        // owners.payout_currency is NOT NULL in the schema — only an empty value needs guarding.
        return $ownerPayoutCurrency !== ''
            ? ['code' => $ownerPayoutCurrency, 'source' => 'From the owner profile']
            : ['code' => null, 'source' => null];
    }

    /**
     * `unit_id` null = the terms cover the whole portfolio; set = that unit only.
     *
     * @param  array<string, string>  $unitLabels
     */
    private function scopeLabel(ManagementAgreement $a, array $unitLabels): string
    {
        if ($a->unit_id === null) {
            return 'Portfolio-wide';
        }

        return $unitLabels[(string) $a->unit_id] ?? 'Unit-specific';
    }

    /**
     * Factual DATE context for an agreement that is not in effect today — real columns only. A phase is
     * NEVER rendered as a status: `status` has exactly two values (active | ended), so inventing a third
     * ("Upcoming"/"Past") would be a fabricated state.
     */
    private function timingNote(ManagementAgreement $a, Carbon $today): ?string
    {
        $from = $this->asDate($a->effective_from);
        $to = $this->asDate($a->effective_to);

        if ($from !== null && $from->gt($today)) {
            return 'Starts '.$from->format('j M Y');
        }

        if ($to !== null && $to->lt($today)) {
            return 'Ran to '.$to->format('j M Y');
        }

        return null;
    }

    // ── Statements + payouts ─────────────────────────────────────────────────────────────────────

    /**
     * REAL statement rows. Breakdown figures (gross/commission) are in `source_currency`; `net_payable`
     * is in the payout `currency` — each is rendered in ITS OWN currency and the two are never added
     * together (invariant #17). No link: the statement detail is on the OWNER-scoped route, which an
     * operator session cannot pass (see the class docblock).
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function statementRows(Collection $statements): array
    {
        return $statements->map(function (OwnerStatement $s): array {
            $source = (string) $s->source_currency;
            $payout = (string) $s->currency;

            return [
                'sequence_no' => $s->sequence_no !== null ? (int) $s->sequence_no : null,
                'period' => $s->period_start->format('j M Y').' – '.$s->period_end->format('j M Y'),
                'gross' => $this->money((int) $s->gross_minor, $source),
                'commission' => $this->money((int) $s->commission_minor, $source),
                'net' => $this->money((int) $s->net_payable_minor, $payout),
                'status_meta' => $this->statementStatusMeta((string) $s->status),
                'has_unverified' => (bool) $s->has_unverified,
                'locked' => $s->isLocked(),
                'finalized' => $this->dateDisplay($s->finalized_at),
            ];
        })->values()->all();
    }

    /**
     * REAL payout rows. The rail label comes from the SHARED {@see RailLabel} map (the screen and the
     * remittance PDF must never disagree about how money reached an owner) — it is never re-matched here.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return list<array<string, mixed>>
     */
    private function payoutRows(Collection $payouts): array
    {
        return $payouts->map(fn (Payout $p): array => [
            'amount' => $this->money((int) $p->amount_minor, (string) $p->currency),
            'method' => RailLabel::for((string) $p->rail),
            'status_meta' => $this->payoutStatusMeta((string) $p->status),
            'paid_at' => $this->dateDisplay($p->paid_at),
            'failed_at' => $this->dateDisplay($p->failed_at),
            'failure_reason' => $this->nullableString($p->failure_reason),
            'external_ref' => $this->nullableString($p->external_ref),
        ])->values()->all();
    }

    // ── Real enums → label + WARM tone (never a status the column does not carry) ─────────────────

    /**
     * `owner_statements.status` — the enum is EXACTLY draft|finalized|paid. The design's "Scheduled" is
     * not one of them and is never rendered. An unexpected value is echoed, never re-badged.
     *
     * @return array{label: string, tone: string}
     */
    private function statementStatusMeta(string $status): array
    {
        return match ($status) {
            'draft' => ['label' => 'Draft', 'tone' => 'departed'],
            'finalized' => ['label' => 'Finalized', 'tone' => 'pending'],
            'paid' => ['label' => 'Paid', 'tone' => 'confirmed'],
            default => ['label' => ucfirst($status), 'tone' => 'departed'],
        };
    }

    /**
     * `payouts.status` — the enum is EXACTLY pending|paid|failed|reversed. A payout is never shown as
     * "Scheduled" (no such state) and a failure is always shown honestly rather than softened.
     *
     * @return array{label: string, tone: string}
     */
    private function payoutStatusMeta(string $status): array
    {
        return match ($status) {
            'pending' => ['label' => 'Pending', 'tone' => 'pending'],
            'paid' => ['label' => 'Paid', 'tone' => 'confirmed'],
            'failed' => ['label' => 'Failed', 'tone' => 'no-show'],
            'reversed' => ['label' => 'Reversed', 'tone' => 'cancelled'],
            default => ['label' => ucfirst($status), 'tone' => 'departed'],
        };
    }

    /**
     * `management_agreements.status` — the REAL enum only (active | ended).
     *
     * @return array{label: string, tone: string}
     */
    private function agreementStatusMeta(string $status): array
    {
        return match ($status) {
            'active' => ['label' => 'Active', 'tone' => 'confirmed'],
            'ended' => ['label' => 'Ended', 'tone' => 'departed'],
            default => ['label' => ucfirst($status), 'tone' => 'departed'],
        };
    }

    /**
     * `owners.status` — the REAL enum only (active | inactive). An owner is `inactive` until they accept
     * their invite, so this is a genuine, meaningful state, not decoration.
     *
     * @return array{label: string, tone: string}
     */
    private function ownerStatusMeta(string $status): array
    {
        return match ($status) {
            'active' => ['label' => 'Active', 'tone' => 'confirmed'],
            'inactive' => ['label' => 'Inactive', 'tone' => 'departed'],
            default => ['label' => ucfirst($status), 'tone' => 'departed'],
        };
    }

    /** `owners.type` — the REAL enum only (individual | company). */
    private function ownerTypeLabel(string $type): string
    {
        return match ($type) {
            'individual' => 'Individual',
            'company' => 'Company',
            default => ucfirst($type),
        };
    }

    // ── Portal access ────────────────────────────────────────────────────────────────────────────

    /**
     * The owner's REAL portal-access state.
     *
     * ⚠️ `owners.user_id !== null` is NOT sufficient to claim portal access: OwnerInviteService links the
     * owner to an INACTIVE (`status = 'invited'`) login the moment the invite is created, and the login
     * form is active-only. So the order below reports what is actually true:
     *   1. a still-PENDING invite → the pending invite it really is, with its REAL expiry as a date fact;
     *   2. a linked login that is genuinely `active` → portal access active (they can sign in today);
     *   3. any other real OwnerInvite state (accepted / expired / revoked) → that exact enum word;
     *   4. nothing → "No portal access".
     * No re-invite WRITE is offered here — the real invite POST lives on the Owners list screen.
     *
     * @return array{label: string, detail: string|null, tone: string}
     */
    private function portalAccess(Owner $record, ?OwnerInvite $invite): array
    {
        if ($invite !== null && (string) $invite->status === 'pending') {
            return [
                'label' => 'Invite pending',
                'detail' => $this->inviteExpiryNote($invite),
                'tone' => 'pending',
            ];
        }

        $user = $record->user;

        if ($record->user_id !== null && $user instanceof User && (string) $user->status === 'active') {
            return ['label' => 'Portal access active', 'detail' => null, 'tone' => 'confirmed'];
        }

        if ($invite !== null) {
            return match ((string) $invite->status) {
                'accepted' => ['label' => 'Invite accepted', 'detail' => $invite->accepted_at?->format('j M Y'), 'tone' => 'departed'],
                'expired' => ['label' => 'Invite expired', 'detail' => $this->inviteExpiryNote($invite), 'tone' => 'departed'],
                'revoked' => ['label' => 'Invite revoked', 'detail' => null, 'tone' => 'cancelled'],
                default => ['label' => ucfirst((string) $invite->status), 'detail' => null, 'tone' => 'departed'],
            };
        }

        return ['label' => 'No portal access', 'detail' => null, 'tone' => 'departed'];
    }

    /**
     * The invite's REAL `expires_at` as a factual date. Whether it has passed is a fact about the date,
     * not an invented status — the `status` column keeps whatever it genuinely holds until the daily
     * `invites:prune-expired` command flips it.
     */
    private function inviteExpiryNote(OwnerInvite $invite): ?string
    {
        $expires = $invite->expires_at;

        if ($expires === null) {
            return null;
        }

        return ($expires->isFuture() ? 'Expires ' : 'Expired ').$expires->format('j M Y');
    }

    // ── Small shared helpers ─────────────────────────────────────────────────────────────────────

    private function dateDisplay(mixed $value): ?string
    {
        return $this->asDate($value)?->format('j M Y');
    }

    /**
     * The `date` casts hand back Carbon at runtime; this stays tolerant of a raw string so a date is
     * either rendered from the REAL column or not rendered at all — never defaulted to "today".
     */
    private function asDate(mixed $value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }

    /** A nullable/blank column becomes null so the view can say "Not set" instead of printing nothing. */
    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
