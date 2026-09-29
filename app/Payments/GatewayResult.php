<?php

declare(strict_types=1);

namespace App\Payments;

/**
 * Immutable result of a payment-gateway operation.
 *
 * `clientSecret` is an additive, optional field (Step 6) — it carries the SCA/3DS next-step
 * secret the frontend needs to complete authentication when `status === 'requires_action'`. It
 * is purely additive: existing callers (ManualPaymentGateway) and the PaymentGateway method
 * signatures are unchanged.
 */
final class GatewayResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $reference,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?string $clientSecret = null,
        // ⚠️ ADMIN 06 — Stripe's own livemode for the object behind this result (null: the manual rail, or not reported)
        public readonly ?bool $livemode = null,
    ) {}
}
