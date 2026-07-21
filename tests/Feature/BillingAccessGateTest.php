<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 7b · SG3 — the ACCESS gate (lapsed org → Paywall, never mid-trial, fail-open on no record) + the
// FEATURE gate by plan (channels is Pro-only). Both act on the REAL derived billing state.

/** Give an org a subscription in a given plan/state (context already set by seedScenario). */
function billOrg(string $orgId, string $planCode, string $status, ?string $trialEnds = null): void
{
    app(BillingService::class)->ensurePlans();
    $plan = Plan::query()->where('code', $planCode)->firstOrFail();

    Subscription::create([
        'organization_id' => $orgId,
        'plan_id' => $plan->id,
        'status' => $status,
        'trial_ends_at' => $trialEnds,
    ]);
}

// ── 1. 🔒 ACCESS GATE — an EXPIRED/lapsed org is redirected to the Paywall (mutation-proven) ────────
it('sends a lapsed org to the Paywall and lets an in-trial / active org straight through', function () {
    // Expired trial → paywall.
    $expired = seedScenario(1);
    billOrg((string) $expired['org']->id, 'pro_monthly', 'trialing', now()->subDay()->toDateTimeString());
    $this->actingAs(operatorUser($expired, [], 'Property Owner'))->get('/app/dashboard')
        ->assertRedirect(route('app.billing'));

    // Canceled → paywall.
    $canceled = seedScenario(1);
    billOrg((string) $canceled['org']->id, 'pro_monthly', 'canceled');
    $this->actingAs(operatorUser($canceled, [], 'Property Owner'))->get('/app/dashboard')
        ->assertRedirect(route('app.billing'));

    // In-trial (future) → FULL access, never locked out mid-trial.
    $trialing = seedScenario(1);
    billOrg((string) $trialing['org']->id, 'pro_monthly', 'trialing', now()->addDays(5)->toDateTimeString());
    $this->actingAs(operatorUser($trialing, [], 'Property Owner'))->get('/app/dashboard')->assertOk();

    // Active → full access.
    $active = seedScenario(1);
    billOrg((string) $active['org']->id, 'pro_monthly', 'active');
    $this->actingAs(operatorUser($active, [], 'Property Owner'))->get('/app/dashboard')->assertOk();
});

// ── 2. 🔒 FAIL-OPEN — an org with NO subscription is never paywalled; the Paywall stays reachable ──
it('fails open on a missing subscription and always lets a lapsed org reach the Paywall', function () {
    // No subscription at all → full access (never lock out a legacy/data-gap org).
    $none = seedScenario(1);
    $this->actingAs(operatorUser($none, [], 'Property Owner'))->get('/app/dashboard')->assertOk();

    // A lapsed org can still REACH /app/billing (else it could never subscribe).
    $expired = seedScenario(1);
    billOrg((string) $expired['org']->id, 'pro_monthly', 'trialing', now()->subDay()->toDateTimeString());
    $this->actingAs(operatorUser($expired, [], 'Property Owner'))->get('/app/billing')->assertOk();
});

// ── 3. 🔒 FEATURE GATE BY PLAN — channels is Pro-only (mutation-proven both ways) ──────────────────
it('gates channels to Pro plans — Essential is refused, Pro (and the Pro-trial) is granted', function () {
    $essential = seedScenario(1);
    billOrg((string) $essential['org']->id, 'essential_monthly', 'active');
    expect($essential['org']->planAllows('channels'))->toBeFalse()      // 🔒 Essential → refused
        ->and($essential['org']->planAllows('analytics'))->toBeTrue();  // Essential still gets analytics

    $pro = seedScenario(1);
    billOrg((string) $pro['org']->id, 'pro_monthly', 'active');
    expect($pro['org']->planAllows('channels'))->toBeTrue();            // 🔒 Pro → granted

    // A Pro-tier TRIAL also grants Pro features (full access to evaluate).
    $trial = seedScenario(1);
    billOrg((string) $trial['org']->id, 'pro_monthly', 'trialing', now()->addDays(10)->toDateTimeString());
    expect($trial['org']->planAllows('channels'))->toBeTrue();
});

// ── 4. ✅ FEATURE GATE SURFACING — an Essential org sees the "Pro" lock on Channels in the nav ──────
it('shows the Channels Pro-lock to an Essential org and not to a Pro org', function () {
    $essential = seedScenario(1);
    billOrg((string) $essential['org']->id, 'essential_monthly', 'active');
    $this->actingAs(operatorUser($essential, [], 'Property Owner'))->get('/app/dashboard')
        ->assertOk()->assertSee('Channels is a Pro feature');   // the real plan gate, surfaced

    $pro = seedScenario(1);
    billOrg((string) $pro['org']->id, 'pro_monthly', 'active');
    $this->actingAs(operatorUser($pro, [], 'Property Owner'))->get('/app/dashboard')
        ->assertOk()->assertDontSee('Channels is a Pro feature');
});
