<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\StripeWebhookController;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeEvent;
use App\Support\Billing\BillingEventOrganization;
use App\Support\Billing\PurchasedPlan;
use App\Support\Billing\StripeTaxIdSync;
use App\Support\Billing\SubscriptionStatus;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Subscription-billing webhook handling (Phase 8 · SG1) — the ONLY place an org's subscription becomes
 * `active`, `past_due` or `canceled`. 🔒 Fulfilment is on the WEBHOOK, never the redirect: the subscribe
 * action just creates a Checkout Session, so a closed tab still activates the org here when Stripe delivers
 * `checkout.session.completed`. Runs inside the event's org tenant context (set by
 * {@see StripeWebhookController}); receiving-side idempotency is the
 * {@see WebhookEvent} unique index, so a re-delivered event never double-activates.
 *
 * The 7b {@see SubscriptionStatus} derives access purely from `status`, so updating the
 * status here drives the paywall/access gate from real Stripe events — no fabricated "active".
 */
class BillingWebhookService
{
    /** The billing event types this service fulfils (everything else the webhook acknowledges + ignores). */
    private const HANDLED = [
        'checkout.session.completed',
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'invoice.paid',
        'invoice.payment_failed',
    ];

    public static function handles(string $type): bool
    {
        return in_array($type, self::HANDLED, true);
    }

    /**
     * The org the event belongs to — resolved through the ONE {@see BillingEventOrganization} resolver.
     *
     * ⚠️⚠️ THIS USED TO BE `return $event->metadataOrganizationId();` AND THAT MADE TWO HANDLERS INERT.
     * Invoices do not carry the metadata the checkout stamps, so `invoice.paid` and
     * `invoice.payment_failed` never resolved and were answered `202 ignored` — which is
     * indistinguishable from success. Resolution now reads the Stripe ids this product already stores.
     */
    public function organizationFor(StripeEvent $event, TenantContext $tenant): BillingEventOrganization
    {
        return BillingEventOrganization::resolve($event, $tenant);
    }

    /** Apply the event to the org's subscription. The org tenant context is already set by the caller. */
    public function handle(StripeEvent $event): void
    {
        $subscription = Subscription::query()->latest()->first();
        if ($subscription === null) {
            return;   // no subscription to update (an org that never trialed) — acknowledged, nothing to do
        }

        match ($event->type) {
            'checkout.session.completed' => $this->activate($subscription, $event),
            'customer.subscription.created', 'customer.subscription.updated' => $this->sync($subscription, $event),
            'customer.subscription.deleted' => $subscription->update(['status' => 'canceled']),
            'invoice.paid' => $this->applyInvoice($subscription, $event),
            'invoice.payment_failed' => $this->applyInvoice($subscription, $event),
            default => null,
        };
    }

    /** checkout.session.completed → the org is now subscribed. Store the Stripe ids + go active. */
    private function activate(Subscription $subscription, StripeEvent $event): void
    {
        $subscription->update([
            'status' => 'active',
            'stripe_subscription_id' => $event->objectField('subscription') ?? $subscription->stripe_subscription_id,
            'stripe_customer_id' => $event->objectField('customer') ?? $subscription->stripe_customer_id,
        ]);

        $this->pushVatId($subscription);
    }

    /**
     * DO-BILL-1 — put the org's stored VAT number on the Stripe customer now that one exists.
     *
     * ⚠️ THIS IS THE EARLIEST POSSIBLE MOMENT. The app never creates the Stripe customer — Checkout does, and
     * we only learn its id from this event. Attaching here means the number is on the customer before the
     * FIRST RENEWAL invoice, which is where it matters most: Stripe re-computes tax per invoice, so a missing
     * id would overcharge a reverse-charge business every month, not just once.
     *
     * ⚠️ Never allowed to break fulfilment. A webhook that throws is retried and can become a poison message,
     * so a VAT failure is logged inside the sync and the subscription activates regardless.
     */
    private function pushVatId(Subscription $subscription): void
    {
        // ⚠️ Unscoped on purpose: a webhook arrives with NO tenant context (there is no logged-in user), so the
        // org must be resolved by its own key. This mirrors how the rest of this service already resolves the
        // subscription for an inbound event — it is not a tenancy bypass, it IS the tenant resolution.
        $organization = Organization::query()->find($subscription->organization_id);

        if ($organization !== null) {
            StripeTaxIdSync::push($organization);
        }
    }

