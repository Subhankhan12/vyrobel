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
    ) {}
}
