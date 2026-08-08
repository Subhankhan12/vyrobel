<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\GatewayResult;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
use App\Payments\Stripe\StripeIntent;
use App\Support\Money\Money;

/**
 * Real Stripe implementation of the Phase 1 {@see PaymentGateway} interface (Step 6). It maps our
 * fixed contract onto Stripe PaymentIntents behind the {@see StripeClientInterface} seam — so the
 * ledger (FolioService) is unchanged and tests run against FakeStripeClient with no network.
 *
 * TOKEN-ONLY: `$token` is a Stripe payment_method id; `reference` is always a Stripe id
 * (payment_intent / refund) — a raw PAN/CVV never reaches this class. SCA/3DS is surfaced, not
 * swallowed: a `requires_action` intent returns an UNsuccessful result carrying the client_secret
 * for the frontend, and the payment stays pending until a webhook confirms it.
 */
class StripeGateway implements PaymentGateway
{
    public function __construct(
        private StripeClientInterface $stripe,
    ) {}

    public function name(): string
    {
        return 'stripe';
    }

    public function charge(Money $amount, ?string $token, array $metadata = []): GatewayResult
    {
        $intent = $this->stripe->createPaymentIntent(
            $amount->amountMinor(),
            $amount->currency(),
            $token,
            capture: true,
            metadata: $this->stringMetadata($metadata),
            idempotencyKey: $this->idempotencyKey('folio_charge', $metadata),
        );

        return $this->intentToResult($intent, successStatuses: ['succeeded'], successLabel: 'succeeded');
    }

    public function authorize(Money $amount, ?string $token, array $metadata = []): GatewayResult
    {
        $intent = $this->stripe->createPaymentIntent(
            $amount->amountMinor(),
            $amount->currency(),
            $token,
            capture: false,
            metadata: $this->stringMetadata($metadata),
            idempotencyKey: $this->idempotencyKey('folio_authorize', $metadata),
        );

        return $this->intentToResult($intent, successStatuses: ['requires_capture', 'succeeded'], successLabel: 'authorized');
    }

    public function capture(string $reference, Money $amount): GatewayResult
    {
        // Capturing an already-captured intent is idempotent — key on the intent id so a retry returns it.
        $intent = $this->stripe->capturePaymentIntent($reference, $amount->amountMinor(), StripeIdempotency::keyFor('folio_capture', $reference));

        return new GatewayResult($intent->status === 'succeeded', $intent->id, 'captured');
    }

    public function void(string $reference): GatewayResult
    {
        $intent = $this->stripe->cancelPaymentIntent($reference);

        return new GatewayResult($intent->status === 'canceled', $intent->id, 'voided');
    }

    public function refund(string $reference, Money $amount, ?string $idempotencyKey = null): GatewayResult
    {
        // Keyed like charge/authorize/capture: a replayed refund returns Stripe's ORIGINAL refund rather
        // than issuing a second one. The caller (FolioService) derives the key from the payment id + the
        // cumulative-refunded state read INSIDE its row lock, so a genuine second PARTIAL refund gets a
        // distinct key while a duplicate submit of the SAME logical refund collapses.
        $refund = $this->stripe->refund($reference, $amount->amountMinor(), $idempotencyKey);

        return new GatewayResult($refund->status === 'succeeded', $refund->id, 'succeeded');
    }

    /**
     * @param  list<string>  $successStatuses  Stripe statuses that mean "done" for this operation
     */
    private function intentToResult(StripeIntent $intent, array $successStatuses, string $successLabel): GatewayResult
    {
        if (in_array($intent->status, $successStatuses, true)) {
            return new GatewayResult(true, $intent->id, $successLabel);
        }

        if ($intent->status === 'requires_action') {
            // SCA/3DS — not a failure, not yet a success. Hand the client_secret back for the
            // frontend to complete authentication; a webhook settles it later.
            return new GatewayResult(false, $intent->id, 'requires_action', 'SCA/3DS authentication required', $intent->clientSecret);
        }

        if ($intent->status === 'processing') {
            return new GatewayResult(false, $intent->id, 'pending', 'Payment processing');
        }

        return new GatewayResult(false, $intent->id, 'failed', "Stripe intent status: {$intent->status}");
    }

    /**
     * The deterministic Idempotency-Key for a charge/authorize, derived from a STABLE ref the caller puts in
     * `metadata['idempotency_ref']` (a per-payment-attempt id — created before the charge). No ref → null (the
     * caller opted out); the plumbing is ready and the guest-payment flow (SG2) supplies the ref.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function idempotencyKey(string $operation, array $metadata): ?string
    {
        $ref = $metadata['idempotency_ref'] ?? null;

        return is_scalar($ref) && (string) $ref !== '' ? StripeIdempotency::keyFor($operation, (string) $ref) : null;
    }

    /**
     * Stripe metadata values must be scalars/strings — flatten to strings and drop nested data.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, string>
     */
    private function stringMetadata(array $metadata): array
    {
        $out = [];
        foreach ($metadata as $key => $value) {
            if (is_scalar($value)) {
                $out[(string) $key] = (string) $value;
            }
        }

        return $out;
    }
}
