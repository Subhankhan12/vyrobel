<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Folio;
use App\Models\Guest;
use App\Services\FolioService;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\ReservationStage;
use Illuminate\Support\Facades\DB;

/**
 * THE ONE definition of what a guest is worth — "Total billed" + the open balance beside it — shared by the
 * Guests CRM list and the Guest profile (Wave 2). Same discipline as {@see MoneyFormatter}
 * / {@see ReservationStage} / {@see \App\Support\Folio\FolioTotals}: two screens
 * showing the same guest must never disagree about their headline number.
 *
 * WHY A DEFINITION AND NOT JUST A SUM (locked decision 1, docs/DECISIONS.md 2026-07-16). The Wave 2 design's
 * "lifetime value" was SELF-CONTRADICTORY — it netted a refund off (which only makes sense for money
 * COLLECTED) while including €701 of unpaid charges (which only makes sense for money BILLED). It cannot be
 * both. The decision: **BILLED** — what we invoiced the guest — labelled "Total billed", with the real open
 * balance shown NEXT TO IT rather than folded in. The SQL is trivial; the definition was the deliverable.
 *
 * THE SOURCE, and why it reconciles by construction:
 *   billed  = Σ `folios.total_minor`    ≡ Σ items.amount_minor      ≡ FolioTotals::for()['charges_minor']
 *   balance = Σ `folios.balance_minor`  ≡ charges − paid            ≡ FolioTotals::for()['balance_minor']
 * Those are not two opinions: {@see FolioService::recompute()} writes the cached columns FROM
 * the same append-only rows {@see FolioTotals} reads (`total_minor = Σ items`, `paid = Σ settled payments −
 * Σ succeeded refunds`, `balance = total − paid`) on every ledger write. `GuestSpendTest` asserts the
 * identity against FolioTotals so a future divergence fails loudly rather than silently. The cached columns
 * are used because a list must not load every folio's items + payments to show one column.
 *
 * PER CURRENCY, NEVER SUMMED ACROSS. `folios.currency` is per row; adding EUR to USD would invent an FX rate
 * (invariant #17). Results are bucketed by currency; {@see primary()} picks the largest-billed bucket for a
 * headline and the caller renders the rest.
 *
 * COUNTS EVERY FOLIO, including a cancelled stay's. "Billed" is a historical fact — the ledger is
 * append-only, so a stay that was charged and later refunded keeps its charge line and keeps its billed
 * figure; the refund shows up in the balance, not by rewriting history. (A stay cancelled before anything
 * was charged has `total_minor = 0` and contributes nothing on its own.)
 *
 * Pure + read-only: it never writes, and it is not a second folio-maths path — it reads the columns
 * FolioService already maintains.
 */
final class GuestSpend
{
    /**
     * Billed + open balance + stay count for MANY guests in ONE query, bucketed by currency.
     *
     * Tenant-scoped: the base query is {@see Folio} (BelongsToOrganization), so the global scope constrains
     * `folios.organization_id` before the join — another org's folios can never enter the sum.
     *
     * @param  list<string>  $guestIds
     * @return array<string, array<string, array{billed_minor: int, balance_minor: int, stays: int}>>
     *                                                                                                guest id → currency → figures
     */
    public static function forGuestIds(array $guestIds): array
    {
        if ($guestIds === []) {
            return [];
        }

        // `toBase()` — deliberately, not `->get()` on the Eloquent builder. These rows are AGGREGATES
        // (guest_id / billed_minor / stays), not folios; hydrating them into Folio models would hang
        // columns that are not Folio's onto a Folio. It still applies the global scopes first
        // (`applyScopes()->getQuery()`), so `folios.organization_id` is constrained exactly as below.
        $rows = Folio::query()
            ->join('reservations', 'reservations.id', '=', 'folios.reservation_id')
            ->whereIn('reservations.lead_guest_id', $guestIds)
            ->groupBy('reservations.lead_guest_id', 'folios.currency')
            ->select([
                'reservations.lead_guest_id as guest_id',
                'folios.currency as currency',
                DB::raw('SUM(folios.total_minor) as billed_minor'),
                DB::raw('SUM(folios.balance_minor) as balance_minor'),
                DB::raw('COUNT(DISTINCT reservations.id) as stays'),
            ])
            ->toBase()
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->guest_id][(string) $row->currency] = [
                'billed_minor' => (int) $row->billed_minor,
                'balance_minor' => (int) $row->balance_minor,
                'stays' => (int) $row->stays,
            ];
        }

        return $out;
    }

    /**
     * The same figures for ONE guest — the profile's source, identical to the list's by construction.
     *
     * @return array<string, array{billed_minor: int, balance_minor: int, stays: int}> currency → figures
     */
    public static function for(Guest $guest): array
    {
        return self::forGuestIds([(string) $guest->getKey()])[(string) $guest->getKey()] ?? [];
    }

    /**
     * The headline bucket: the currency the guest has been billed most in, or null when they have no folio.
     *
     * A guest with no billing history returns NULL rather than a zero — the caller renders "—", never
     * "€0.00" in a currency nobody chose (the Wave 1 empty-bucket rule).
     *
     * @param  array<string, array{billed_minor: int, balance_minor: int, stays: int}>  $buckets
     * @return array{currency: string, billed_minor: int, balance_minor: int, stays: int}|null
     */
    public static function primary(array $buckets): ?array
    {
        if ($buckets === []) {
            return null;
        }

        $currency = array_key_first($buckets);
        foreach ($buckets as $code => $figures) {
            if ($figures['billed_minor'] > $buckets[$currency]['billed_minor']) {
                $currency = $code;
            }
        }

        return ['currency' => (string) $currency] + $buckets[$currency];
    }
}
