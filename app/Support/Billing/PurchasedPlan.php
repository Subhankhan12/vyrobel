<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Plan;
use App\Payments\Stripe\StripeEvent;
use App\Payments\Stripe\StripeSubscription;

/**
 * WHAT THE CUSTOMER ACTUALLY BOUGHT — resolved from the Stripe PRICE, never from what the app intended.
 *
 * ⚠️⚠️ WHY THIS EXISTS. Nothing wrote `subscriptions.plan_id` in response to a purchase. An org started on
 * the trial tier, bought Essential, Stripe billed €49 — and the application went on showing Pro and billing
 * from Pro's captured terms. Measured end to end through a signed webhook. That is the ORDINARY FIRST
 * SUBSCRIPTION, not an edge case: every org begins on the trial plan and then buys something.
 *
 * ⚠️ THE PRICE IS THE TRUTH, AND THE METADATA IS ONLY A CROSS-CHECK. `startCheckout()` stamps
 * `metadata['plan']` — that is what the app INTENDED. The Price on the subscription Stripe created is what
 * Stripe CHARGED. They must agree; where they do not, the Price wins and the disagreement is recorded.
 * ⚠️ AND THE METADATA IS NEVER A FALLBACK. Using it when the Price cannot be resolved would be a guess
 * wearing evidence's clothes — the one thing a money path must not do.
 *
 * ⚠️ WHICH EVENTS CARRY IT. Only the SUBSCRIPTION object does (`items.data[].price.id`, or the legacy
 * `plan.id`). A `checkout.session.completed` payload does NOT include line items unless the session was
 * expanded, so the session cannot answer this question and is not asked to.
 *
 * ⚠️ REFUSING IS A RESULT, NOT AN EXCEPTION. This runs inside a webhook handler: a throw is retried by
 * Stripe and can become a poison message, and the customer has already paid. So an unresolvable or
 * ambiguous Price yields a `problem` the caller reports — it never guesses, and it never blocks the
 * activation the customer paid for.
 */
final class PurchasedPlan
{
    private function __construct(
        /** The plan the Price maps to, or null when the event carries no Price / none could be trusted. */
        public readonly ?Plan $plan,
        public readonly ?string $priceId,
        /** Why no plan may be written — an unresolvable or ambiguous Price. Null when there is nothing wrong. */
        public readonly ?string $problem,
        /** The plan code the checkout metadata claimed, when it disagrees with the Price. */
        public readonly ?string $metadataDisagreement,
    ) {}

    /**
     * ⚠️⚠️ THE PREFERRED ENTRY POINT — the subscription as Stripe reports it NOW, not as an event described
     * it at some earlier moment. Reading the object back is what makes webhook ORDER irrelevant.
     */
    public static function fromSubscription(StripeSubscription $subscription): self
    {
        $ids = array_values(array_unique($subscription->priceIds));
        $claimed = $subscription->metadata['plan'] ?? null;

        return self::resolve(
            count($ids) === 1 ? $ids[0] : ($ids === [] ? null : implode(' + ', $ids)),
            is_string($claimed) && $claimed !== '' ? $claimed : null,
        );
    }

    /**
     * From the event payload — used only when the live object could not be retrieved. ⚠️ It is Stripe's own
     * signed statement of state at that moment, so it is evidence rather than a guess; it is simply older.
     */
    public static function fromEvent(StripeEvent $event): self
    {
        return self::resolve(self::priceIdIn($event->object()), self::metadataPlanCode($event));
    }

    /** The ONE resolution both entry points share (§N.8f) — a second copy would drift. */
    private static function resolve(?string $priceId, ?string $claimedCode): self
    {

        if ($priceId === null) {
            // Not every handled event carries a Price (an invoice-less status change, a deletion). Nothing to
            // resolve is not a problem — it is simply silence, and silence must not move `plan_id`.
            return new self(null, null, null, null);
        }

        // ⚠️ `Plan::query()` respects soft-deletion on purpose: a Price whose only plan row was archived names
        //    nothing the catalogue can sell, which is the "no plan row" case below, not a match.
        $matches = Plan::query()->where('stripe_price_id', $priceId)->get();

        // ⚠️⚠️ AMBIGUITY IS A REAL STATE, NOT A HYPOTHETICAL. `plans.stripe_price_id` carries NO unique index
        //    (only `id` and `code` do), so two rows may legitimately hold one Price id. Picking the first
        //    would silently assign a customer to whichever row sorted earlier.
        if ($matches->count() > 1) {
            return new self(null, $priceId, 'price ['.$priceId.'] maps to '.$matches->count()
                .' plan rows ('.$matches->pluck('code')->join(', ').'), so which one was bought is ambiguous', null);
        }

        $plan = $matches->first();

        if ($plan === null) {
            return new self(null, $priceId, 'price ['.$priceId.'] matches no active plan row, so the customer '
                .'is paying for something this catalogue cannot name', null);
        }

        // ⚠️ THE CROSS-CHECK. A disagreement means something changed between creating the Checkout Session and
        //    Stripe charging the card. The Price still wins — it is what the money was taken for — but the
        //    caller reports it rather than absorbing it silently.
        $disagreement = ($claimedCode !== null && $claimedCode !== $plan->code) ? $claimedCode : null;

        return new self($plan, $priceId, null, $disagreement);
    }

    /** The plan code the app stamped at checkout (`subscription_data.metadata.plan`), if present. */
    private static function metadataPlanCode(StripeEvent $event): ?string
    {
        $metadata = $event->object()['metadata'] ?? [];
        $code = is_array($metadata) ? ($metadata['plan'] ?? null) : null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    /**
     * The Price id on a Stripe subscription object — the modern `items.data[].price.id` and the legacy
     * `plan.id`, in that order.
     *
     * ⚠️ MORE THAN ONE DISTINCT PRICE IS ALSO AMBIGUOUS. This product sells one line per subscription, so a
     * multi-line subscription is not something the catalogue can name either — it is reported, not guessed at.
     *
     * @param  array<string, mixed>  $object
     */
    private static function priceIdIn(array $object): ?string
    {
        $ids = [];

        $items = $object['items']['data'] ?? [];
        if (is_array($items)) {
            foreach ($items as $item) {
                $id = is_array($item) ? ($item['price']['id'] ?? null) : null;
                if (is_string($id) && $id !== '') {
                    $ids[] = $id;
                }
            }
        }

        if ($ids === []) {
            $legacy = $object['plan']['id'] ?? null;
            if (is_string($legacy) && $legacy !== '') {
                $ids[] = $legacy;
            }
        }

        $ids = array_values(array_unique($ids));

        // Two different Prices on one subscription: report it as unresolvable rather than take the first.
        return count($ids) === 1 ? $ids[0] : ($ids === [] ? null : implode(' + ', $ids));
    }
}
