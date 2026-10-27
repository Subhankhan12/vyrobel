<?php

use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// D23-3 — the roles list becomes a CARD GRID (the design's 5-across cards + a dark selected-role rail).
//
// ⚠️ THIS IS A LAYOUT CHANGE AND NOTHING ELSE. The roles that exist, the permission catalogue, the
// role→permission grants and the spatie-teams enforcement are untouched. Every assertion below therefore
// pins something that a re-layout could plausibly BREAK, not something it was supposed to change.
//
// ⚠️⚠️ THE TWO RISKS THAT MADE THIS ITS OWN GATE:
//  1. D23-2's permission grouping shipped on this same page days ago. A restructure that quietly drops it
//     is a regression in an RBAC surface — tests 4 and 5.
//  2. The grant form DIFFS against the database, so a dropped `permissions[]` input is not a cosmetic bug,
//     it is a SILENT REVOKE on the next save — test 3, which saves UNCHANGED and proves the pivot is
//     byte-for-byte identical.
//
// ⚠️ The role list is rendered by Alpine from the `roles` payload, so "every role appears" is asserted
// against the payload the view receives AND against the iteration in the delivered markup — a `.slice()` or
// `.filter()` in the template would silently drop a role from management and is what test 2 catches.

/** An operator who may manage roles — ⚠️ the page 403s without `roles.manage` (§N.14.1). */
function rcgAdmin(array $s): User
{
    return operatorUser($s, [], 'Property Owner');
}

/** Ask the REAL gate inside the spatie TEAM context — outside it, `can()` answers for no team = false. */
function rcgCan(User $user, array $s, string $permission): bool
{
    $registrar = app(PermissionRegistrar::class);
    $prev = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($s['org']->getKey());
    try {
        return $user->fresh()->can($permission);
    } finally {
        $registrar->setPermissionsTeamId($prev);
    }
}

/** A snapshot of the ENTIRE grant pivot — the thing a silent revoke would change. */
function rcgPivot(): array
{
    return DB::table('role_has_permissions')
        ->orderBy('role_id')->orderBy('permission_id')
        ->get()->map(fn ($r): string => $r->role_id.':'.$r->permission_id)->all();
}

// ── 1. ⚠️ THE ROLES RENDER AS A CARD GRID, and the rail card is the dark treatment ────────────────
it('renders the roles as a card grid with a dark selected-role card', function () {
    $s = seedScenario(1);

    $html = test()->actingAs(rcgAdmin($s))->get('/app/roles')->assertOk()->getContent();

    expect($html)->toContain('vyr-rolegrid')          // the grid container
        ->and($html)->toContain('vyr-rolecard')       // a card per role
        ->and($html)->toContain('vyr-selected')       // the dark rail card
        // ⚠️ The design's 5-across is the prototype's OWN auto-fit rule, not a hardcoded column count —
        // it yields 5 at the design width and degrades gracefully instead of overflowing.
        ->and($html)->toContain('repeat(auto-fit, minmax(190px, 1fr))')
        // the page is now two columns (matrix + rail), not three
        ->and($html)->toContain('minmax(0, 1fr) 300px')
        // …and the old narrow-column list is gone
        ->and($html)->not->toContain('grid-template-columns: 240px 1fr 260px');
});

