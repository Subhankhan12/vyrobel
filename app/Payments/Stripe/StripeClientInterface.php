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
    public function refund(string $intentId, int $amountMinor, ?string $idempotencyKey = null, ?string $connectedAccountId = null): StripeRefund;

    /**
     * Create a SUBSCRIPTION-mode Checkout Session (Phase 8 · SG1). The card is entered on Stripe's hosted
     * page (never our server). `$metadata` (e.g. organization_id) is stamped on BOTH the session and the
     * created subscription, so every downstream webhook can resolve the org. Returns the session id + url;
     * fulfilment happens on the WEBHOOK, not this call.
     *
     * @param  array<string, string>  $metadata
     */
    /**
     * Attach a TYPED tax ID to an existing Stripe customer, replacing any different one already on them.
     *
     * ⚠️ DO-BILL-1 — this is what makes reverse charge reach the customer. Stripe Tax reads the customer's
     * `tax_ids` to decide whether an EU B2B sale is zero-rated; without one, a business that already told
     * Vyrobel its VAT number is charged VAT it was entitled to reverse-charge.
     *
     * @param  array{type: string, value: string}  $taxId  Never a bare string — the type decides how Stripe
     *                                                     interprets the number.
     * @return bool true when the id is attached (or already present); false when Stripe REJECTED it, in which
     *              case the customer stays TAXABLE. ⚠️ Never throws for a rejection: a bad VAT number must not
     *              break billing, it must simply fail to earn a zero rating.
     */
    public function syncCustomerTaxId(string $customerId, array $taxId): bool;

    public function createSubscriptionCheckout(string $priceId, string $successUrl, string $cancelUrl, ?string $customerId, ?string $customerEmail, array $metadata = [], ?string $idempotencyKey = null, int $quantity = 1, bool $prorate = false, bool $automaticTax = false): StripeCheckoutSession;

    /**
     * Update a live subscription's QUANTITY (BILLING PASS 1) — the per-unit path: the org added or removed a
     * unit mid-cycle, so the subscription must carry the new count.
     *
     * ⚠️ `$prorate` is the founder's ruling made concrete: a per-unit plan charges the difference IMMEDIATELY
     * rather than at the next renewal, so the customer-facing "prorated" promise becomes true. Passing false
     * would defer it silently, which is the shape of claim this project treats as a defect (§N.8d).
     */
    public function updateSubscriptionQuantity(string $subscriptionId, int $quantity, bool $prorate = true, ?string $idempotencyKey = null): void;

    /**
     * Create a PAYMENT-mode Checkout Session for a one-off amount (Phase 8 · SG2 — a guest folio charge). The
     * card is entered on Stripe's hosted page. Returns the session id + url + the PaymentIntent id Stripe
     * created (a pending Payment is pre-created against it; the payment_intent.succeeded webhook settles it).
     * Integer minor units.
     *
     * @param  array<string, string>  $metadata
     */
    public function createPaymentCheckout(int $amountMinor, string $currency, string $description, string $successUrl, string $cancelUrl, array $metadata = [], ?string $idempotencyKey = null, ?string $connectedAccountId = null): StripeCheckoutSession;

    /**
     * Verify a webhook signature (Stripe's `Stripe-Signature` scheme) and return the event, or
     * throw if the signature/secret is invalid. Never trusts an unverified payload.
     */
    public function constructWebhookEvent(string $payload, ?string $signatureHeader): StripeEvent;

    // ── Connect (owner payout rail — Phase 8 · SG3) ──────────────────────────────────────────────────

    /**
     * Create a Connect account (Standard) for an owner. `$metadata` (e.g. owner_id) is stamped on the account.
     *
     * @param  array<string, string>  $metadata
     */
    public function createConnectAccount(string $email, array $metadata = [], ?string $idempotencyKey = null): StripeConnectAccount;

    /** Fetch a Connect account's current state (payouts_enabled reflects Stripe's KYC verification). */
    public function retrieveConnectAccount(string $accountId): StripeConnectAccount;

    /** Create a hosted onboarding Account Link — the URL the owner completes KYC on (Stripe-hosted). */
    public function createConnectAccountLink(string $accountId, string $refreshUrl, string $returnUrl): StripeAccountLink;

    /**
     * Create a TRANSFER (platform → a connected owner account) — REAL MONEY LEAVING. The deterministic
     * `$idempotencyKey` makes a retry return THIS transfer, never a second. `$destinationAccountId` is the
     * OWNER's connected account (resolved from the payout's owner — never another owner's).
     *
     * @param  array<string, string>  $metadata
     */
    public function createTransfer(int $amountMinor, string $currency, string $destinationAccountId, array $metadata = [], ?string $idempotencyKey = null): StripeTransfer;

    /**
     * Verify a CONNECT webhook signature against the SEPARATE Connect endpoint secret (a distinct endpoint
     * from the account webhook, per Stripe best practice). Throws on an invalid signature.
     */
    public function constructConnectWebhookEvent(string $payload, ?string $signatureHeader): StripeEvent;
}
