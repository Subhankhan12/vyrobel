<?php

namespace App\Services;

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
     * pending/paid payout for the statement is returned as-is.
     */
    public function createFromStatement(OwnerStatement $statement): ?Payout
    {
        if (! $statement->isLocked()) {
            throw new RuntimeException('A payout can only be created from a finalized (locked) statement, not a draft.');
        }

        $existing = Payout::query()
            ->where('owner_statement_id', $statement->getKey())
            ->whereIn('status', ['pending', 'paid'])
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
        ]);

        $this->domainEvents->record('payout.created', $payout, ['amount_minor' => $net]);
        $this->audit->record('payout.created', $payout, null, ['status' => 'pending', 'amount_minor' => $net]);

        return $payout;
    }

    /**
     * Disburse a pending payout through the (config-selected or supplied) rail. On success the payout
     * is marked paid and its source statement flipped to paid; on failure the payout is marked failed
     * (retry via {@see retry()} creates a new record).
     */
    public function pay(Payout $payout, ?PayoutRail $rail = null): Payout
    {
        if ($payout->status !== 'pending') {
            throw new RuntimeException("Only a pending payout can be paid (status: {$payout->status}).");
        }

        $rail ??= $this->rail;
        $result = $rail->send($payout);

        DB::transaction(function () use ($payout, $rail, $result): void {
            if ($result->successful) {
                $payout->update([
                    'status' => 'paid',
                    'rail' => $rail->name(),
                    'external_ref' => $result->externalRef,
                    'paid_at' => now(),
                ]);

                OwnerStatement::query()->whereKey($payout->owner_statement_id)->update(['status' => 'paid']);
            } else {
                $payout->update([
                    'status' => 'failed',
                    'rail' => $rail->name(),
                    'failure_reason' => $result->failureReason,
                    'failed_at' => now(),
                ]);
            }
        });

        $event = $result->successful ? 'payout.paid' : 'payout.failed';
        $this->domainEvents->record($event, $payout->refresh(), ['rail' => $rail->name()]);
        $this->audit->record($event, $payout, ['status' => 'pending'], ['status' => $payout->status, 'rail' => $rail->name()]);

        return $payout->refresh();
    }

    /**
     * Retry a failed payout — a NEW pending record superseding the failed one (append-only). The
     * failed row is preserved so the history reconstructs what was attempted, when, and why it failed.
     */
    public function retry(Payout $failed): Payout
    {
        if ($failed->status !== 'failed') {
            throw new RuntimeException("Only a failed payout can be retried (status: {$failed->status}).");
        }

        $retry = Payout::create([
            'organization_id' => $failed->organization_id,
            'owner_id' => $failed->owner_id,
            'owner_statement_id' => $failed->owner_statement_id,
            'amount_minor' => $failed->amount_minor,
            'currency' => $failed->currency,
            'rail' => $this->rail->name(),
            'status' => 'pending',
            'supersedes_id' => $failed->getKey(),
        ]);

        $this->domainEvents->record('payout.retried', $retry, ['supersedes_id' => $failed->getKey()]);
        $this->audit->record('payout.retried', $retry, null, ['supersedes_id' => $failed->getKey()]);

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
