<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 7b · SG1 — the plan/tier + subscription-state model + the REAL trial (derived from the 7a
// provisioning timestamp). No charge is computed anywhere — the live charge is account-gated (deferred).

/** Provision a fresh org through the 7a path (which now starts the trial atomically). */
function provisionTrialOrg(string $email = 'founder@trial.test'): array
{
    return app(TenantProvisioningService::class)->provision(
        ['name' => 'Trial Co', 'currency' => 'EUR'],
        ['name' => 'Fen Founder', 'email' => $email, 'password' => 'password123'],
        ['name' => 'Trial Villas', 'type' => 'villa'],
    );
}

/** Attach a subscription in a given state to an existing (context-set) org. */
function subscribeOrg(string $orgId, string $status, ?string $trialEndsAt): Subscription
{
    app(BillingService::class)->ensurePlans();

    return Subscription::create([
        'organization_id' => $orgId,
        'plan_id' => app(BillingService::class)->trialPlan()->id,
        'status' => $status,
        'trial_ends_at' => $trialEndsAt,
    ]);
}

// ── 1. 🔒 THE REAL TRIAL — provisioning starts a trialing sub; remaining days derive from the timestamp ─
it('starts a real free trial at provisioning, with days derived from the creation timestamp', function () {
    $org = provisionTrialOrg()['organization'];

    $status = $org->billingStatus();
    expect($status->onTrial())->toBeTrue()
        ->and($status->hasFullAccess())->toBeTrue()
        ->and($status->isLapsed())->toBeFalse()
        ->and($status->trialDaysRemaining())->toBe(14);   // config('billing.trial_days')

    $sub = $org->currentSubscription();
    expect($sub)->not->toBeNull()
        ->and($sub->status)->toBe('trialing')
        // trial_ends = created + 14 days — a REAL derivation, not a stored countdown.
        ->and($sub->trial_ends_at->toDateString())->toBe($org->created_at->copy()->addDays(14)->toDateString())
        ->and($sub->stripe_subscription_id)->toBeNull();   // no live charge — account-gated
});

// ── 2. 🔒 A PAST-TRIAL ORG IS EXPIRED (derived) — no full access, lapsed → paywall in SG3 ───────────
it('derives an expired trial as lapsed once the timestamp passes', function () {
    $s = seedScenario(1);
    subscribeOrg((string) $s['org']->id, 'trialing', now()->subDay()->toDateTimeString());

    $status = $s['org']->billingStatus();
    expect($status->onTrial())->toBeFalse()
        ->and($status->trialExpired())->toBeTrue()
        ->and($status->hasFullAccess())->toBeFalse()
        ->and($status->isLapsed())->toBeTrue()
        ->and($status->trialDaysRemaining())->toBe(0);
});

// ── 3. ACCESS-STATE LOGIC — active = full; canceled = lapsed; no subscription fails OPEN ────────────
it('computes access state: active is full, canceled is lapsed, no-subscription fails open', function () {
    $active = seedScenario(1);
    subscribeOrg((string) $active['org']->id, 'active', null);
    expect($active['org']->billingStatus()->hasFullAccess())->toBeTrue()
        ->and($active['org']->billingStatus()->isLapsed())->toBeFalse();

    $canceled = seedScenario(1);
    subscribeOrg((string) $canceled['org']->id, 'canceled', null);
    expect($canceled['org']->billingStatus()->hasFullAccess())->toBeFalse()
        ->and($canceled['org']->billingStatus()->isLapsed())->toBeTrue();

    // A legacy org with NO subscription row is never paywalled (fail-open safety).
    $none = seedScenario(1);
    expect($none['org']->billingStatus()->hasFullAccess())->toBeTrue()
        ->and($none['org']->billingStatus()->isLapsed())->toBeFalse();
});

// ── 4. THE PLAN CATALOG — placeholder tiers materialise; `channels` is a REAL Pro-only tier gate ─────
it('materialises the placeholder plan catalog with a real Essential/Pro feature difference', function () {
    app(BillingService::class)->ensurePlans();

    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    // Placeholder prices from config/billing.php (integer minor units), real Plan rows.
    expect($essential->price_minor)->toBe(4900)
        ->and($pro->price_minor)->toBe(9900)
        ->and(Plan::query()->whereIn('code', ['essential_yearly', 'pro_yearly'])->count())->toBe(2);

    // The real tier difference used by the SG3 feature-gate: `channels` is Pro-only.
    expect((bool) $essential->features()->where('key', 'channels')->firstOrFail()->enabled)->toBeFalse()
        ->and((bool) $pro->features()->where('key', 'channels')->firstOrFail()->enabled)->toBeTrue();
});
