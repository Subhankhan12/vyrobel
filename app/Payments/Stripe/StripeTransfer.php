<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * A Stripe Connect TRANSFER (platform → a connected owner account) — Phase 8 · SG3. `id` (tr_…) is the
 * external reference stored on the Payout; the transfer carries a deterministic Idempotency-Key so a retry
 * returns THIS transfer, never a second money movement.
 */
final class StripeTransfer
{
    public function __construct(
        public readonly string $id,
        public readonly string $destination,
        // ⚠️ ADMIN 06 — Stripe's own livemode for this object (null: not reported)
        public readonly ?bool $livemode = null,
    ) {}
}
