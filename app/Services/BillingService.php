<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Billing\SubscriptionStatus;
use Database\Seeders\BillingPlanSeeder;

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
}
