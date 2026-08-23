<?php

use App\Models\Property;
use App\Models\User;
use App\Services\PropertyScopeService;
use App\Support\Property\PropertyContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// ══ P-7 · CLUSTER J — PER-PROPERTY STAFF SCOPING BECOMES REACHABLE ═══════════════════════════════════
//
// ⚠️⚠️ WHAT WAS WRONG, PRECISELY. `property_user`'s only writer in `app/` is PropertyController::store(),
// guarded by `$user->propertyAssignments()->exists()` — a user can only GAIN a pivot row if they ALREADY
// have one. From an empty set the scoped population can never grow, and the table was empty everywhere
// (0 rows in the demo database; not even the seeder writes one). So EVERY operator fell through to the
// "empty pivot ⇒ full org" rule and per-property scoping was decorative.
//
// ⚠️ THE FALLBACK ITSELF IS NOT THE DEFECT AND IS NOT CHANGED. "Empty pivot ⇒ full org" is ratified (#38,
// User::accessiblePropertyIds()) and deliberate — it keeps single-property behaviour unregressed and locks
// nobody out. What was wrong is that NO OTHER STATE WAS REACHABLE. These tests therefore prove BOTH sides:
// the fallback still fires for an unscoped user (so the assertions below are not vacuous), and it NO
// LONGER fires once that user is scoped.

/** One org, two properties, an admin who may scope people, and a colleague to scope. */
function scopeScenario(): array
{
    $org = newDemoOrg();
    $a = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $b = Property::create(['name' => 'Bravo House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);

    $admin = operatorUser(['org' => $org], ['name' => 'Ada Admin'], 'Group Admin');
    $staff = operatorUser(['org' => $org], ['name' => 'Sam Staff'], 'Hotel Manager');

    return compact('org', 'a', 'b', 'admin', 'staff');
}

/** The property ids a user may work in, evaluated inside their own tenant — the enforced set. */
function accessibleFor(User $user, string $orgId): array
{
    return app(TenantContext::class)->forOrganization($orgId, fn (): array => $user->fresh()->accessiblePropertyIds());
}

// ══ REACHABILITY — the shape the P-6 guard uses, applied to the pivot ════════════════════════════════
it('⚠️ registers an app-reachable, permission-gated write path for per-property scoping', function () {
    $route = Route::getRoutes()->getByName('app.settings.team-scope');

    Assert::assertNotNull($route, '⚠️ per-property scoping has no registered route — it is unreachable again');
    Assert::assertContains('POST', $route->methods());
    // ⚠️ users.manage, NOT properties.manage: deciding which properties OTHER people may see is access
    // administration, so a property manager must not be able to do it (#30).
    Assert::assertContains('can:users.manage', $route->gatherMiddleware());
});

// ══ ⚠️ THE PROOF THAT MATTERS — the fallback stops firing for a SCOPED user ══════════════════════════
it('⚠️ stops the "all properties" fallback firing once a user is actually scoped', function () {
    ['org' => $org, 'a' => $a, 'b' => $b, 'admin' => $admin, 'staff' => $staff] = scopeScenario();

    // ⚠️ THE NON-VACUITY HALF: unscoped, the fallback DOES fire and they see both. If this ever fails the
    // test below proves nothing, because the "after" state would have been the "before" state too.
    $before = accessibleFor($staff, (string) $org->id);
    Assert::assertEqualsCanonicalizing([(string) $a->id, (string) $b->id], $before,
        'the unscoped baseline is wrong — this test would not be proving that scoping narrows anything');

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $staff->getKey(), 'scope' => 'selected', 'properties' => [(string) $b->id],
    ])->assertRedirect();

    $after = accessibleFor($staff, (string) $org->id);

    Assert::assertSame([(string) $b->id], $after, '⚠️ the scoped user still resolves more than their one property');
    Assert::assertNotContains((string) $a->id, $after, '⚠️ the "all properties" fallback still fires for a scoped user');
});

it('⚠️ keeps a scoped user out of the other property’s working context', function () {
    ['org' => $org, 'a' => $a, 'b' => $b, 'admin' => $admin, 'staff' => $staff] = scopeScenario();

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $staff->getKey(), 'scope' => 'selected', 'properties' => [(string) $b->id],
    ])->assertRedirect();

    // The context normalises any selection against the accessible set, so asking for A must not yield A.
    $ctx = new PropertyContext;
    $ctx->activate(accessibleFor($staff, (string) $org->id), (string) $a->id);

    Assert::assertSame((string) $b->id, $ctx->id(), '⚠️ a scoped user selected a property they may not access');
});

it('returns a user to full-org access when scope is set back to all', function () {
    ['org' => $org, 'a' => $a, 'b' => $b, 'admin' => $admin, 'staff' => $staff] = scopeScenario();

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $staff->getKey(), 'scope' => 'selected', 'properties' => [(string) $b->id],
    ])->assertRedirect();

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $staff->getKey(), 'scope' => 'all',
    ])->assertRedirect();

    Assert::assertSame(0, DB::table('property_user')->where('user_id', (string) $staff->getKey())->count());
    Assert::assertEqualsCanonicalizing([(string) $a->id, (string) $b->id], accessibleFor($staff, (string) $org->id));
});

