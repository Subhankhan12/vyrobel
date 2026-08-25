<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * A Stripe Connect account (Phase 8 · SG3) — the owner's connected account (Standard). `payoutsEnabled`
 * reflects Stripe's own KYC/verification state; a transfer is only ever sent to an enabled account.
 */
final class StripeConnectAccount
{
    public function __construct(
        public readonly string $id,
        public readonly bool $payoutsEnabled = false,
        // ⚠️ CONNECT PHASE 1 — ADDITIVE, and defaulted so the owner payout path is unchanged.
        // ⚠️⚠️ `chargesEnabled` AND `detailsSubmitted` ARE NOT THE SAME FACT. `details_submitted` means the
        // account holder finished the form; `charges_enabled` means Stripe will actually let a charge
        // through. An account can submit everything and still be blocked pending review, and treating the
        // first as readiness is how a storefront ends up offering a card it cannot take.
        public readonly bool $chargesEnabled = false,
        public readonly bool $detailsSubmitted = false,
    ) {}
}
