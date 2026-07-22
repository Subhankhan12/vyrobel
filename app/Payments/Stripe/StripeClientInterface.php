<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

use App\Models\WebhookEvent;

/**
 * The thin Stripe SDK seam. {@see StripeGateway} depends on this, never on \Stripe\* directly, so
 * tests bind {@see FakeStripeClient} (in-memory, no network) while production binds the real
 * {@see StripeClient}. All amounts are integer minor units. Card data never passes through here —
 * only tokens/ids (payment_method, payment_intent, charge, refund).
 *
 * IDEMPOTENCY (Phase 8): every WRITE takes an optional deterministic `$idempotencyKey` ({@see
 * StripeIdempotency}). Stripe returns the ORIGINAL object for a repeated key, so a retry/double-click
 * can never create a second charge — the sending-side half of the two-layer idempotency (the receiving
 * half is the {@see WebhookEvent} unique index).
 */
interface StripeClientInterface
{
    /**
     * Create + confirm a PaymentIntent. `capture` false → manual capture (deposit hold /
     * authorize). Returns the resulting intent (status may be succeeded, requires_capture,
     * requires_action for 3DS, processing, or a failure state).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function createPaymentIntent(int $amountMinor, string $currency, ?string $paymentMethod, bool $capture, array $metadata = [], ?string $idempotencyKey = null): StripeIntent;

    /**
     * Capture a previously authorized (manual-capture) intent, fully or partially.
     */
    public function capturePaymentIntent(string $intentId, ?int $amountMinor = null, ?string $idempotencyKey = null): StripeIntent;

    /**
     * Cancel/void an uncaptured intent (releases a hold).
     */
    public function cancelPaymentIntent(string $intentId): StripeIntent;

    /**
     * Refund a captured/succeeded intent, fully or partially — a new money movement on Stripe's
     * side (our ledger records it as a new offsetting Payment).
     */
    public function refund(string $intentId, int $amountMinor, ?string $idempotencyKey = null): StripeRefund;

    /**
     * Verify a webhook signature (Stripe's `Stripe-Signature` scheme) and return the event, or
     * throw if the signature/secret is invalid. Never trusts an unverified payload.
     */
    public function constructWebhookEvent(string $payload, ?string $signatureHeader): StripeEvent;
}
