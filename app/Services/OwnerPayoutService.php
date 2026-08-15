<?php

namespace App\Services;

use App\Exceptions\Owner\PayoutHeldException;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Payouts\Contracts\PayoutRail;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * OwnerPayoutService (Phase 3 · Module 1 Step 5) — turns a FINALIZED statement's net payable into a
 * payout and disburses it through a config-selected PayoutRail. Calculation + lifecycle only; the
 * real money rail (Stripe Connect) is a deferred, config-gated skeleton.
 *
 * Negative-net behaviour (chosen + documented): a statement's net is period activity. When it is
 * POSITIVE a payout is created; when it is ZERO nothing is owed and no payout is created; when it is
 * NEGATIVE (e.g. a period dominated by a post-lock refund) the owner is NEVER reverse-charged —
 * instead the negative balance is CARRIED FORWARD as a single `adjustment` ledger line dated into the
 * next period, so it offsets a future positive payout (standard "balance brought forward").
 *
 * Append-only lifecycle: status transitions are explicit and audited; a retry of a failed payout or a
 * reversal of a paid one is a NEW row (`supersedes_id`) — history is always reconstructable.
 */
class OwnerPayoutService
{
    public function __construct(
        private PayoutRail $rail,
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Create the payout for a finalized statement's net payable. Returns null when nothing is payable
     * (zero net), or when a negative net is carried forward instead. Idempotent: an existing
     * pending/processing/held/paid payout for the statement is returned as-is (so an automated engine
     * re-run never creates a SECOND payout for a statement already in flight). `$payoutRunId` links the
     * created payout to the engine run that made it (null for a manual/operator-triggered payout).
     */
    public function createFromStatement(OwnerStatement $statement, ?string $payoutRunId = null): ?Payout
    {
        if (! $statement->isLocked()) {
            throw new RuntimeException('A payout can only be created from a finalized (locked) statement, not a draft.');
        }

        // Any non-terminal-or-successful payout already claims this statement — return it, never a second row.
        $existing = Payout::query()
            ->where('owner_statement_id', $statement->getKey())
            ->whereIn('status', Payout::LIVE_STATUSES)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $net = (int) $statement->net_payable_minor;

        if ($net < 0) {
            $this->carryForward($statement);

            return null;
        }

        if ($net === 0) {
            return null; // nothing owed
        }

        $payout = Payout::create([
            'organization_id' => $statement->organization_id,
            'owner_id' => $statement->owner_id,
            'owner_statement_id' => $statement->getKey(),
            'amount_minor' => $net,
            'currency' => $statement->currency,
            'rail' => $this->rail->name(),
            'status' => 'pending',
            'payout_run_id' => $payoutRunId,
        ]);

        $this->domainEvents->record('payout.created', $payout, ['amount_minor' => $net]);
        $this->audit->record('payout.created', $payout, null, ['status' => 'pending', 'amount_minor' => $net]);

        return $payout;
    }

    /**
     * Disburse a pending payout through the (config-selected or supplied) rail. On success a manual/offline
     * payout is marked `paid` + its statement flipped to paid; a Stripe Connect transfer is marked
     * `processing` (the Connect webhook confirms it → `paid`). On failure the payout is marked failed (retry
     * via {@see retry()} creates a new record).
     *
     * 🔒 NO DOUBLE-TRANSFER (the highest-stakes guard): the whole disburse runs inside a DB transaction that
     * ROW-LOCKS the payout (`lockForUpdate`) and RE-CHECKS `pending` INSIDE the lock — so a concurrent pay()
     * (a double-click, a webhook racing a sync callback) BLOCKS, then sees the row is no longer pending and
     * refuses, never sending a second transfer. The rail's deterministic Idempotency-Key backstops it at
     * Stripe. Two layers, both load-bearing.
     */
    public function pay(Payout $payout, ?PayoutRail $rail = null): Payout
    {
        $rail ??= $this->rail;

        [$result, $fresh] = DB::transaction(function () use ($payout, $rail): array {
            // 🔒 Row-lock THIS payout; a concurrent pay() waits here until we commit.
            $locked = Payout::query()->lockForUpdate()->findOrFail($payout->getKey());

            // Re-check INSIDE the lock — a second caller now sees a non-pending row and refuses (no 2nd transfer).
            if ($locked->status !== 'pending') {
                throw new RuntimeException("Only a pending payout can be paid (status: {$locked->status}).");
            }

            // HELD until tax details are on file (Sub-gate 4) — BEFORE the transfer, so no money moves for a
            // tax-incomplete owner; releases automatically once they add a tax ID.
            $owner = $locked->owner;
            if ($owner !== null && ! $owner->hasTaxDetails()) {
                throw PayoutHeldException::missingTaxDetails((string) $owner->name);
            }

            $result = $rail->send($locked);   // the transfer — still under the lock (idempotency-keyed by the rail)

            if ($result->successful) {
                $locked->update([
                    // A Connect transfer is INITIATED here; the Connect webhook flips it to `paid`. Manual = paid now.
                    'status' => $result->pending ? 'processing' : 'paid',
                    'rail' => $rail->name(),
                    'external_ref' => $result->externalRef,
                    'paid_at' => $result->pending ? null : now(),
                ]);

                if (! $result->pending) {
                    OwnerStatement::query()->whereKey($locked->owner_statement_id)->update(['status' => 'paid']);
                }
            } else {
                $locked->update([
                    'status' => 'failed',
                    'rail' => $rail->name(),
                    'failure_reason' => $result->failureReason,
                    'failed_at' => now(),
                ]);
            }

            return [$result, $locked];
        });

        $event = $result->successful ? 'payout.paid' : 'payout.failed';
        $this->domainEvents->record($event, $fresh->refresh(), ['rail' => $rail->name()]);
        $this->audit->record($event, $fresh, ['status' => 'pending'], ['status' => $fresh->status, 'rail' => $rail->name()]);

        return $fresh->refresh();
    }

    /**
     * Confirm a Stripe Connect payout `processing → paid` from the CONNECT webhook (transfer.created), and flip
     * its statement to paid. Idempotent: only a `processing` row advances (a re-delivered webhook is a no-op),
     * so real money is confirmed exactly once. Resolved by the transfer id (external_ref).
     */
    public function confirmTransfer(string $transferId): void
    {
        $payout = Payout::query()->where('external_ref', $transferId)->where('status', 'processing')->first();
        if ($payout === null) {
            return;   // unknown / already confirmed → no-op
        }

        DB::transaction(function () use ($payout): void {
            $payout->update(['status' => 'paid', 'paid_at' => now()]);
            OwnerStatement::query()->whereKey($payout->owner_statement_id)->update(['status' => 'paid']);
        });

        $this->domainEvents->record('payout.paid', $payout->refresh(), ['rail' => 'stripe_connect']);
        $this->audit->record('payout.paid', $payout, ['status' => 'processing'], ['status' => 'paid']);
    }

    /**
     * HOLD a pending payout (the automated payout engine · Phase 1) — persist the tax-hold as a REAL,
     * visible state instead of leaving the row `pending`. Called by the engine when {@see pay()} raises
     * {@see PayoutHeldException} (owner has no tax details on file). NO money has moved — the guard in
     * pay() runs BEFORE the rail — so this only records that the payout is waiting on tax details.
     * Auto-releasable: {@see releaseHold()} returns it to `pending` once the owner completes their tax id.
     */
    public function hold(Payout $payout): Payout
    {
        if ($payout->status !== 'pending') {
            throw new RuntimeException("Only a pending payout can be held (status: {$payout->status}).");
        }

        $payout->update(['status' => 'held', 'held_at' => now()]);

        $this->domainEvents->record('payout.held', $payout->refresh(), ['reason' => 'missing_tax_details']);
        $this->audit->record('payout.held', $payout, ['status' => 'pending'], ['status' => 'held']);

        return $payout->refresh();
    }

    /**
     * RELEASE a held payout back to `pending` (the engine · Phase 1) — the auto-release once the owner's
     * tax details are on file. The next {@see pay()} then passes the tax-hold guard and disburses normally.
     * Guarded: only a `held` payout can be released (a re-run is a no-op on an already-released row).
     */
    public function releaseHold(Payout $payout): Payout
    {
        if ($payout->status !== 'held') {
            throw new RuntimeException("Only a held payout can be released (status: {$payout->status}).");
        }

        $payout->update(['status' => 'pending', 'held_at' => null]);

        $this->domainEvents->record('payout.released', $payout->refresh(), []);
        $this->audit->record('payout.released', $payout, ['status' => 'held'], ['status' => 'pending']);

        return $payout->refresh();
    }

    /**
     * Retry a failed payout — a NEW pending record superseding the failed one (append-only). The
     * failed row is preserved so the history reconstructs what was attempted, when, and why it failed.
     *
     * ⚠️ THIS METHOD DOES NOT MOVE MONEY. It re-QUEUES: the new row is `pending`, and only {@see pay()}
     * (operator-permissioned, rail-gated) ever sends. That separation is why an owner may trigger a retry
     * at all — they ask for another attempt; they never touch the rail.
     *
     * 🔒 NO DOUBLE-PAY (the highest-stakes guard on this path — a second live row for ONE statement is a
     * genuine double payment, because {@see PayoutRunService::processOwner()} disburses EVERY pending
     * payout an owner has). Two layers, both inside one transaction:
     *
     *   1. ROW-LOCK the target + RE-CHECK `failed` INSIDE the lock. The caller's copy — and the status a
     *      page rendered minutes ago — is a SNAPSHOT. A Connect webhook landing in between can move the
     *      real row on, so the decision is made against the freshly-locked row, never the displayed one.
     *   2. THE SAME "one live payout per statement" INVARIANT {@see createFromStatement()} enforces (§N.8f
     *      — one rule, not a second copy of it): if a `pending`/`processing`/`held`/`paid` payout already
     *      claims this statement, that row is RETURNED as-is and nothing is created. This is what makes a
     *      double-click, a retry of a row whose sibling already succeeded, and a retry stacking on one
     *      already in flight all resolve to exactly ONE effect.
     *
     * Callers distinguish the two outcomes with `wasRecentlyCreated`, the same idiom
     * {@see PayoutRunService} already uses for {@see createFromStatement()}.
     */
    public function retry(Payout $failed): Payout
    {
        [$retry, $created] = DB::transaction(function () use ($failed): array {
            // 🔒 Row-lock THIS payout; a concurrent retry() waits here until we commit.
            $locked = Payout::query()->lockForUpdate()->findOrFail($failed->getKey());

            // Re-check INSIDE the lock — never trust the passed-in (or displayed) status.
            if ($locked->status !== 'failed') {
                throw new RuntimeException("Only a failed payout can be retried (status: {$locked->status}).");
            }

            // 🔒 One live payout per statement — the createFromStatement invariant, enforced here too.
            $live = Payout::query()
                ->where('owner_statement_id', $locked->owner_statement_id)
                ->whereIn('status', Payout::LIVE_STATUSES)
                ->lockForUpdate()
                ->first();

            if ($live !== null) {
                return [$live, false];   // already re-queued / in flight / already paid → never a second row
            }

            $retry = Payout::create([
                'organization_id' => $locked->organization_id,
                'owner_id' => $locked->owner_id,
                'owner_statement_id' => $locked->owner_statement_id,
                'amount_minor' => $locked->amount_minor,
                'currency' => $locked->currency,
                'rail' => $this->rail->name(),
                'status' => 'pending',
                'supersedes_id' => $locked->getKey(),
            ]);

            return [$retry, true];
        });

        if ($created) {
            $this->domainEvents->record('payout.retried', $retry, ['supersedes_id' => $failed->getKey()]);
            $this->audit->record('payout.retried', $retry, null, ['supersedes_id' => $failed->getKey()]);
        }

        return $retry;
    }

    /**
     * Reverse a paid payout — a NEW `reversed` record superseding the paid one; the source statement
     * returns to finalized (unpaid). The paid row is never edited away.
     */
    public function reverse(Payout $paid, string $reason): Payout
    {
        if ($paid->status !== 'paid') {
            throw new RuntimeException("Only a paid payout can be reversed (status: {$paid->status}).");
        }

        $reversal = DB::transaction(function () use ($paid, $reason): Payout {
            $reversal = Payout::create([
                'organization_id' => $paid->organization_id,
                'owner_id' => $paid->owner_id,
                'owner_statement_id' => $paid->owner_statement_id,
                'amount_minor' => $paid->amount_minor,
                'currency' => $paid->currency,
                'rail' => $paid->rail,
                'status' => 'reversed',
                'supersedes_id' => $paid->getKey(),
                'failure_reason' => $reason,
            ]);

            OwnerStatement::query()->whereKey($paid->owner_statement_id)->update(['status' => 'finalized']);

            return $reversal;
        });

        $this->domainEvents->record('payout.reversed', $reversal, ['supersedes_id' => $paid->getKey()]);
        $this->audit->record('payout.reversed', $reversal, ['status' => 'paid'], ['status' => 'reversed']);

        return $reversal;
    }

    /**
     * Carry a negative statement net forward as a single adjustment ledger line dated into the next
     * period, so it offsets a future positive payout instead of reverse-charging the owner.
     */
    private function carryForward(OwnerStatement $statement): void
    {
        $nextDate = CarbonImmutable::parse($statement->period_end)->addDay()->toDateString();
        $amount = abs((int) $statement->net_source_minor);

        OwnerLedgerEntry::firstOrCreate(
            ['idempotency_key' => 'carry:'.$statement->getKey()],
            [
                'organization_id' => $statement->organization_id,
                'owner_id' => $statement->owner_id,
                'entry_type' => 'adjustment',
                'direction' => 'debit', // net_source is negative → carry a debit into the next period
                'amount_minor' => $amount,
                'currency' => $statement->source_currency,
                'description' => 'Balance carried forward from statement '.($statement->sequence_no ?? ''),
                'service_date' => $nextDate,
                'attribution_version' => 1,
                'prior_period_adjustment' => true,
                'note' => 'balance_carried_forward',
                'source_type' => $statement->getMorphClass(),
                'source_id' => $statement->getKey(),
                'posted_at' => now(),
            ],
        );

        $this->domainEvents->record('owner_statement.balance_carried_forward', $statement, [
            'amount_minor' => $amount,
            'to_date' => $nextDate,
        ]);
    }
}
