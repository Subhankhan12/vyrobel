<?php

use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Http\Middleware\SetTenantForWebRequest;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// THE /app BACK-OFFICE GATE — the security regression net for the critical authorization hole recorded
// in docs/DECISIONS.md 2026-07-16.
//
// THE HOLE: /app was `middleware('auth')` and nothing else, while OwnerInviteService gives an OWNER an
// active Membership (the only way SetTenantForWebRequest can resolve their org). So "authenticated +
// has a Membership" silently read as "is staff", and an owner could walk into the whole operator app —
// and, because OwnerScope is INACTIVE off-portal by design, read EVERY owner's financials at
// /app/owners/{owner}. These tests fail if that door is ever reopened.

/** A linked owner: Membership (so their org resolves) + an Owner row. NO back-office role — as production. */
function gateOwner(array $s, string $name, int $unitIndex, int $roomMinor): array
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
    ]);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);

    return compact('user', 'owner', 'unit');
}

/** Every /app route worth proving the gate on — the whole operator surface, not just the leaky screen. */
function backOfficeRoutes(array $ctx): array
{
    return [
        '/app/dashboard',
        '/app/rack',
        '/app/units',
        '/app/front-desk',
        '/app/housekeeping',
        '/app/owners',
        '/app/owners/'.$ctx['ownerId'],        // ← the screen that leaked every owner's financials
        '/app/payouts/run',
        '/app/maintenance',
        '/app/inbox',
    ];
}

// ── 1. THE HEADLINE: an owner is refused the ENTIRE back office, and leaks nothing ────────────────
it('refuses an owner-role user every /app route and leaks no operator or cross-owner data', function () {
    $s = seedScenario(2);
    $a = gateOwner($s, 'Amara Okafor', 0, 20000);
    $b = gateOwner($s, 'Bruno Keller', 1, 42500);   // a SECOND owner in the SAME org

    $routes = backOfficeRoutes(['ownerId' => $b['owner']->id]);

    foreach ($routes as $route) {
        $response = $this->actingAs($a['user'])->get($route);

        // An owner has a home — they are redirected there, never handed the operator app.
        $response->assertRedirect(route('owner.dashboard'));

        // The rejection body carries NO operator data and NO trace of the other owner.
        $body = $response->getContent();
        expect($body)->not->toContain('Bruno Keller', "leaked owner B's name at {$route}")
            ->and($body)->not->toContain('€425.00', "leaked owner B's money at {$route}")
            ->and($body)->not->toContain($b['unit']->code, "leaked owner B's unit at {$route}");
    }
});

// ── 2. The gate does NOT break staff — a real operator still works ───────────────────────────────
it('lets a real staff member (Hotel Manager) into every /app route', function () {
    $s = seedScenario(2);
    $b = gateOwner($s, 'Bruno Keller', 1, 42500);
    $staff = operatorUser($s, ['name' => 'Marcus Bellweather'], 'Hotel Manager');

    foreach (backOfficeRoutes(['ownerId' => $b['owner']->id]) as $route) {
        $this->actingAs($staff)->get($route)->assertOk();
    }
});

// ── 3. …and Accountant (the other assignable staff role) is equally admitted ──────────────────────
it('lets an Accountant into the back office', function () {
    $s = seedScenario(1);
    $staff = operatorUser($s, ['name' => 'Priya Raman'], 'Accountant');

    $this->actingAs($staff)->get('/app/dashboard')->assertOk();
    $this->actingAs($staff)->get('/app/payouts/run')->assertOk();
});

// ── 4. The owner portal still works — the gate must not touch /owner ─────────────────────────────
it('still lets the owner into their own portal, scoped to their own data', function () {
    $s = seedScenario(2);
    $a = gateOwner($s, 'Amara Okafor', 0, 20000);
    $b = gateOwner($s, 'Bruno Keller', 1, 42500);

    foreach (['/owner', '/owner/units', '/owner/balance', '/owner/expenses', '/owner/agreement', '/owner/archive', '/owner/payouts', '/owner/documents'] as $route) {
        $this->actingAs($a['user'])->get($route)
            ->assertOk()
            ->assertDontSee('Bruno Keller')
            ->assertDontSee($b['unit']->code);
    }
});

