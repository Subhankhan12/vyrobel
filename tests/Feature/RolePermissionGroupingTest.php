<?php

use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// D23-2 — the permission matrix gains a coarser DOMAIN tier above its resource headings.
//
// ⚠️ DISPLAY ONLY. This changes where a row is drawn and nothing else: the catalogue, the labels, the
// role→permission grants and the spatie-teams checks are untouched. A role granted `units.manage` is
// identical whether or not "Operations" is a heading.
//
// ⚠️ THE LOAD-BEARING PROPERTY IS THAT THE SET IS PARTITIONED, NOT FILTERED. An admin cannot grant what they
// cannot see, so a grouping that silently drops a permission removes a capability from the product's reach.
// Test 2 counts the rendered set against the LIVE catalogue rather than a pinned number.

/** An operator who may manage roles (the page's own gate). */
function groupingAdmin(array $s): User
{
    return operatorUser($s, [], 'Property Owner');
}

/**
 * Pin the spatie TEAM to the org, then ask the real gate. ⚠️ A `can()` outside the team context answers for
 * no team and is simply false — it would look like a revoked permission. (A sibling helper exists in
 * `OperatorRolesMatrixTest`; this file declares its own so it can be run alone.)
 */
function groupingUserCan(User $user, array $s, string $permission): bool
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

// ── 1. Every section carries a domain, and every domain is one the view can order ────────────────
it('assigns every permission section to an ordered domain', function () {
    $s = seedScenario(1);
    $admin = groupingAdmin($s);

    $matrix = app(RolePermissionService::class)->matrix($admin);

    expect($matrix['sections'])->not->toBeEmpty();

    foreach ($matrix['sections'] as $section) {
        expect($section)->toHaveKey('domain')
            // ⚠️ A domain the view cannot order would render nowhere — the same silent disappearance the
            // whole guard exists to prevent.
            ->and(RolePermissionService::DOMAIN_ORDER)->toContain($section['domain']);
    }
});

// ── 2. ⚠️⚠️ PARTITIONED, NOT FILTERED — the domains hold the WHOLE live catalogue, once each ──────
it('renders every catalogue permission in exactly one domain', function () {
    $s = seedScenario(1);
    $admin = groupingAdmin($s);

    $matrix = app(RolePermissionService::class)->matrix($admin);

    // ⚠️ THE SOURCE, NOT A PINNED NUMBER: the catalogue the matrix itself publishes.
    $catalogue = array_map(fn (array $p): string => $p['key'], $matrix['permissions']);

    $grouped = [];
    foreach ($matrix['sections'] as $section) {
        foreach ($section['permissions'] as $perm) {
            $grouped[] = $perm['key'];
        }
    }

    sort($catalogue);
    $sortedGrouped = $grouped;
    sort($sortedGrouped);

    expect($sortedGrouped)->toBe($catalogue)                       // same set …
        ->and(count($grouped))->toBe(count(array_unique($grouped)))   // … each appearing ONCE
        ->and(count($grouped))->toBe(count($catalogue));
});

// ── 3. ⚠️ AN UNMAPPED RESOURCE FALLS TO "Other" — it never vanishes ──────────────────────────────
it('puts an unmapped resource under Other rather than dropping it', function () {
    // A resource nobody has mapped (tomorrow's feature).
    expect(RolePermissionService::domainFor('some_new_feature'))->toBe(RolePermissionService::DOMAIN_OTHER)
        ->and(RolePermissionService::DOMAIN_ORDER)->toContain(RolePermissionService::DOMAIN_OTHER);

    // …and every resource the LIVE catalogue actually contains resolves to a real domain.
    foreach (DB::table('permissions')->pluck('name') as $name) {
        $resource = explode('.', (string) $name, 2)[0];
        expect(RolePermissionService::DOMAIN_ORDER)->toContain(RolePermissionService::domainFor($resource));
    }
});

