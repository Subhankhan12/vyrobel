<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Subscription;
use App\Payments\Stripe\StripeEvent;
use App\Support\Tenancy\TenantContext;

/**
 * THE ONE resolver that answers "which organisation does this billing event belong to?".
 *
 * ⚠️⚠️ WHY IT EXISTS — TWO HANDLERS WERE INERT IN PRODUCTION AND NOTHING SAID SO. Org resolution read
 * `data.object.metadata.organization_id`, and `createSubscriptionCheckout()` stamps that metadata on the
 * Checkout SESSION and on `subscription_data` — i.e. on the SUBSCRIPTION. **Stripe does not copy
 * subscription metadata onto the invoices it generates.** So a real `invoice.paid` or
 * `invoice.payment_failed` arrived carrying none, failed to resolve, and was answered `202 ignored`.
 *
 * ⚠️ THE CONSEQUENCE WAS REVENUE, NOT DISPLAY. `invoice.payment_failed` is the only thing that ever moves an
 * organisation to `past_due`, and `SubscriptionStatus::hasFullAccess()` is `onTrial() || active()`. With the
 * handler inert, a customer whose card stopped working kept full access indefinitely: the app's view of who
 * is paying was frozen at whatever the checkout last set.
 *
 * ⚠️ AND A CODE COMMENT ASSERTED THE OPPOSITE. `StripeClient::createSubscriptionCheckout()` said the stamp
 * was "so subscription/invoice webhooks resolve the org". The subscription half is true; the invoice half
 * never was. That sentence is why nobody looked.
 *
 * ⚠️⚠️ RESOLVE FROM THE OBJECT, NOT FROM INTENT — the same lesson as {@see PurchasedPlan}. The metadata
 * records what the app INTENDED at checkout; the Stripe object records what Stripe DID. Where the object
 * names something this product already stores, that is the stronger evidence.
 *
 * ⚠️ BUT METADATA IS NOT MERELY A CROSS-CHECK HERE, AND CLAIMING OTHERWISE WOULD BREAK THE WORKING PATH.
 * On `checkout.session.completed` and `customer.subscription.created` — the FIRST events of a purchase —
 * the local row has no `stripe_subscription_id` and no `stripe_customer_id` yet, because those ids are what
 * the event is delivering. There is nothing to anchor to, and metadata is the ONLY evidence available. So
 * the rule is: **anchors first where they resolve, metadata where they cannot, and a cross-check whenever
 * both are present.**
 */
class BillingEventOrganization
{
    /** How the organisation was identified — for the log, and for the honest "could not resolve" reason. */
    public function __construct(
        public readonly ?string $organizationId,
        public readonly string $via,
        public readonly ?string $problem = null,
    ) {}

    public static function resolve(StripeEvent $event, TenantContext $tenant): self
    {
        $metadataOrg = $event->metadataOrganizationId();

        // ⚠️ The two anchors this product actually stores, most specific first. A subscription id names ONE
        //    subscription; a customer id can legitimately name several (an org that re-subscribed).
        $bySubscription = self::byColumn($tenant, 'stripe_subscription_id', self::subscriptionIdFor($event));

        if ($bySubscription->problem !== null) {
            return $bySubscription;
        }

        $byCustomer = $bySubscription->organizationId !== null
            ? null
            : self::byColumn($tenant, 'stripe_customer_id', $event->objectField('customer'));

        if ($byCustomer?->problem !== null) {
            return $byCustomer;
        }

        $anchored = $bySubscription->organizationId ?? $byCustomer?->organizationId;

        if ($anchored === null) {
            // No anchor matched. Metadata is the only remaining evidence — and on a first purchase it is
            // the CORRECT one, because the ids it would anchor to do not exist locally yet.
            return $metadataOrg === null
                ? new self(null, 'none', 'no organization_id in metadata and no stored Stripe id matched')
                : new self($metadataOrg, 'metadata');
        }

        // ⚠️ BOTH PRESENT → CROSS-CHECK, AND A DISAGREEMENT IS A REFUSAL. Writing subscription state into
        //    the wrong tenant is the worst outcome available on this path, so a conflict resolves to
        //    nothing rather than to either candidate.
        if ($metadataOrg !== null && $metadataOrg !== $anchored) {
            return new self(null, 'conflict',
                "the event's metadata names organisation [{$metadataOrg}] but its Stripe ids belong to [{$anchored}]");
        }

        return new self($anchored, $bySubscription->organizationId !== null ? 'stripe_subscription_id' : 'stripe_customer_id');
    }

    /**
     * The organisation owning the subscription with this Stripe id — refusing if more than one does.
     *
     * ⚠️ NEITHER COLUMN IS UNIQUE. `stripe_customer_id` carries a plain index and `stripe_subscription_id`
     * has no index at all, so "two rows match" is a shape the schema permits. {@see PurchasedPlan} met the
     * same thing on `plans.stripe_price_id` and refused rather than taking the first; the same rule applies
     * here, for the same reason: an ambiguous resolution is a guess, and this one would write billing state
     * into somebody else's tenant.
     *
     * ⚠️ Scopes are dropped to read across tenants — a webhook arrives with NO tenant context, so this IS
     * the tenant resolution rather than a bypass — but `SoftDeletingScope` is put back deliberately, or a
     * deleted subscription could resurrect an organisation's billing.
     */
    private static function byColumn(TenantContext $tenant, string $column, ?string $value): self
    {
        if ($value === null || $value === '') {
            return new self(null, 'none');
        }

        $organizationIds = $tenant->runAsSuperAdmin(
            fn (): array => Subscription::query()
                ->where($column, $value)
                ->distinct()
                ->pluck('organization_id')
                ->map(fn ($id): string => (string) $id)
                ->all(),
        );

        if (count($organizationIds) > 1) {
            return new self(null, $column,
                "Stripe id [{$value}] maps to ".count($organizationIds)." organisations via {$column} — refusing to guess");
        }

        return new self($organizationIds[0] ?? null, $column);
    }

    /**
     * The subscription this event is about, across every shape it takes.
     *
     * ⚠️ A subscription event's object IS the subscription, so its `id` is the anchor. An invoice NAMES one
     * — at `subscription` on older API versions and at `parent.subscription_details.subscription` on
     * 2025-era ones. No API version is pinned anywhere in this app, so both are read; reading one would
     * leave this silently inert on half of all accounts, which is the failure mode that looks like success.
     */
    private static function subscriptionIdFor(StripeEvent $event): ?string
    {
        if (str_starts_with($event->type, 'customer.subscription.')) {
            return $event->objectField('id');
        }

        $direct = $event->objectField('subscription');

        if ($direct !== null && $direct !== '') {
            return $direct;
        }

        $nested = data_get($event->object(), 'parent.subscription_details.subscription');

        return is_string($nested) && $nested !== '' ? $nested : null;
    }
}
