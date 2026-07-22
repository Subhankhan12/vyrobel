<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * In-memory, deterministic, inspectable {@see StripeClientInterface} — what every test uses
 * (bound whenever `services.stripe.fake` is true, the default). Never touches the network.
 *
 * Webhook signing uses Stripe's REAL, documented `Stripe-Signature` scheme
 * (`t=<ts>,v1=<hmac_sha256(ts.payload, secret)>`), so `signature()` (used by tests) and
 * `constructWebhookEvent()` agree with what live Stripe would send — signature validation is
 * genuine, only the transport is faked.
 */
class FakeStripeClient implements StripeClientInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $intents = [];

    /**
     * Idempotency-Key → the id of the object it first created. A repeated key returns the ORIGINAL
     * (Stripe's real behaviour), so a double-charge is structurally impossible — provable without network.
     *
     * @var array<string, string>
     */
    public array $idempotency = [];

    /** @var list<array{intent_id: string, amount_minor: int}> */
    public array $captures = [];

    /** @var list<array{intent_id: string, amount_minor: int, id: string}> */
    public array $refunds = [];

    /** @var list<string> */
    public array $voids = [];

    /**
     * Force the status of the NEXT createPaymentIntent (then auto-clears). Use 'requires_action'
     * to simulate an SCA/3DS challenge.
     */
    public ?string $nextIntentStatus = null;

    public function __construct(
        private readonly ?string $webhookSecret = null,
    ) {}

    public function createPaymentIntent(int $amountMinor, string $currency, ?string $paymentMethod, bool $capture, array $metadata = [], ?string $idempotencyKey = null): StripeIntent
    {
        // A repeated Idempotency-Key returns the ORIGINAL intent — never a second charge (Stripe's guarantee).
        if ($idempotencyKey !== null && isset($this->idempotency[$idempotencyKey])) {
            $existing = $this->intents[$this->idempotency[$idempotencyKey]];

            return new StripeIntent((string) $existing['id'], (string) $existing['status'], null, (int) $existing['amount_minor'], (string) $existing['currency']);
        }

        $id = 'pi_fake_'.Str::lower(Str::random(20));
        // Mirror live Stripe: with no payment method an intent is `requires_payment_method` (it can
        // neither be confirmed nor held); with a method, capture:true settles now and capture:false
        // is a holdable `requires_capture`. (Reconciled against live Stripe test-mode 2026-07-03.)
        $status = $this->nextIntentStatus ?? ($paymentMethod === null
            ? 'requires_payment_method'
            : ($capture ? 'succeeded' : 'requires_capture'));
        $this->nextIntentStatus = null;

        $clientSecret = $status === 'requires_action' ? $id.'_secret_'.Str::lower(Str::random(10)) : null;

        $this->intents[$id] = [
            'id' => $id,
            'status' => $status,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'metadata' => $metadata,
        ];
        if ($idempotencyKey !== null) {
            $this->idempotency[$idempotencyKey] = $id;
        }

        return new StripeIntent($id, $status, $clientSecret, $amountMinor, $currency);
    }

    public function capturePaymentIntent(string $intentId, ?int $amountMinor = null, ?string $idempotencyKey = null): StripeIntent
    {
        $intent = $this->intents[$intentId] ?? throw new RuntimeException("FakeStripeClient: unknown intent {$intentId}.");
        // Mirror live Stripe: only a `requires_capture` intent may be captured (a hold created with
        // no payment method is `requires_payment_method` and errors here). (Reconciled 2026-07-03.)
        if (($intent['status'] ?? null) !== 'requires_capture') {
            throw new RuntimeException("FakeStripeClient: intent {$intentId} is {$intent['status']}, only requires_capture may be captured.");
        }
        $this->intents[$intentId]['status'] = 'succeeded';
        $this->captures[] = ['intent_id' => $intentId, 'amount_minor' => $amountMinor ?? (int) $intent['amount_minor']];

        return new StripeIntent($intentId, 'succeeded', null, $amountMinor ?? (int) $intent['amount_minor'], (string) $intent['currency']);
    }

    public function cancelPaymentIntent(string $intentId): StripeIntent
    {
        $this->intents[$intentId]['status'] = 'canceled';
        $this->voids[] = $intentId;

        return new StripeIntent($intentId, 'canceled');
    }

    public function refund(string $intentId, int $amountMinor, ?string $idempotencyKey = null): StripeRefund
    {
        // A repeated Idempotency-Key returns the ORIGINAL refund — never a double refund.
        if ($idempotencyKey !== null && isset($this->idempotency[$idempotencyKey])) {
            return new StripeRefund($this->idempotency[$idempotencyKey], 'succeeded');
        }

        $id = 're_fake_'.Str::lower(Str::random(20));
        $this->refunds[] = ['intent_id' => $intentId, 'amount_minor' => $amountMinor, 'id' => $id];
        if ($idempotencyKey !== null) {
            $this->idempotency[$idempotencyKey] = $id;
        }

        return new StripeRefund($id, 'succeeded');
    }

    public function constructWebhookEvent(string $payload, ?string $signatureHeader): StripeEvent
    {
        if (! is_string($this->webhookSecret) || $this->webhookSecret === '' || $signatureHeader === null) {
            throw new RuntimeException('FakeStripeClient: missing webhook secret or signature.');
        }

        if (! $this->signatureValid($payload, $signatureHeader, $this->webhookSecret)) {
            throw new RuntimeException('FakeStripeClient: invalid webhook signature.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return new StripeEvent(
            id: (string) ($decoded['id'] ?? ''),
            type: (string) ($decoded['type'] ?? ''),
            payload: $decoded,
        );
    }

    /**
     * Build a Stripe-Signature header for a payload (test helper — mirrors what live Stripe sends).
     */
    public static function signature(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $v1 = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$v1}";
    }

    private function signatureValid(string $payload, string $header, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? null;
        $provided = $parts['v1'] ?? null;
        if ($timestamp === null || $provided === null) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return hash_equals($expected, $provided);
    }
}