// ══ ⚠️ TENANCY — MEASURED ON THE SIDE WHERE THE EVIDENCE WOULD LAND ══════════════════════════════════
it('⚠️ refuses to scope a user who belongs to another organisation', function () {
    ['org' => $mine, 'b' => $b, 'admin' => $admin] = scopeScenario();

    $theirs = newDemoOrg();
    $victim = operatorUser(['org' => $theirs], ['name' => 'Victim Vic'], 'Hotel Manager');
    app(TenantContext::class)->set((string) $mine->id);

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $victim->getKey(), 'scope' => 'selected', 'properties' => [(string) $b->id],
    ])->assertRedirect();

    // ⚠️ RAW, UNSCOPED, AND KEYED ON THE VICTIM — `property_user` has NO tenant global scope and NO
    // organization_id auto-fill, so nothing would have corrected a bad write for us. Counting rows in the
    // attacker's org would also miss it: the row would carry the victim's user_id either way.
    Assert::assertSame(0, DB::table('property_user')->where('user_id', (string) $victim->getKey())->count(),
        '⚠️ a cross-organisation user was scoped — tenancy did not hold on the pivot');
});

it('⚠️ silently drops a foreign property id rather than writing it', function () {
    ['org' => $mine, 'b' => $b, 'admin' => $admin, 'staff' => $staff] = scopeScenario();

    $theirs = newDemoOrg();
    $foreign = Property::create(['name' => 'Foreign Villa', 'type' => 'villa', 'currency' => 'EUR', 'timezone' => 'UTC']);
    app(TenantContext::class)->set((string) $mine->id);

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $staff->getKey(), 'scope' => 'selected',
        'properties' => [(string) $b->id, (string) $foreign->id],
    ])->assertRedirect();

    $rows = DB::table('property_user')->where('user_id', (string) $staff->getKey())
        ->pluck('property_id')->map(fn ($id): string => (string) $id)->all();

    Assert::assertSame([(string) $b->id], $rows, '⚠️ another organisation’s property was written into the pivot');
});

// ⚠️⚠️ THE SERVICE'S OWN CROSS-ORG GUARD, TESTED AT ITS OWN LAYER — and this test exists because the
// first version of it was VACUOUS. Removing the guard inside PropertyScopeService left the suite GREEN,
// because the CONTROLLER independently filters the target by membership and the HTTP test never reached
// the service's check. Defence in depth needs a test PER LAYER, or all but the outermost are decorative
// and the next caller (a job, a command, a second controller) inherits nothing.
it('⚠️ refuses a foreign user at the SERVICE layer, not only at the controller', function () {
    ['org' => $mine, 'b' => $b] = scopeScenario();

    $theirs = newDemoOrg();
    $victim = operatorUser(['org' => $theirs], ['name' => 'Layer Victim'], 'Hotel Manager');
    app(TenantContext::class)->set((string) $mine->id);

    $threw = false;
    try {
        app(PropertyScopeService::class)->setScope($victim, [(string) $b->id]);
    } catch (RuntimeException $e) {
        $threw = true;
    }

    Assert::assertTrue($threw, '⚠️ the service scoped a user from another organisation when called directly');
    Assert::assertSame(0, DB::table('property_user')->where('user_id', (string) $victim->getKey())->count());
});

// ══ THE SCREEN ACTUALLY RENDERS — static checks cannot see a rendered page ═══════════════════════════
it('renders the scope editor on the settings page without leaking raw Blade', function () {
    ['admin' => $admin, 'staff' => $staff] = scopeScenario();

    $page = $this->actingAs($admin)->get('/app/settings')->assertOk();

    $page->assertSee('Property access', false);
    $page->assertSee('Sam Staff');                       // the roster spans the org, name-only (#28)
    $page->assertDontSee('@if (', false);                // uncompiled Blade would ship as literal text
    $page->assertDontSee('@foreach', false);
});

// ══ SELF-LOCKOUT + AUDIT ═════════════════════════════════════════════════════════════════════════════
it('⚠️ refuses to let an administrator change their own property access', function () {
    ['org' => $org, 'b' => $b, 'admin' => $admin] = scopeScenario();

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $admin->getKey(), 'scope' => 'selected', 'properties' => [(string) $b->id],
    ])->assertRedirect();

    Assert::assertSame(0, DB::table('property_user')->where('user_id', (string) $admin->getKey())->count(),
        '⚠️ an administrator scoped themselves — one mis-click from locking themselves out');
});

it('records an audit event naming the before and after scope', function () {
    ['org' => $org, 'b' => $b, 'admin' => $admin, 'staff' => $staff] = scopeScenario();

    $this->actingAs($admin)->post('/app/settings/team-scope', [
        'user_id' => (string) $staff->getKey(), 'scope' => 'selected', 'properties' => [(string) $b->id],
    ])->assertRedirect();

    $row = DB::table('audit_events')->where('action', 'user.property_scope_changed')
        ->where('subject_id', (string) $staff->getKey())->first();

    Assert::assertNotNull($row, 'an access change was not audited');
    Assert::assertStringContainsString((string) $b->id, (string) $row->after);
});

it('refuses a staff member without users.manage', function () {
    ['b' => $b, 'staff' => $staff] = scopeScenario();
    $other = operatorUser(['org' => newDemoOrg()], [], 'Front Desk');

    $this->actingAs($staff)->post('/app/settings/team-scope', [
        'user_id' => (string) $other->getKey(), 'scope' => 'selected', 'properties' => [(string) $b->id],
    ])->assertForbidden();
});
