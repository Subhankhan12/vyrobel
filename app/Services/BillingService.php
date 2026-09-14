<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Payments\Stripe\StripeCheckoutSession;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
use App\Support\Billing\StripePriceWiring;
use App\Support\Billing\SubscriptionStatus;
use Database\Seeders\BillingPlanSeeder;
use RuntimeException;

/**
 * Vyrobel subscription billing (Phase 7b) — the write/orchestration side (the read/derive side is the pure
 * {@see SubscriptionStatus}). Owns: starting a new org's free trial (from the 7a provisioning timestamp),
 * ensuring the placeholder plan catalog exists, and resolving an org's billing state.
 *
 * ⚠️ THE LIVE CHARGE IS ACCOUNT-GATED. There is deliberately NO "charge" / "activate" method here that takes
 * money — a real subscription (`status = 'active'`, `stripe_subscription_id` set) can only ever be reached
 * through live Stripe Billing (Subhan's to obtain), the same honest deferral as the guest card charge + the
 * owner payout rail. Nothing flips an org to active off a payment that didn't happen.
 */
final class BillingService
{
    /**
     * Start a new org's free trial: a `trialing` Subscription on the trial tier's plan, ending `trial_days`
     * after the org's creation timestamp (so the remaining days derive from real time, never a faked
     * countdown). Called ATOMICALLY inside {@see TenantProvisioningService} — if it throws, the whole org
     * rolls back. No-ops if the org already has a subscription.
     */
    public function startTrialFor(Organization $organization): Subscription
    {
        $existing = $organization->currentSubscription();
        if ($existing !== null) {
            return $existing;
        }

        $this->ensurePlans();
        $plan = $this->trialPlan();
        $trialDays = (int) config('billing.trial_days', 14);
        $from = $organization->created_at ?? now();

        // ⚠️ Terms are captured by the model's `creating` hook — see Subscription::booted(). Doing it here
        // as well would be a second mechanism for one concept, and the one nobody updated would drift.
        return Subscription::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'trial_ends_at' => $from->copy()->addDays($trialDays),
        ]);
    }

    public function statusFor(Organization $organization): SubscriptionStatus
    {
        return $organization->billingStatus();
    }

    /**
     * Start a live Stripe Checkout for a tier×interval (Phase 8 · SG1). Returns the hosted Checkout Session —
     * the card is entered on Stripe's page, never our server. 🔒 This does NOT activate the org; fulfilment is
     * on the `checkout.session.completed` WEBHOOK ({@see BillingWebhookService}). The org id is stamped into
     * the session + subscription metadata so the webhook resolves the tenant; a deterministic Idempotency-Key
     * keeps a double-submit to ONE session.
     */
    public function startCheckout(Organization $organization, string $tier, string $interval): StripeCheckoutSession
    {
        $plan = $this->planFor($tier, $interval)
            ?? throw new RuntimeException("No plan for tier [{$tier}] interval [{$interval}].");

        // ⚠️ BILLING PASS 2 — the last hop before real money. In production this refuses a missing or
        // placeholder Price id, and refuses a per-unit plan whose VAT convention nobody has declared (the
        // checkout sends no tax, so charging one would under-bill by the VAT). In dev/CI both are no-ops: the
        // fake client stands in and nothing moves.
        StripePriceWiring::assertTaxConventionResolved($plan);
        $priceId = StripePriceWiring::priceIdFor($plan);

        if ($priceId === '') {
            throw new RuntimeException("Plan [{$plan->code}] is not wired to a Stripe price.");
        }

        $subscription = $organization->currentSubscription();

        return app(StripeClientInterface::class)->createSubscriptionCheckout(
            priceId: $priceId,
            successUrl: route('app.billing').'?checkout=success',
            cancelUrl: route('app.billing').'?checkout=cancel',
            customerId: $subscription?->stripe_customer_id,
            customerEmail: is_string($organization->billing_email) ? $organization->billing_email : null,
            metadata: ['organization_id' => (string) $organization->getKey(), 'plan' => $plan->code],
            idempotencyKey: StripeIdempotency::keyFor('subscription_checkout', $organization->getKey().'_'.$plan->getKey()),
            // ⚠️ SHAPE-AWARE (BILLING PASS 1). Flat → 1; per-unit → the org's REAL active-unit count, derived
            // at this moment from the ONE source, never a stored copy. Proration is on only where it means
            // something: a per-unit subscription bills a mid-cycle unit change immediately.
            quantity: $plan->quantityFor(),
            prorate: $plan->isPerUnit(),
            // ⚠️ BILLING PASS 2b — Stripe Tax, on exactly where the tax is EXCLUSIVE. Under the founder's ruling
            // that is the per-unit shape: its Price is pre-tax, so Stripe must add the customer's own tax on top
            // (€0 for an EU business under reverse charge). A flat tier's price is already the final amount.
            automaticTax: StripePriceWiring::usesStripeTax($plan),
        );
    }

    /**
     * Re-quantify a live PER-UNIT subscription after the org's unit count changed (BILLING PASS 1).
     *
     * ⚠️ The ruled behaviour: the difference is charged IMMEDIATELY (Stripe proration), not deferred to the next
     * renewal. A FLAT plan is a no-op — its price does not move with the rack, so there is nothing to re-quantify
     * and sending a quantity would multiply the bill.
     *
     * Returns the quantity now on the subscription, or null when the plan is flat / there is nothing to update.
     */
    public function syncSubscriptionQuantity(Organization $organization): ?int
    {
        $subscription = $organization->currentSubscription();

        if ($subscription === null) {
            return null;   // nothing subscribed — a genuine no-op, and knowably so.
        }

        // ⚠️⚠️ THE SHAPE COMES FROM THE SNAPSHOT, NOT THE LIVE CATALOGUE ROW — and `PlanTerms`' own docblock
        //    already said so: **"Terms frozen, quantity live."** The code froze the RATE and read the SHAPE
        //    live, which is the opposite of what the sentence promises. Whether a plan is FLAT or PER-UNIT
        //    decides what the customer pays, so by the line drawn in `docs/BILLING-PLAN-ASSUMPTIONS.md` it is
        //    a TERM — and a term read live is a term that can change under a customer, which is the entire
        //    reason the snapshot exists. The comment was right; the code is now what it describes.
        //
        // ⚠️ AND THIS DELETES A CASE RATHER THAN GUARDING ONE. Deciding whether to sync no longer needs the
        //    plan ROW at all, so the "plan does not resolve" branch that used to throw here is simply gone —
        //    `terms()` still refuses when a subscription has neither a capture nor a plan, which is the only
        //    remaining state where the answer is genuinely unknown.
        $terms = $subscription->terms();

        if (! $terms->isPerUnit()) {
            return null;   // a flat plan does not move with the rack — nothing to re-quantify.
        }

        $stripeId = $subscription->stripe_subscription_id;
        if (! is_string($stripeId) || $stripeId === '') {
            return null;   // not yet a live Stripe subscription (trialing, or pre-checkout) — nothing to sync.
        }

        // ⚠️ THE QUANTITY STAYS LIVE. `PlanTerms::quantity()` is `BillableUnits::count()` for a per-unit
        //    shape — byte-identical to `Plan::quantityFor()` — because an org that adds a unit genuinely owes
        //    for it. **Frozen shape, live count**: the two halves must not be confused.
        $quantity = $terms->quantity();

        app(StripeClientInterface::class)->updateSubscriptionQuantity(
            subscriptionId: $stripeId,
            quantity: $quantity,
            prorate: true,
            idempotencyKey: StripeIdempotency::keyFor('subscription_quantity', $stripeId.'_'.$quantity),
        );

        return $quantity;
    }

    /** Idempotently ensure the placeholder plan catalog exists (used by startTrial + the Paywall). */
    public function ensurePlans(): void
    {
        if (Plan::query()->count() === 0) {
            app(BillingPlanSeeder::class)->run();
        }
    }

    /** The plan the trial grants (the trial tier's monthly plan) — full-feature access to evaluate. */
    public function trialPlan(): Plan
    {
        $tier = (string) config('billing.trial_tier', 'pro');

        // ⚠️ The same assumption as `catalog()`, and the same correction: `firstOrFail()` surfaced a missing
        //    trial tier as a 404, which describes a page that does not exist rather than a catalogue that is
        //    not configured. ⚠️ It still FAILS — a trial cannot start without a plan to start it on, and
        //    provisioning is transactional so nothing half-created survives — but it says what is wrong.
        $plan = Plan::query()->where('code', $tier.'_monthly')->first();

        if ($plan === null) {
            throw new RuntimeException(
                "Cannot start a trial: `billing.trial_tier` is [{$tier}] but no active plan row `{$tier}_monthly` "
                .'exists. ⚠️ Fix the catalogue (or the config), never the guard.'
            );
        }

        return $plan;
    }

    /** A specific tier's plan for an interval (e.g. 'essential', 'yearly'). */
    public function planFor(string $tier, string $interval): ?Plan
    {
        return Plan::query()->where('code', $tier.'_'.$interval)->first();
    }

    /**
     * The tier catalog for the Paywall/Checkout — REAL plan rows + the config feature splits. Prices are the
     * PLACEHOLDER minor-unit values from config/billing.php (Subhan sets the real ones); no charge is implied.
     *
     * @return list<array{code: string, name: string, blurb: string, currency: string, monthly_minor: int, yearly_minor: int, features: list<array{key: string, label: string, enabled: bool, limit: ?int}>}>
     */
    public function catalog(): array
    {
        $this->ensurePlans();
        $out = [];

        /** @var array<string, array{name: string, blurb: string, features: array<string, array{label: string, enabled?: bool, limit?: ?int}>}> $tiers */
        $tiers = config('billing.tiers', []);

        foreach ($tiers as $code => $tier) {
            // ⚠️⚠️ THE COMMENT THAT USED TO SIT HERE WAS FALSE, AND IT WAS FALSE ON A MONEY PATH. It read
            //    "ensurePlans() above guarantees both interval rows exist for every config tier" — but
            //    `ensurePlans()` reseeds only when `Plan::query()->count() === 0`. With one plan missing and
            //    the others present the count is non-zero, nothing is reseeded, and `firstOrFail()` threw
            //    `ModelNotFoundException` → a **404 on /app/billing for a paying customer**, measured.
            //
            // ⚠️ THE GUARANTEE IS NOT MADE TRUE INSTEAD, DELIBERATELY. Reseeding here would recreate rows an
            //    administrator had removed, on the page that sells them — a write with real consequences,
            //    triggered by a read. Tolerating the absence is the smaller claim: a tier whose plan rows do
            //    not resolve genuinely cannot be bought, so it is not offered.
            $monthly = Plan::query()->where('code', $code.'_monthly')->first();
            $yearly = Plan::query()->where('code', $code.'_yearly')->first();

            if ($monthly === null || $yearly === null) {
                continue;
            }

            // Build 1 · Gate 3 — the DB `plan_features` are AUTHORITATIVE for enabled/limit (the platform admin
            // edits them); config supplies only the human LABEL (display copy). Seeded from config, so this is
            // byte-identical today — but an admin feature edit now flows to the Paywall, not just the gate.
            $dbFeatures = PlanFeature::query()->where('plan_id', $monthly->id)->get()->keyBy('key');
            $features = [];
            foreach ($tier['features'] as $key => $feature) {
                $dbFeature = $dbFeatures->get($key);
                $features[] = [
                    'key' => $key,
                    'label' => (string) $feature['label'],
                    'enabled' => $dbFeature !== null ? (bool) $dbFeature->enabled : (bool) ($feature['enabled'] ?? true),
                    'limit' => $dbFeature !== null ? $dbFeature->limit : ($feature['limit'] ?? null),
                ];
            }

            $out[] = [
                'code' => $code,
                'name' => (string) $tier['name'],
                'blurb' => (string) $tier['blurb'],
                'currency' => $monthly->currency,
                'monthly_minor' => $monthly->price_minor,
                'yearly_minor' => $yearly->price_minor,
                'features' => $features,
            ];
        }

        return $out;
    }
}
