<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * ⚠️⚠️ A LAPSED WORKSPACE MUST NOT TRAP ITS STAFF IN A 403 LOOP (2026-10-09, his report: "I can't log into any of
 * the roles … it tells me I don't have access to this … Back to dashboard is also not working").
 *
 * 🔬 Production's refusal log (read 2026-10-09): one user of the internal test workspace refused `/app/billing`,
 * five times in nine seconds, from the afternoon its 14-day trial ended (7 Oct 12:05 UTC). EnsureSubscriptionActive
 * sent EVERY `/app` page to the paywall, and the paywall is `can:billing.manage` — held by the organisation admin
 * alone. So every other role got the 403, whose "Back to the dashboard" went to `/app/dashboard`, which sent them
 * straight back to the paywall. ⚠️ BillingAccessGateTest only ever signed in as Property Owner, so the other ten
 * back-office roles had never been driven through a lapsed workspace.
 *
 * ⚠️ WHAT DOES NOT CHANGE: a lapsed workspace is still closed (#41) — whoever can renew still gets the paywall, and
 * nobody else gets the back office. Only the dead end goes: they are told why, and given a way out that works.
 */
uses(DatabaseTransactions::class);

/** Give the scenario's organisation a trial that ended yesterday — the state the internal workspace was in. */
function lapseTrial(array $scenario): void
{
    app(BillingService::class)->ensurePlans();

    Subscription::create([
        'organization_id' => $scenario['org']->id,
        'plan_id' => app(BillingService::class)->trialPlan()->id,
        'status' => 'trialing',
        'trial_ends_at' => now()->subDay()->toDateTimeString(),
    ]);
}

/**
 * The back-office roles that cannot renew, read from the catalogue — so a role added later is covered without
 * editing this file. ⚠️ Asserted non-empty and to include Front Desk below, so it can never pass by being empty.
 *
 * @return list<string>
 */
function rolesThatCannotRenew(): array
{
    $matrix = config('authorization.roles');

    return array_values(array_filter(
        config('authorization.back_office_roles'),
        fn (string $role): bool => ($matrix[$role] ?? []) !== ['*'] && ! in_array('billing.manage', $matrix[$role] ?? [], true),
    ));
}

it('reads the roles that cannot renew from the catalogue — Front Desk among them, the admin not', function () {
    expect(rolesThatCannotRenew())->toContain('Front Desk', 'Housekeeping', 'Accountant', 'Group Admin')
        ->not->toContain('Property Owner');
});

it('⚠️⚠️ shows every role that cannot renew a plain page with a way out — never the paywall it is refused', function () {
    foreach (rolesThatCannotRenew() as $role) {
        $s = seedScenario(1);
        lapseTrial($s);
        $user = operatorUser($s, [], $role);

        foreach (['/app/dashboard', '/app/reservations'] as $path) {
            $html = (string) $this->actingAs($user)->get($path)
                ->assertStatus(403)   // ⚠️ was 302 → /app/billing → 403 "Your role doesn't include this area"
                ->assertSee('This workspace’s subscription has ended')
                ->assertSee('Nothing has been deleted')
                ->getContent();

            // The way out works: sign out (a form to the logout route) — and NO link back into the loop.
            expect($html)->toContain('action="'.route('logout').'"')
                ->and($html)->not->toContain('Back to the dashboard')
                ->and($html)->not->toContain('href="'.url('/app/dashboard').'"')
                ->and($html)->not->toContain('href="/app/dashboard"');
        }
    }
});

it('⚠️ still sends whoever can renew to the paywall — by the PERMISSION, not the role name', function () {
    // The organisation admin.
    $s = seedScenario(1);
    lapseTrial($s);
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/dashboard')
        ->assertRedirect(route('app.billing'));

    // A Hotel Manager who has been GIVEN `billing.manage` (the Roles page can grant it) can renew, so they get the
    // paywall too — the rule must read the permission, or this person would be shut out of a page they may use.
    $t = seedScenario(1);
    lapseTrial($t);
    $manager = operatorUser($t, [], 'Hotel Manager');
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($t['org']->id);
    Role::findByName('Hotel Manager', 'web')->givePermissionTo('billing.manage');
    $registrar->forgetCachedPermissions();

    $this->actingAs($manager)->get('/app/dashboard')->assertRedirect(route('app.billing'));
    $this->actingAs($manager)->get('/app/billing')->assertOk();
});

it('⚠️ lets someone in a lapsed workspace move to another workspace they belong to', function () {
    $lapsed = seedScenario(1);
    lapseTrial($lapsed);
    $user = operatorUser($lapsed, [], 'Front Desk');

    $working = seedScenario(1);
    Membership::create(['organization_id' => $working['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    grantBackOfficeRole($user, $working['org'], 'Front Desk');

    $this->actingAs($user)->withSession(['active_organization_id' => (string) $lapsed['org']->id])
        ->get('/app/dashboard')
        ->assertStatus(403)
        ->assertSee('Switch to '.$working['org']->name)
        ->assertDontSee('Switch to '.$lapsed['org']->name)   // never the workspace that refused them
        ->assertSee('action="'.route('app.workspace.switch').'"', false);

    // ⚠️ The switch itself was caught by the gate too (302 → /app/billing → 403), so the button would have led
    //    straight back into the loop.
    $this->actingAs($user)->withSession(['active_organization_id' => (string) $lapsed['org']->id])
        ->post(route('app.workspace.switch'), ['organization_id' => (string) $working['org']->id])
        ->assertRedirect(route('app.dashboard'))
        ->assertSessionHas('active_organization_id', (string) $working['org']->id);

    $this->actingAs($user)->withSession(['active_organization_id' => (string) $working['org']->id])
        ->get('/app/dashboard')->assertOk();
});

it('never offers a suspended workspace as the way out', function () {
    $lapsed = seedScenario(1);
    lapseTrial($lapsed);
    $user = operatorUser($lapsed, [], 'Front Desk');

    $suspended = seedScenario(1);
    Membership::create(['organization_id' => $suspended['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    grantBackOfficeRole($user, $suspended['org'], 'Front Desk');
    $suspended['org']->forceFill(['status' => 'suspended'])->save();

    $this->actingAs($user)->withSession(['active_organization_id' => (string) $lapsed['org']->id])
        ->get('/app/dashboard')
        ->assertStatus(403)
        ->assertSee('This workspace’s subscription has ended')
        ->assertDontSee('Switch to '.$suspended['org']->name);
});

it('answers a background request from a lapsed workspace with a plain 403, not a redirect', function () {
    $s = seedScenario(1);
    lapseTrial($s);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))->getJson('/app/dashboard')
        ->assertStatus(403)
        ->assertJson(['message' => 'This workspace’s subscription has ended.']);
});

it('leaves a workspace in its trial fully open to every back-office role', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    Subscription::create([
        'organization_id' => $s['org']->id,
        'plan_id' => app(BillingService::class)->trialPlan()->id,
        'status' => 'trialing',
        'trial_ends_at' => now()->addDays(5)->toDateTimeString(),
    ]);

    foreach (config('authorization.back_office_roles') as $role) {
        $this->actingAs(operatorUser($s, [], $role))->get('/app/dashboard')->assertOk();
    }
});