    /**
     * customer.subscription.created/updated → map Stripe's status onto ours, carry the period end, and
     * ⚠️⚠️ RECORD WHICH PLAN THE CUSTOMER ACTUALLY BOUGHT.
     *
     * ⚠️ NOTHING USED TO DO THAT. An org started on the trial tier, bought Essential, Stripe billed €49 —
     * and `plan_id` never moved, so the app showed Pro and billed from Pro's captured terms. Measured end to
     * end. It is the ordinary first subscription, because every org begins on the trial plan.
     *
     * ⚠️ THIS EVENT, NOT `checkout.session.completed`. Only the SUBSCRIPTION object carries the Price
     * (`items.data[].price.id`); a session payload has no line items unless it was expanded. The session
     * knows what the app INTENDED (its metadata); this knows what Stripe CHARGED.
     *
     * ⚠️ ONE SAVE, DELIBERATELY. `plan_id` goes into the SAME `update()` as the status and ids, so the
     * model's `updating` hook sees `isDirty('plan_id')` and re-captures the terms in the same write. Two
     * saves would open a window where the plan and its captured terms disagreed.
     */
    private function sync(Subscription $subscription, StripeEvent $event): void
    {
        // ⚠️⚠️ READ THE OBJECT BACK FROM STRIPE — THIS IS WHAT MAKES ORDER IRRELEVANT.
        //
        // Re-delivery was already handled (`webhook_events` is UNIQUE on `(source, external_event_id)`), but
        // that solves DUPLICATES, not ORDER: two DISTINCT events arriving in the wrong sequence used to write
        // the older payload straight over the newer state — `plan_id`, `status`, `current_period_end`,
        // `stripe_customer_id`, and (through the model's re-capture hook) all seven `terms_*` columns.
        //
        // ⚠️ A WATERMARK WAS THE OTHER OPTION AND IS WORSE HERE. Stripe's `created` is a UNIX SECOND, so two
        //    events in the same second cannot be ordered by it at all, and the Stripe object carries no
        //    version or sequence field to break the tie. Worse, a watermark's failure mode is DISCARDING A
        //    LEGITIMATE EVENT — after which the app diverges from Stripe permanently, and (there being no
        //    reconciliation anywhere in this product) NOBODY WOULD EVER NOTICE. That is the defect the
        //    previous pass fixed, wearing a new face.
        //
        // ⚠️ NOTHING IS EVER SKIPPED. Every event is applied; it is simply applied to CURRENT truth. There is
        //    no "drop" branch to get wrong.
        $eventSubscriptionId = $event->objectField('id');
        $live = $eventSubscriptionId !== null
            ? app(StripeClientInterface::class)->retrieveSubscription($eventSubscriptionId)
            : null;

        if ($live === null && $eventSubscriptionId !== null) {
            // ⚠️ NOT SILENT. Stripe not knowing a subscription an event is about is close to impossible in
            //    production — an unreachable Stripe THROWS, so the delivery retries rather than landing here.
            //    The event payload is still Stripe's own SIGNED statement of state, so it is evidence and not
            //    a guess; it is only older, and the ordering exposure returns for this path alone.
            Log::warning('Could not read the Stripe subscription back — applying the EVENT payload, which may be stale', [
                'subscription' => $subscription->getKey(),
                'stripe_subscription' => $eventSubscriptionId,
            ]);
        }

        // ⚠️ `$live->` NOT `$live?->`, and it IS safe: PHP's `??` uses isset() semantics, so a property read
        //    on a null object yields the right-hand side rather than erroring. PHPStan flags the nullsafe as
        //    redundant here for exactly that reason — do not "fix" it back.
        $update = ['status' => $this->mapStatus($live->status ?? $event->objectField('status'), $subscription->status)];

        $purchased = $live !== null ? PurchasedPlan::fromSubscription($live) : PurchasedPlan::fromEvent($event);

        if ($purchased->problem !== null) {
            // ⚠️ A REFUSAL, NOT A GUESS — and deliberately not an exception. The customer has already paid and
            //    a throw here is retried by Stripe until it becomes a poison message, so this activates them
            //    on everything else and leaves `plan_id` exactly as it was rather than inventing one.
            //    ⚠️ It does NOT fall back to `metadata['plan']`: that is what the app hoped, not what was
            //    charged, and using it here would be a guess wearing evidence's clothes.
            Log::warning('Stripe subscription price does not resolve to a plan — plan_id left unchanged', [
                'subscription' => $subscription->getKey(),
                'organization' => $subscription->organization_id,
                'problem' => $purchased->problem,
            ]);
        } elseif ($purchased->plan !== null && (string) $purchased->plan->getKey() !== (string) $subscription->plan_id) {
            if ($purchased->metadataDisagreement !== null) {
                // ⚠️ THE PRICE WINS, AND THE DISAGREEMENT IS RECORDED. Something changed between creating the
                //    Checkout Session and Stripe charging the card. Refusing to activate would leave a PAYING
                //    customer without service — a second harm on top of the first — so the subscription is
                //    recorded against what the money was actually taken for, loudly.
                Log::warning('Stripe charged a different plan than the checkout intended — recording the CHARGED plan', [
                    'subscription' => $subscription->getKey(),
                    'charged' => $purchased->plan->code,
                    'intended' => $purchased->metadataDisagreement,
                    'price' => $purchased->priceId,
                ]);
            }

            $update['plan_id'] = $purchased->plan->getKey();
        }

        // ⚠️ EVERY field below prefers the LIVE object and falls back to the event only when there was none.
        $subId = $live->id ?? $eventSubscriptionId;
        if ($subId !== null) {
            $update['stripe_subscription_id'] = $subId;
        }
        $customer = $live->customerId ?? $event->objectField('customer');
        $customerIsNew = $customer !== null && $customer !== $subscription->stripe_customer_id;
        if ($customer !== null) {
            $update['stripe_customer_id'] = $customer;
        }
        $periodEnd = $live->currentPeriodEnd ?? $event->objectInt('current_period_end');
        if ($periodEnd !== null) {
            $update['current_period_end'] = Carbon::createFromTimestamp($periodEnd);
        }

        $subscription->update($update);

        // A customer we had not seen before — same reasoning as activate().
        if ($customerIsNew) {
            $this->pushVatId($subscription);
        }
    }

