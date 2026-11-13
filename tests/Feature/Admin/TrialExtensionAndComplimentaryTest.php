<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Support\Platform\PlatformCustomers;
use App\Support\Platform\PlatformMetrics;
use App\Support\Platform\Revenue\PlatformRevenue;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(DatabaseTransactions::class);

/**
 * DEC-7 (his ruling, 2026-11-13: "C") — the console can EXTEND A TRIAL and grant a COMPLIMENTARY plan.
 *
 * Before: the only extension ever made was a one-row write a session did at his word. Now it is a console action with
 * a written reason, his password, a cap, and an audit row. A complimentary workspace has an END DATE (it lapses to the
 * paywall on that day like a trial), shows as €0 and is never counted as a trial waiting to convert or as revenue.
 */
function tcOrg(string $status = 'trialing', ?Carbon $trialEnds = null, ?string $stripeId = null): Organization
{
    $org = Organization::create(['name' => 'TC '.Str::random(6), 'slug' => 'tc-'.Str::lower(Str::random(10)), 'status' => 'active', 'default_currency' => 'EUR']);
    $plan = Plan::create(['code' => 'tc_'.Str::lower(Str::random(8)), 'name' => 'TC Plan', 'price_minor' => 4900, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true]);
    app(TenantContext::class)->forOrganization((string) $org->id, fn () => Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $plan->id, 'status' => $status,
        'trial_ends_at' => $trialEnds ?? now()->addDays(5), 'stripe_subscription_id' => $stripeId,
    ]));

    return $org;
}

function tcSub(Organization $org): Subscription
{
    return app(TenantContext::class)->forOrganization((string) $org->id, fn () => Subscription::query()->where('organization_id', $org->id)->sole());
}

/** The validation-error KEYS of the LAST call alone (this app's session stores the bag as a plain array — see sessionErrorKeys()). */
function tcErrorsOf(TestResponse $response): array
{
    $keys = sessionErrorKeys();
    test()->flushSession();

    return $keys;
}

function tcExtend(string $orgId, array $body = []): TestResponse
{
    test()->flushSession();

    return test()->actingAs(platformAdmin(), 'admin')->from(route('admin.customers.show', $orgId))
        ->post(route('admin.customers.extend-trial', $orgId), $body + ['days' => 7, 'reason' => 'A pilot hotel asked for a week more', 'current_password' => 'password']);
}

function tcGrant(string $orgId, array $body = []): TestResponse
{
    test()->flushSession();

    return test()->actingAs(platformAdmin(), 'admin')->from(route('admin.customers.show', $orgId))
        ->post(route('admin.customers.complimentary', $orgId), $body + ['until' => now()->addDays(60)->toDateString(), 'reason' => 'Pilot partner, agreed by the founder', 'current_password' => 'password']);
}

// ── Extend trial ─────────────────────────────────────────────────────────────────────────────────────────────
it('extends a running trial by the days asked, from where it ends, and writes an audit row', function () {
    $org = tcOrg(trialEnds: now()->addDays(5));
    $before = tcSub($org)->trial_ends_at;

    tcExtend($org->id, ['days' => 7])->assertRedirect(route('admin.customers.show', $org->id));

    expect(tcSub($org)->trial_ends_at->equalTo($before->copy()->addDays(7)))->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.extend_trial')->where('target_id', $org->id)->count())->toBe(1);
});

it('brings an EXPIRED trial back from today, not from the day it ended', function () {
    $org = tcOrg(trialEnds: now()->subDays(30));

    tcExtend($org->id, ['days' => 10]);

    $sub = tcSub($org);
    expect($sub->trial_ends_at->isFuture())->toBeTrue()
        ->and($sub->trial_ends_at->between(now()->addDays(9), now()->addDays(11)))->toBeTrue()
        ->and($org->fresh()->billingStatus()->hasFullAccess())->toBeTrue();
});

it('refuses without the admin’s password, and refuses without a written reason — nothing changes', function () {
    $org = tcOrg();
    $before = tcSub($org)->trial_ends_at;

    expect(tcErrorsOf(tcExtend($org->id, ['current_password' => 'not-the-password'])))->toContain('current_password');
    expect(tcErrorsOf(tcExtend($org->id, ['reason' => ''])))->toContain('reason');

    expect(tcSub($org)->trial_ends_at->equalTo($before))->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.extend_trial')->where('target_id', $org->id)->count())->toBe(0);
});

it('caps one extension at 60 days and the whole trial at 90 days from today', function () {
    $org = tcOrg(trialEnds: now()->addDays(5));
    $before = tcSub($org)->trial_ends_at;

    expect(tcErrorsOf(tcExtend($org->id, ['days' => 61])))->toContain('days');
    expect(tcErrorsOf(tcExtend($org->id, ['days' => 0])))->toContain('days');

    $long = tcOrg(trialEnds: now()->addDays(50));   // 50 + 60 = 110 days from today: over the 90-day ceiling
    $longBefore = tcSub($long)->trial_ends_at;
    expect(tcErrorsOf(tcExtend($long->id, ['days' => 60])))->toContain('days');

    expect(tcSub($org)->trial_ends_at->equalTo($before))->toBeTrue()
        ->and(tcSub($long)->trial_ends_at->equalTo($longBefore))->toBeTrue();
});

