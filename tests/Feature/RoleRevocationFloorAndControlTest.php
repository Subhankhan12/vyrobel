<?php

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use App\Support\Authorization\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ THE FLOOR AND THE DOOR — 2026-08-30 ═════════════════════════════════════════════════════════════
//
// ⚠️ THE FLOOR LANDED FIRST, then the control — because until now the ABSENCE of a revoke control was what
// partly mitigated the ABSENCE of a last-admin guard: `app.roles.revoke` was routed and gated but reachable
// only by a hand-made POST.
//
// ⚠️ AND THE OLD FLOOR WAS AN ACCIDENT — a side effect of the no-self-management rule, which answers a
// completely different question. It is now explicit, so relaxing that rule cannot silently reopen the hole.

function fdTeam(array $s): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

function fdMap(array $s): array
{
    fdTeam($s);

    return RolePermissions::mapFor((string) $s['org']->id);
}

/** An org whose ONLY role-managers are two Group Admins — the shape where the floor is load-bearing. */
function fdTwoManagerOrg(array $s): array
{
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $a = operatorUser($s, ['name' => 'Ana Admin'], 'Group Admin');
    $b = operatorUser($s, ['name' => 'Ben Admin'], 'Group Admin');

    app(RolePermissionService::class)->save($owner->fresh(), 'Group Admin', [...fdMap($s)['Group Admin'], 'roles.manage']);
    DB::table('model_has_roles')->where('model_id', $owner->getKey())->delete();   // no wildcard holder left
    fdTeam($s);

    return ['a' => $a->fresh(), 'b' => $b->fresh()];
}

// ══ PART 1 · THE FLOOR ══════════════════════════════════════════════════════════════════════════════

// ── 1. ⚠️ THE ONE DEFINITION, ASKED BOTH WAYS ───────────────────────────────────────────────────────
it('answers who would still manage access, for both hypotheticals', function () {
    $s = seedScenario(1);
    ['a' => $a, 'b' => $b] = fdTwoManagerOrg($s);
    $map = fdMap($s);

    // No hypothetical: the real managers.
    expect(RolePermissions::roleManagersAfter($map))->toHaveCount(2);

    // ⚠️ THE MATRIX's question — "if Group Admin stopped granting roles.manage…"
    expect(RolePermissions::roleManagersAfter($map, withoutRole: 'Group Admin'))->toBe([]);

    // ⚠️ REVOCATION's question — "if this user stopped holding Group Admin…" One of two still leaves one.
    expect(RolePermissions::roleManagersAfter($map, withoutUserRole: [(string) $a->getKey(), 'Group Admin']))
        ->toBe([(string) $b->getKey()]);
});

// ── 2. ⚠️ THE MATRIX PATH STILL REFUSES, THROUGH THE SHARED DEFINITION ──────────────────────────────
it('refuses a matrix edit that would leave nobody able to manage access', function () {
    $s = seedScenario(1);
    ['a' => $a] = fdTwoManagerOrg($s);

    $without = array_values(array_diff(fdMap($s)['Group Admin'], ['roles.manage']));
    expect(fn () => app(RolePermissionService::class)->save($a->fresh(), 'Group Admin', $without))
        ->toThrow(RoleAssignmentException::class, 'At least one role must keep permission-management access');

    expect(RolePermissions::roleManagersAfter(fdMap($s)))->toHaveCount(2);   // nothing moved
});

// ── 3. ⚠️⚠️ ORDINARY ADMINISTRATION IS NOT BLOCKED — the whole risk of a floor ──────────────────────
// Removing a departing colleague must work whenever someone else can still manage access. This also proves
// revoke() CONSULTS the floor: force the floor to answer "nobody survives" and this test is the one that dies.
it('lets an administrator revoke a colleague while another manager remains', function () {
    $s = seedScenario(1);
    ['a' => $a, 'b' => $b] = fdTwoManagerOrg($s);

    app(RoleAssignmentService::class)->revoke($a->fresh(), $b->fresh(), 'Group Admin');
    fdTeam($s);

    expect($b->fresh()->getRoleNames()->all())->toBe([])
        ->and(RolePermissions::roleManagersAfter(fdMap($s)))->toBe([(string) $a->getKey()]);
});

// ── 4. ⚠️⚠️ THE HONEST PIN — the floor is NOT what refuses on the revoke path today ─────────────────
// ⚠️ MEASURED, not assumed. To revoke a role you must hold everything it grants (the subset rule), so an
// actor able to revoke a role-managing role IS a role manager and therefore survives; and you cannot revoke
// your own. The floor is therefore currently UNREACHABLE here — belt for a brace that already holds. It is
// built anyway so that relaxing either older rule cannot silently reopen the hole, and this test records
// exactly WHICH rule refuses, so the day that changes, it changes here first.
it('records which rule actually refuses an attempted lockout on the revoke path', function () {
    $s = seedScenario(1);
    ['a' => $a, 'b' => $b] = fdTwoManagerOrg($s);
    app(RoleAssignmentService::class)->revoke($a->fresh(), $b->fresh(), 'Group Admin');   // A is now the last
    fdTeam($s);

    expect(RolePermissions::roleManagersAfter(fdMap($s), withoutUserRole: [(string) $a->getKey(), 'Group Admin']))
        ->toBe([]);   // ⚠️ the floor WOULD fire — the state really is a lockout

    // …but neither reachable actor gets that far.
    expect(fn () => app(RoleAssignmentService::class)->revoke($a->fresh(), $a->fresh(), 'Group Admin'))
        ->toThrow(RoleAssignmentException::class, 'You can’t change your own role');
    expect(fn () => app(RoleAssignmentService::class)->revoke($b->fresh(), $a->fresh(), 'Group Admin'))
        ->toThrow(RoleAssignmentException::class, 'You can’t assign a role with more access than your own.');

    expect($a->fresh()->getRoleNames()->all())->toBe(['Group Admin']);   // the last manager still stands
});

