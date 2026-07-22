<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\StripeWebhookController;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeEvent;
use App\Support\Billing\SubscriptionStatus;
use Illuminate\Support\Carbon;

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

    /** The org the event belongs to (stamped into the object metadata at Checkout create). */
    public function organizationIdFor(StripeEvent $event): ?string
    {
        return $event->metadataOrganizationId();
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
            'invoice.paid' => $this->renew($subscription, $event),
            'invoice.payment_failed' => $subscription->update(['status' => 'past_due']),
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
    }

    /** customer.subscription.created/updated → map Stripe's status onto ours + carry the period end. */
    private function sync(Subscription $subscription, StripeEvent $event): void
    {
        $update = ['status' => $this->mapStatus($event->objectField('status'), $subscription->status)];

        $subId = $event->objectField('id');
        if ($subId !== null) {
            $update['stripe_subscription_id'] = $subId;
        }
        $customer = $event->objectField('customer');
        if ($customer !== null) {
            $update['stripe_customer_id'] = $customer;
        }
        $periodEnd = $event->objectInt('current_period_end');
        if ($periodEnd !== null) {
            $update['current_period_end'] = Carbon::createFromTimestamp($periodEnd);
        }

        $subscription->update($update);
    }

    /** invoice.paid → a successful renewal keeps the org active and extends the period. */
    private function renew(Subscription $subscription, StripeEvent $event): void
    {
        $update = ['status' => 'active'];
        $periodEnd = $event->objectInt('period_end');
        if ($periodEnd !== null) {
            $update['current_period_end'] = Carbon::createFromTimestamp($periodEnd);
        }

        $subscription->update($update);
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
