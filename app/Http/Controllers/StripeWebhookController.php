<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeEvent;
use App\Services\FolioService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Public (unauthenticated) inbound Stripe webhook endpoint (Step 6). Confirms async payment
 * status (SCA/3DS completion, processing→succeeded, failures). Signature is validated with
 * Stripe's real `Stripe-Signature` scheme via the client seam; the config-gated
 * `services.stripe.webhook_secret` is the key.
 *
 * Idempotency reuses the Step 4 pattern: dedupe on `webhook_events(source='stripe',
 * external_event_id)` via `createOrFirst` (concurrent-safe), and the settle itself only advances a
 * PENDING payment (double-apply-safe). Status updates go THROUGH {@see FolioService} so the folio/
 * reservation totals are recomputed from the ledger — never around it.
 *
 * Runs with no tenant context: it resolves the target Payment (hence the org) under an explicit
 * super-admin context, then sets that org for the scoped writes.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TenantContext $tenant,
        StripeClientInterface $stripe,
        FolioService $folios,
    ): JsonResponse {
        try {
            $event = $stripe->constructWebhookEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (Throwable) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        $paymentIntentId = $event->paymentIntentId();
        if ($paymentIntentId === null) {
            return response()->json(['status' => 'ignored'], 202); // event we don't act on
        }

        // Resolve the local payment (and thus its org) across tenants, without leaking the guard.
        $payment = $tenant->runAsSuperAdmin(
            fn () => Payment::query()->where('gateway_ref', $paymentIntentId)->first(),
        );

        if ($payment === null) {
            return response()->json(['status' => 'ignored'], 202); // not an intent we track
        }

        return $tenant->forOrganization((string) $payment->organization_id, function () use ($event, $payment, $folios): JsonResponse {
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
                return response()->json(['status' => 'duplicate'], 202); // already handled
            }

            $this->apply($event, $payment, $folios);

            $webhookEvent->update(['status' => WebhookEvent::STATUS_PROCESSED, 'processed_at' => now()]);

            return response()->json(['status' => 'processed'], 202);
        });
    }

    private function apply(StripeEvent $event, Payment $payment, FolioService $folios): void
    {
        match ($event->type) {
            'payment_intent.succeeded' => $folios->settlePayment($payment, 'succeeded'),
            'payment_intent.payment_failed', 'payment_intent.canceled' => $folios->settlePayment($payment, 'failed'),
            default => null, // acknowledged but not acted on
        };
    }
}