// ══ PART 2 · THE CONTROL (built second) ═════════════════════════════════════════════════════════════

/** The rendered revoke form for one holder, cut at its own </form>. */
function fdRevokeForm(string $html, string $userId): string
{
    foreach (array_slice(explode('<form', $html), 1) as $chunk) {
        $body = explode('</form>', $chunk)[0];
        if (str_contains($body, 'roles/revoke') && str_contains($body, 'value="'.$userId.'"')) {
            return $body;
        }
    }

    return '';
}

// ── 5. ⚠️⚠️ PROVEN FROM A RENDERED PAGE, THEN FOLLOWED — not "the route works" ──────────────────────
// ⚠️ The route always worked. That IS M-14. So this fetches the page, finds the control, reads the values
// OUT of the markup, and posts exactly those — the H-2 precedent.
it('renders a revoke control and revoking through it genuinely removes the role', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin->changePasswordTo('secret-Passw0rd!');
    $target = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');

    $html = $this->actingAs($admin->fresh())->get('/app/roles')->assertOk()->getContent();
    $form = fdRevokeForm($html, (string) $target->getKey());

    expect($form)->not->toBe('');
    // ⚠️ The confirmation names WHO loses WHAT.
    expect(str_contains($form, 'Finn Desk'))->toBeTrue('the confirmation must name the person');
    expect(str_contains($form, 'will lose the'))->toBeTrue('the confirmation must say what is lost');
    expect(str_contains($form, 'Front Desk'))->toBeTrue('the confirmation must name the role');
    expect(str_contains($form, 'name="current_password"'))->toBeTrue('the control must carry the re-auth the route requires');

    // Read the payload OUT of the rendered markup and follow it.
    preg_match('/name="_token" value="([^"]+)"/', $form, $t);
    preg_match('/name="user_id" value="([^"]+)"/', $form, $u);
    preg_match('/name="role" value="([^"]+)"/', $form, $r);

    $this->actingAs($admin->fresh())->from(route('app.roles'))
        ->post(route('app.roles.revoke'), [
            '_token' => $t[1], 'user_id' => $u[1], 'role' => $r[1], 'current_password' => 'secret-Passw0rd!',
        ])->assertRedirect(route('app.roles'));

    fdTeam($s);
    expect($target->fresh()->getRoleNames()->all())->toBe([]);
});

// ── 6. The page's own promise is now true ───────────────────────────────────────────────────────────
// ⚠️ The Roles page has always said: "to move a person off a role, revoke it from them in the assignment
// section — they keep their other roles." Both halves are now real: the control lives in that section, and
// revocation genuinely leaves other roles alone.
it('makes the page’s existing promise true, including “they keep their other roles”', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin->changePasswordTo('secret-Passw0rd!');
    $target = operatorUser($s, ['name' => 'Multi Role'], 'Front Desk');
    app(RoleAssignmentService::class)->assign($admin->fresh(), $target->fresh(), 'Housekeeping');

    $html = $this->actingAs($admin->fresh())->get('/app/roles')->assertOk()->getContent();
    expect(str_contains($html, 'revoke it from them in the assignment section'))
        ->toBeTrue('the promise this control exists to honour must still be on the page');
    expect(str_contains($html, 'id="revoke"'))->toBeTrue('…and the section it points at must exist');

    $this->actingAs($admin->fresh())->from(route('app.roles'))
        ->post(route('app.roles.revoke'), [
            'user_id' => $target->id, 'role' => 'Front Desk', 'current_password' => 'secret-Passw0rd!',
        ])->assertRedirect(route('app.roles'));

    fdTeam($s);
    expect($target->fresh()->getRoleNames()->all())->toBe(['Housekeeping']);   // the other role survives
});

// ── 7. ⚠️ THE CONTROL DOES NOT INVITE A REFUSAL IT COULD PREDICT ────────────────────────────────────
it('offers no revoke control for the actor’s own roles, and says why', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');

    $html = $this->actingAs($admin->fresh())->get('/app/roles')->assertOk()->getContent();

    expect(fdRevokeForm($html, (string) $admin->getKey()))->toBe('');
    expect(str_contains($html, 'Ask another administrator to change your own roles.'))
        ->toBeTrue('the absent control must be explained, not silently missing');
});

// ── 8. ⚠️ PER-ROW PASSWORD IDS — the defect the team-scope editor shipped two passes ago ────────────
it('gives every revoke row its own password field id', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');
    operatorUser($s, ['name' => 'Hank Housekeeper'], 'Housekeeping');

    $html = $this->actingAs($admin->fresh())->get('/app/roles')->assertOk()->getContent();

    preg_match_all('/id="(revoke_pw_[^"]+)"/', $html, $m);
    expect(count($m[1]))->toBeGreaterThanOrEqual(2)
        ->and(array_unique($m[1]))->toHaveCount(count($m[1]));
});