// ── 5. Fails CLOSED for everyone else — no role, no owner link ────────────────────────────────────
it('403s a member with no role and no owner link, revealing nothing', function () {
    $s = seedScenario(1);
    $stranger = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    // A Membership alone is NOT operator access — that assumption WAS the hole.
    $this->actingAs($stranger)->get('/app/dashboard')->assertForbidden();
    $this->actingAs($stranger)->get('/app/owners')->assertForbidden();
});

// ── 6. A back-office role in ANOTHER org grants nothing here (composes with tenant isolation) ─────
it('refuses a user whose back-office role belongs to a different organization', function () {
    $home = seedScenario(1);
    $foreign = seedScenario(1);

    // A real operator of the FOREIGN org…
    $user = operatorUser($foreign, ['name' => 'Outside Operator'], 'Hotel Manager');
    // …who is also an active member of the home org, but holds NO role there.
    Membership::create(['organization_id' => $home['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // Their active org resolves to the FIRST active membership (the foreign one) — where they are staff.
    $this->actingAs($user)->get('/app/dashboard')->assertOk();

    // Now act in the HOME org: the role does NOT travel across the team boundary.
    // NB re-resolve the user from the DB. spatie's hasRole() does loadMissing('roles'), so the SAME PHP
    // instance would reuse the roles relation loaded above under the FOREIGN team and wrongly pass. A real
    // request always resolves a fresh user from the session, so this models production rather than the
    // test process's object reuse.
    $fresh = User::query()->findOrFail($user->getKey());

    $this->withSession(['active_organization_id' => $home['org']->id])
        ->actingAs($fresh)->get('/app/dashboard')->assertForbidden();
});

// ── 7. Unauthenticated still redirects to login (the gate must not shadow `auth`) ─────────────────
it('sends an unauthenticated visitor to login, not to the gate', function () {
    $this->get('/app/dashboard')->assertRedirect(route('login'));
    $this->get('/app/owners')->assertRedirect(route('login'));
});

// ── 8. EVERY /app route carries the gate — a route added later cannot forget it ───────────────────
it('applies the gate to every single /app route', function () {
    $ungated = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r): bool => str_starts_with((string) $r->uri(), 'app/'))
        ->reject(fn ($r): bool => in_array(EnsureBackOfficeAccess::class, $r->gatherMiddleware(), true))
        ->map(fn ($r): string => $r->methods()[0].' '.$r->uri())
        ->values()->all();

    expect($ungated)->toBe([], 'these /app routes are NOT behind the back-office gate: '.implode(', ', $ungated));
})->group('security');

// ── 9. ORDERING is load-bearing: the gate MUST run after the tenant/team middleware ───────────────
// hasAnyRole() is team-filtered; if EnsureBackOfficeAccess ran before SetTenantForWebRequest set the
// team id, it would match nothing and lock out EVERY operator. Neither is in Router::$middlewarePriority,
// so they sink to the end in declaration order — assert it, so a future priority tweak fails loudly.
it('runs the gate after the tenant context middleware that sets the permissions team id', function () {
    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($r): bool => $r->getName() === 'app.dashboard');

    $chain = array_values(app('router')->gatherRouteMiddleware($route));
    $tenant = array_search(SetTenantForWebRequest::class, $chain, true);
    $gate = array_search(EnsureBackOfficeAccess::class, $chain, true);

    expect($tenant)->not->toBeFalse('SetTenantForWebRequest missing from the /app chain')
        ->and($gate)->not->toBeFalse('EnsureBackOfficeAccess missing from the /app chain')
        ->and($tenant)->toBeLessThan($gate, 'the gate must run AFTER the permissions team id is set');
})->group('security');

// ── 10. The allow-list is real: every configured role exists, and portal roles are excluded ───────
it('configures back-office roles that exist in the catalog and excludes the portal roles', function () {
    $backOffice = config('authorization.back_office_roles');
    $catalog = array_keys(config('authorization.roles'));

    expect($backOffice)->toBeArray()->not->toBeEmpty();

    // NB Pest's toContain() treats extra args as MORE needles, not a failure message — so assert the
    // set difference instead, which also names the offenders when it fails.
    $unknown = array_values(array_diff($backOffice, $catalog));
    expect($unknown)->toBe([], 'back_office_roles names roles absent from the catalog: '.implode(', ', $unknown));

    // The portal roles must NEVER be back-office — that is the boundary this whole gate defends.
    expect($backOffice)->not->toContain('External Owner')
        ->and($backOffice)->not->toContain('Guest');
})->group('security');
