<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Payout;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeEvent;
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
    public function __invoke(Request $request, TenantContext $tenant, StripeClientInterface $stripe, OwnerPayoutService $payouts): JsonResponse
    {
        try {
            $event = $stripe->constructConnectWebhookEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (Throwable) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        return match ($event->type) {
            'account.updated' => $this->handleAccountUpdated($event, $tenant),
            'transfer.created', 'transfer.updated' => $this->handleTransfer($event, $tenant, $payouts),
            default => response()->json(['status' => 'ignored'], 202),
        };
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