it('refuses an organisation that is billed, cancelled or already complimentary', function () {
    $paying = tcOrg('active', null, 'sub_test_123');
    expect(tcErrorsOf(tcExtend($paying->id)))->toContain('days');
    expect(tcSub($paying)->status)->toBe('active');

    $comp = tcOrg();
    tcGrant($comp->id);
    $endsAt = tcSub($comp)->trial_ends_at;
    expect(tcErrorsOf(tcExtend($comp->id)))->toContain('days');
    expect(tcSub($comp)->trial_ends_at->equalTo($endsAt))->toBeTrue();
});

it('is only for a console role that may manage billing — Support gets a 403', function () {
    $org = tcOrg();
    $support = PlatformAdmin::factory()->withTwoFactor()->role('support')->create();

    $this->actingAs($support, 'admin')->post(route('admin.customers.extend-trial', $org->id), ['days' => 7, 'reason' => 'trying it', 'current_password' => 'password'])->assertForbidden();
    $this->actingAs($support, 'admin')->post(route('admin.customers.complimentary', $org->id), ['until' => now()->addDays(30)->toDateString(), 'reason' => 'trying it', 'current_password' => 'password'])->assertForbidden();
    $this->actingAs($support, 'admin')->post(route('admin.customers.complimentary.end', $org->id), ['reason' => 'trying it', 'current_password' => 'password'])->assertForbidden();
});

it('refuses a protected (baseline-locked) organisation and says so in the ledger', function () {
    $org = tcOrg();
    $org->forceFill(['lifecycle_lock' => 'baseline'])->save();
    $before = tcSub($org)->trial_ends_at;

    expect(tcErrorsOf(tcExtend($org->id)))->toContain('lock');
    expect(tcErrorsOf(tcGrant($org->id)))->toContain('lock');

    expect(tcSub($org)->trial_ends_at->equalTo($before))->toBeTrue()
        ->and(tcSub($org)->complimentary_until)->toBeNull()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.refused')->where('target_id', $org->id)->count())->toBe(2);
});

// ── Complimentary ────────────────────────────────────────────────────────────────────────────────────────────
it('grants a complimentary plan with an end date: full access now, the paywall on that day', function () {
    $org = tcOrg(trialEnds: now()->addDays(2));
    $until = now()->addDays(40)->startOfDay();

    tcGrant($org->id, ['until' => $until->toDateString()])->assertRedirect(route('admin.customers.show', $org->id));

    $sub = tcSub($org);
    expect($sub->complimentary_until)->not->toBeNull()
        ->and($sub->complimentary_until->toDateString())->toBe($until->toDateString())
        ->and($org->fresh()->billingStatus()->hasFullAccess())->toBeTrue()
        ->and($org->fresh()->billingStatus()->isComplimentary())->toBeTrue()
        ->and($org->fresh()->billingStatus()->label())->toContain('Complimentary')
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.grant_complimentary')->where('target_id', $org->id)->count())->toBe(1);

    $this->travelTo($until->copy()->addDay());
    expect($org->fresh()->billingStatus()->isLapsed())->toBeTrue();   // the day passed: back to the paywall, like a trial
});

it('needs an end date within a year — never open-ended, never in the past', function () {
    $org = tcOrg();

    expect(tcErrorsOf(tcGrant($org->id, ['until' => ''])))->toContain('until');
    expect(tcErrorsOf(tcGrant($org->id, ['until' => now()->subDay()->toDateString()])))->toContain('until');
    expect(tcErrorsOf(tcGrant($org->id, ['until' => now()->addDays(400)->toDateString()])))->toContain('until');

    expect(tcSub($org)->complimentary_until)->toBeNull();
});

it('refuses to make a BILLED organisation complimentary', function () {
    $paying = tcOrg('active', null, 'sub_test_456');

    expect(tcErrorsOf(tcGrant($paying->id)))->toContain('until');

    expect(tcSub($paying)->complimentary_until)->toBeNull()->and(tcSub($paying)->status)->toBe('active');
});

it('the directory’s “On a free trial” filter lists a trial and not a complimentary workspace', function () {
    $trial = tcOrg();
    $comp = tcOrg();
    tcGrant($comp->id);

    $rows = app(TenantContext::class)->runAsSuperAdmin(fn () => app(PlatformCustomers::class)->list(['status' => 'trialing', 'search' => 'TC '])->items());
    $found = collect($rows)->pluck('id')->map(fn ($v) => (string) $v)->all();
    expect($found)->toContain((string) $trial->id)->not->toContain((string) $comp->id);
});

