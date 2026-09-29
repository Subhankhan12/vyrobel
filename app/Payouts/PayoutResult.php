<?php

declare(strict_types=1);

namespace App\Payouts;

/**
 * Immutable result of a PayoutRail disbursement (Phase 3 · Module 1 Step 5). `externalRef` is the
 * rail's reference (bank transfer ref / Stripe transfer id); `failureReason` is set when the rail
 * could not disburse.
 */
final class PayoutResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $externalRef = null,
        public readonly ?string $failureReason = null,
        // Phase 8: the money movement is INITIATED but not yet confirmed (a Stripe Connect transfer — the
        // Connect webhook flips the payout to `paid`). The manual rail leaves this false (settled offline).
        public readonly bool $pending = false,
        // ⚠️ ADMIN 06 — Stripe's own livemode for the transfer (null on the manual rail)
        public readonly ?bool $livemode = null,
    ) {}
}
