<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Services\OwnerAttributionService;
use App\Support\Folio\FolioTotals;

/**
 * ⚠️⚠️ THE ONE RULE FOR "IS THIS OWNER LEDGER ENTRY BACKED BY MONEY WE ACTUALLY HOLD?"
 *
 * ⚠️ WHY THIS EXISTS, AND WHAT IT DELIBERATELY DOES NOT DO.
 *
 * `owner_ledger` is an **ACCRUAL**, and it is a correct one. It records revenue when it is EARNED: rows are
 * keyed on `service_date`, carry accounting entry types (`room_revenue`, `commission`, `management_fee`,
 * `ota_cost`, …), are append-only and correct-by-reversal, and are written by
 * {@see OwnerAttributionService} from the folio's posted charges. That is what an owner is
 * OWED, and it is the right thing for the ledger to say. ⚠️ **NOTHING HERE CHANGES THE ACCRUAL BASIS** —
 * doing so would rewrite the meaning of every existing entry and restate history.
 *
 * ⚠️⚠️ THE DEFECT IS NARROWER: nothing distinguished owed-and-COLLECTED from owed-and-UNCOLLECTED. With
 * `PAYMENTS_GATEWAY=manual` every storefront booking is pay-on-arrival, so an owner accrues the full amount
 * with **no payment recorded anywhere** — and a statement claimed every unstatemented row regardless
 * (`OwnerStatementService::claimable()` filtered on `service_date` alone). Finalisation and payout would then
 * have released money the business never received.
 *
 * ⚠️ **THE TWO DEFECTS CANCELLED**: nothing finalises a statement, so nothing was paid, so nothing was
 * overpaid. **Unpicking finalisation without this gate is what would have started paying owners against
 * uncollected revenue** — which is why this lands first.
 *
 * ⚠️ COLLECTION IS READ FROM {@see FolioTotals}, THE CERTIFIED DEFINITION — `paid = Σ settled payments −
 * Σ succeeded refunds`, `balance = charges − paid`. There is no second notion of "collected" in this
 * codebase, and this class does not invent one (§N.8f).
 *
 * ⚠️⚠️ THE RULE IS CONSERVATIVE AND WHOLE-BOOKING, ON PURPOSE:
 *
 *   • An entry with **no `reservation_id`** — an expense, a management fee, an adjustment, a withholding —
 *     is NOT guest revenue and is ALWAYS releasable. ⚠️ Gating those would withhold a DEDUCTION, which
 *     would pay the owner MORE, not less. The failure direction matters more than the symmetry.
 *   • Entries **with** a `reservation_id` are grouped BY reservation and held or released together.
 *     ⚠️ A booking's revenue credit and its commission debit must travel as one, or a statement shows a
 *     commission with no revenue behind it and stops being explainable line-by-line.
 *   • ⚠️⚠️ **A GROUP IS HELD ONLY IF IT IS UNCOLLECTED *AND* WOULD PAY THE OWNER (net > 0).** This
 *     asymmetry is load-bearing, and the first draft of this class got it wrong. A REFUND reverses the
 *     original attribution with mirror DEBIT rows — and a refund also drives the folio balance back
 *     positive, so the reservation reads as "uncollected". Holding on collection alone therefore held the
 *     REVERSAL, leaving the owner credited for money that had been handed back. ⚠️ **Never hold a group
 *     that REDUCES what we pay.** Hold the payment, never the clawback. (Caught by an existing post-lock
 *     refund test — §N.27.7: a pre-existing test can encode the rule you are about to break.)
 *   • A reservation with **no folio at all** is uncollected. Fail closed.
 *
 * ⚠️ HELD ROWS ARE NOT LOST. They keep `statement_id = NULL` and are claimed by a LATER statement once the
 * guest pays — through the straggler path `claimable()` already had for post-lock rows. No carry-forward
 * mechanism was invented; the existing one was sufficient.
 *
 * ⛔ **SURFACED, NOT DECIDED — THIS ALLOCATES COLLECTION RISK.** Holding until collection means the platform
 * does not front an owner their money, so the owner waits on the guest. The alternatives are a founder call,
 * probably an owner-agreement question rather than a code one:
 *   (a) hold until fully collected — implemented here, and the only option that cannot overpay;
 *   (b) release pro-rata with the folio's collection ratio — pays sooner, needs fractional allocation and a
 *       partial-claim model the ledger does not currently have;
 *   (c) the platform fronts the owner and carries the bad-debt risk itself — a financing decision.
 */
