<?php

declare(strict_types=1);

namespace App\Payouts\Rails;

use App\Models\Payout;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\PayoutResult;
use App\Services\OwnerPayoutService;
use Throwable;

/**
 * Stripe Connect owner payouts (Phase 8 · SG3) — REAL MONEY LEAVING to an owner's connected account. The
 * highest-stakes seam, so the safety is layered:
 *
 * 🔒 CORRECT OWNER ONLY: the transfer destination is THE PAYOUT'S owner's connected account
 * (`$payout->owner->stripe_connect_account_id`) — resolved from the payout, never a request/another owner.
 * 🔒 NO DOUBLE-TRANSFER (sending side): a deterministic Idempotency-Key ({@see StripeIdempotency::keyFor} on
 *    the payout id) — a retry returns the ORIGINAL transfer, never a second. The DB row-lock in
 *    {@see OwnerPayoutService::pay()} is the other half.
 * ⛔ REFUSES if the owner has no connected account or Stripe hasn't enabled payouts (KYC incomplete) — it can
 *    never silently pretend to pay. Marked `processing`; the CONNECT webhook flips it to `paid`.
 *
 * Test-mode: the {@see FakeStripeClient} simulates transfers (no real money). The live
 * rail is only ever active with `PAYOUTS_RAIL=stripe_connect` + real keys (Subhan's go-live).
 */
class StripeConnectPayoutRail implements PayoutRail
{
    public function __construct(private readonly StripeClientInterface $stripe) {}

    public function name(): string
    {
        return 'stripe_connect';
    }

    public function send(Payout $payout): PayoutResult
    {
        $owner = $payout->owner;
        $account = $owner?->stripe_connect_account_id;

        if ($owner === null || ! is_string($account) || $account === '') {
            return new PayoutResult(successful: false, failureReason: 'Owner has not connected a Stripe account.');
        }
        if (! $owner->stripe_connect_payouts_enabled) {
            return new PayoutResult(successful: false, failureReason: 'Owner’s Stripe account is not yet enabled for payouts.');
        }

        try {
            $transfer = $this->stripe->createTransfer(
                amountMinor: (int) $payout->amount_minor,
                currency: (string) $payout->currency,
                destinationAccountId: $account,               // 🔒 THIS owner's account only
                metadata: ['payout_id' => (string) $payout->getKey(), 'owner_id' => (string) $payout->owner_id],
                idempotencyKey: StripeIdempotency::keyFor('owner_payout', (string) $payout->getKey()),
            );
        } catch (Throwable $e) {
            return new PayoutResult(successful: false, failureReason: 'Stripe transfer failed: '.$e->getMessage());
        }

        // Initiated — the Connect webhook (transfer.created) confirms it → `paid`.
        return new PayoutResult(successful: true, externalRef: $transfer->id, pending: true, livemode: $transfer->livemode);
    }
}
