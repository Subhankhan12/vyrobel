<?php

declare(strict_types=1);

use App\Http\Middleware\SetTenantForWebRequest;
use App\Models\Membership;
use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * ISOLATION REGRESSION — the auth/isolation pass, item 2.
 *
 * `active_organization_id` was READ by {@see SetTenantForWebRequest} and NEVER WRITTEN
 * anywhere in the repo. The read side was correct — it validates the session value against the user's active
 * memberships and otherwise falls back to `$memberOrgIds->first()` — but with no write path, a user belonging
 * to two organizations was silently pinned to whichever membership came back first, permanently.
 *
 * ⚠️ The switch is a WORKING-CONTEXT change: it must move a user only between organizations they already
 * belong to, and grant nothing their membership doesn't (#38).
 */
it('persists a workspace switch, so a two-org user is no longer pinned to the first membership', function () {
    $a = seedScenario(1);
    $user = operatorUser($a);

    // A SECOND organization the same user genuinely belongs to.
    $second = app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'Second Workspace', 'slug' => 'second-'.Str::random(8), 'default_currency' => 'EUR',
    ]));
    app(TenantContext::class)->forOrganization((string) $second->id, fn () => Membership::create([
        'organization_id' => $second->id, 'user_id' => $user->id, 'status' => 'active',
    ]));

    $this->actingAs($user)
        ->post('/app/workspace/switch', ['organization_id' => (string) $second->id])
        ->assertRedirect();

    // The selection PERSISTS in the session — this is the write that did not exist.
    expect(session('active_organization_id'))->toBe((string) $second->id);
});

it('refuses a workspace the user does not belong to, and stores nothing', function () {
    $a = seedScenario(1);
    $user = operatorUser($a);

    // A foreign org the user has NO membership in — the crafted-id attack.
    $foreign = app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'Not Mine', 'slug' => 'foreign-'.Str::random(8), 'default_currency' => 'EUR',
    ]));

    $this->actingAs($user)
        ->post('/app/workspace/switch', ['organization_id' => (string) $foreign->id])
        ->assertSessionHasErrors('organization_id');

    // Nothing was written — a rejected switch must not leave a foreign id behind for the middleware to read.
    expect(session('active_organization_id'))->not->toBe((string) $foreign->id);
});

it('clears the property selection on switch, so no foreign property id survives the move', function () {
    $a = seedScenario(1);
    $user = operatorUser($a);

    $second = app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'Second Workspace', 'slug' => 'second-'.Str::random(8), 'default_currency' => 'EUR',
    ]));
    app(TenantContext::class)->forOrganization((string) $second->id, fn () => Membership::create([
        'organization_id' => $second->id, 'user_id' => $user->id, 'status' => 'active',
    ]));

    // A property from the OLD workspace is selected before the move.
    $this->actingAs($user)
        ->withSession(['current_property_id' => (string) $a['property']->id])
        ->post('/app/workspace/switch', ['organization_id' => (string) $second->id]);

    expect(session('current_property_id'))->toBeNull();
});

it('rejects a missing organization id rather than defaulting to one', function () {
    $a = seedScenario(1);

    $this->actingAs(operatorUser($a))
        ->post('/app/workspace/switch', [])
        ->assertSessionHasErrors('organization_id');
});

/**
 * ⚠️ FOUND IN THE BROWSER, not by a test: switching to a workspace where the user holds a Membership but NO
 * back-office role landed on the 403 page. The switch itself was CORRECT — a Membership is not operator
 * access, and EnsureBackOfficeAccess refused exactly as it should — but offering the choice invited the 403,
 * which #30 forbids ("gate the CONTROL too"). The picker now lists only workspaces the user can enter.
 */
it('offers only workspaces the user can actually enter, never one that would 403', function () {
    $a = seedScenario(1);
    $user = operatorUser($a);   // holds a back-office role in org A

    // Org B: a real active membership, but NO back-office role — /app would answer 403 there.
    $b = app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'No Role Here', 'slug' => 'norole-'.Str::random(8), 'default_currency' => 'EUR',
    ]));
    app(TenantContext::class)->forOrganization((string) $b->id, fn () => Membership::create([
        'organization_id' => $b->id, 'user_id' => $user->id, 'status' => 'active',
    ]));

    $html = $this->actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

    // The role-less workspace is not offered…
    expect($html)->not->toContain('No Role Here');

    // …and were it somehow posted, the switch is still ACCEPTED (the membership is real) — this control
    // hides a dead end, it does not become the security boundary. EnsureBackOfficeAccess remains the guard
    // that protects /app, and this change does not touch it.
    //
    // ⚠️ SCOPE, stated honestly: that the role-less workspace then answers 403 was verified IN THE BROWSER
    // (switching there landed on the branded 403 page — which is how this refinement was found at all). It
    // is not asserted here because the fixture's spatie team/role state does not reproduce the middleware's
    // team-scoped role check faithfully, and an assertion that passes for the wrong reason guards nothing.
    $this->actingAs($user)
        ->post('/app/workspace/switch', ['organization_id' => (string) $b->id])
        ->assertRedirect();
    expect(session('active_organization_id'))->toBe((string) $b->id);
});
