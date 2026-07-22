<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

use RuntimeException;
use Stripe\StripeClient as StripeSdk;
use Stripe\Webhook;

/**
 * Real SDK-backed {@see StripeClientInterface}. Config-gated (`services.stripe.fake = false`) and
 * never exercised by the test suite — bound only when explicitly opted in with real keys.
 *
 * Amounts are integer minor units throughout (Stripe's native unit — no float). Confirms intents
 * server-side; a `requires_action` result means SCA/3DS must be completed client-side with the
 * returned client_secret. This class IS exercised only against live Stripe test-mode keys — the
 * deferred payment integration checkpoint (parallel to Channex staging), not by CI.
 */
class StripeClient implements StripeClientInterface
{
    private StripeSdk $sdk;

    public function __construct(
        string $secret,
        private readonly ?string $webhookSecret,
    ) {
        if ($secret === '') {
            throw new RuntimeException(
                'StripeClient has no API secret (services.stripe.secret) — refusing to go live. '
                .'Set STRIPE_SECRET or keep STRIPE_FAKE=true.'
            );
        }

        $this->sdk = new StripeSdk($secret);
    }

    public function createPaymentIntent(int $amountMinor, string $currency, ?string $paymentMethod, bool $capture, array $metadata = [], ?string $idempotencyKey = null): StripeIntent
    {
        $params = [
            'amount' => $amountMinor,
            'currency' => strtolower($currency),
            'capture_method' => $capture ? 'automatic' : 'manual',
            'confirm' => $paymentMethod !== null,
            'metadata' => $metadata,
        ];

        if ($paymentMethod !== null) {
            $params['payment_method'] = $paymentMethod;
            // Do not redirect for 3DS — surface requires_action to the caller instead.
            $params['automatic_payment_methods'] = ['enabled' => true, 'allow_redirects' => 'never'];
        }

        // The deterministic Idempotency-Key: a retry with the same key returns the ORIGINAL intent
        // (Stripe's guarantee) — no second charge.
        $intent = $this->sdk->paymentIntents->create($params, $this->options($idempotencyKey));

        return new StripeIntent(
            (string) $intent->id,
            (string) $intent->status,
            $intent->client_secret !== null ? (string) $intent->client_secret : null,
            (int) $intent->amount,
            strtoupper((string) $intent->currency),
        );
    }

    public function capturePaymentIntent(string $intentId, ?int $amountMinor = null, ?string $idempotencyKey = null): StripeIntent
    {
        $params = $amountMinor !== null ? ['amount_to_capture' => $amountMinor] : [];
        $intent = $this->sdk->paymentIntents->capture($intentId, $params, $this->options($idempotencyKey));

        return new StripeIntent((string) $intent->id, (string) $intent->status);
    }

    public function cancelPaymentIntent(string $intentId): StripeIntent
    {
        $intent = $this->sdk->paymentIntents->cancel($intentId);

        return new StripeIntent((string) $intent->id, (string) $intent->status);
    }

    public function refund(string $intentId, int $amountMinor, ?string $idempotencyKey = null): StripeRefund
    {
        $refund = $this->sdk->refunds->create([
            'payment_intent' => $intentId,
            'amount' => $amountMinor,
        ], $this->options($idempotencyKey));

        return new StripeRefund((string) $refund->id, (string) $refund->status);
    }

    /**
     * The Stripe request options — the deterministic Idempotency-Key when the caller supplied one.
     *
     * @return array{idempotency_key?: string}
     */
    private function options(?string $idempotencyKey): array
    {
        return $idempotencyKey !== null && $idempotencyKey !== '' ? ['idempotency_key' => $idempotencyKey] : [];
    }

    public function constructWebhookEvent(string $payload, ?string $signatureHeader): StripeEvent
    {
        if (! is_string($this->webhookSecret) || $this->webhookSecret === '' || $signatureHeader === null) {
            throw new RuntimeException('StripeClient: missing webhook secret or signature.');
        }

        // Throws \Stripe\Exception\SignatureVerificationException on a bad signature.
        $event = Webhook::constructEvent($payload, $signatureHeader, $this->webhookSecret);

        return new StripeEvent((string) $event->id, (string) $event->type, $event->toArray());
    }
}
