<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Guest\WalletOverdrawException;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\GuestCreditEntry;
use App\Models\PromoCode;
use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The guest credit WALLET (Phase 4c · Sub-gate 2) — an APPEND-ONLY ledger, same discipline as owner_ledger/folio.
 *
 * 🪤 THE BALANCE IS DERIVED, never stored: {@see spendableMinor()} = Σ(live credits) − Σ(debits). There is no
 * balance column. Every mutation is an INSERT (a credit or a debit); nothing is ever edited or deleted —
 * reversing a credit posts a NEW debit ({@see reverse()}). Integer minor units throughout (#17).
 *
 * 🪤 NO OVERDRAW: {@see debit()} and {@see applyToBooking()} refuse to spend more than the derived spendable
 * balance (throwing {@see WalletOverdrawException} BEFORE any write), and an EXPIRED credit does not count toward
 * that spendable balance. (Scope note: expiry is a query-time exclusion of unspent expired credits; per-lot FIFO
 * allocation of debits to specific credits is not tracked — the no-overdraw guard + expiry exclusion cover the
 * real cases; strict FIFO lot accounting is a documented seam, not built here.)
 *
 * 🔒 CONCURRENCY (wireframe-parity WAVE 2 · S1). A DERIVED balance is only safe if it is read under the same lock
 * that writes it. Every debit path — {@see debit()}, {@see reverse()}, {@see applyToBooking()} — now opens a
 * transaction, takes a row lock via {@see lockWallet()}, and RE-READS its bound inside that lock; {@see
 * redeemCode()} locks the promo row it is bounded by. Writes carry a deterministic `idempotency_key`, so the
 * table's UNIQUE index is the backstop when two replays race past the fast path. This mirrors
 * {@see FolioService::refundPayment()} (itself mirroring {@see OwnerPayoutService::pay()}) — the same fix, the
 * same order: lock, re-read, then write.
 */
class GuestWalletService
{
    public function __construct(private FolioService $folios) {}

    /**
     * The guest's own ledger, newest first.
     *
     * @return Collection<int, GuestCreditEntry>
     */
    public function entries(Guest $guest): Collection
    {
        return GuestCreditEntry::query()
            ->where('guest_id', $guest->getKey())
            ->orderByDesc('posted_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /** DERIVED spendable balance = Σ(live credits) − Σ(debits). Expired credits are excluded; debits always count. */
    public function spendableMinor(Guest $guest, ?CarbonInterface $now = null): int
    {
        $now = $now !== null ? CarbonImmutable::parse($now) : CarbonImmutable::now();
        $spendable = 0;

        foreach (GuestCreditEntry::query()->where('guest_id', $guest->getKey())->get() as $entry) {
            if ((string) $entry->direction === GuestCreditEntry::DIRECTION_DEBIT) {
                $spendable -= (int) $entry->amount_minor;

                continue;
            }
            // A credit counts only while live (no expiry, or not yet expired).
            if ($entry->expires_at === null || $entry->expires_at->greaterThan($now)) {
                $spendable += (int) $entry->amount_minor;
            }
        }

        return $spendable;
    }

    /** Post a CREDIT (grant) — refund / promo / birthday / referral / …. Append-only. */
    public function credit(Guest $guest, string $type, string $reason, Money $amount, ?Model $source = null, ?CarbonInterface $expiresAt = null, ?string $idempotencyKey = null): GuestCreditEntry
    {
        return $this->post($guest, GuestCreditEntry::DIRECTION_CREDIT, $type, $reason, $amount, $source, $expiresAt, $idempotencyKey);
    }

    /**
     * Post a DEBIT (spend) — GUARDED against overdraw, and the guard is re-read INSIDE the guest row-lock so two
     * concurrent debits cannot both pass it. Throws {@see WalletOverdrawException} before any write.
     */
    public function debit(Guest $guest, string $type, string $reason, Money $amount, ?Model $source = null): GuestCreditEntry
    {
        return DB::transaction(function () use ($guest, $type, $reason, $amount, $source): GuestCreditEntry {
            $this->lockWallet($guest);
            $this->assertSpendable($guest, $amount->amountMinor());

            return $this->post($guest, GuestCreditEntry::DIRECTION_DEBIT, $type, $reason, $amount, $source);
        });
    }

    /**
     * Reverse a credit — correct-by-reversal: a NEW debit with `reverses_id` set (the original row is untouched).
     * Guarded like any debit, under the same lock.
     *
     * ⚠️ A credit can be reversed ONCE. Two concurrent reversals of one credit would otherwise both pass the
     * balance guard and post two debits — the append-only ledger cannot then be tidied up (the same shape as the
     * bounded-refund and bounded-termination guards: append-only bounds EDITS, it does not bound the COUNT).
     */
    public function reverse(GuestCreditEntry $credit, string $reason): GuestCreditEntry
    {
        return DB::transaction(function () use ($credit, $reason): GuestCreditEntry {
            $guest = $credit->guest;
            $this->lockWallet($guest);

            if (GuestCreditEntry::query()->where('reverses_id', $credit->getKey())->exists()) {
                throw new WalletOverdrawException('That credit has already been reversed.');
            }

            $this->assertSpendable($guest, (int) $credit->amount_minor);

            $entry = $this->post($guest, GuestCreditEntry::DIRECTION_DEBIT, GuestCreditEntry::TYPE_REVERSAL, $reason, Money::of((int) $credit->amount_minor, (string) $credit->currency));
            $entry->update(['reverses_id' => $credit->getKey()]);

            return $entry->refresh();
        });
    }

    /**
     * Apply wallet credit to a booking's balance — a real DEBIT (guest-scoped, guarded, integer money) AND a folio
     * credit that reduces what's owed (through {@see FolioService::applyWalletCredit()}, the single folio write
     * path). Can't overdraw the wallet, and can't apply more than the folio balance owed.
     */
    public function applyToBooking(Guest $guest, Reservation $reservation, Money $amount, ?string $idempotencyKey = null): GuestCreditEntry
    {
        $amountMinor = $amount->amountMinor();
        if ($amountMinor <= 0) {
            throw new \InvalidArgumentException('The applied amount must be positive.');
        }

        // 🔒 Cheap fast path for a replayed submit (the refundPayment() discipline). The UNIQUE index on
        // guest_credit_ledger.idempotency_key is the real backstop if two replays race past this.
        if ($idempotencyKey !== null && ($existing = $this->findByIdempotencyKey($idempotencyKey)) !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($guest, $reservation, $amount, $amountMinor, $idempotencyKey): GuestCreditEntry {
            // 🔒 Serialize every wallet write for this guest, then RE-READ both bounds inside the lock. Both
            // checks used to run before the transaction opened, so two concurrent applies could each see the
            // full balance, both pass, and both post — spending the same credit twice and over-crediting the
            // folio. A derived balance is only safe if it is read under the same lock that writes it.
            $this->lockWallet($guest);

            $folio = Folio::query()->where('reservation_id', $reservation->getKey())->lockForUpdate()->first();
            $folio?->load(['items', 'payments']);

            $owedMinor = $folio !== null ? FolioTotals::for($folio)['balance_minor'] : 0;
            if ($amountMinor > $owedMinor) {
                // Throwing here rolls the transaction back: a refused apply writes NOTHING.
                throw new WalletOverdrawException("A wallet credit of {$amountMinor} exceeds the {$owedMinor} owed on this booking.");
            }

            $spendable = $this->spendableMinor($guest);
            if ($amountMinor > $spendable) {
                throw WalletOverdrawException::forShortfall($amountMinor, $spendable);
            }

            // Deterministic per LOGICAL apply: the same guest applying the same amount to the same booking at
            // the same wallet state replays to ONE debit, while a genuine second apply (different balance) gets
            // a distinct key. Same construction as the folio refund key.
            $key = $idempotencyKey ?? $this->keyFor('wallet_apply', implode(':', [
                (string) $guest->getKey(), (string) $reservation->getKey(), $spendable, $amountMinor,
            ]));

            $debit = $this->post($guest, GuestCreditEntry::DIRECTION_DEBIT, GuestCreditEntry::TYPE_APPLIED, 'Applied to booking '.$reservation->reference, $amount, $reservation, null, $key);
            $this->folios->applyWalletCredit($folio, $amount, $debit);

            return $debit;
        });
    }

    /**
     * Redeem a promo code → a wallet CREDIT. Single-use (bounded by `max_redemptions`, total) and once-per-guest;
     * an invalid/expired/inactive code is refused honestly. Never fabricates a redemption.
     *
     * @return array{ok: bool, message: string, entry: ?GuestCreditEntry}
     */
    public function redeemCode(Guest $guest, string $code, ?CarbonInterface $now = null): array
    {
        $now = $now !== null ? CarbonImmutable::parse($now) : CarbonImmutable::now();
        $code = trim($code);

        $promo = PromoCode::query()->where('code', $code)->where('is_active', true)->first();
        if ($promo === null || ($promo->expires_at !== null && $promo->expires_at->lessThanOrEqualTo($now))) {
            return ['ok' => false, 'message' => (string) __('php/services/guest_wallet_service.code_invalid'), 'entry' => null];
        }

        // 🔒 Both bounds — once-per-guest AND the total `max_redemptions` cap — used to be counted with no
        // transaction and no lock, so two concurrent redemptions could each see the pre-redemption count, both
        // pass, and both post a credit: free money, twice, and a cap silently exceeded. The promo row is the
        // natural serialization point (it is what the cap belongs to), and the counts are re-read inside it.
        try {
            $entry = DB::transaction(function () use ($guest, $promo): ?GuestCreditEntry {
                PromoCode::query()->lockForUpdate()->findOrFail($promo->getKey());

                $mineAlready = GuestCreditEntry::query()->where('guest_id', $guest->getKey())
                    ->where('source_type', $promo->getMorphClass())->where('source_id', $promo->getKey())->exists();
                $totalRedemptions = GuestCreditEntry::query()
                    ->where('source_type', $promo->getMorphClass())->where('source_id', $promo->getKey())->count();

                if ($mineAlready || $totalRedemptions >= (int) $promo->max_redemptions) {
                    return null;
                }

                // Deterministic, so once-per-guest is enforced by the UNIQUE index too, not only by the count
                // above — the database is the last line, exactly as it is for payments.
                $key = $this->keyFor('wallet_promo', $promo->getKey().':'.$guest->getKey());

                return $this->post($guest, GuestCreditEntry::DIRECTION_CREDIT, GuestCreditEntry::TYPE_PROMO, 'Promo code '.$promo->code, Money::of((int) $promo->amount_minor, (string) $promo->currency), $promo, $promo->expires_at, $key);
            });
        } catch (QueryException $e) {
            // The UNIQUE idempotency_key fired — a concurrent redemption won the race. Honest refusal, not a 500.
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }
            $entry = null;
        }

        if ($entry === null) {
            return ['ok' => false, 'message' => (string) __('php/services/guest_wallet_service.code_used'), 'entry' => null];
        }

        return ['ok' => true, 'message' => (string) __('php/services/guest_wallet_service.credit_added'), 'entry' => $entry];
    }

    /** The guard shared by every debit path — throws BEFORE any write if the spend would overdraw. */
    private function assertSpendable(Guest $guest, int $amountMinor): void
    {
        $spendable = $this->spendableMinor($guest);
        if ($amountMinor > $spendable) {
            throw WalletOverdrawException::forShortfall($amountMinor, $spendable);
        }
    }

    /**
     * 🔒 Serialize every wallet mutation for ONE guest.
     *
     * The wallet balance is DERIVED (Σ credits − Σ debits) with no balance column, so there is no single row to
     * lock the way {@see FolioService::refundPayment()} locks the payment it bounds. The guest row is the
     * aggregate root the ledger hangs off, so locking it makes every debit path mutually exclusive per guest —
     * which is what the derived-balance guards need in order to mean anything.
     */
    private function lockWallet(Guest $guest): void
    {
        Guest::query()->lockForUpdate()->findOrFail($guest->getKey());
    }

    /** An existing entry for a replayed idempotency key, or null. */
    private function findByIdempotencyKey(string $key): ?GuestCreditEntry
    {
        return GuestCreditEntry::query()->where('idempotency_key', $key)->first();
    }

    /** A deterministic, collision-safe key that fits the column (the ledger's UNIQUE index does the enforcing). */
    private function keyFor(string $scope, string $discriminator): string
    {
        return $scope.'_'.hash('sha256', $discriminator);
    }

    /** MySQL 1062 = duplicate entry for a UNIQUE key. */
    private function isUniqueViolation(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }

    private function post(Guest $guest, string $direction, string $type, string $reason, Money $amount, ?Model $source = null, ?CarbonInterface $expiresAt = null, ?string $idempotencyKey = null): GuestCreditEntry
    {
        // organization_id is auto-filled from the tenant context (the guest's own org). amount stored positive.
        return GuestCreditEntry::create([
            'guest_id' => $guest->getKey(),
            'direction' => $direction,
            'type' => $type,
            'amount_minor' => abs($amount->amountMinor()),
            'currency' => $amount->currency(),
            'description' => $reason,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'expires_at' => $expiresAt,
            'idempotency_key' => $idempotencyKey,
            'posted_at' => now(),
        ]);
    }
}