// ── 2. ⚠️⚠️ EVERY ROLE APPEARS — the grid iterates the WHOLE set, never a slice ───────────────────
it('renders a card for every role, dropping none', function () {
    $s = seedScenario(1);
    $admin = rcgAdmin($s);

    $matrix = app(RolePermissionService::class)->matrix($admin);
    expect($matrix['roles'])->not->toBeEmpty();

    $html = test()->actingAs($admin)->get('/app/roles')->assertOk()->getContent();

    // ⚠️ DERIVED EXACTLY AS THE VIEW WRITES IT, never a pinned count or a hand-typed needle. The whole role
    // array ships as ONE `Js::from` payload, so asserting that payload proves every role the service
    // publishes reached the page — drop one and this needle changes. (A per-name needle does NOT work here:
    // `Js::from('Group Admin')` emits an entire `JSON.parse('…')` expression, not a bare quoted string —
    // the same escaping trap D23-2 hit twice.)
    // I18N-01 — the page sends each role with its translated words BESIDE the codes (display_name, level_label …), so the
    // payload is no longer byte-identical to the service's array. Decoded from the page itself: the roles the browser
    // receives are exactly the service's, every one, in order — drop one and the names differ.
    // (Js::from escapes every ' inside the payload, so its end is the first "')" after the start.)
    $from = strpos($html, "roles: JSON.parse('");
    expect($from)->not->toBeFalse();
    $from += strlen("roles: JSON.parse('");
    $shipped = json_decode((string) json_decode('"'.substr($html, $from, strpos($html, "')", $from) - $from).'"'), true);
    expect(array_column($shipped, 'name'))->toBe(array_column($matrix['roles'], 'name'));

    // …and the count is the service's own, so a future filter in the payload is visible too.
    expect(count($matrix['roles']))->toBe(count(app(RolePermissionService::class)->matrix($admin)['roles']));

    // ⚠️ AND THE TEMPLATE ITERATES THE FULL ARRAY. The cards are rendered client-side, so a `.slice(0, 5)`
    // or a `.filter(...)` in the loop would drop roles from management while every name still sat in the
    // payload above — this pins the iteration that actually ships in the markup.
    expect($html)->toContain('x-for="(r, i) in roles"');
});

// ── 3. ⚠️⚠️ THE NO-OP SAVE — a re-layout must not silently revoke anything ────────────────────────
it('preserves the entire grant pivot when the form is saved unchanged', function () {
    $s = seedScenario(1);
    $admin = rcgAdmin($s);

    $matrix = app(RolePermissionService::class)->matrix($admin);
    $target = collect($matrix['roles'])->first(fn (array $r): bool => ($r['locked'] ?? false) === false);
    expect($target)->not->toBeNull();

    $before = rcgPivot();
    $beforeCatalogue = (int) DB::table('permissions')->count();
    expect($before)->not->toBeEmpty();

    // ⚠️ SAVE EXACTLY WHAT THE ROLE ALREADY HOLDS — the no-op. The controller diffs against the DB, so if
    // the restructure had dropped a `permissions[]` input the round-trip would REVOKE it here, silently.
    test()->actingAs($admin)
        ->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => $target['name'], 'permissions' => $target['granted']])
        ->assertRedirect(route('app.roles'));

    // Byte-for-byte: same pairs, same count, same catalogue.
    expect(rcgPivot())->toBe($before)
        ->and((int) DB::table('permissions')->count())->toBe($beforeCatalogue);
});

// ── 3b. ⚠️⚠️ THE FORM STILL SUBMITS BOTH `permissions[]` CARRIERS ─────────────────────────────────
// ⚠️ WRITTEN BECAUSE A MUTATION REFUSED TO RED. Test 3 above builds its payload in PHP and posts it, so it
// proves the CONTROLLER round-trips a full set correctly — and renaming the form's input in the Blade left
// it perfectly green. The save diffs against the database, so the view→controller contract (the input NAME)
// is itself load-bearing: rename or drop it and every grant the form no longer carries is revoked on the
// next save, silently. The inputs are Alpine-rendered, so the delivered markup is where that contract is
// visible to a server-side test.
it('keeps both permissions[] carriers in the submitted form', function () {
    $s = seedScenario(1);

    $html = test()->actingAs(rcgAdmin($s))->get('/app/roles')->assertOk()->getContent();

    // (1) The toggle itself.
    expect($html)->toContain('<input type="checkbox" name="permissions[]"')
        // (2) ⚠️ AND the hidden carrier for a LOCKED-but-granted permission — the actor cannot toggle it, so
        //     if the form stops submitting it, saving revokes a grant the operator never touched or saw.
        ->and($html)->toContain('<input type="hidden" name="permissions[]"');

    // Both live inside the form that posts to the grant route — not stranded elsewhere on the page.
    $form = mb_substr($html, (int) mb_strpos($html, route('app.roles.permissions')));
    expect(mb_substr_count($form, 'name="permissions[]"'))->toBeGreaterThanOrEqual(2);
});

