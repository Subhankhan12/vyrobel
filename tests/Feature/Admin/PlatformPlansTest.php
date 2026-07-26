<?php

declare(strict_types=1);

use App\Http\Middleware\EnsurePlanFeature;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdminAction;
use App\Models\Subscription;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

// SUPER ADMIN · BUILD 1 · GATE 3 — Plans: DB-backed CRUD + feature-gating (additive, mutation-proven).

/** A plan with the three real gateable features. */
function planWithFeatures(string $code = 'pro_monthly', array $enabled = ['analytics' => true, 'channels' => true, 'team' => true], ?int $teamLimit = null): Plan
{
    $plan = Plan::create(['code' => $code, 'name' => 'Pro', 'price_minor' => 9900, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true]);
    $plan->features()->create(['key' => 'analytics', 'enabled' => $enabled['analytics'], 'limit' => null]);
    $plan->features()->create(['key' => 'channels', 'enabled' => $enabled['channels'], 'limit' => null]);
    $plan->features()->create(['key' => 'team', 'enabled' => $enabled['team'], 'limit' => $teamLimit]);

    return $plan;
}

/** An org subscribed to a plan (trialing = full access, so planAllows evaluates the plan's features). */
function orgOnPlan(Plan $plan, string $slug = 'sub-co'): Organization
{
    $org = Organization::create(['name' => 'Sub Co', 'slug' => $slug, 'status' => 'active', 'default_currency' => 'EUR']);
    Subscription::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'plan_id' => $plan->id,
        'status' => 'trialing',
        'trial_ends_at' => now()->addDays(10),
    ]);

    return $org;
}

// ── CRUD ──────────────────────────────────────────────────────────────────────────────────────────
it('lists the DB-backed plan catalog', function () {
    planWithFeatures();

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.plans.index'))
        ->assertOk()
        ->assertSee('Pro')
        ->assertSee('pro_monthly');
})->group('security');

it('updates a plan — integer money (#17) + feature toggles — and AUDITS it', function () {
    $plan = planWithFeatures();

    $this->actingAs(platformAdmin(), 'admin')
        ->put(route('admin.plans.update', $plan->id), [
            'name' => 'Pro Plus',
            'price' => '129.50',
            'is_active' => '1',
            'features' => [
                'analytics' => ['enabled' => '1'],
                // channels unchecked (absent) → must become disabled
                'team' => ['enabled' => '1', 'limit' => '25'],
            ],
        ])
        ->assertRedirect(route('admin.plans.index'));

    $plan->refresh();
    expect($plan->name)->toBe('Pro Plus');
    expect($plan->price_minor)->toBe(12950);                         // €129.50 → integer minor (#17)
    expect((bool) $plan->features()->where('key', 'channels')->value('enabled'))->toBeFalse();   // disabled
    expect((bool) $plan->features()->where('key', 'analytics')->value('enabled'))->toBeTrue();
    expect((int) $plan->features()->where('key', 'team')->value('limit'))->toBe(25);
    expect(PlatformAdminAction::query()->where('action', 'platform.plan.updated')->exists())->toBeTrue();
})->group('security');

// ── FEATURE-GATING (the key mechanism, ADDITIVE) ────────────────────────────────────────────────────
it('editing a plan feature flips what planAllows returns for orgs on that plan (mutation proof)', function () {
    $plan = planWithFeatures(enabled: ['analytics' => true, 'channels' => false, 'team' => true]);
    $org = orgOnPlan($plan);

    // The org's plan EXCLUDES channels → gated.
    expect($org->fresh()->planAllows('channels'))->toBeFalse();

    // Admin enables channels on the plan → the SAME org is now granted it (no other change).
    $this->actingAs(platformAdmin(), 'admin')->put(route('admin.plans.update', $plan->id), [
        'name' => 'Pro', 'price' => '99.00', 'is_active' => '1',
        'features' => ['analytics' => ['enabled' => '1'], 'channels' => ['enabled' => '1'], 'team' => ['enabled' => '1']],
    ]);

    expect($org->fresh()->planAllows('channels'))->toBeTrue();
})->group('security');

it('EnsurePlanFeature gates additively — granted plan passes, excluding plan gets a clean 403, no context fails open', function () {
    $mw = app(EnsurePlanFeature::class);
    $next = fn () => response('OK');

    // No tenant context → fail open (never invent a restriction).
    app(TenantContext::class)->forget();
    expect($mw->handle(Request::create('/x'), $next, 'channels')->getStatusCode())->toBe(200);

    // Org on a plan that EXCLUDES channels → gated (clean 403 page).
    $plan = planWithFeatures(enabled: ['analytics' => true, 'channels' => false, 'team' => true]);
    $org = orgOnPlan($plan);
    app(TenantContext::class)->set($org->id);
    $gated = $mw->handle(Request::create('/x'), $next, 'channels');
    expect($gated->getStatusCode())->toBe(403);

    // A feature the plan GRANTS passes (existing behavior preserved).
    expect($mw->handle(Request::create('/x'), $next, 'analytics')->getStatusCode())->toBe(200);

    app(TenantContext::class)->forget();
})->group('security');

// ── ASSIGN A PLAN (drives features; Stripe deferred) ───────────────────────────────────────────────
it('assigning a plan to a customer changes its features + is AUDITED; Stripe stays untouched (deferred)', function () {
    $essential = planWithFeatures('essential_monthly', ['analytics' => true, 'channels' => false, 'team' => true]);
    $pro = planWithFeatures('pro_monthly', ['analytics' => true, 'channels' => true, 'team' => true]);
    $org = orgOnPlan($essential, 'assign-co');

    // On Essential → channels gated.
    expect($org->fresh()->planAllows('channels'))->toBeFalse();

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.assign-plan', $org->id), ['plan_id' => $pro->id])
        ->assertRedirect(route('admin.customers.show', $org->id));

    // Now on Pro → channels granted; the subscription's plan really changed.
    expect($org->fresh()->planAllows('channels'))->toBeTrue();
    $sub = Subscription::withoutGlobalScopes()->where('organization_id', $org->id)->latest()->first();
    expect($sub->plan_id)->toBe($pro->id);
    expect($sub->stripe_subscription_id)->toBeNull();               // 🔒 Stripe DEFERRED — no billing sync
    expect(PlatformAdminAction::query()->where('action', 'platform.customer.assign_plan')->exists())->toBeTrue();
})->group('security');

// ── isolation ───────────────────────────────────────────────────────────────────────────────────────
it('a TENANT USER cannot reach any Plans route', function () {
    $s = seedScenario(1);
    $plan = planWithFeatures();

    $this->actingAs(operatorUser($s))->get(route('admin.plans.index'))->assertRedirect(route('admin.login'));
    $this->actingAs(operatorUser($s))->put(route('admin.plans.update', $plan->id), ['name' => 'Hacked', 'price' => '1'])->assertRedirect(route('admin.login'));
    expect($plan->fresh()->name)->toBe('Pro');   // unchanged
})->group('security');