it('the platform trial and revenue figures leave a complimentary workspace out', function () {
    $before = app(TenantContext::class)->runAsSuperAdmin(fn () => [
        'on_trial' => app(PlatformMetrics::class)->compute(true)['on_trial'],
        'unbilled' => app(PlatformRevenue::class)->compute(true)['trialing_unbilled'],
    ]);

    $comp = tcOrg();
    $afterTrial = app(TenantContext::class)->runAsSuperAdmin(fn () => [
        'on_trial' => app(PlatformMetrics::class)->compute(true)['on_trial'],
        'unbilled' => app(PlatformRevenue::class)->compute(true)['trialing_unbilled'],
    ]);
    expect($afterTrial['on_trial'])->toBe($before['on_trial'] + 1)->and($afterTrial['unbilled'])->toBe($before['unbilled'] + 1);

    tcGrant($comp->id);
    $afterComp = app(TenantContext::class)->runAsSuperAdmin(fn () => [
        'on_trial' => app(PlatformMetrics::class)->compute(true)['on_trial'],
        'unbilled' => app(PlatformRevenue::class)->compute(true)['trialing_unbilled'],
    ]);

    expect($afterComp['on_trial'])->toBe($before['on_trial'])->and($afterComp['unbilled'])->toBe($before['unbilled']);
});

it('ending a complimentary plan sends the organisation to the paywall at once', function () {
    $org = tcOrg();
    tcGrant($org->id);

    $this->flushSession();
    $this->actingAs(platformAdmin(), 'admin')->from(route('admin.customers.show', $org->id))
        ->post(route('admin.customers.complimentary.end', $org->id), ['reason' => 'The pilot ended', 'current_password' => 'password'])
        ->assertRedirect(route('admin.customers.show', $org->id));

    $sub = tcSub($org);
    expect($sub->complimentary_until)->toBeNull()
        ->and($org->fresh()->billingStatus()->isLapsed())->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.end_complimentary')->where('target_id', $org->id)->count())->toBe(1);
});

it('ending refuses an organisation that is not complimentary', function () {
    $org = tcOrg();

    $this->actingAs(platformAdmin(), 'admin')->from(route('admin.customers.show', $org->id))
        ->post(route('admin.customers.complimentary.end', $org->id), ['reason' => 'No such plan', 'current_password' => 'password']);

    expect(PlatformAdminAction::query()->where('action', 'platform.customer.end_complimentary')->where('target_id', $org->id)->count())->toBe(0);
});

it('a real Stripe activation clears the complimentary mark — a paying customer is never labelled free', function () {
    $org = tcOrg();
    tcGrant($org->id);

    app(TenantContext::class)->forOrganization((string) $org->id, fn () => tap(Subscription::query()->where('organization_id', $org->id)->sole(), fn (Subscription $s) => $s->update(['status' => 'active', 'stripe_subscription_id' => 'sub_live_1'])));

    expect(tcSub($org)->complimentary_until)->toBeNull();
});

it('the console detail page shows the complimentary state and the forms only to a role that may use them', function () {
    $org = tcOrg();
    tcGrant($org->id);

    $this->flushSession();
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $org->id))
        ->assertOk()->assertSee('Complimentary')->assertSee(route('admin.customers.complimentary.end', $org->id), false);

    $trial = tcOrg();
    $this->flushSession();
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $trial->id))
        ->assertOk()->assertSee(route('admin.customers.extend-trial', $trial->id), false)->assertSee(route('admin.customers.complimentary', $trial->id), false);

    // Support may open the customer (tenants.manage) but not manage billing (billing.manage): the forms are not offered.
    $support = PlatformAdmin::factory()->withTwoFactor()->role('support')->create();
    $this->flushSession();
    $this->actingAs($support, 'admin')->get(route('admin.customers.show', $trial->id))
        ->assertOk()->assertDontSee(route('admin.customers.extend-trial', $trial->id), false)->assertDontSee(route('admin.customers.complimentary', $trial->id), false);
});

// ── What the ORGANISATION sees ────────────────────────────────────────────────────────────────────────────────
it('the operator Billing page tells a complimentary workspace it is complimentary — no trial countdown', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    $sub = Subscription::create([
        'organization_id' => $s['org']->id,
        'plan_id' => app(BillingService::class)->trialPlan()->id,
        'status' => 'trialing',
        'trial_ends_at' => now()->addDays(30)->endOfDay(),
        'complimentary_until' => now()->addDays(30)->endOfDay(),
    ]);

    $res = test()->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/billing');

    $res->assertOk()->assertSee('Complimentary')->assertSee('no payment needed')->assertDontSee('left in your free trial');
    assertNoRawBlade($res);
    expect($sub->fresh()->complimentary_until)->not->toBeNull();
});
