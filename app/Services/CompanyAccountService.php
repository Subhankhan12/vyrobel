<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\Folio;
use App\Support\Events\AuditEventRecorder;
use App\Support\Folio\FolioTotals;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * ⚠️⚠️ BILLING A FOLIO BALANCE TO A COMPANY ACCOUNT — THE DEBT MOVES, IT DOES NOT VANISH.
 *
 * ⚠️⚠️ THE ONE FACT THAT DECIDES WHETHER THIS IS HONEST: this posts NOTHING to the folio. No payment, no
 * charge reversal, no adjustment. `FolioTotals::balance_minor` is EXACTLY what it was, so:
 *
 *   1. ⚠️ THE DEBT STAYS VISIBLE. The folio still reads "owed" — as billed to an account rather than as
 *      settled. A "€0 balance" on money nobody has received would be the display≠charge defect with a new
 *      hiding place, and STRICTLY WORSE than today, where the balance at least sits where someone sees it.
 *
 *   2. ⚠️⚠️ IT IS NOT COLLECTED. `OwnerCollection::reservationIsCollected()` is literally
 *      `FolioTotals::for($folio)['balance_minor'] <= 0`. Had billing reduced the balance, the collection
 *      gate would have counted it as collected IMMEDIATELY and the payout engine would pay the owner for
 *      money nobody has received — silently, out of the operator's own working capital, surfacing months
 *      later as a shortfall nobody could trace back to this decision. Leaving the balance alone is what
 *      makes that impossible, and it needs no change to the gate at all.
 *
 *   3. ⚠️ THE OWNER LEDGER MOVES BY EXACTLY ZERO, because nothing is posted to it.
 *
 * ⚠️ When the company eventually pays, the payment is recorded through the ORDINARY `FolioService` path,
 * the balance reaches zero, and the owner becomes payable THEN — through the existing mechanism. No
 * parallel settlement path was built, because a mechanism beside an existing one is worse than a missing
 * caller.
 */
class CompanyAccountService
{
    public function __construct(private AuditEventRecorder $audit) {}

    /**
     * ⚠️ UN-BILLING IS A NEW STATE, NOT AN EDIT — see {@see unbill()}. Billing is recorded with who did it
     * and when, because it defers a debt on one person's say-so.
     */
    public function bill(Folio $folio, CompanyAccount $account): Folio
    {
        if ((string) $account->organization_id !== (string) $folio->organization_id) {
            throw new InvalidArgumentException('That account belongs to a different organization.');
        }

        if (! $account->is_active) {
            throw new InvalidArgumentException('That account is closed.');
        }

        $folio->loadMissing(['items', 'payments']);
        if ((int) FolioTotals::for($folio)['balance_minor'] <= 0) {
            // Nothing is owed, so there is nothing to defer. Refusing here keeps the state meaningful:
            // an account's outstanding total is the sum of REAL balances, never a list of settled folios.
            throw new InvalidArgumentException('That folio has nothing outstanding to bill.');
        }

        $before = ['billed_to_account_id' => $folio->billed_to_account_id];

        DB::transaction(function () use ($folio, $account): void {
            $folio->update([
                'billed_to_account_id' => $account->getKey(),
                'billed_at' => CarbonImmutable::now(),
                'billed_by_user_id' => Auth::id(),
            ]);
        });

        $this->audit->record('folio.billed_to_account', $folio, $before, [
            'billed_to_account_id' => (string) $account->getKey(),
            'account_name' => (string) $account->name,
            'balance_minor' => (int) FolioTotals::for($folio->fresh(['items', 'payments']))['balance_minor'],
        ]);

        return $folio->refresh();
    }

    /**
     * ⚠️ UN-BILLING IS A DELIBERATE, AUDITED TRANSITION — never a silent edit. Moving a debt back to the
     * guest is as consequential as deferring it, so it leaves the same trail rather than blanking a column.
     * ⚠️ It is a NEW STATE rather than a reversal because nothing was POSTED to reverse: the folio's own
     * balance never moved, so there is no ledger entry to correct — only an attribution to withdraw.
     */
    public function unbill(Folio $folio): Folio
    {
        if ($folio->billed_to_account_id === null) {
            return $folio;   // idempotent
        }

        $before = ['billed_to_account_id' => (string) $folio->billed_to_account_id];

        DB::transaction(function () use ($folio): void {
            $folio->update([
                'billed_to_account_id' => null,
                'billed_at' => null,
                'billed_by_user_id' => null,
            ]);
        });

        $this->audit->record('folio.unbilled_from_account', $folio, $before, ['billed_to_account_id' => null]);

        return $folio->refresh();
    }

    /**
     * ⚠️ WHAT DOES THIS COMPANY OWE US? — the sum of the REAL folio balances billed to it, computed through
     * the SAME `FolioTotals` the folio screen renders (§N.8f). There is no stored receivable total, so
     * there is nothing that can drift away from the folios it is made of.
     *
     * @return array{outstanding_minor: int, currency: string, folios: list<array{folio_id: string, reference: string|null, balance_minor: int, billed_at: string|null}>}
     */
    public function outstandingFor(CompanyAccount $account): array
    {
        $folios = Folio::query()
            ->where('billed_to_account_id', $account->getKey())
            ->with(['items', 'payments', 'reservation'])
            ->orderBy('billed_at')
            ->get();

        $rows = [];
        $total = 0;
        $currency = 'EUR';

        foreach ($folios as $folio) {
            $balance = (int) FolioTotals::for($folio)['balance_minor'];
            $currency = (string) $folio->currency;
            $total += $balance;
            $rows[] = [
                'folio_id' => (string) $folio->getKey(),
                'reference' => $folio->reservation?->reference,
                'balance_minor' => $balance,
                // The cast is declared on Folio, but the relation-loaded instance can arrive as a raw
                // string; parse rather than assume, so the shape is the same either way.
                'billed_at' => $folio->billed_at === null ? null : CarbonImmutable::parse($folio->billed_at)->toDateString(),
            ];
        }

        return ['outstanding_minor' => $total, 'currency' => $currency, 'folios' => $rows];
    }
}
