<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * OwnerStatementService (Phase 3 · Module 1 Step 4) — builds owner statements as a PURE PROJECTION of
 * `owner_ledger`. A DRAFT is reproducible (regenerating yields identical numbers); the ledger stays
 * the single source of truth. FINALIZING stamps `owner_ledger.statement_id` on every claimed line and
 * LOCKS the period — the statement becomes immutable.
 *
 * Gap-free carry-forward: a statement claims every UNSTAMPED ledger line with
 * `service_date <= period_end`. Locked-period lines are already stamped, so the only unstamped lines a
 * later statement can pick up are its own period's lines plus any post-lock stragglers dated into a
 * sealed period — those surface as clearly-labeled PRIOR-PERIOD ADJUSTMENTS. Every ledger line is thus
 * claimed by exactly one statement. (Statements are expected to be finalized in period order.)
 */
class OwnerStatementService
{
    /** entry_type → statement bucket. Credits are +, debits are − via signedMinor(). */
    private const BUCKET = [
        'room_revenue' => 'gross',
        'cleaning' => 'other_revenue',
        'addon' => 'other_revenue',
        'commission' => 'commission',
        'management_fee' => 'management_fee',
        'expense' => 'expenses',
        'ota_cost' => 'ota_cost',
        'payment_fee' => 'payment_fee',
        'adjustment' => 'adjustments',
    ];

    /** Presentation order for the component lines (net is appended last). */
    private const LINE_ORDER = ['gross', 'other_revenue', 'commission', 'management_fee', 'expenses', 'ota_cost', 'payment_fee', 'adjustments'];

    private const LINE_TYPE = [
        'gross' => 'gross',
        'other_revenue' => 'other_revenue',
        'commission' => 'commission',
        'management_fee' => 'management_fee',
        'expenses' => 'expense',
        'ota_cost' => 'ota_cost',
        'payment_fee' => 'payment_fee',
        'adjustments' => 'prior_period_adjustment',
    ];

    private const LABEL = [
        'gross' => 'Gross room revenue',
        'other_revenue' => 'Other revenue (cleaning / add-ons)',
        'commission' => 'Management commission',
        'management_fee' => 'Management fee',
        'expenses' => 'Deductible expenses',
        'ota_cost' => 'OTA commission',
        'payment_fee' => 'Payment & ops fees',
        'adjustments' => 'Prior-period adjustments',
    ];

    public function __construct(
        private DomainEventRecorder $domainEvents,
    ) {}

