<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Owner\OwnerProfileController;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Collection;

/**
 * Owner TAX CENTER backend (Phase 2 · Sub-gate 4) — the owner's own tax profile, the payout hold that depends
 * on it, and the real annual aggregation behind the summary + PDF.
 *
 * ⚠️ SELF-ONLY: the owner is resolved by `user_id` from the authenticated user ({@see Owner()}), exactly like
 * {@see OwnerProfileController} — there is NO owner id in any request, so an owner
 * can only ever read or write their OWN tax details / summaries. `tax_id` + `vat_number` are ENCRYPTED at rest;
 * the audit records WHICH fields changed, never their values (invariant #28).
 *
 * The payout HOLD is NOT enforced here — it lives in the single write path ({@see OwnerPayoutService::pay()},
 * which refuses to disburse to an owner with no tax ID). This service only READS that state (which payouts are
 * held) and the completeness flag; the genuine block is at the money path.
 */
class OwnerTaxService
{
    /** The tax-profile fields the owner may set. `tax_id` is the required one (it gates payouts). */
    private const FIELDS = ['legal_name', 'type', 'tax_country', 'tax_id', 'vat_number'];

    public function __construct(private AuditEventRecorder $audit) {}

    /**
     * THE self-resolution: the authenticated user's OWN owner record (never a request id). firstOrFail so a
     * non-linked user 404s (fail closed), matching the owner profile write.
     */
    public function owner(User $user): Owner
    {
        return Owner::query()->where('user_id', $user->getKey())->firstOrFail();
    }

    /**
     * Persist the owner's tax details on their OWN record. Only the whitelisted fields are written; the audit
     * names the fields, never the (encrypted) values.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveTaxDetails(User $user, array $data): Owner
    {
        $owner = $this->owner($user);

        $changes = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $changes[$field] = $data[$field];
            }
        }

        $owner->forceFill($changes)->save();

        $this->audit->record('owner.tax_details_updated', $owner, null, ['fields' => array_keys($changes)]);

        return $owner;
    }

    /**
     * The owner's HELD payouts — pending payouts that cannot be disbursed because tax details are incomplete.
     * Once a tax ID is on file, nothing is held (the same rule {@see OwnerPayoutService::pay()} enforces).
     *
     * @return Collection<int, Payout>
     */
    public function heldPayouts(Owner $owner): Collection
    {
        if ($owner->hasTaxDetails()) {
            return collect();
        }

        return Payout::query()
            ->where('owner_id', $owner->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * The held total, per currency (payouts never combine across currencies — invariant #17).
     *
     * @return array<string, int>
     */
    public function heldTotalMinor(Owner $owner): array
    {
        $total = [];
        foreach ($this->heldPayouts($owner) as $payout) {
            $currency = (string) $payout->currency;
            $total[$currency] = ($total[$currency] ?? 0) + (int) $payout->amount_minor;
        }
        ksort($total);

        return $total;
    }

    /**
     * The years for which the owner has committed (finalized or paid) statements — the summaries they can view.
     *
     * @return list<int>
     */
    public function availableYears(Owner $owner): array
    {
        return $this->committedStatements($owner)
            ->map(fn (OwnerStatement $s): int => (int) $s->period_start->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * The REAL annual summary for a year — aggregated from the owner's committed statements, reconciling to the
     * cent (gross + deductions = net, because a statement's buckets sum to its net_source_minor). All figures in
     * the statements' source currency. Returns null when there are no statements for that year.
     *
     * @return array<string, mixed>|null
     */
    public function annualSummary(Owner $owner, int $year): ?array
    {
        $statements = $this->committedStatements($owner)
            ->filter(fn (OwnerStatement $s): bool => (int) $s->period_start->format('Y') === $year)
            ->sortBy(fn (OwnerStatement $s): string => $s->period_start->toDateString())
            ->values();

        if ($statements->isEmpty()) {
            return null;
        }

        $currency = (string) $statements->first()->source_currency;

        $grossMinor = 0;
        $netMinor = 0;
        $buckets = ['commission_minor' => 0, 'management_fee_minor' => 0, 'ota_cost_minor' => 0, 'expenses_minor' => 0, 'adjustments_minor' => 0];
        $months = [];

        foreach ($statements as $statement) {
            $gross = (int) $statement->gross_minor + (int) $statement->other_revenue_minor;
            $net = (int) $statement->net_source_minor;
            $commission = (int) $statement->commission_minor;

            $grossMinor += $gross;
            $netMinor += $net;
            foreach ($buckets as $col => $_) {
                $buckets[$col] += (int) $statement->{$col};
            }

            $months[] = [
                'label' => $statement->period_start->format('F'),
                'gross_minor' => $gross,
                'commission_minor' => $commission,
                // The remainder reconciles the row exactly: gross + commission + fees = net.
                'fees_minor' => $net - $gross - $commission,
                'net_minor' => $net,
            ];
        }

        $deductionsMinor = array_sum($buckets);   // signed (≤ 0) — gross + deductions = net, by construction

        return [
            'year' => $year,
            'currency' => $currency,
            'statement_count' => $statements->count(),
            'period_start' => $statements->first()->period_start->format('j M Y'),
            'period_end' => $statements->last()->period_end->format('j M Y'),
            'gross_minor' => $grossMinor,
            'deductions_minor' => $deductionsMinor,
            'net_minor' => $netMinor,
            'categories' => [
                ['label' => 'Management commission', 'minor' => $buckets['commission_minor']],
                ['label' => 'Management fees', 'minor' => $buckets['management_fee_minor']],
                ['label' => 'Channel / OTA commissions', 'minor' => $buckets['ota_cost_minor']],
                ['label' => 'Expenses & cleaning', 'minor' => $buckets['expenses_minor']],
                ['label' => 'Prior-period adjustments', 'minor' => $buckets['adjustments_minor']],
            ],
            'months' => $months,
        ];
    }

    /**
     * The owner's committed (finalized or paid) statements — the locked figures a tax summary is built from. A
     * draft is provisional and excluded. Owner-scoped by BelongsToOwner AND pinned to this owner id.
     *
     * @return Collection<int, OwnerStatement>
     */
    private function committedStatements(Owner $owner): Collection
    {
        return OwnerStatement::query()
            ->where('owner_id', $owner->id)
            ->whereIn('status', ['finalized', 'paid'])
            ->get();
    }
}