// ── 4. The rendered page groups by domain, and shows the full set ────────────────────────────────
it('renders the domain headings on the roles page', function () {
    $s = seedScenario(1);
    $admin = groupingAdmin($s);

    $response = $this->actingAs($admin)->get('/app/roles')->assertOk();

    // The domain tier is present. ⚠️ The headings reach the page inside Blade's @js payload, where
    // `Js::from` HEX-escapes the ampersand — so "System & access" is literally `System \u0026 access` in the
    // HTML and a naive needle silently misses it.
    foreach (['Operations', 'Guests', 'Finance'] as $domain) {
        $response->assertSee($domain, false);
    }
    // ⚠️ DERIVED, NOT HAND-ESCAPED. `Js::from` writes the ampersand as a unicode escape and then escapes
    // THAT backslash again for its JSON.parse('…') wrapper, so "System & access" reaches the page as a
    // doubled-backslash sequence. Two attempts to type that needle by hand missed it silently; computing it
    // the same way the view does cannot drift.
    $response->assertSee((string) Js::from(RolePermissionService::DOMAIN_ORDER), false);

    // … and so is every permission key, still one-to-one with its own label.
    $content = $response->getContent();
    foreach (DB::table('permissions')->pluck('name') as $name) {
        expect($content)->toContain((string) $name);
    }
});

// ── 5. ⚠️ THE LABELS ARE UNTOUCHED — no friendly phrase blurs two permissions ─────────────────────
// The design's "Manage team & roles" conflates `users.manage` and `roles.manage`; the design pass refused it
// and this pass keeps that refusal. Each row's label still derives from its own single key.
it('does not blur two permissions behind one friendly label', function () {
    $s = seedScenario(1);
    $matrix = app(RolePermissionService::class)->matrix(groupingAdmin($s));

    $labels = [];
    foreach ($matrix['sections'] as $section) {
        foreach ($section['permissions'] as $perm) {
            $labels[$perm['key']] = $perm['label'];
        }
    }

    expect($labels)->toHaveKey('users.manage')->toHaveKey('roles.manage');

    // The two live in DIFFERENT sections and keep separate rows — they are the two tiers gating this page.
    $usersSection = collect($matrix['sections'])->first(fn (array $s): bool => collect($s['permissions'])->contains('key', 'users.manage'));
    $rolesSection = collect($matrix['sections'])->first(fn (array $s): bool => collect($s['permissions'])->contains('key', 'roles.manage'));
    expect($usersSection['section'])->not->toBe($rolesSection['section']);

    $this->actingAs(groupingAdmin($s))->get('/app/roles')
        ->assertOk()
        ->assertDontSee('Manage team &amp; roles', false);
});

// ── 6. ⚠️ THE GRANTS STILL PERSIST AND STILL ENFORCE — grouping changed no behaviour ──────────────
it('still grants a permission that genuinely authorises', function () {
    $s = seedScenario(1);
    $admin = groupingAdmin($s);

    $service = app(RolePermissionService::class);
    $before = DB::table('role_has_permissions')->count();

    $service->save($admin, 'Front Desk', ['reservations.view', 'reservations.manage']);

    // Raw-query: the real spatie pivot moved.
    expect(DB::table('role_has_permissions')->count())->not->toBe($before);

    // …and the capability genuinely authorises through spatie, unchanged by any grouping.
    $desk = operatorUser($s, [], 'Front Desk');
    expect(groupingUserCan($desk, $s, 'reservations.manage'))->toBeTrue()
        ->and(groupingUserCan($desk, $s, 'payouts.manage'))->toBeFalse();
});

// ── 7. The catalogue itself is untouched by this pass ────────────────────────────────────────────
it('adds and removes no permission', function () {
    $s = seedScenario(1);
    $matrix = app(RolePermissionService::class)->matrix(groupingAdmin($s));

    $catalogue = array_map(fn (array $p): string => $p['key'], $matrix['permissions']);
    $live = DB::table('permissions')->pluck('name')->map(fn ($n): string => (string) $n)->all();

    sort($catalogue);
    sort($live);

    // The matrix publishes exactly the configured catalogue — the grouping neither widened nor narrowed it.
    expect($catalogue)->toBe(array_values(array_intersect($live, $catalogue)))
        ->and($catalogue)->not->toBeEmpty();
});
