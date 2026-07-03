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
    ) {}
}
