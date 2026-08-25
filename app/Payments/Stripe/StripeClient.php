<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

use RuntimeException;
use Stripe\Exception\InvalidRequestException;
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
        private readonly ?string $connectWebhookSecret = null,
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

    public function syncCustomerTaxId(string $customerId, array $taxId): bool
    {
        try {
            // Already on the customer? Nothing to do — and DON'T re-create it: Stripe would happily hold two.
            foreach ($this->sdk->customers->allTaxIds($customerId, ['limit' => 20])->data as $existing) {
                if ((string) $existing->type === $taxId['type'] && (string) $existing->value === $taxId['value']) {
                    return true;
                }

                // A DIFFERENT number is stale — the org corrected it in-app. Remove it, or Stripe may apply the
                // old one and zero-rate against a number the customer no longer claims.
                $this->sdk->customers->deleteTaxId($customerId, (string) $existing->id);
            }

            $this->sdk->customers->createTaxId($customerId, ['type' => $taxId['type'], 'value' => $taxId['value']]);

            return true;
        } catch (InvalidRequestException $e) {
            // ⚠️ THE FAIL-SAFE. Stripe refused the number — so it earns NO zero rating and the customer stays
            // taxable. Deliberately not rethrown: a bad VAT number must not break billing, and an overcharge is
            // recoverable where a wrong zero-rating is a compliance liability. The caller surfaces it.
            return false;
        }
    }

    public function createSubscriptionCheckout(string $priceId, string $successUrl, string $cancelUrl, ?string $customerId, ?string $customerEmail, array $metadata = [], ?string $idempotencyKey = null, int $quantity = 1, bool $prorate = false, bool $automaticTax = false): StripeCheckoutSession
    {
        $params = [
            'mode' => 'subscription',
            // ⚠️ The quantity used to be hardcoded to 1, which silently charged a PER-UNIT plan as if the org
            // ran a single unit. It is now whatever the plan's shape says (Plan::quantityFor()).
            'line_items' => [['price' => $priceId, 'quantity' => max(1, $quantity)]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            // Propagate the org id onto the SUBSCRIPTION too, so subscription/invoice webhooks resolve the org.
            'subscription_data' => ['metadata' => $metadata]
                // A per-unit subscription bills quantity changes immediately; a flat one has nothing to prorate.
                + ($prorate ? ['proration_behavior' => 'create_prorations'] : []),
        ];
        if ($customerId !== null) {
            $params['customer'] = $customerId;
        } elseif ($customerEmail !== null) {
            $params['customer_email'] = $customerEmail;
        }

        // ⚠️ BILLING PASS 2b — STRIPE TAX. Only an EXCLUSIVE (per-unit) Price needs it: the €9 is pre-tax and
        // Stripe adds the right amount for this customer — including €0 for an EU business under reverse charge.
        // A flat tier is VAT-inclusive, so its total is already final and nothing here applies to it.
        if ($automaticTax) {
            $params['automatic_tax'] = ['enabled' => true];

            // ⚠️ THE INPUTS ARE NOT OPTIONAL. Stripe Tax computes from the customer's COUNTRY and TAX STATUS; with
            // neither it falls back to taxable, which is exactly the wrong answer for a reverse-charge business.
            // So the session must collect both:
            $params['billing_address_collection'] = 'required';
            $params['tax_id_collection'] = ['enabled' => true];

            // ⚠️ And Stripe REJECTS the session outright if an existing customer is passed with automatic_tax on
            // and no `customer_update.address` — the address collected here has to be allowed to land on them.
            if ($customerId !== null) {
                $params['customer_update'] = ['address' => 'auto', 'name' => 'auto'];
            }
        }

        $session = $this->sdk->checkout->sessions->create($params, $this->options($idempotencyKey));

        return new StripeCheckoutSession((string) $session->id, (string) $session->url);
    }

    public function createPaymentCheckout(int $amountMinor, string $currency, string $description, string $successUrl, string $cancelUrl, array $metadata = [], ?string $idempotencyKey = null): StripeCheckoutSession
    {
        $session = $this->sdk->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($currency),
                    'unit_amount' => $amountMinor,
                    'product_data' => ['name' => $description],
                ],
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            // Propagate the metadata onto the PaymentIntent too (reconciliation breadcrumbs).
            'payment_intent_data' => ['metadata' => $metadata],
        ], $this->options($idempotencyKey));

        $paymentIntent = $session->payment_intent;

        return new StripeCheckoutSession((string) $session->id, (string) $session->url, is_string($paymentIntent) ? $paymentIntent : null);
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

    public function createConnectAccount(string $email, array $metadata = [], ?string $idempotencyKey = null): StripeConnectAccount
    {
        $account = $this->sdk->accounts->create([
            'type' => 'standard',
            'email' => $email,
            'metadata' => $metadata,
        ], $this->options($idempotencyKey));

        return new StripeConnectAccount((string) $account->id, (bool) $account->payouts_enabled);
    }

    public function retrieveConnectAccount(string $accountId): StripeConnectAccount
    {
        $account = $this->sdk->accounts->retrieve($accountId);

        // ⚠️ Readiness is read from Stripe on every call — this is the source of truth, not a stored flag.
        return new StripeConnectAccount(
            (string) $account->id,
            (bool) $account->payouts_enabled,
            (bool) $account->charges_enabled,
            (bool) $account->details_submitted,
        );
    }

    public function createConnectAccountLink(string $accountId, string $refreshUrl, string $returnUrl): StripeAccountLink
    {
        $link = $this->sdk->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return new StripeAccountLink((string) $link->url);
    }

    public function createTransfer(int $amountMinor, string $currency, string $destinationAccountId, array $metadata = [], ?string $idempotencyKey = null): StripeTransfer
    {
        $transfer = $this->sdk->transfers->create([
            'amount' => $amountMinor,
            'currency' => strtolower($currency),
            'destination' => $destinationAccountId,
            'metadata' => $metadata,
        ], $this->options($idempotencyKey));

        return new StripeTransfer((string) $transfer->id, (string) $transfer->destination);
    }

    public function constructConnectWebhookEvent(string $payload, ?string $signatureHeader): StripeEvent
    {
        if (! is_string($this->connectWebhookSecret) || $this->connectWebhookSecret === '' || $signatureHeader === null) {
            throw new RuntimeException('StripeClient: missing CONNECT webhook secret or signature.');
        }

        $event = Webhook::constructEvent($payload, $signatureHeader, $this->connectWebhookSecret);

        return new StripeEvent((string) $event->id, (string) $event->type, $event->toArray());
    }

    public function updateSubscriptionQuantity(string $subscriptionId, int $quantity, bool $prorate = true, ?string $idempotencyKey = null): void
    {
        $subscription = $this->sdk->subscriptions->retrieve($subscriptionId, []);
        $itemId = (string) ($subscription->items->data[0]->id ?? '');
        if ($itemId === '') {
            throw new RuntimeException("Subscription [{$subscriptionId}] has no item to re-quantify.");
        }

        $this->sdk->subscriptions->update($subscriptionId, [
            'items' => [['id' => $itemId, 'quantity' => max(1, $quantity)]],
            // The ruled behaviour: charge the difference now, not at renewal.
            'proration_behavior' => $prorate ? 'create_prorations' : 'none',
        ], $this->options($idempotencyKey));
    }
}
