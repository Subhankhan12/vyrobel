<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\OwnerManageController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Livewire\Owner\OwnerAgreement;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerStatement;
use App\Models\OwnerUser;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\RateProposal;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Services\PeriodLockGuard;
use App\Services\UnitOwnerAssignmentService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\I18n\LocalDate;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\CommissionChange;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Owner\OwnerPortalAccess;
use App\Support\Payouts\Iban;
use App\Support\Payouts\RailLabel;
use App\Support\Property\PropertyContext;
use App\Support\Property\PropertyDay;
use Carbon\CarbonImmutable;
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
 *   • WRITES (each a controller POST through every middleware, #20 — this component still only renders):
 *       – change the commission ({@see OwnerManageController::update()} by the ONE rule in
 *         {@see CommissionChange}) · pause / resume payouts · close a period · add a note · remove the owner (an archive,
 *         with your password and the owner's name) — `users.manage`, closing a period `payouts.manage`;
 *       – send a waiting invite again (your password) or cancel it — the Owners list's own writes, coming back here;
 *       – switch the owner's PORTAL ACCESS off (a typed reason) and on (your password) — DEC-o, 2026-11-12, `users.manage`:
 *         one date on the owner ({@see OwnerPortalAccess}), the team goes with them, nobody's login is touched;
 *       – propose a rate change / withdraw / accept a counter — `rates.manage`.
 *     ⚠️ Since 2026-10-03 each dialog reopens with its refusal beside the field (`_form` names the dialog) — before, a refused
 *     write closed its dialog and the page said nothing.
 *   • NOT OFFERED: any estimated next-payout DATE; "Scheduled" as a status
 *     (neither real enum holds it); the design's demo figures. A statement row carries no link (see NOT LINKED above).
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

        // ── #93 additions ─────────────────────────────────────────────────────────────────────────
        // Net-to-owner breakdown of the latest FINALIZED statement — the REAL stamped ledger, reconciling
        // Σ(credits) − Σ(deductions) == net by construction (#17), at the real agreement commission.
        $latestFinal = $statements->first(fn (OwnerStatement $s): bool => (string) $s->status !== 'draft');
        $netToOwner = null;
        if ($latestFinal !== null) {
            $stmtLedger = OwnerLedgerEntry::query()->where('owner_id', $ownerId)->where('statement_id', $latestFinal->id)->get();
            $breakdown = OwnerLedgerAttribution::breakdown($stmtLedger);
            $netToOwner = $breakdown[0] ?? null;
            if ($netToOwner !== null) {
                $netToOwner['period'] = CarbonImmutable::parse($latestFinal->period_start)->translatedFormat(LocalDate::pattern('M Y'));
                // ⚠️ the rate of the agreement that covered THAT period — today's rate mislabels a past statement once a change
                //    has taken effect (the review, 2026-10-03)
                $netToOwner['commission_pct'] = OwnerCommissionRate::trim(CommissionChange::governing($ownerId, CarbonImmutable::parse($latestFinal->period_start))?->commission_pct);
            }
        }

        // Paid this year + occupancy this year (owner's own physical units).
        $yearStart = CarbonImmutable::today()->startOfYear();
        $paidYearMinor = 0;
        $paidCount = 0;
        foreach ($payouts as $p) {
            if ((string) $p->status === 'paid' && $p->paid_at !== null && CarbonImmutable::parse($p->paid_at)->greaterThanOrEqualTo($yearStart)) {
                $paidYearMinor += (int) $p->amount_minor;
                $paidCount++;
            }
        }
        $unitIds = $units->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();
        $occupancy = PerformanceMetrics::forOwnerUnits($unitIds, $yearStart, CarbonImmutable::today(), $payoutCurrency)->occupancyPct();

        // Masked IBAN — CAP_PAYOUT only, never the full number, the operator never enters it (#28).
        $canPayout = (bool) auth()->user()?->can('payouts.manage');
        $method = PayoutMethod::query()->where('owner_id', $ownerId)->orderByDesc('is_default')->first();
        $ibanMasked = ($canPayout && $method !== null && (string) $method->iban_last4 !== '') ? Iban::masked((string) $method->iban_last4) : null;

        // Real payout-pause state + the operator note timeline.
        $payoutPaused = (bool) OwnerPayoutPreference::query()->where('owner_id', $ownerId)->value('paused');

        // The commission now, the change waiting to start, and where a save would start — the write's own rule (2026-10-03).
        $startsOn = CommissionChange::startsOn($ownerId, $today);
        $nowRate = CommissionChange::governing($ownerId, $today);
        $waiting = CommissionChange::plannedAfter($ownerId, $today);
        $commission = [
            'now' => OwnerCommissionRate::display($nowRate?->commission_pct),
            'next' => $waiting !== null && OwnerCommissionRate::trim($waiting->commission_pct) !== OwnerCommissionRate::trim($nowRate?->commission_pct)
                ? ['display' => OwnerCommissionRate::display($waiting->commission_pct) ?? __('php/livewire/operator/operator_owner_detail.no_rate'), 'from' => $waiting->effective_from->translatedFormat(LocalDate::pattern('j M Y')),
                    'replaced_by_save' => $waiting->effective_from->toDateString() === $startsOn->toDateString()]
                : null,
            'starts_on' => $startsOn->translatedFormat(LocalDate::pattern('j M Y')),
            // no agreement in effect today → a change starts today, not on the 1st (CommissionChange::startsOn)
            'starts_today' => $nowRate === null,
            'prefill' => CommissionChange::rateOn($ownerId, $startsOn) ?? OwnerCommissionRate::trim($nowRate?->commission_pct) ?? '',
        ];
        $noteEntries = $record->noteEntries()->with('author')->latest()->limit(20)->get()->map(fn ($n): array => [
            'body' => (string) $n->body,
            'author' => $n->author instanceof User ? (string) $n->author->name : __('php/livewire/operator/operator_owner_detail.author_fallback'),
            'when' => $n->created_at?->diffForHumans() ?? '',
        ])->all();

        // the people on the owner's team — said before a removal and before a portal switch, because both take them along
        $teamCount = OwnerUser::query()->where('owner_id', $ownerId)->where('status', 'active')->count();

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
            'netToOwner' => $netToOwner,
            'paidYear' => ['display' => MoneyFormatter::display($paidYearMinor, $payoutCurrency), 'count' => $paidCount],
            'occupancy' => $occupancy,
            'ibanMasked' => $ibanMasked,
            'canPayout' => $canPayout,
            'payoutPaused' => $payoutPaused,
            'noteEntries' => $noteEntries,
            'canManage' => (bool) auth()->user()?->can('users.manage'),
            'removeUnitNames' => $units->map(fn (Unit $u): string => (string) ($u->name ?? $u->code))->values()->all(),
            // ⚠️ How many people go with the owner — the modal states it because removal now cascades to
            //    the owner's team, and an operator should see the size of what they are about to close.
            'removeTeamCount' => $teamCount,
            'portal' => $this->portalAccess($record, $invite),
            // DEC-o — the switch: ON is always offered for an owner whose portal is off; OFF only for one whose login is active
            'portalOff' => $record->portalIsOff(),
            'portalCanSwitchOff' => ! $record->portalIsOff() && $record->user instanceof User && (string) $record->user->status === User::SIGN_IN_STATUS,
            'portalTeam' => $teamCount,
            'mailSends' => MailSafetyGuard::sendingNow(),
            // A waiting invite can be sent again (pending or expired) or cancelled (pending) from here — the Owners list's writes.
            'invite' => $invite !== null && in_array((string) $invite->status, ['pending', 'expired'], true) && (string) $invite->role !== OwnerInviteService::FOUNDER_ROLE
                ? ['id' => (string) $invite->id, 'email' => (string) $invite->email, 'cancellable' => (string) $invite->status === 'pending']
                : null,
            'commission' => $commission,
            // Cancelling a waiting invite also removes an owner who never activated — the service's own condition, said in
            // the dialog before it happens ({@see OwnerInviteService::cancel()}).
            'inviteCancelRemoves' => (string) $record->status === 'inactive' && ! ($record->user instanceof User && (string) $record->user->status === 'active'),
            // Whether the automatic payout run is switched on — the resume dialog only promises the next automatic run if so.
            'engineOn' => (bool) config('services.payouts.engine_enabled', false),
            'kpis' => [
                // Per currency, NEVER summed across currencies (invariant #17).
                'balance' => $this->moneyLines($balance, $payoutCurrency),
                'paid' => $this->moneyLines($this->payoutTotals($payouts, 'paid'), $payoutCurrency),
                'pending' => $this->moneyLines($this->payoutTotals($payouts, 'pending'), $payoutCurrency),
                'multi_currency' => count($balance) > 1,
                'units' => $units->count(),
            ],
            'units' => $this->unitRows($units),
            // DEC-p — the units this person may be given: no owner, inside the viewer's properties; each with today AT ITS property (the date's default and its floor)
            'giveUnits' => (bool) auth()->user()?->can('users.manage') ? $this->giveableUnits($record) : [],
            'ownerId' => $ownerId,
            // Wave C · GATE 1 — the owner's distinct room types (for the propose-a-rate-change form) + this owner's
            // rate proposals (pending are withdrawable). The service refuses a room type the owner doesn't own
            // exclusively, so listing all is safe.
            'roomTypes' => $units->map(fn (Unit $u): array => [
                'id' => (string) $u->room_type_id,
                'name' => (string) ($u->roomType->name ?? __('php/livewire/operator/operator_owner_detail.room_type_fallback')),
            ])->unique('id')->values()->all(),
            // ⚠️ The propose-a-rate form's "Proposed nightly rate (…)" label hardcoded '€'. A rate is priced in
            // the PROPERTY's currency, which is not necessarily the owner's `payout_currency` — so this is
            // deliberately resolved from the unit's property rather than reusing $payoutCurrency above.
            'rateCurrencySymbol' => $this->currencySymbol((string) ($units->first()?->property->currency ?? $payoutCurrency)),
            'proposals' => $this->proposalRows($ownerId),
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
            // ⚠️ AUDIT H-2 — the CLOSE-A-PERIOD control. `app.owners.close-period` and
            // OwnerStatementService::finalize() existed with NO view referencing them, so outside the demo
            // seeder no statement could ever be finalised and therefore NO PAYOUT COULD EVER BE CREATED.
            // These three values are what the control needs; they change no figure and post nothing.
            'closePeriod' => $this->closePeriodDefaults($today),
        ]);
    }

    /**
     * Defaults for the CLOSE-A-PERIOD control (audit H-2).
     *
     * ⚠️ THE DEFAULT MUST BE THE NEXT **OPEN** PERIOD, NOT SIMPLY LAST MONTH. `generateDraft()` throws
     * "Cannot regenerate a finalized/locked statement." for an already-sealed period, so a naive
     * last-calendar-month default made the very first click fail on demo data (April–October are sealed).
     * Found by driving the real page, not by reading it.
     *
     * `lockedThrough` comes from {@see PeriodLockGuard} — the SAME guard the attribution and expense
     * services consult — so the control can never disagree with the services about what is sealed.
     *
     * @return array{start: string, end: string, lockedThrough: ?string, lockedThroughWords: ?string, minStart: ?string}
     */
    private function closePeriodDefaults(Carbon $today): array
    {
        $lockedThrough = app(PeriodLockGuard::class)->lockedThrough($this->ownerId);

        $start = $lockedThrough !== null
            ? Carbon::parse($lockedThrough)->addDay()          // the day after everything sealed
            : $today->copy()->subMonthNoOverflow()->startOfMonth();

        return [
            'start' => $start->toDateString(),
            'end' => $start->copy()->endOfMonth()->toDateString(),
            'lockedThrough' => $lockedThrough,
            // ⚠️ the dialog printed the database date ("2026-09-30"); people read "30 Sep 2026" (2026-10-03)
            'lockedThroughWords' => $lockedThrough !== null ? Carbon::parse($lockedThrough)->translatedFormat(LocalDate::pattern('j M Y')) : null,
            // Bounds the picker so a sealed date cannot be chosen in the first place.
            'minStart' => $lockedThrough !== null ? Carbon::parse($lockedThrough)->addDay()->toDateString() : null,
        ];
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
    /**
     * This owner's rate proposals (Wave C · GATE 1) — a truthful before/after + state; a pending one is
     * withdrawable by the operator. Explicit owner_id filter (OwnerScope is inactive on /app).
     *
     * @return list<array{id: string, room_type: string, summary: string, state: string, pending: bool}>
     */
    private function proposalRows(string $ownerId): array
    {
        return RateProposal::query()
            ->where('owner_id', $ownerId)
            ->with('roomType')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (RateProposal $p): array {
                $currency = (string) $p->currency;
                $from = $p->current_rate_minor !== null ? MoneyFormatter::display((int) $p->current_rate_minor, $currency) : '—';
                $to = MoneyFormatter::display((int) $p->proposed_rate_minor, $currency);

                // ⚠️⚠️ THE COUNTER MUST REACH THE SCREEN, AND IT DID NOT (audit M-14 · F1).
                //
                // `summary` was built from `proposed_rate_minor` for EVERY state — so a COUNTERED proposal
                // showed the operator's OWN asking price with "Status: Countered" beneath it, and the
                // owner's counter-offer appeared nowhere on this page. That is worse than a blank: a stale
                // number rendered confidently, next to a label saying it had been superseded.
                // ⚠️ The data was always there (`countered_rate_minor`, written by
                // `RateProposalService::counter()`) and the owner's own portal already displays it
                // (`OwnerRates:143`). Only THIS view never read it — the false-decline shape.
                $countered = $p->isCountered() && $p->countered_rate_minor !== null;
                $counter = $countered
                    ? MoneyFormatter::display((int) $p->countered_rate_minor, $currency)
                    : null;

                return [
                    'id' => (string) $p->id,
                    'room_type' => (string) ($p->roomType->name ?? __('php/livewire/operator/operator_owner_detail.room_type_fallback')),
                    // ⚠️ On a countered row the headline is the OWNER'S number, because that is the one a
                    //    decision would apply. The operator's original ask is kept beside it, not replaced.
                    'summary' => __('php/livewire/operator/operator_owner_detail.proposal_summary', ['from' => $from, 'to' => $countered ? $counter : $to]),
                    'proposed' => $to,
                    'counter' => $counter,
                    'state' => (string) $p->state,
                    'state_label' => $this->proposalStateLabel((string) $p->state),
                    'pending' => $p->isPending(),
                    'countered' => $countered,
                ];
            })
            ->all();
    }

    /** The proposal's REAL state in words — a match keyed by the code; an unexpected value is echoed, never re-badged. */
    private function proposalStateLabel(string $state): string
    {
        return match ($state) {
            RateProposal::STATE_PENDING => __('php/livewire/operator/operator_owner_detail.proposal_pending'),
            RateProposal::STATE_APPROVED => __('php/livewire/operator/operator_owner_detail.proposal_approved'),
            RateProposal::STATE_DECLINED => __('php/livewire/operator/operator_owner_detail.proposal_declined'),
            RateProposal::STATE_WITHDRAWN => __('php/livewire/operator/operator_owner_detail.proposal_withdrawn'),
            RateProposal::STATE_COUNTERED => __('php/livewire/operator/operator_owner_detail.proposal_countered'),
            default => ucfirst($state),
        };
    }

    private function unitRows(Collection $units): array
    {
        return $units->map(fn (Unit $u): array => [
            'code' => (string) $u->code,
            'name' => $this->nullableString($u->name),
            'kind_label' => $this->unitKindLabel((string) $u->unit_kind),
            'room_type' => $u->roomType !== null ? (string) $u->roomType->name : null,
            'sleeps' => (int) $u->max_occupancy,
            'active' => (bool) $u->is_active,
            // DEC-p — the day the owner took the unit over ("since" once it has begun, "from" while it is ahead); null = always (an invite or an approved request)
            'since' => $u->owner_since?->translatedFormat(LocalDate::pattern('j M Y')),
            'since_ahead' => $u->owner_since !== null && $u->owner_since->toDateString() > Carbon::today()->toDateString(),
        ])->values()->all();
    }

    /**
     * DEC-p — the units this owner may be given from here: ones with NO owner, inside the viewer's properties (the same rule the write applies), and only for an
     * active owner (the write refuses anyone else). Each carries today AT ITS PROPERTY (the day the owner may start from at the earliest) and the last day allowed.
     *
     * @return list<array{id: string, label: string, property: ?string, today: string, max: string}>
     */
    private function giveableUnits(Owner $owner): array
    {
        if ((string) $owner->status !== 'active') {
            return [];
        }

        $ids = app(PropertyContext::class)->readIds();
        // ⚠️ D-DEC-P-INVITE-PICKER: a unit a waiting invitation lists is promised to that invitee, so it is not offered here (the write refuses it too)
        $promised = OwnerInvite::unitIdsOnPendingInvites();
        $units = Unit::query()->with('property:id,name')->whereNull('owner_id')
            ->when($promised !== [], fn ($q) => $q->whereNotIn('id', $promised))
            ->when($ids !== [], fn ($q) => $q->whereIn('property_id', $ids))
            ->orderBy('code')->get(['id', 'code', 'name', 'property_id']);
        $todays = PropertyDay::todays($units->pluck('property_id')->map(fn (mixed $id): string => (string) $id)->unique()->values()->all());
        $manyProperties = $units->pluck('property_id')->unique()->count() > 1;

        return $units->sortBy(fn (Unit $u): string => (string) $u->code, SORT_NATURAL)->values()->map(function (Unit $u) use ($todays, $manyProperties): array {
            $today = $todays[(string) $u->property_id] ?? Carbon::today()->toDateString();

            return [
                'id' => (string) $u->id,
                'label' => (string) ($u->name !== null && (string) $u->name !== '' ? $u->name : $u->code),
                'property' => $manyProperties && $u->property !== null ? (string) $u->property->name : null,
                'today' => $today,
                'max' => CarbonImmutable::parse($today)->addDays(UnitOwnerAssignmentService::MAX_DAYS_AHEAD)->toDateString(),
            ];
        })->all();
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
            // replaced before its first day (CommissionChange) — its dates run backwards, so the view says so in words
            // ⚠️ I18N-01: decided from the dates (a code), never by comparing the translated note (CONVERSION.md rule 10)
            'never_applied' => $this->replacedBeforeStart($a),
            'status_meta' => $this->agreementStatusMeta((string) $a->status),
        ])->values()->all();
    }

    /**
     * The REAL commission, e.g. "15.00%" — the `decimal:2` cast already renders the stored scale, so the
     * figure is never re-rounded here. A null column → null → the view says "Not set", NEVER "0%".
     */
    /** Via the ONE shared formatter (wireframe-parity WAVE 3) — a raw `decimal:2` cast printed "15.00%". */
    private function commissionDisplay(ManagementAgreement $a): ?string
    {
        return OwnerCommissionRate::display($a->commission_pct);
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
            'monthly' => __('php/livewire/operator/operator_owner_detail.interval_monthly'),
            'quarterly' => __('php/livewire/operator/operator_owner_detail.interval_quarterly'),
            'yearly' => __('php/livewire/operator/operator_owner_detail.interval_yearly'),
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
            return ['code' => $onAgreement, 'source' => __('php/livewire/operator/operator_owner_detail.currency_source_agreement')];
        }

        // owners.payout_currency is NOT NULL in the schema — only an empty value needs guarding.
        return $ownerPayoutCurrency !== ''
            ? ['code' => $ownerPayoutCurrency, 'source' => __('php/livewire/operator/operator_owner_detail.currency_source_profile')]
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
            return __('php/livewire/operator/operator_owner_detail.scope_portfolio');
        }

        return $unitLabels[(string) $a->unit_id] ?? __('php/livewire/operator/operator_owner_detail.scope_unit_specific');
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

        // ⚠️ A change replaced before its first day ends the day before it starts (CommissionChange) — say so in words.
        if ($this->replacedBeforeStart($a)) {
            return __('php/livewire/operator/operator_owner_detail.timing_replaced');
        }

        if ($from !== null && $from->gt($today)) {
            return __('php/livewire/operator/operator_owner_detail.timing_starts', ['date' => $from->translatedFormat(LocalDate::pattern('j M Y'))]);
        }

        if ($to !== null && $to->lt($today)) {
            return __('php/livewire/operator/operator_owner_detail.timing_ran_to', ['date' => $to->translatedFormat(LocalDate::pattern('j M Y'))]);
        }

        return null;
    }

    /** A change replaced before its first day: it ends the day before it starts (CommissionChange) — its dates run backwards. */
    private function replacedBeforeStart(ManagementAgreement $a): bool
    {
        $from = $this->asDate($a->effective_from);
        $to = $this->asDate($a->effective_to);

        return $from !== null && $to !== null && $to->lt($from);
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
                'period' => __('php/livewire/operator/operator_owner_detail.period_range', ['start' => $s->period_start->translatedFormat(LocalDate::pattern('j M Y')), 'end' => $s->period_end->translatedFormat(LocalDate::pattern('j M Y'))]),
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
            'draft' => ['label' => __('php/livewire/operator/operator_owner_detail.statement_draft'), 'tone' => 'departed'],
            'finalized' => ['label' => __('php/livewire/operator/operator_owner_detail.statement_finalized'), 'tone' => 'pending'],
            'paid' => ['label' => __('php/livewire/operator/operator_owner_detail.statement_paid'), 'tone' => 'confirmed'],
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
            'active' => ['label' => __('php/livewire/operator/operator_owner_detail.agreement_active'), 'tone' => 'confirmed'],
            'ended' => ['label' => __('php/livewire/operator/operator_owner_detail.agreement_ended'), 'tone' => 'departed'],
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
            'active' => ['label' => __('php/livewire/operator/operator_owner_detail.owner_active'), 'tone' => 'confirmed'],
            'inactive' => ['label' => __('php/livewire/operator/operator_owner_detail.owner_inactive'), 'tone' => 'departed'],
            default => ['label' => ucfirst($status), 'tone' => 'departed'],
        };
    }

    /** `owners.type` — the REAL enum only (individual | company). */
    private function ownerTypeLabel(string $type): string
    {
        return match ($type) {
            'individual' => __('php/livewire/operator/operator_owner_detail.type_individual'),
            'company' => __('php/livewire/operator/operator_owner_detail.type_company'),
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
        // ⚠️ DEC-o — an operator switched it OFF: that wins over every other state (a pending invite, an active login), because
        //    none of them lets anyone in while it is off. The date is a fact; who and why are in the audit log.
        if ($record->portalIsOff()) {
            return [
                'label' => __('php/livewire/operator/operator_owner_detail.portal_off'),
                'detail' => __('php/livewire/operator/operator_owner_detail.portal_off_since', ['date' => $record->portal_disabled_at?->translatedFormat(LocalDate::pattern('j M Y'))]),
                'tone' => 'cancelled',
            ];
        }

        if ($invite !== null && (string) $invite->status === 'pending') {
            return [
                'label' => __('php/livewire/operator/operator_owner_detail.portal_invite_pending'),
                'detail' => $this->inviteExpiryNote($invite),
                'tone' => 'pending',
            ];
        }

        $user = $record->user;

        if ($record->user_id !== null && $user instanceof User && (string) $user->status === 'active') {
            return ['label' => __('php/livewire/operator/operator_owner_detail.portal_active'), 'detail' => null, 'tone' => 'confirmed'];
        }

        if ($invite !== null) {
            return match ((string) $invite->status) {
                'accepted' => ['label' => __('php/livewire/operator/operator_owner_detail.portal_invite_accepted'), 'detail' => $invite->accepted_at?->translatedFormat(LocalDate::pattern('j M Y')), 'tone' => 'departed'],
                'expired' => ['label' => __('php/livewire/operator/operator_owner_detail.portal_invite_expired'), 'detail' => $this->inviteExpiryNote($invite), 'tone' => 'departed'],
                'revoked' => ['label' => __('php/livewire/operator/operator_owner_detail.portal_invite_revoked'), 'detail' => null, 'tone' => 'cancelled'],
                default => ['label' => ucfirst((string) $invite->status), 'detail' => null, 'tone' => 'departed'],
            };
        }

        return ['label' => __('php/livewire/operator/operator_owner_detail.portal_none'), 'detail' => null, 'tone' => 'departed'];
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

        return $expires->isFuture()
            ? __('php/livewire/operator/operator_owner_detail.invite_expires', ['date' => $expires->translatedFormat(LocalDate::pattern('j M Y'))])
            : __('php/livewire/operator/operator_owner_detail.invite_expired', ['date' => $expires->translatedFormat(LocalDate::pattern('j M Y'))]);
    }

    // ── Small shared helpers ─────────────────────────────────────────────────────────────────────

    private function dateDisplay(mixed $value): ?string
    {
        return $this->asDate($value)?->translatedFormat(LocalDate::pattern('j M Y'));
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
