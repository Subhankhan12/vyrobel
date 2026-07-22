<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * A Stripe Connect onboarding Account Link (Phase 8 · SG3) — the hosted URL the owner completes their KYC on
 * (Stripe-hosted; no identity data touches our server). Single-use, short-lived.
 */
final class StripeAccountLink
{
    public function __construct(
        public readonly string $url,
    ) {}
}
