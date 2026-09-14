<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Payment;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeEvent;
use App\Services\BillingWebhookService;
use App\Services\FolioService;
use App\Support\Tenancy\TenantContext;
use App\Support\Webhooks\WebhookEventClaim;
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

        // ── SUBSCRIPTION BILLING events → resolve the org from the STRIPE OBJECT ───────────────────────
        //
        // ⚠️⚠️ THIS USED TO RESOLVE FROM `metadata.organization_id` ALONE, AND THAT MADE TWO HANDLERS INERT
        //    IN PRODUCTION. The checkout stamps that metadata on the Session and on the Subscription;
        //    Stripe does not copy it onto the invoices it generates, so a real `invoice.paid` /
        //    `invoice.payment_failed` carried none, resolved to nothing, and was answered `202 ignored`.
        //    Nothing ever marked an organisation `past_due`, so a customer whose card stopped working kept
        //    full access indefinitely. Resolution now goes through {@see BillingEventOrganization}.
        if (BillingWebhookService::handles($event->type)) {
            $resolution = $billing->organizationFor($event, $tenant);
            $orgId = $resolution->organizationId;
            $orgExists = $orgId !== null && $tenant->runAsSuperAdmin(
                fn (): bool => Organization::query()->whereKey($orgId)->exists(),
            );

            if (! $orgExists) {
                // ⚠️⚠️ "IGNORED" IS NOT A RESULT, AND IT USED TO LEAVE NO TRACE AT ALL — not a log line, not
                //    a row. An event the product could not place looked exactly like one it handled
                //    perfectly, which is the silence that hid the inert handlers for this product's whole
                //    life. It is now RECORDED, with the reason, as a row that does NOT read as processed.
                //
                // ⚠️ STILL 202, DELIBERATELY. A 5xx would make Stripe retry, and an event naming an
                //    organisation this deployment genuinely does not have would retry until it became a
                //    poison message. Acknowledged and written down is the honest pair.
                // ⚠️ SUPER-ADMIN, because there is no tenant context to write under — that is precisely
                //    what could not be resolved. `webhook_events` is a tenant-scoped model, so writing the
                //    evidence of a failed resolution would otherwise throw for the same reason it failed.
                $tenant->runAsSuperAdmin(fn () => WebhookEventClaim::recordUnresolved(
                    source: 'stripe',
                    externalEventId: $event->id,
                    eventType: $event->type,
                    payload: $event->payload,
                    reason: $resolution->problem
                        ?? ($orgId === null
                            ? 'could not resolve an organisation from the event'
                            : "resolved organisation [{$orgId}] does not exist in this deployment"),
                ));

                return response()->json(['status' => 'unresolved'], 202);
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
     * ⚠️⚠️ MERGED — THE TRIGGER FIRED, AND THE RECORD BELOW IS KEPT BECAUSE IT PREDICTED THIS.
     *
     * This docblock used to say "NOT MERGED NOW", and named the exact conditions under which it should be.
     * BOTH have since fired: `ChannexWebhookController` is a THIRD writer of `webhook_events`, and the
     * claim/process separation is precisely "a change of the rule's shape … a status". The rule now lives in
     * {@see WebhookEventClaim}, once.
     *
     * ⚠️ WHAT *IS* CORRECTLY SEPARATE: the two receivers verify DIFFERENT secrets and dispatch DIFFERENT
     * handlers. That separation is real and must stay.
     * ⚠️ WHAT *WAS* DUPLICATED: the idempotency RULE itself. Both copies wrote
     * `['source' => 'stripe', 'external_event_id' => $event->id]` against the SAME
     * `unique(source, external_event_id)` index — one rule, one key space, two implementations that differed
     * only in line formatting. There is now one.
     *
     * ⚠️ SHARING THE KEY SPACE IS DELIBERATE AND LOAD-BEARING: a Stripe event id is globally unique, so an
     * event delivered to both endpoints dedupes ACROSS them. That is a property of the shared `source`
     * value — which is precisely why the two copies must not drift.
     *
     * ⚠️ THE TRIGGER, AS WRITTEN AT THE TIME — merge on the FIRST of:
     *   · a THIRD receiver needing this guard (two that have not drifted is a warning; three is a pattern);
     *   · ANY change to the rule's shape (a new field, a different key, a TTL, a status) — the moment the
     *     two must be edited in lockstep is the moment duplicates drift.
     * ⚠️ Both fired. The merge happened here rather than editing one rule in two files, which is the drift
     *    this note existed to prevent. ⚠️ `ChannexWebhookController` is deliberately NOT merged in: it
     *    QUEUES its work and its state machine lives in the job, which is a different shape, not a copy.
     */
    /**
     * Run the event's work at most once — ⚠️⚠️ and let a delivery that FAILED be retried.
     *
     * ⚠️ THE RULE NOW LIVES IN ONE PLACE ({@see WebhookEventClaim}). This method's previous body claimed the
     * `webhook_events` row and then tested only whether the row EXISTED, so a throw inside the closure
     * consumed the event: Stripe retried, the row was already there, the retry was answered "duplicate", and
     * the delivery was lost permanently. The guard and the loss mechanism were the same line.
     *
     * ⚠️ The two receivers' shared docblock pre-committed to merging on the first of "a THIRD receiver" or
     * "ANY change to the rule's shape (… a status)". Both have fired — `ChannexWebhookController` is a third
     * writer of this table, and this IS a status change — so the copies are gone rather than edited twice.
     */
    private function once(StripeEvent $event, Closure $apply): JsonResponse
    {
        $result = WebhookEventClaim::once(
            source: 'stripe',
            externalEventId: $event->id,
            eventType: $event->type,
            payload: $event->payload,
            apply: $apply,
        );

        return response()->json(['status' => $result], 202);
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