    /**
     * `invoice.paid` / `invoice.payment_failed` → the org's subscription state, read back from STRIPE.
     *
     * ⚠️⚠️ WHAT THIS REPLACED, AND WHY IT WAS THE WORST OF THE THREE. `renew()` wrote
     * `status = 'active'` UNCONDITIONALLY and `current_period_end` straight from the invoice payload. A
     * late delivery therefore RESTORED SERVICE TO A CANCELLED ACCOUNT — `SubscriptionStatus::hasFullAccess()`
     * is `onTrial() || active()`, so writing `active` hands a cancelled org the whole app back — and
     * extended its period with it. Nothing in the product would have noticed: there is still no
     * reconciliation anywhere, which is the second of the three reasons the re-fetch was chosen over a
     * watermark (docs/BILLING-WEBHOOK-ORDERING.md).
     *
     * ⚠️⚠️ THE WRINKLE THAT MAKES THIS DIFFERENT FROM THE OTHER TWO INSTANCES: THE EVENT IS AN INVOICE AND
     * THE WRITE IS TO A SUBSCRIPTION. Re-fetching the invoice would prove the INVOICE is still paid and say
     * nothing at all about whether the SUBSCRIPTION is still active. **The object to re-fetch is the one
     * being CHANGED, not the one that arrived.**
     *
     * ⚠️ AND THE PERIOD COMES FROM THE SUBSCRIPTION, NOT THE INVOICE. They are different fields with
     * different meanings: an invoice's `period_end` is the span that invoice BILLED FOR, while
     * `current_period_end` is how long the subscription is PAID UP UNTIL. They coincide on a clean renewal
     * and diverge on proration, mid-cycle plan changes, and one-off items — and the second is the value
     * this column means. Taking the invoice's period because it "arrived with the payment" is the same
     * class of mistake as taking its `status`.
     *
     * ⚠️ BOTH INVOICE EVENTS GO THROUGH HERE, deliberately. They had the same defect in the same shape —
     * `payment_failed` wrote `past_due` from the payload just as unconditionally — and one definition
     * cannot drift from itself.
     */
    private function applyInvoice(Subscription $subscription, StripeEvent $event): void
    {
        $subscriptionId = $this->invoiceSubscriptionId($event);

        // ⚠️ AN INVOICE NEED NOT NAME A SUBSCRIPTION AT ALL — a one-off charge is an invoice with no
        //    subscription behind it. There is nothing to renew or lapse, so this REFUSES rather than
        //    guessing: it does not fall back to the payload, and it does not throw, because a retry would
        //    deliver exactly the same subscription-less invoice forever.
        if ($subscriptionId === null) {
            Log::warning('An invoice event names no subscription — nothing applied', [
                'subscription' => $subscription->getKey(),
                'organization' => $subscription->organization_id,
                'event' => $event->type,
            ]);

            return;
        }

        // ⚠️ AND IT MUST NAME **THIS** SUBSCRIPTION. The org was resolved from event metadata and the row
        //    from `latest()`; an invoice for some other subscription of the same org must not write here.
        $known = $subscription->stripe_subscription_id;

        if ($known !== null && $known !== $subscriptionId) {
            Log::warning('An invoice event names a different subscription than the org holds — nothing applied', [
                'subscription' => $subscription->getKey(),
                'held' => $known,
                'invoiced' => $subscriptionId,
                'event' => $event->type,
            ]);

            return;
        }

        $live = app(StripeClientInterface::class)->retrieveSubscription($subscriptionId);

        if ($live === null) {
            // ⚠️⚠️ THROW, AND THE ASYMMETRY IS THE ARGUMENT — the same one the Connect pass settled.
            //
            // `invoice.paid` moves an account TOWARDS SERVICE. An unverifiable move in that direction is
            // exactly the defect above, so DEGRADING TO THE PAYLOAD IS THE ONE OPTION THAT IS NOT
            // AVAILABLE — it is what this method was rewritten to stop doing. Returning silently was the
            // other rejected option: it would leave a genuinely renewing customer's period unextended and
            // leave no trace of why.
            //
            // ⚠️ A THROW IS SAFE HERE NOW, AND IT WAS NOT ALWAYS. The event-loss pass separated CLAIMED
            //    from PROCESSED, so a throw no longer destroys the event: the row is written `failed` WITH
            //    THIS REASON, it is re-claimable, and Stripe's retry is admitted. And a retry can
            //    genuinely succeed — an unreachable Stripe is transient — which is the test for when a
            //    throw beats a refusal.
            throw new RuntimeException(
                "Could not read subscription [{$subscriptionId}] back from Stripe while applying "
                ."[{$event->type}]; refusing to write subscription state from a possibly-stale payload."
            );
        }

        // ⚠️ THE SAME MAPPING AS `sync()`, so a subscription Stripe reports as `canceled` STAYS canceled no
        //    matter which invoice event arrives, and an unknown status leaves the current one untouched.
        $update = ['status' => $this->mapStatus($live->status, $subscription->status)];

        if ($live->currentPeriodEnd !== null) {
            $update['current_period_end'] = Carbon::createFromTimestamp($live->currentPeriodEnd);
        }

        $subscription->update($update);
    }

