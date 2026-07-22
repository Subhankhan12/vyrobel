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
