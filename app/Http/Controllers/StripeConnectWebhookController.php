<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesStripeLifecycleEvents;
use App\Models\Organization;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Payout;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeEvent;
use App\Services\FolioService;
use App\Services\OwnerPayoutService;
use App\Support\Tenancy\TenantContext;
use App\Support\Webhooks\WebhookEventClaim;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
    use HandlesStripeLifecycleEvents;

    public function __invoke(Request $request, TenantContext $tenant, StripeClientInterface $stripe, OwnerPayoutService $payouts, FolioService $folios): JsonResponse
    {
        try {
            $event = $stripe->constructConnectWebhookEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (Throwable) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        return match (true) {
            $event->type === 'account.updated' => $this->handleAccountUpdated($event, $tenant, $stripe),
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

            // ⚠️ ADMIN 06 — a guest's Checkout page closing, and a card dispute (direct charges live on this plane)
            in_array($event->type, ['checkout.session.completed', 'checkout.session.expired'], true) => $this->handleCheckoutSession($event, $tenant),
            str_starts_with($event->type, 'charge.dispute.') => $this->handleDispute($event, $tenant),

            default => $this->unresolved($event, $tenant, 'an event type this deployment does not act on'),
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
            return $this->unresolved($event, $tenant, 'a payment this deployment has no record of — its organisation may have been deleted');
        }

        $to = match ($event->type) {
            'payment_intent.succeeded' => 'succeeded',
            'payment_intent.payment_failed', 'payment_intent.canceled' => 'failed',
            default => null,
        };
        if ($to === null) {
            // unreachable today (only these three types are routed here) — recorded, never dropped, if that changes
            return $tenant->forOrganization((string) $payment->organization_id, fn (): JsonResponse => $this->once($event, fn () => null));
        }

        return $tenant->forOrganization(
            (string) $payment->organization_id,
            fn (): JsonResponse => $this->once($event, fn () => $folios->settlePayment($payment, $to, $event->livemode())),
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
            return $this->unresolved($event, $tenant, 'a refund this deployment has no record of — its payment or organisation may have been deleted');
        }

        $status = (string) ($event->objectField('status') ?? '');
        $to = match (true) {
            $status === 'succeeded' => 'succeeded',
            in_array($status, ['failed', 'canceled'], true) => 'failed',
            default => null,
        };
        if ($to === null) {
            // still pending — nothing to settle yet; recorded as received, never dropped
            return $tenant->forOrganization((string) $refund->organization_id, fn (): JsonResponse => $this->once($event, fn () => null));
        }

        return $tenant->forOrganization(
            (string) $refund->organization_id,
            fn (): JsonResponse => $this->once($event, fn () => $folios->settleRefund($refund, $to, $event->livemode())),
        );
    }

    /**
     * `account.updated` → the owner's payouts-enabled flag, ⚠️⚠️ READ BACK FROM STRIPE, NOT TAKEN FROM THE
     * PAYLOAD.
     *
     * ⚠️⚠️ THIS FLAG GATES MONEY LEAVING. It used to be written straight from `data.object.payouts_enabled`,
     * so a LATE `account.updated` re-enabled payouts for an owner Stripe had since disabled — and Stripe
     * disables them for reasons that matter: failed verification, a dispute, sanctions screening. The
     * subscription path proved the shape of the fix one pass ago; this is the same shape on the rail where
     * money leaves.
     *
     * ⚠️⚠️ THE READ-BACK HAPPENS **BEFORE** `once()`, AND THAT ORDERING IS LOAD-BEARING. `once()` claims the
     * `webhook_events` row FIRST and only then runs its closure, so anything that throws INSIDE the closure
     * leaves the row claimed — and Stripe's retry reads `wasRecentlyCreated === false` and returns
     * "duplicate". The event would be **permanently lost**, which is the exact failure this sequence of
     * passes exists to prevent. Failing before the claim lets a retry work.
     *
     * ⚠️⚠️ AND THE TWO DIRECTIONS ARE NOT TREATED ALIKE, DELIBERATELY. Wrongly DISABLING delays an owner's
     * money and they tell us; wrongly ENABLING sends money at a rail Stripe has closed and nobody finds out,
     * because this product has no reconciliation job, no drift check and no alert. So:
     *   · ENABLE requires a CONFIRMED LIVE READ. If the account cannot be read, the delivery is allowed to
     *     fail so Stripe retries, and the flag keeps its last confirmed value.
     *   · DISABLE may apply from the payload alone — it is Stripe's own SIGNED statement, and acting on it
     *     early errs in the recoverable direction.
     * ⚠️ THE SYMMETRIC ALTERNATIVE (never apply the payload in either direction) was considered and rejected:
     *    it is simpler, but during a Stripe outage it would leave a stale ENABLED flag standing while Stripe
     *    had already closed the rail — choosing simplicity over the money-leaving direction.
     */
    private function handleAccountUpdated(StripeEvent $event, TenantContext $tenant, StripeClientInterface $stripe): JsonResponse
    {
        $accountId = $event->objectField('id');
        $owner = $accountId === null ? null : $tenant->runAsSuperAdmin(
            fn () => Owner::query()->where('stripe_connect_account_id', $accountId)->first(),
        );
        if ($owner === null) {
            $operator = $accountId !== null && $tenant->runAsSuperAdmin(fn () => Organization::withTrashed()->where('stripe_connect_account_id', $accountId)->exists());

            return $this->unresolved($event, $tenant, $operator
                ? 'an operator’s own Connect account — nothing here acts on its updates (a card charge re-reads it from Stripe)'
                : 'a Connect account no owner here holds — its organisation may have been deleted', routine: $operator);
        }

        $claimed = $event->objectBool('payouts_enabled');

        try {
            // ⚠️ The account as Stripe reports it NOW. Two events in any order both write this same answer,
            //    so ordering stops mattering — the case is removed rather than guarded.
            $enabled = $stripe->retrieveConnectAccount($accountId)->payoutsEnabled;
        } catch (Throwable $e) {
            if ($claimed) {
                // ⚠️⚠️ NEVER ENABLE ON UNVERIFIED EVIDENCE. Rethrowing before `once()` has claimed the row
                //    means Stripe retries this event cleanly; the flag keeps its last CONFIRMED value.
                Log::warning('Could not read the Connect account back — refusing to ENABLE payouts from the payload alone', [
                    'owner' => $owner->getKey(),
                    'account' => $accountId,
                ]);

                throw $e;
            }

            // ⚠️ A DISABLE is the safe direction, and the payload is Stripe's own signed statement.
            Log::warning('Could not read the Connect account back — applying the payload’s DISABLE, the safe direction', [
                'owner' => $owner->getKey(),
                'account' => $accountId,
            ]);

            $enabled = false;
        }

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
            return $this->unresolved($event, $tenant, 'a transfer to a payout this deployment has no record of — its organisation may have been deleted');
        }

        return $tenant->forOrganization((string) $payout->organization_id, fn (): JsonResponse => $this->once(
            $event,
            fn () => $payouts->confirmTransfer((string) $transferId, $event->livemode()),
        ));
    }

    /** Receiving-side idempotency: the event is recorded once (unique index); `$apply` runs only the first time. */
    /**
     * ⚠️⚠️ SETTLED (switch follow-up), SO IT STOPS BEING DEFERRED: this IS duplication, not correct
     * separation — and the distinction was worth making.
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
}
