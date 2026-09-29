<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * Thin, SDK-agnostic view of a Stripe PaymentIntent — only the fields the gateway needs. Keeps
 * the rest of the app decoupled from \Stripe\* objects so tests use FakeStripeClient without the
 * SDK. `status` is Stripe's own PaymentIntent status vocabulary (succeeded, requires_action,
 * requires_capture, requires_payment_method, processing, canceled).
 */
final class StripeIntent
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $clientSecret = null,
        public readonly ?int $amountMinor = null,
        public readonly ?string $currency = null,
        // ⚠️ ADMIN 06 — Stripe's own livemode for this object (null: not reported)
        public readonly ?bool $livemode = null,
    ) {}
}
