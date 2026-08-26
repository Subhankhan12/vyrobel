<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeEvent;
use App\Services\FolioService;
use App\Services\OwnerPayoutService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Public inbound Stripe CONNECT webhook (/webhooks/stripe/connect) — Phase 8 · SG3. A SEPARATE endpoint with
 * its OWN signing secret (`services.stripe.connect_webhook_secret`), distinct from the account webhook, per
 * Stripe best practice. Verifies the signature against the RAW body via the Connect secret.
 *
 * Handles: `account.updated` → flip the owner's `stripe_connect_payouts_enabled` (KYC cleared); `transfer.created`
 * → confirm the owner payout `processing → paid` ({@see OwnerPayoutService::confirmTransfer()}). ONE idempotency
 * guarantee ({@see once()}): the `webhook_events` unique index — a re-delivered event confirms/enables exactly
 * once, so real money is never confirmed twice. Resolves the target org from the LOCAL record (the owner's
 * connected account id / the payout's transfer id), under super-admin, then scopes the write.
 */
class StripeConnectWebhookController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, StripeClientInterface $stripe, OwnerPayoutService $payouts, FolioService $folios): JsonResponse
    {
        try {
            $event = $stripe->constructConnectWebhookEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (Throwable) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        return match (true) {
            $event->type === 'account.updated' => $this->handleAccountUpdated($event, $tenant),
            in_array($event->type, ['transfer.created', 'transfer.updated'], true) => $this->handleTransfer($event, $tenant, $payouts),

            // ⚠️⚠️ THE MONEY SWITCH — a DIRECT charge lives on the connected account, so ITS events arrive
            //    HERE, on this plane, signed with the CONNECT secret. Without this routing Stripe succeeds
            //    while the folio never learns: PAYMENT TAKEN, BOOKING UNPAID, and the guest holds a receipt
            //    for a reservation the desk shows unsettled.
            // ⚠️⚠️ MATCHED ON THE EVENT TYPE, NOT ON A SNIFFED ID. `StripeEvent::paymentIntentId()` falls
            //    back to `data.object.id` when no `payment_intent` field is present, so a REFUND object
            //    (`re_…`) reads as a payment-intent id. A first version ordered this branch above the
            //    refund one and silently swallowed every refund settlement — a queued refund invisible for
            //    ever, which is the same payment-taken-booking-unpaid shape by a third route.
            in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed', 'payment_intent.canceled'], true) => $this->handlePaymentIntent($event, $tenant, $folios),

            // ⚠️⚠️ AND A QUEUED REFUND IS THE **NORMAL** CASE HERE, not an edge one: an operator on
            //    automatic daily payouts runs a near-zero balance by design, and Stripe holds a card refund
            //    pending until the balance covers it. Leave this unrouted and a queued refund is invisible,
            //    the headroom never releases or settles, and the desk re-issues — the exact hole the
            //    pending-refund pass existed to close.
            in_array($event->type, ['refund.updated', 'refund.failed'], true) => $this->handleRefundSettlement($event, $tenant, $folios),

            default => response()->json(['status' => 'ignored'], 202),
        };
    }

    /**
     * A direct-charge PaymentIntent settling on the connected account → the folio learns.
     *
     * ⚠️ The org is resolved from the LOCAL Payment (pre-created as `pending` at checkout), never from the
     * event — the same rule the platform receiver follows.
     */
    private function handlePaymentIntent(StripeEvent $event, TenantContext $tenant, FolioService $folios): JsonResponse
    {
        $intentId = (string) $event->paymentIntentId();
        $payment = $tenant->runAsSuperAdmin(
            fn () => Payment::query()->where('gateway_ref', $intentId)->first(),
        );
        if ($payment === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $to = match ($event->type) {
            'payment_intent.succeeded' => 'succeeded',
            'payment_intent.payment_failed', 'payment_intent.canceled' => 'failed',
            default => null,
        };
        if ($to === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        return $tenant->forOrganization(
            (string) $payment->organization_id,
            fn (): JsonResponse => $this->once($event, fn () => $folios->settlePayment($payment, $to)),
        );
    }

    /**
     * A refund on the connected account settling — `pending` → succeeded or failed.
     *
     * ⚠️ THE STATUS IS READ FROM THE PAYLOAD, NOT INFERRED FROM THE EVENT NAME. The same flagged inference
     * as on the platform plane: Stripe documents `refund.updated` as "sent when the refund is updated" and
     * illustrates it with metadata/ARN rather than stating outright that a pending→succeeded transition
     * emits it. So an unexpected shape settles NOTHING rather than settling wrongly.
     */
    private function handleRefundSettlement(StripeEvent $event, TenantContext $tenant, FolioService $folios): JsonResponse
    {
        $refundRef = $event->objectField('id');
        $refund = $refundRef === null ? null : $tenant->runAsSuperAdmin(
            fn () => Payment::query()->where('type', 'refund')->where('gateway_ref', $refundRef)->first(),
        );
        if ($refund === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $status = (string) ($event->objectField('status') ?? '');
        $to = match (true) {
            $status === 'succeeded' => 'succeeded',
            in_array($status, ['failed', 'canceled'], true) => 'failed',
            default => null,
        };
        if ($to === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        return $tenant->forOrganization(
            (string) $refund->organization_id,
            fn (): JsonResponse => $this->once($event, fn () => $folios->settleRefund($refund, $to)),
        );
    }

    private function handleAccountUpdated(StripeEvent $event, TenantContext $tenant): JsonResponse
    {
        $accountId = $event->objectField('id');
        $owner = $accountId === null ? null : $tenant->runAsSuperAdmin(
            fn () => Owner::query()->where('stripe_connect_account_id', $accountId)->first(),
        );
        if ($owner === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $enabled = $event->objectBool('payouts_enabled');

        return $tenant->forOrganization((string) $owner->organization_id, fn (): JsonResponse => $this->once(
            $event,
            fn () => Owner::query()->whereKey($owner->getKey())->update(['stripe_connect_payouts_enabled' => $enabled]),
        ));
    }

    private function handleTransfer(StripeEvent $event, TenantContext $tenant, OwnerPayoutService $payouts): JsonResponse
    {
        $transferId = $event->objectField('id');
        $payout = $transferId === null ? null : $tenant->runAsSuperAdmin(
            fn () => Payout::query()->where('external_ref', $transferId)->first(),
        );
        if ($payout === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        return $tenant->forOrganization((string) $payout->organization_id, fn (): JsonResponse => $this->once(
            $event,
            fn () => $payouts->confirmTransfer((string) $transferId),
        ));
    }

    /** Receiving-side idempotency: the event is recorded once (unique index); `$apply` runs only the first time. */
    private function once(StripeEvent $event, Closure $apply): JsonResponse
    {
        $webhookEvent = WebhookEvent::createOrFirst(
            ['source' => 'stripe', 'external_event_id' => $event->id],
            ['event_type' => $event->type, 'signature_verified' => true, 'payload' => $event->payload, 'status' => WebhookEvent::STATUS_RECEIVED, 'received_at' => now()],
        );

        if (! $webhookEvent->wasRecentlyCreated) {
            return response()->json(['status' => 'duplicate'], 202);
        }

        $apply();
        $webhookEvent->update(['status' => WebhookEvent::STATUS_PROCESSED, 'processed_at' => now()]);

        return response()->json(['status' => 'processed'], 202);
    }
}