    /**
     * Create/refresh the DRAFT statement for a period (idempotent). Recomputes numbers + lines from
     * the ledger without stamping anything. Throws if the period is already finalized/locked.
     */
    public function generateDraft(string $ownerId, string $periodStart, string $periodEnd, ?string $fxRate = null, ?string $fxSource = null): OwnerStatement
    {
        $owner = Owner::query()->findOrFail($ownerId);

        $statement = OwnerStatement::query()->firstOrNew([
            'owner_id' => $ownerId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        if ($statement->exists && $statement->isLocked()) {
            throw new RuntimeException('Cannot regenerate a finalized/locked statement.');
        }

        $claimed = $this->claimable($ownerId, $periodEnd);
        $this->applyProjection($statement, $owner, $periodStart, $claimed, $fxRate, $fxSource);
        $statement->status = 'draft';
        $statement->save();

        $this->writeLines($statement, $claimed, $periodStart);

        return $statement->refresh();
    }

    /**
     * Finalize a statement: STAMP its claimed ledger lines, recompute, lock the period, assign a
     * per-owner gap-free sequence number. Idempotent-safe guard: a locked statement throws.
     */
    public function finalize(OwnerStatement $statement, ?string $finalizedBy = null, ?string $fxRate = null, ?string $fxSource = null): OwnerStatement
    {
        if ($statement->isLocked()) {
            throw new RuntimeException('Statement is already finalized/locked.');
        }

        $owner = Owner::query()->findOrFail($statement->owner_id);

        return DB::transaction(function () use ($statement, $owner, $finalizedBy, $fxRate, $fxSource): OwnerStatement {
            $claimed = $this->claimable((string) $statement->owner_id, $statement->period_end->toDateString());

            $this->applyProjection(
                $statement, $owner, $statement->period_start->toDateString(), $claimed,
                $fxRate ?? $statement->fx_rate, $fxSource ?? $statement->fx_source,
            );

            $statement->status = 'finalized';
            $statement->locked = true;
            $statement->finalized_at = now();
            $statement->finalized_by = $finalizedBy;
            $statement->sequence_no = (int) OwnerStatement::query()
                ->where('owner_id', $statement->owner_id)->max('sequence_no') + 1;
            $statement->save();

            // Claim: stamp every projected line so it belongs to exactly one statement.
            OwnerLedgerEntry::query()
                ->whereIn('id', $claimed->pluck('id')->all())
                ->update(['statement_id' => $statement->getKey()]);

            $this->writeLines($statement, $claimed, $statement->period_start->toDateString());

            $this->domainEvents->record('owner_statement.finalized', $statement, [
                'sequence_no' => $statement->sequence_no,
                'net_payable_minor' => $statement->net_payable_minor,
            ]);

            return $statement->refresh();
        });
    }

    // ---------------------------------------------------------------------------------------------

    /**
     * Unstamped ledger lines for an owner up to and including the period end — the lines this
     * statement claims (its own period plus any post-lock stragglers from sealed periods).
     *
     * @return Collection<int, OwnerLedgerEntry>
     */
    private function claimable(string $ownerId, string $periodEnd): Collection
    {
        return OwnerLedgerEntry::query()
            ->where('owner_id', $ownerId)
            ->whereNull('statement_id')
            ->whereDate('service_date', '<=', $periodEnd)
            ->orderBy('service_date')
            ->get();
    }

    /**
     * @param  Collection<int, OwnerLedgerEntry>  $claimed
     */
    private function applyProjection(OwnerStatement $statement, Owner $owner, string $periodStart, Collection $claimed, ?string $fxRate, ?string $fxSource): void
    {
        $buckets = $this->buckets($claimed, $periodStart);
        $netSource = array_sum($buckets);

        $sourceCurrency = $this->sourceCurrency($claimed, (string) $owner->payout_currency);
        $payoutCurrency = (string) $owner->payout_currency;
        $sameCurrency = $sourceCurrency === $payoutCurrency;

        if (! $sameCurrency && $fxRate === null) {
            throw new RuntimeException("Statement payout currency [{$payoutCurrency}] differs from ledger currency [{$sourceCurrency}] but no fx_rate was supplied.");
        }

        $netPayable = $sameCurrency ? $netSource : (int) round($netSource * (float) $fxRate);

        $statement->organization_id = $owner->organization_id;
        $statement->currency = $payoutCurrency;
        $statement->source_currency = $sourceCurrency;
        $statement->gross_minor = $buckets['gross'];
        $statement->other_revenue_minor = $buckets['other_revenue'];
        $statement->commission_minor = $buckets['commission'];
        $statement->management_fee_minor = $buckets['management_fee'];
        $statement->expenses_minor = $buckets['expenses'];
        $statement->ota_cost_minor = $buckets['ota_cost'];
        $statement->payment_fee_minor = $buckets['payment_fee'];
        $statement->adjustments_minor = $buckets['adjustments'];
        $statement->net_source_minor = $netSource;
        $statement->fx_rate = $sameCurrency ? null : (float) $fxRate;
        $statement->fx_source = $sameCurrency ? null : $fxSource;
        $statement->net_payable_minor = $netPayable;
        $statement->has_unverified = $claimed->contains(fn (OwnerLedgerEntry $e): bool => $e->unverified === true);
    }

    /**
     * Signed sums per bucket. A line dated before the period start, or explicitly flagged
     * prior_period_adjustment, is a carry-forward regardless of its entry_type.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $claimed
     * @return array<string, int>
     */
    private function buckets(Collection $claimed, string $periodStart): array
    {
        $buckets = ['gross' => 0, 'other_revenue' => 0, 'commission' => 0, 'management_fee' => 0, 'expenses' => 0, 'ota_cost' => 0, 'payment_fee' => 0, 'adjustments' => 0];

        foreach ($claimed as $line) {
            $isPrior = $line->prior_period_adjustment === true
                || $line->service_date->toDateString() < $periodStart;

            $bucket = $isPrior ? 'adjustments' : self::BUCKET[$line->entry_type];
            $buckets[$bucket] += $line->signedMinor();
        }

        return $buckets;
    }

    /**
     * @param  Collection<int, OwnerLedgerEntry>  $claimed
     */
    private function sourceCurrency(Collection $claimed, string $fallback): string
    {
        $first = $claimed->first();

        return $first !== null ? (string) $first->currency : $fallback;
    }

    /**
     * Replace the statement's presentation lines from the projection (component lines sum EXACTLY to
     * net_source; a summary net line is appended).
     *
     * @param  Collection<int, OwnerLedgerEntry>  $claimed
     */
    private function writeLines(OwnerStatement $statement, Collection $claimed, string $periodStart): void
    {
        $statement->lines()->delete();

        $buckets = $this->buckets($claimed, $periodStart);
        $unverifiedBuckets = $this->unverifiedBuckets($claimed, $periodStart);
        $currency = (string) $statement->source_currency;
        $sort = 0;

        foreach (self::LINE_ORDER as $bucket) {
            // Omit empty components — but always show gross, and always surface an unverified line
            // (a pending OTA line can be 0 until reconciled and must stay visible to the owner).
            if ($buckets[$bucket] === 0 && $bucket !== 'gross' && empty($unverifiedBuckets[$bucket])) {
                continue;
            }

            OwnerStatementLine::create([
                'organization_id' => $statement->organization_id,
                'owner_statement_id' => $statement->getKey(),
                'owner_id' => $statement->owner_id,
                'line_type' => self::LINE_TYPE[$bucket],
                'description' => self::LABEL[$bucket],
                'amount_minor' => $buckets[$bucket],
                'currency' => $currency,
                'unverified' => $unverifiedBuckets[$bucket] ?? false,
                'sort_order' => $sort++,
            ]);
        }

        OwnerStatementLine::create([
            'organization_id' => $statement->organization_id,
            'owner_statement_id' => $statement->getKey(),
            'owner_id' => $statement->owner_id,
            'line_type' => 'net',
            'description' => 'Net payable',
            'amount_minor' => $statement->net_source_minor,
            'currency' => $currency,
            'sort_order' => $sort,
        ]);
    }

    /**
     * @param  Collection<int, OwnerLedgerEntry>  $claimed
     * @return array<string, bool>
     */
    private function unverifiedBuckets(Collection $claimed, string $periodStart): array
    {
        $flags = [];

        foreach ($claimed as $line) {
            if ($line->unverified !== true) {
                continue;
            }
            $isPrior = $line->prior_period_adjustment === true || $line->service_date->toDateString() < $periodStart;
            $bucket = $isPrior ? 'adjustments' : self::BUCKET[$line->entry_type];
            $flags[$bucket] = true;
        }

        return $flags;
    }
}
