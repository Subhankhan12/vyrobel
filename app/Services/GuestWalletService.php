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
     * Post a DEBIT (spend) — GUARDED against overdraw. Throws {@see WalletOverdrawException} before any write when
     * the amount exceeds the derived spendable balance.
     */
    public function debit(Guest $guest, string $type, string $reason, Money $amount, ?Model $source = null): GuestCreditEntry
    {
        $this->assertSpendable($guest, $amount->amountMinor());

        return $this->post($guest, GuestCreditEntry::DIRECTION_DEBIT, $type, $reason, $amount, $source);
    }

    /**
     * Reverse a credit — correct-by-reversal: a NEW debit with `reverses_id` set (the original row is untouched).
     * Guarded like any debit (can't reverse below the spendable balance).
     */
    public function reverse(GuestCreditEntry $credit, string $reason): GuestCreditEntry
    {
        $this->assertSpendable($credit->guest, (int) $credit->amount_minor);

        $entry = $this->post($credit->guest, GuestCreditEntry::DIRECTION_DEBIT, GuestCreditEntry::TYPE_REVERSAL, $reason, Money::of((int) $credit->amount_minor, (string) $credit->currency));
        $entry->update(['reverses_id' => $credit->getKey()]);

        return $entry->refresh();
    }

    /**
     * Apply wallet credit to a booking's balance — a real DEBIT (guest-scoped, guarded, integer money) AND a folio
     * credit that reduces what's owed (through {@see FolioService::applyWalletCredit()}, the single folio write
     * path). Can't overdraw the wallet, and can't apply more than the folio balance owed.
     */
    public function applyToBooking(Guest $guest, Reservation $reservation, Money $amount): GuestCreditEntry
    {
        $amountMinor = $amount->amountMinor();
        if ($amountMinor <= 0) {
            throw new \InvalidArgumentException('The applied amount must be positive.');
        }

        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->first();
        $owedMinor = $folio !== null ? FolioTotals::for($folio)['balance_minor'] : 0;
        if ($amountMinor > $owedMinor) {
            throw new WalletOverdrawException("A wallet credit of {$amountMinor} exceeds the {$owedMinor} owed on this booking.");
        }

        $this->assertSpendable($guest, $amountMinor);

        return DB::transaction(function () use ($guest, $reservation, $amount, $folio): GuestCreditEntry {
            $debit = $this->post($guest, GuestCreditEntry::DIRECTION_DEBIT, GuestCreditEntry::TYPE_APPLIED, 'Applied to booking '.$reservation->reference, $amount, $reservation);
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
            return ['ok' => false, 'message' => 'That code isn’t valid.', 'entry' => null];
        }

        $mineAlready = GuestCreditEntry::query()->where('guest_id', $guest->getKey())
            ->where('source_type', $promo->getMorphClass())->where('source_id', $promo->getKey())->exists();
        $totalRedemptions = GuestCreditEntry::query()
            ->where('source_type', $promo->getMorphClass())->where('source_id', $promo->getKey())->count();

        if ($mineAlready || $totalRedemptions >= (int) $promo->max_redemptions) {
            return ['ok' => false, 'message' => 'That code has already been used.', 'entry' => null];
        }

        $entry = $this->credit($guest, GuestCreditEntry::TYPE_PROMO, 'Promo code '.$promo->code, Money::of((int) $promo->amount_minor, (string) $promo->currency), $promo, $promo->expires_at);

        return ['ok' => true, 'message' => 'Credit added to your wallet.', 'entry' => $entry];
    }

    /** The guard shared by every debit path — throws BEFORE any write if the spend would overdraw. */
    private function assertSpendable(Guest $guest, int $amountMinor): void
    {
        $spendable = $this->spendableMinor($guest);
        if ($amountMinor > $spendable) {
            throw WalletOverdrawException::forShortfall($amountMinor, $spendable);
        }
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
