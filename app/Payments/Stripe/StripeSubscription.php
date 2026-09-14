<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * A Stripe subscription as Stripe currently reports it — the CURRENT truth, not what an event carried.
 *
 * ⚠️⚠️ WHY THIS EXISTS. Webhooks are re-delivered (already handled: `webhook_events` is UNIQUE on
 * `(source, external_event_id)`) and they also arrive OUT OF ORDER, which nothing handled. A late
 * `customer.subscription.updated` wrote its older payload straight over newer state. Reading the object back
 * from Stripe on every event makes ordering irrelevant: two events, in any order, both write the same
 * current truth. **The case is removed rather than guarded.**
 *
 * ⚠️ It carries only what the billing handler writes — status, customer, period end, the Price ids and the
 * metadata. Adding a field here means the handler learned to write one.
 */
final class StripeSubscription
{
    /**
     * @param  list<string>  $priceIds
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $status = null,
        public readonly ?string $customerId = null,
        public readonly ?int $currentPeriodEnd = null,
        public readonly array $priceIds = [],
        public readonly array $metadata = [],
    ) {}
}
