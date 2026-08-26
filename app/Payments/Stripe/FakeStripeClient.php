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

    /** @var list<array{id: string, amount_minor: int, currency: string, destination: string, metadata: array<string, string>}> — created transfers, inspectable in tests. */
    public array $transfers = [];

    /** @var array<string, bool> — connect account id → payouts_enabled (test-mode owner accounts). */
    public array $connectAccounts = [];

    /** @var array<string, bool> Stripe's `charges_enabled` per connected account — the ONLY readiness fact. */
    public array $connectChargesEnabled = [];

    /** @var array<string, bool> Stripe's `details_submitted` — the form was finished. ⚠️ NOT readiness. */
    public array $connectDetailsSubmitted = [];

    /** @var list<array{account: string, refresh_url: string, return_url: string}> every account link minted. */
    public array $accountLinksCreated = [];

    /** @var list<?string> the connected account each payment Checkout named (null = the platform). */
    public array $checkoutAccounts = [];

    /** @var list<?string> the connected account each refund named (null = the platform). */
    public array $refundAccounts = [];

    /** @var list<?string> the connected account each PaymentIntent named — the DESK charge path. */
    public array $intentAccounts = [];

    public function __construct(
        private readonly ?string $webhookSecret = null,
        private readonly ?string $connectWebhookSecret = null,
    ) {}

    public function createPaymentIntent(int $amountMinor, string $currency, ?string $paymentMethod, bool $capture, array $metadata = [], ?string $idempotencyKey = null, ?string $connectedAccountId = null): StripeIntent
    {
        $this->intentAccounts[] = $connectedAccountId;
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

    public function refund(string $intentId, int $amountMinor, ?string $idempotencyKey = null, ?string $connectedAccountId = null): StripeRefund
    {
        $this->refundAccounts[] = $connectedAccountId;
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

    /** @var array<string, array<string, mixed>> — created Checkout sessions, inspectable in tests. */
    public array $checkoutSessions = [];

    /**
     * Recorded tax IDs per customer — what WOULD reach Stripe, assertable without a network call.
     *
     * @var array<string, list<array{type: string, value: string}>>
     */
    public array $customerTaxIds = [];

    /**
     * Values the fake should reject, simulating Stripe refusing a malformed number.
     *
     * @var list<string>
     */
    public array $rejectTaxIdValues = [];

    public function syncCustomerTaxId(string $customerId, array $taxId): bool
    {
        // Stripe rejects a number it cannot parse. Tests use this to prove the FAIL-SAFE path: a rejection
        // must leave the customer taxable, not break the flow.
        if (in_array($taxId['value'], $this->rejectTaxIdValues, true)) {
            return false;
        }

        // Replace rather than append — a customer carrying two different VAT numbers is ambiguous, and the
        // stale one could win. This mirrors what the real client does.
        $this->customerTaxIds[$customerId] = [$taxId];

        return true;
    }

    public function createSubscriptionCheckout(string $priceId, string $successUrl, string $cancelUrl, ?string $customerId, ?string $customerEmail, array $metadata = [], ?string $idempotencyKey = null, int $quantity = 1, bool $prorate = false, bool $automaticTax = false): StripeCheckoutSession
    {
        // A repeated Idempotency-Key returns the ORIGINAL session — never a second subscription.
        if ($idempotencyKey !== null && isset($this->idempotency[$idempotencyKey])) {
            $existing = $this->checkoutSessions[$this->idempotency[$idempotencyKey]];

            return new StripeCheckoutSession((string) $existing['id'], (string) $existing['url']);
        }

        $id = 'cs_fake_'.Str::lower(Str::random(20));
        $url = 'https://checkout.stripe.test/'.$id;
        $this->checkoutSessions[$id] = [
            'id' => $id,
            'url' => $url,
            'price' => $priceId,
            // Recorded so a test can assert the quantity + proration that WOULD reach Stripe — the whole point
            // of the fake rail is that the engine is provable without a live charge.
            'quantity' => max(1, $quantity),
            'prorate' => $prorate,
            // ⚠️ Recorded so a test can prove Stripe Tax is switched on for an exclusive per-unit Price without a
            // live charge — the tax itself is Stripe's to compute, but WHETHER WE ASK is ours and is assertable.
            'automatic_tax' => $automaticTax,
            'customer' => $customerId ?? 'cus_fake_'.Str::lower(Str::random(14)),
            'subscription' => 'sub_fake_'.Str::lower(Str::random(14)),
            'metadata' => $metadata,
        ];
        if ($idempotencyKey !== null) {
            $this->idempotency[$idempotencyKey] = $id;
        }

        return new StripeCheckoutSession($id, $url);
    }

    public function createPaymentCheckout(int $amountMinor, string $currency, string $description, string $successUrl, string $cancelUrl, array $metadata = [], ?string $idempotencyKey = null, ?string $connectedAccountId = null): StripeCheckoutSession
    {
        $this->checkoutAccounts[] = $connectedAccountId;
        // A repeated Idempotency-Key returns the ORIGINAL session (+ its PaymentIntent) — never a second charge.
        if ($idempotencyKey !== null && isset($this->idempotency[$idempotencyKey])) {
            $existing = $this->checkoutSessions[$this->idempotency[$idempotencyKey]];

            return new StripeCheckoutSession((string) $existing['id'], (string) $existing['url'], (string) $existing['payment_intent']);
        }

        $id = 'cs_fake_'.Str::lower(Str::random(20));
        $url = 'https://checkout.stripe.test/'.$id;
        $paymentIntent = 'pi_fake_'.Str::lower(Str::random(20));
        $this->checkoutSessions[$id] = [
            'id' => $id,
            'url' => $url,
            'mode' => 'payment',
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'payment_intent' => $paymentIntent,
            'metadata' => $metadata,
        ];
        if ($idempotencyKey !== null) {
            $this->idempotency[$idempotencyKey] = $id;
        }

        return new StripeCheckoutSession($id, $url, $paymentIntent);
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

    public function createConnectAccount(string $email, array $metadata = [], ?string $idempotencyKey = null): StripeConnectAccount
    {
        $id = 'acct_fake_'.Str::lower(Str::random(16));
        $this->connectAccounts[$id] = false;   // KYC not yet complete — payouts disabled until account.updated

        return new StripeConnectAccount($id, false);
    }

    public function retrieveConnectAccount(string $accountId): StripeConnectAccount
    {
        // ⚠️ CONNECT PHASE 1 — the fake models the THREE Stripe facts separately so a test can prove that a
        //    submitted-but-not-yet-enabled account reads as NOT READY. `$connectAccounts` keeps its original
        //    bool meaning (payouts) so every existing owner-payout test is untouched.
        return new StripeConnectAccount(
            $accountId,
            $this->connectAccounts[$accountId] ?? false,
            $this->connectChargesEnabled[$accountId] ?? false,
            $this->connectDetailsSubmitted[$accountId] ?? false,
        );
    }

    public function createConnectAccountLink(string $accountId, string $refreshUrl, string $returnUrl): StripeAccountLink
    {
        // ⚠️ CONNECT PHASE 1 — recorded so a test can prove a FRESH link is minted on every return. Stripe's
        //    account links EXPIRE and are SINGLE-USE; a stored one fails days later, mid-signup, for someone
        //    who cannot act on the error.
        $this->accountLinksCreated[] = ['account' => $accountId, 'refresh_url' => $refreshUrl, 'return_url' => $returnUrl];

        return new StripeAccountLink('https://connect.stripe.test/onboard/'.$accountId.'/'.count($this->accountLinksCreated));
    }

    public function createTransfer(int $amountMinor, string $currency, string $destinationAccountId, array $metadata = [], ?string $idempotencyKey = null): StripeTransfer
    {
        // 🔒 A repeated Idempotency-Key returns the ORIGINAL transfer — never a second money movement.
        if ($idempotencyKey !== null && isset($this->idempotency[$idempotencyKey])) {
            $id = $this->idempotency[$idempotencyKey];

            return new StripeTransfer($id, $destinationAccountId);
        }

        $id = 'tr_fake_'.Str::lower(Str::random(20));
        $this->transfers[] = ['id' => $id, 'amount_minor' => $amountMinor, 'currency' => $currency, 'destination' => $destinationAccountId, 'metadata' => $metadata];
        if ($idempotencyKey !== null) {
            $this->idempotency[$idempotencyKey] = $id;
        }

        return new StripeTransfer($id, $destinationAccountId);
    }

    public function constructConnectWebhookEvent(string $payload, ?string $signatureHeader): StripeEvent
    {
        if (! is_string($this->connectWebhookSecret) || $this->connectWebhookSecret === '' || $signatureHeader === null) {
            throw new RuntimeException('FakeStripeClient: missing CONNECT webhook secret or signature.');
        }
        if (! $this->signatureValid($payload, $signatureHeader, $this->connectWebhookSecret)) {
            throw new RuntimeException('FakeStripeClient: invalid CONNECT webhook signature.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return new StripeEvent((string) ($decoded['id'] ?? ''), (string) ($decoded['type'] ?? ''), $decoded);
    }

    /** Test helper — mark a fake Connect account's payouts enabled (mirrors the account.updated webhook). */
    public function enableConnectAccount(string $accountId): void
    {
        $this->connectAccounts[$accountId] = true;
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

    /** @var list<array{subscription: string, quantity: int, prorate: bool}> */
    public array $quantityUpdates = [];

    public function updateSubscriptionQuantity(string $subscriptionId, int $quantity, bool $prorate = true, ?string $idempotencyKey = null): void
    {
        $this->quantityUpdates[] = [
            'subscription' => $subscriptionId,
            'quantity' => max(1, $quantity),
            'prorate' => $prorate,
        ];
    }
}