final class OwnerCollection
{
    /**
     * Is this entry backed by money actually received?
     *
     * ⚠️ Pure over the entry plus one folio lookup. It never writes, and it never consults the payout state —
     * a thing is collected or not regardless of whether anyone has been paid from it.
     */
    public static function isCollected(OwnerLedgerEntry $entry): bool
    {
        $reservationId = $entry->reservation_id;

        // Not guest revenue — an expense, fee, adjustment or withholding. Always releasable (see the note
        // above: holding a debit would pay the owner MORE).
        if ($reservationId === null || (string) $reservationId === '') {
            return true;
        }

        return self::reservationIsCollected((string) $reservationId);
    }

    /**
     * ⚠️ Fully collected ⇔ the folio's outstanding balance is zero or less, via the certified
     * {@see FolioTotals}. A credit balance (overpayment) is collected; a missing folio is NOT.
     */
    public static function reservationIsCollected(string $reservationId): bool
    {
        $folio = Folio::query()->where('reservation_id', $reservationId)->first();

        if (! $folio instanceof Folio) {
            return false;   // fail closed — no folio means no evidence of payment
        }

        return FolioTotals::for($folio)['balance_minor'] <= 0;
    }

    /**
     * Split entries into what may be released and what is held pending collection.
     *
     * ⚠️ Grouping by reservation means exactly ONE folio lookup per booking, however many lines it has.
     *
     * @param  iterable<OwnerLedgerEntry>  $entries
     * @return array{collected: list<OwnerLedgerEntry>, uncollected: list<OwnerLedgerEntry>}
     */
    public static function split(iterable $entries): array
    {
        $collected = [];
        $groups = [];

        // Pass 1 — non-reservation entries release immediately; the rest gather PER RESERVATION, because
        // hold-or-release is a decision about a BOOKING, never about one line of it.
        foreach ($entries as $entry) {
            $reservationId = $entry->reservation_id === null ? '' : (string) $entry->reservation_id;

            if ($reservationId === '') {
                $collected[] = $entry;

                continue;
            }

            $groups[$reservationId][] = $entry;
        }

        // Pass 2 — ⚠️ hold a group ONLY when it would PAY the owner AND is uncollected. A net-negative group
        // (a refund's mirror-debit reversal) always releases, or the owner keeps money handed back to a guest.
        // ⚠️ `paysOwner` is evaluated FIRST so an uncollected-but-negative group never even hits the folio.
        $uncollected = [];

        foreach ($groups as $reservationId => $group) {
            if (self::paysOwner($group) && ! self::reservationIsCollected($reservationId)) {
                foreach ($group as $entry) {
                    $uncollected[] = $entry;
                }

                continue;
            }

            foreach ($group as $entry) {
                $collected[] = $entry;
            }
        }

        return ['collected' => $collected, 'uncollected' => $uncollected];
    }

    /**
     * ⚠️ Would releasing this group INCREASE what we pay the owner? Only such a group may be held.
     *
     * @param  list<OwnerLedgerEntry>  $group
     */
    private static function paysOwner(array $group): bool
    {
        foreach (OwnerLedgerAttribution::netPerCurrency($group) as $net) {
            if ($net > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * The accrued-but-uncollected net per currency — what the owner is owed but cannot yet be paid.
     *
     * ⚠️ Uses {@see OwnerLedgerAttribution::netPerCurrency()}, the ONE net primitive, so this figure and
     * every "Net to you" on every owner surface are derived the same way and cannot drift.
     *
     * @param  iterable<OwnerLedgerEntry>  $entries
     * @return array<string, int>
     */
    public static function uncollectedNetPerCurrency(iterable $entries): array
    {
        return OwnerLedgerAttribution::netPerCurrency(self::split($entries)['uncollected']);
    }
}
