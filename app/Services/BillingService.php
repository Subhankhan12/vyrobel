<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Payments\Stripe\StripeCheckoutSession;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
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

        $priceId = $plan->stripe_price_id;
        if (! is_string($priceId) || $priceId === '') {
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
        );
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

        return Plan::query()->where('code', $tier.'_monthly')->firstOrFail();
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
            // ensurePlans() above guarantees both interval rows exist for every config tier.
            $monthly = Plan::query()->where('code', $code.'_monthly')->firstOrFail();
            $yearly = Plan::query()->where('code', $code.'_yearly')->firstOrFail();

            $features = [];
            foreach ($tier['features'] as $key => $feature) {
                $features[] = [
                    'key' => $key,
                    'label' => (string) $feature['label'],
                    'enabled' => (bool) ($feature['enabled'] ?? true),
                    'limit' => $feature['limit'] ?? null,
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
