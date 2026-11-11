<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Payment;
use App\Models\PaymentDispute;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeEvent;
use App\Support\Billing\CheckoutSessions;
use App\Support\Tenancy\TenantContext;
use App\Support\Webhooks\WebhookEventClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️⚠️ ADMIN 06 — what BOTH Stripe receivers do with an event that is not simply applied, in ONE place.
 *
 *   · A correctly signed event this deployment cannot place — its payment, refund, payout, owner or organisation no
 *     longer exists (a DISPUTE can arrive months after the charge, long after a deletion), or a type it does not act
 *     on — is RECORDED with the reason and answered 202. Never a 5xx (Stripe would retry it for days into a poison
 *     message), never a crash, never silently dropped: every such event used to be answered `202 ignored` with no
 *     trace at all, except the billing events.
 *   · A Checkout page closing (`checkout.session.completed` / `.expired`) closes its recorded session, so a deletion
 *     stops refusing on it (KNOWN-LIMITS L19).
 *   · A card DISPUTE (`charge.dispute.*`) is recorded by its own id ({@see PaymentDispute}), so a deletion refuses
 *     while any is open.
 *
 * The using controller supplies `once(StripeEvent, Closure): JsonResponse` — the ONE idempotency rule.
 */
trait HandlesStripeLifecycleEvents
{
    private function unresolved(StripeEvent $event, TenantContext $tenant, string $reason, bool $routine = false): JsonResponse
    {
        // `failed` only where the event shows it was OURS — it names an organisation (every object the app creates at
        // Stripe carries `metadata.organization_id`) or it is a dispute (always someone's money). Anything else — a
        // subscription invoice's PaymentIntent, an operator account's update, a type the app does not act on — is
        // recorded `ignored`, with the same reason, so the reconciliation's list stays a list of real problems.
        // ⚠️ REVIEW 2 (D-R2-1) + REVIEW 3: `$routine` — the caller KNOWS it is nothing to act on: an EXISTING operator's
        // own Connect account, which carries its organisation's id too (a card charge re-reads the account from Stripe).
        // Recording each of its updates `failed` turned the daily reconciliation red the moment any operator onboarded.
        // Only that case: an account update naming an organisation that is GONE stays `failed` — a deleted customer's
        // account still attached to the platform is a real problem.
        $ours = ! $routine && ($event->metadataOrganizationId() !== null || str_starts_with($event->type, 'charge.dispute.'));
        $tenant->runAsSuperAdmin(fn () => WebhookEventClaim::recordUnresolved(
            source: 'stripe',
            externalEventId: $event->id,
            eventType: $event->type,
            payload: $event->payload,
            reason: $reason,
            status: $ours ? WebhookEvent::STATUS_FAILED : WebhookEvent::STATUS_IGNORED,
        ));

        return response()->json(['status' => 'unresolved'], 202);
    }

    private function handleCheckoutSession(StripeEvent $event, TenantContext $tenant): JsonResponse
    {
        $org = CheckoutSessions::organizationFor($event, $tenant);
        if ($org === null) {
            // ⚠️ D-R3-3 / DEC-g (2026-11-11, his "do all"): A TEST-MODE PAGE'S EXPIRY, ARRIVING AFTER ITS ORGANISATION WAS DELETED, IS ROUTINE.
            //    It names the organisation (so it was recorded `failed`, and the daily reconciliation showed one red row) for a page that moved
            //    no money. Recorded `ignored` — with the same reason — only when ALL of: it is an EXPIRY (a page that was paid after the
            //    deletion is an anomaly, `.completed` stays `failed`); Stripe says it is TEST mode (a live event, or one that says nothing,
            //    stays `failed` — money may be involved); it names an organisation; and that organisation is really GONE (one that still
            //    exists, an archived one included, has no record of this page, which is a real problem).
            $named = $event->metadataOrganizationId();
            $routine = $event->type === 'checkout.session.expired'
                && $event->livemode() === false
                && $named !== null
                && ! $tenant->runAsSuperAdmin(fn () => DB::table('organizations')->where('id', $named)->exists());

            return $this->unresolved($event, $tenant, 'a Checkout page this deployment has no record of — never opened here, or its organisation was deleted', routine: $routine);
        }

        return $tenant->forOrganization($org, fn (): JsonResponse => $this->once($event, fn () => CheckoutSessions::closeFromEvent($event)));
    }

    private function handleDispute(StripeEvent $event, TenantContext $tenant): JsonResponse
    {
        $intent = $event->objectField('payment_intent');
        $payment = $intent === null ? null : $tenant->runAsSuperAdmin(
            fn () => Payment::query()->where('gateway_ref', $intent)->where('type', '!=', 'refund')->first(),
        );
        if ($payment === null) {
            return $this->unresolved($event, $tenant, 'a dispute on a payment this deployment has no record of — its organisation may have been deleted; answer it in Stripe');
        }

        $disputeId = $event->objectField('id');
        if ($disputeId === null) {
            return $this->unresolved($event, $tenant, 'a dispute event that names no dispute');
        }
        $status = (string) ($event->objectField('status') ?? 'needs_response');

        // each dispute by Stripe's own id, ordered by the event's own time — see PaymentDispute::record()
        return $tenant->forOrganization((string) $payment->organization_id, fn (): JsonResponse => $this->once(
            $event,
            fn () => PaymentDispute::record($payment, $disputeId, $status, $event->created(), $event->livemode()),
        ));
    }
}
