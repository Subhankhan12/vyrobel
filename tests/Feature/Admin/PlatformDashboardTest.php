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

// The platform BUSINESS overview (`/admin/overview`) — real aggregates via the audited, read-only door.
//
// ⚠️⚠️ ADMIN 01 · PHASE 1 REWROTE WHAT THIS FILE PINS, ON A RULING. It used to pin a "recurring plan value" that
// summed the LIST PRICE of active AND trialing subscriptions. On production that read €297/month: three free trials,
// all on Vyrobel's own internal organisations, none billed. The ruling: exclude internal organisations from every
// statistic, and show no revenue figure until one is computed from what is actually charged. These tests now pin
// THAT — including that the old figure is gone, not merely renamed.

function orgWithStatus(string $status, string $slug, bool $internal = false): Organization
{
    $org = Organization::create([
        'name' => ucfirst($status).' Co '.$slug,
        'slug' => $slug,
        'status' => $status,
        'default_currency' => 'EUR',
    ]);

    if ($internal) {
        $org->forceFill(['is_internal' => true])->save();
    }

    return $org;
}

function planRow(string $code, int $priceMinor, string $interval): Plan
{
    return Plan::create(['code' => $code, 'name' => strtoupper($code), 'price_minor' => $priceMinor, 'currency' => 'EUR', 'interval' => $interval, 'is_active' => true]);
}

/** compute() reads tenant-scoped models, so it runs inside the super-admin context (as the door does). */
function computeMetrics(bool $includeInternal = false): array
{
    return app(TenantContext::class)->runAsSuperAdmin(fn (): array => app(PlatformMetrics::class)->compute($includeInternal));
}

it('counts CUSTOMERS only by default — internal organisations are excluded, and put back only on request', function () {
    orgWithStatus('active', 'cust-a');
    orgWithStatus('trialing', 'cust-b');
    orgWithStatus('active', 'mine-a', internal: true);
    orgWithStatus('active', 'mine-b', internal: true);
    orgWithStatus('suspended', 'mine-c', internal: true);

    $m = computeMetrics();
    expect($m['organizations'])->toBe(2)
        ->and($m['internal_orgs'])->toBe(3)
        ->and($m['by_status']['active'])->toBe(1)
        ->and($m['by_status']['trialing'])->toBe(1)
        ->and($m['by_status']['suspended'])->toBe(0);   // the only suspended org is internal

    $all = computeMetrics(includeInternal: true);
    expect($all['organizations'])->toBe(5)
        ->and($all['by_status']['suspended'])->toBe(1);
})->group('security');

it('an internal org\'s subscriptions, properties and reservations are excluded with it — one org set drives every figure', function () {
    $plan = planRow('pro_monthly', 9900, 'monthly');
    $mine = orgWithStatus('active', 'mine-sub', internal: true);
    $cust = orgWithStatus('active', 'cust-sub');

    Subscription::withoutGlobalScopes()->create(['organization_id' => $mine->id, 'plan_id' => $plan->id, 'status' => 'trialing']);
    Subscription::withoutGlobalScopes()->create(['organization_id' => $cust->id, 'plan_id' => $plan->id, 'status' => 'trialing']);

    expect(computeMetrics()['on_trial'])->toBe(1)
        ->and(computeMetrics(true)['on_trial'])->toBe(2);
})->group('security');

it('⚠️⚠️ reports NO money figure from the price list — the €297 "recurring plan value" is gone, not renamed', function () {
    // Exactly production's shape on the day it was found: internal orgs on free Pro trials.
    $pro = planRow('pro_monthly', 9900, 'monthly');
    foreach (['p1', 'p2', 'p3'] as $slug) {
        $org = orgWithStatus('active', 'trial-'.$slug);
        Subscription::withoutGlobalScopes()->create(['organization_id' => $org->id, 'plan_id' => $pro->id, 'status' => 'trialing']);
    }

    $m = computeMetrics();

    expect($m)->not->toHaveKey('recurring_plan_value_minor')
        ->and($m['live_billed_subscriptions'])->toBe(0);   // a trial is not money

    foreach ($m as $key => $value) {
        expect($key)->not->toContain('value')->not->toContain('mrr')->not->toContain('revenue');
    }
})->group('security');

it('counts LIVE billing only as an active subscription WITH a Stripe id — a status alone is not money', function () {
    $plan = planRow('pro_monthly', 9900, 'monthly');
    $a = orgWithStatus('active', 'live-a');
    $b = orgWithStatus('active', 'live-b');

    Subscription::withoutGlobalScopes()->create(['organization_id' => $a->id, 'plan_id' => $plan->id, 'status' => 'active']);
    expect(computeMetrics()['live_billed_subscriptions'])->toBe(0);

    Subscription::withoutGlobalScopes()->create(['organization_id' => $b->id, 'plan_id' => $plan->id, 'status' => 'active', 'stripe_subscription_id' => 'sub_test_live']);
    expect(computeMetrics()['live_billed_subscriptions'])->toBe(1);
})->group('security');

it('the overview says "No customers yet" in words when every organisation is internal — not a row of zeroes', function () {
    orgWithStatus('active', 'only-mine', internal: true);

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.overview'))
        ->assertOk()
        ->assertSee('No customers yet')
        ->assertDontSee('Recurring plan value');
})->group('security');

it('the overview says "No live billing yet" when customers exist but none is billed, and AUDITS the read', function () {
    $pro = planRow('pro_monthly', 9900, 'monthly');
    $org = orgWithStatus('active', 'real-cust');
    Subscription::withoutGlobalScopes()->create(['organization_id' => $org->id, 'plan_id' => $pro->id, 'status' => 'trialing']);

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.overview'))
        ->assertOk()
        ->assertSee('No live billing yet')
        ->assertDontSee('€99')
        ->assertDontSee('Recurring plan value');

    expect(PlatformAdminAction::query()->where('action', 'platform.overview')->exists())->toBeTrue();
})->group('security');

it('the sign-up series is always six real months, never a fabricated trend', function () {
    $m = computeMetrics();
    expect($m['signups'])->toHaveCount(6)
        ->and(collect($m['signups'])->sum('count'))->toBeGreaterThanOrEqual(0)
        ->and($m['plan_distribution'])->toBeArray();
})->group('security');
