<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeEvent;
use App\Services\BillingWebhookService;
use App\Services\FolioService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Public (unauthenticated) inbound Stripe ACCOUNT webhook endpoint (/webhooks/stripe). Confirms async
 * payment status (SCA/3DS, processing→succeeded, failures) AND drives subscription billing (Phase 8 · SG1).
 * Signature is validated against the RAW body with Stripe's real `Stripe-Signature` scheme via the client
 * seam (config-gated `services.stripe.webhook_secret`). The Connect webhook is a SEPARATE endpoint with its
 * own secret (Phase 8 · SG3).
 *
 * Two families, ONE idempotency guarantee ({@see once()}): the receiving-side dedupe is
 * `webhook_events(source='stripe', external_event_id)` via `createOrFirst` — a re-delivered event is
 * processed exactly once (no double-settle, no double-activate). All state changes go THROUGH the services
 * ({@see FolioService} / {@see BillingWebhookService}) — never around the ledger/subscription model.
 *
 * Runs with no tenant context: it resolves the target org (via the Payment, or the billing event's metadata)
 * under an explicit super-admin context, then sets that org for the scoped writes.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TenantContext $tenant,
        StripeClientInterface $stripe,
        FolioService $folios,
        BillingWebhookService $billing,
    ): JsonResponse {
        try {
            $event = $stripe->constructWebhookEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (Throwable) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        // ── SUBSCRIPTION BILLING events → resolve the org from the event metadata ──────────────────────
        if (BillingWebhookService::handles($event->type)) {
            $orgId = $billing->organizationIdFor($event);
            $orgExists = $orgId !== null && $tenant->runAsSuperAdmin(
                fn (): bool => Organization::query()->whereKey($orgId)->exists(),
            );
            if (! $orgExists) {
                return response()->json(['status' => 'ignored'], 202);
            }

            return $tenant->forOrganization((string) $orgId, fn (): JsonResponse => $this->once($event, fn () => $billing->handle($event)));
        }

        // ── REFUND SETTLEMENT → a PENDING refund becomes succeeded or failed ──────────────────────────
        //
        // ⚠️⚠️ THIS IS LIVE ON THE PLATFORM PLANE, NOT ONLY UNDER CONNECT. Stripe's own refunds guide:
        // "Refunds use your available Stripe balance (not including pending amounts). If your available
        // balance doesn't cover the amount of the refund, Stripe holds the refund as pending for card
        // transactions … until your Stripe balance becomes sufficient." That is the PLATFORM's balance —
        // so the moment PAYMENTS_GATEWAY=stripe, a queued refund is reachable and must be settled here.
        //
        // ⚠️ `refund.updated` is the current event; `charge.refund.updated` is DEPRECATED ("Listen to
        // refund.updated for updates on all refunds instead"). `refund.failed` is sent on failure.
        // ⚠️ NOT STATED IN ONE PLACE: the docs list `refund.updated` as "sent when the refund is updated"
        // and illustrate it with metadata/ARN rather than saying outright that a pending→succeeded
        // transition emits it. The status is therefore re-read from the EVENT PAYLOAD rather than assumed
        // from the event name, so an unexpected event shape settles nothing instead of settling wrongly.
        if (in_array($event->type, ['refund.updated', 'refund.failed'], true)) {
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
                default => null,   // still pending / requires_action — nothing to settle yet.
            };
            if ($to === null) {
                return response()->json(['status' => 'ignored'], 202);
            }

            // ⚠️ ONE idempotency rule, the existing `once()` on the webhook_events unique index — a
            //    redelivery is a no-op. settleRefund() ALSO re-checks `pending` inside a row lock, so a
            //    redelivered settlement can never debit an owner twice even if the event row were absent.
            return $tenant->forOrganization(
                (string) $refund->organization_id,
                fn (): JsonResponse => $this->once($event, fn () => $folios->settleRefund($refund, $to)),
            );
        }

        // ── PAYMENT-INTENT events → resolve the org from the local Payment ─────────────────────────────
        $paymentIntentId = $event->paymentIntentId();
        if ($paymentIntentId === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $payment = $tenant->runAsSuperAdmin(
            fn () => Payment::query()->where('gateway_ref', $paymentIntentId)->first(),
        );
        if ($payment === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        return $tenant->forOrganization((string) $payment->organization_id, fn (): JsonResponse => $this->once($event, fn () => $this->applyPayment($event, $payment, $folios)));
    }

    /**
     * The receiving-side idempotency: record the event once (the unique index makes concurrent deliveries
     * resolve to one row); a duplicate is a no-op. `$apply` runs only for the first delivery.
     */
    /**
     * ⚠️⚠️ SETTLED (switch follow-up), SO IT STOPS BEING DEFERRED: this IS duplication, not correct
     * separation — and the distinction was worth making.
     *
     * ⚠️ WHAT *IS* CORRECTLY SEPARATE: the two receivers verify DIFFERENT secrets and dispatch DIFFERENT
     * handlers. That separation is real and must stay.
     * ⚠️ WHAT IS DUPLICATED: the idempotency RULE itself. Both copies write
     * `['source' => 'stripe', 'external_event_id' => $event->id]` against the SAME
     * `unique(source, external_event_id)` index — one rule, one key space, two implementations that differ
     * only in line formatting.
     *
     * ⚠️ SHARING THE KEY SPACE IS DELIBERATE AND LOAD-BEARING: a Stripe event id is globally unique, so an
     * event delivered to both endpoints dedupes ACROSS them. That is a property of the shared `source`
     * value — which is precisely why the two copies must not drift.
     *
     * ⚠️ NOT MERGED NOW: a refactor inside a money pass buys no safety today and risks the thing it
     * protects. ⚠️ THE TRIGGER, NOT A DATE — merge on the FIRST of:
     *   · a THIRD receiver needing this guard (two that have not drifted is a warning; three is a pattern);
     *   · ANY change to the rule's shape (a new field, a different key, a TTL, a status) — the moment the
     *     two must be edited in lockstep is the moment duplicates drift.
     */
    private function once(StripeEvent $event, Closure $apply): JsonResponse
    {
        $webhookEvent = WebhookEvent::createOrFirst(
            ['source' => 'stripe', 'external_event_id' => $event->id],
            [
                'event_type' => $event->type,
                'signature_verified' => true,
                'payload' => $event->payload,
                'status' => WebhookEvent::STATUS_RECEIVED,
                'received_at' => now(),
            ],
        );

        if (! $webhookEvent->wasRecentlyCreated) {
            return response()->json(['status' => 'duplicate'], 202);
        }

        $apply();
        $webhookEvent->update(['status' => WebhookEvent::STATUS_PROCESSED, 'processed_at' => now()]);

        return response()->json(['status' => 'processed'], 202);
    }

    private function applyPayment(StripeEvent $event, Payment $payment, FolioService $folios): void
    {
        match ($event->type) {
            'payment_intent.succeeded' => $folios->settlePayment($payment, 'succeeded'),
            'payment_intent.payment_failed', 'payment_intent.canceled' => $folios->settlePayment($payment, 'failed'),
            default => null, // acknowledged but not acted on
        };
    }
}
