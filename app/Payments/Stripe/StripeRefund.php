<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * Thin, SDK-agnostic view of a Stripe Refund.
 */
final class StripeRefund
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
    ) {}
}