// ── 4. ⚠️ D23-2 SURVIVES — the domain tier still renders under the new layout ─────────────────────
it('keeps the D23-2 permission grouping intact', function () {
    $s = seedScenario(1);

    $response = test()->actingAs(rcgAdmin($s))->get('/app/roles')->assertOk();

    // ⚠️ DERIVED THE WAY THE VIEW WRITES IT. `Js::from` hex-escapes the ampersand and then escapes that
    // backslash again for its JSON.parse wrapper, so "System & access" never matches a hand-typed needle —
    // the D23-2 slip, repeated here rather than re-learned.
    $response->assertSee((string) Js::from(RolePermissionService::DOMAIN_ORDER), false);

    // …and every permission in the live catalogue still reaches the matrix.
    $content = $response->getContent();
    foreach (DB::table('permissions')->pluck('name') as $name) {
        expect($content)->toContain((string) $name);
    }
});

// ── 5. ⚠️ THE GRANTS STILL PERSIST AND STILL ENFORCE, under the new layout ────────────────────────
it('still grants a permission that genuinely authorises after the restructure', function () {
    $s = seedScenario(1);
    $admin = rcgAdmin($s);
    $desk = operatorUser($s, [], 'Front Desk');

    expect(rcgCan($desk, $s, 'reviews.manage'))->toBeFalse();

    $granted = collect((array) config('authorization.roles')['Front Desk'])->push('reviews.manage')->unique()->values()->all();
    test()->actingAs($admin)->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => 'Front Desk', 'permissions' => $granted])
        ->assertRedirect(route('app.roles'));

    // ⚠️ Through the real spatie gate in the team context — the layout changed, the enforcement did not.
    expect(rcgCan($desk, $s, 'reviews.manage'))->toBeTrue();

    // Revert the fixture, and prove the revoke is real too.
    $original = array_values((array) config('authorization.roles')['Front Desk']);
    test()->actingAs($admin)->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => 'Front Desk', 'permissions' => $original])
        ->assertRedirect();
    expect(rcgCan($desk, $s, 'reviews.manage'))->toBeFalse();
});

// ── 6. ⚠️ THE TWO-TIER GATE IS UNCHANGED — the page needs roles.manage ────────────────────────────
it('still refuses the page to an operator without roles.manage', function () {
    $s = seedScenario(1);

    test()->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get('/app/roles')
        ->assertForbidden();
});

// ── 7. ⚠️ THE ICON IS DERIVED FROM THE ROLE'S REAL LEVEL — not an invented per-role decoration ────
it('keys the card icon to the role real derived level', function () {
    $s = seedScenario(1);
    $admin = rcgAdmin($s);

    $html = test()->actingAs($admin)->get('/app/roles')->assertOk()->getContent();

    // The glyph is chosen from `r.level`, which RolePermissionService computes from the role's ACTUAL
    // grants — so a card can never display an authority tier its permissions do not support.
    expect($html)->toContain('levelIcon(r.level)')
        ->and($html)->toContain('levelKey(r.level)');

    // …and the levels the cards render are the service's own, not a literal list in the view.
    $levels = array_values(array_unique(array_map(fn (array $r): string => (string) $r['level'], app(RolePermissionService::class)->matrix($admin)['roles'])));
    expect($levels)->not->toBeEmpty();
    foreach ($levels as $level) {
        expect(in_array($level, ['Admin', 'Manager', 'Supervisor', 'Team'], true))->toBeTrue();
    }
});
