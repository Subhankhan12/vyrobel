<?php

namespace App\Services;

use App\Models\ManagementAgreement;
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Models\Unit;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * OwnerExpenseService (Phase 3 · Module 1 Step 3) — records owner-attributable costs and posts them
 * to `owner_ledger` as DEDUCTION lines, honouring the same append-only / whole-transaction-reversal
 * discipline as revenue attribution.
 *
 *  - Deductibility is resolved from the governing management agreement at record time (a category the
 *    agreement marks non-deductible is the OPERATOR's cost → recorded but NO owner-ledger line).
 *  - An expense on an owner-less unit, or with no governing agreement on its date, is recorded and
 *    FLAGGED (no_owner / no_agreement) — never silently attributed.
 *  - Posting is idempotent (`expense:{id}` ledger key). Voiding/correcting reverses the ledger line
 *    (a new mirror row, `reverses_id` set) and never edits the original.
 */
class OwnerExpenseService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private PeriodLockGuard $lockGuard,
    ) {}

    /**
     * Record an owner-attributable expense. Resolves owner (from the unit when not given), the
     * governing agreement, and deductibility; posts a ledger deduction line when deductible; flags
     * when it cannot be attributed.
     *
     * @param  array{unit_id?: string|null, owner_id?: string|null, category: string, description: string, vendor?: string|null, amount_minor: int, currency: string, expense_date: string, source?: Model|null, created_by?: string|null}  $data
     */
    public function recordExpense(array $data): OwnerExpense
    {
        $unit = isset($data['unit_id']) ? Unit::find($data['unit_id']) : null;
        $ownerId = $data['owner_id'] ?? $unit?->owner_id;

        $agreement = null;
        $flag = null;
        $deductible = null;

        if ($ownerId === null) {
            $flag = 'no_owner';
        } else {
            $agreement = ManagementAgreement::resolveFor((string) $ownerId, $unit?->id ? (string) $unit->id : null, $data['expense_date']);

            if ($agreement === null) {
                $flag = 'no_agreement';
            } else {
                // Deductible unless the agreement marks this category non-deductible (operator cost).
                $nonDeductible = (array) $agreement->rule('non_deductible_categories', []);
                $deductible = ! in_array($data['category'], $nonDeductible, true);
            }
        }

        /** @var Model|null $source */
        $source = $data['source'] ?? null;

        $expense = OwnerExpense::create([
            'owner_id' => $ownerId,
            'unit_id' => $unit?->id,
            'category' => $data['category'],
            'description' => $data['description'],
            'vendor' => $data['vendor'] ?? null,
            'amount_minor' => $data['amount_minor'],
            'currency' => $data['currency'],
            'expense_date' => $data['expense_date'],
            'deductible' => $deductible,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'flag' => $flag,
            'created_by' => $data['created_by'] ?? null,
        ]);

        $this->domainEvents->record('owner_expense.recorded', $expense, [
            'deductible' => $deductible,
            'flag' => $flag,
        ]);

        if ($deductible === true) {
            $this->postExpense($expense);
        }

        return $expense->refresh();
    }

    /**
     * Post the expense's owner-ledger deduction line. Idempotent on `expense:{id}`. No-op for a
     * non-deductible, unattributable, or voided expense.
     */
    public function postExpense(OwnerExpense $expense): ?OwnerLedgerEntry
    {
        if ($expense->deductible !== true || $expense->owner_id === null || $expense->voided_at !== null) {
            return null;
        }

        $line = OwnerLedgerEntry::firstOrCreate(
            ['idempotency_key' => 'expense:'.$expense->getKey()],
            [
                'organization_id' => $expense->organization_id,
                'owner_id' => $expense->owner_id,
                'unit_id' => $expense->unit_id,
                'entry_type' => 'expense',
                'direction' => 'debit',
                'amount_minor' => $expense->amount_minor,
                'currency' => $expense->currency,
                'description' => 'Expense — '.$expense->category.': '.$expense->description,
                'service_date' => $expense->expense_date,
                'attribution_version' => 1,
                'prior_period_adjustment' => $this->lockGuard->isSealed((string) $expense->owner_id, $expense->expense_date),
                'source_type' => $expense->getMorphClass(),
                'source_id' => $expense->getKey(),
                'posted_at' => now(),
            ],
        );

        if ((string) $expense->attributed_ledger_id !== (string) $line->getKey()) {
            $expense->update(['attributed_ledger_id' => $line->getKey()]);
        }

        $this->domainEvents->record('owner.expense_attributed', $expense, [
            'ledger_entry_id' => $line->getKey(),
        ]);

        return $line;
    }

    /**
     * Void/correct an expense: reverse its ledger line (append-only mirror, never an edit) and mark
     * the expense voided. Idempotent — re-voiding creates nothing new.
     */
    public function voidExpense(OwnerExpense $expense): void
    {
        DB::transaction(function () use ($expense): void {
            $original = $expense->attributed_ledger_id !== null
                ? OwnerLedgerEntry::find($expense->attributed_ledger_id)
                : null;

            if ($original !== null) {
                OwnerLedgerEntry::firstOrCreate(
                    ['idempotency_key' => 'rev:'.$original->getKey()],
                    [
                        'organization_id' => $original->organization_id,
                        'owner_id' => $original->owner_id,
                        'unit_id' => $original->unit_id,
                        'entry_type' => $original->entry_type,
                        'direction' => $original->direction === 'credit' ? 'debit' : 'credit',
                        'amount_minor' => $original->amount_minor,
                        'currency' => $original->currency,
                        'description' => 'Reversal — '.$original->description,
                        'service_date' => $original->service_date,
                        'attribution_version' => $original->attribution_version,
                        'reverses_id' => $original->getKey(),
                        'prior_period_adjustment' => $this->lockGuard->isSealed((string) $original->owner_id, $original->service_date),
                        'note' => 'reversal',
                        'posted_at' => now(),
                    ],
                );
            }

            if ($expense->voided_at === null) {
                $expense->update(['voided_at' => now()]);
            }
        });

        $this->domainEvents->record('owner_expense.voided', $expense, []);
    }
}