    /**
     * The subscription an invoice belongs to, across both payload shapes.
     *
     * ⚠️ NO API VERSION IS PINNED ANYWHERE IN THIS APP, so the shape that arrives is whatever the Stripe
     * ACCOUNT's default version emits, and it is not this code's to choose. Older versions put the id at
     * `invoice.subscription`; 2025-era versions moved it to
     * `invoice.parent.subscription_details.subscription`. Reading only one shape would make this silently
     * inert on half the possible accounts — which is the failure mode that looks exactly like success.
     */
    private function invoiceSubscriptionId(StripeEvent $event): ?string
    {
        $direct = $event->objectField('subscription');

        if ($direct !== null && $direct !== '') {
            return $direct;
        }

        $nested = data_get($event->object(), 'parent.subscription_details.subscription');

        return is_string($nested) && $nested !== '' ? $nested : null;
    }

    /** Stripe subscription status → our 7b status (active / past_due / canceled). */
    private function mapStatus(?string $stripeStatus, string $current): string
    {
        return match ($stripeStatus) {
            'active', 'trialing' => 'active',
            'past_due', 'unpaid' => 'past_due',
            'canceled', 'incomplete_expired' => 'canceled',
            default => $current,   // 'incomplete'/unknown → leave the current status untouched
        };
    }
}
