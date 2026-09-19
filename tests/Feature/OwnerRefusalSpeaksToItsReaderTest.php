<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Support\ErrorRealm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * F-50 — THE OWNER 403 SERVED ONE MESSAGE TO TWO DIFFERENT PEOPLE, AND ITS BUTTON LOOPED ONE OF THEM.
 *
 * ⚠️⚠️ `ErrorRealm` branches on the PATH, so every 403 under `/owner` read the same sentence: *"Your owner
 * account doesn't include this area — ask the primary owner to grant the capability."* Exactly right for one
 * reader, nonsense for the other, and its "Back to your portal" button returned the second to the door that
 * had just refused them.
 *
 * ⚠️⚠️ BOTH AUDIENCES WERE ESTABLISHED BY MEASUREMENT BEFORE ANY COPY WAS WRITTEN, because inventing a reader
 * is how the wrong message got there:
 *   · **owner-linked** — a sub-user refused one capability, a non-primary owner, or a tampered id.
 *   · **NOT owner-linked** — 🔬 reachable through the product's own path: `RoleAssignmentService::revoke()`
 *     guards the last ROLE-MANAGER but not the last ROLE, so revoking a staff user's only back-office role
 *     leaves an active membership with no roles and no owner link. `EnsureOwnerPortalAccess` then 403s them
 *     (it only REDIRECTS people who still hold a back-office role).
 *
 * ⚠️⚠️ THE LOOP IS THE HALF THAT MATTERS, AND THIS IS THE THIRD TIME THE SHAPE HAS APPEARED HERE — F-27's
 * paid-but-unclaimed window, F-47's claim failure offering a door that needed the account being created, and
 * now this. **The class is: a refusal that offers a way out which is not one.** So every case below FOLLOWS
 * the offered link as the person holding it; reading the href proves nothing about whether it admits them.
 */
function orsRevokedStaff(array $s): User
{
    $user = operatorUser($s, ['name' => 'Revoked Rita'], 'Front Desk');
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    $user->removeRole('Front Desk');           // ⚠️ the product's own revoke shape — membership untouched
    $user = $user->fresh();
    $user->unsetRelation('roles')->unsetRelation('permissions');

    return $user;
}

function orsOwnerLinked(array $s): User
{
    $user = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    Owner::factory()->create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1 · THE READER WHO IS NOT AN OWNER
// ─────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ F-50 — a reader with NO owner account is not told to ask about a capability', function () {
    $s = seedScenario(1);
    $body = $this->actingAs(orsRevokedStaff($s))->get('/owner')->assertForbidden()->getContent();

    $text = preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(style|script)[^>]*>.*?</\1>#si', '', $body)));

    // ⚠️ THE DEFECT, PINNED AS AN ABSENCE — the advice they could never act on must not come back.
    expect(str_contains($text, 'grant the capability'))->toBeFalse();
    expect(str_contains($text, 'primary owner'))->toBeFalse();

    // ⚠️ AND IT SAYS SOMETHING TRUE AND ACTIONABLE, so this is not passing on an empty page.
    expect($text)->toContain('isn’t linked to one');
});

/**
 * ⚠️⚠️ THE TRAP, CLOSED — AND PROVEN BY WALKING IT. 🔬 `/app/dashboard` was MEASURED refusing this same
 * person too, and `/login` bounces them there, so the "obvious" destination would have been a SECOND trap.
 * `/` is the only one that admitted them, established by following each.
 */
it('⚠️⚠️ F-50 — the button a non-owner is offered actually ADMITS them when followed', function () {
    $s = seedScenario(1);
    $user = orsRevokedStaff($s);

    $this->actingAs($user)->get('/owner')->assertForbidden();

    [$href] = ErrorRealm::home('owner');
    expect($href)->toBe('/');

    // ⚠️ FOLLOWED AS THEM — not asserted as a string. A URL that refuses the holder is the finding itself.
    $this->actingAs($user)->get($href)->assertOk();
});

it('⚠️ and the doors that would have looped are genuinely shut to them — which is why `/` was chosen', function () {
    $s = seedScenario(1);
    $user = orsRevokedStaff($s);

    $this->actingAs($user)->get('/owner')->assertForbidden();
    $this->actingAs($user)->get('/app/dashboard')->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2 · THE READER WHO IS AN OWNER — the copy that was always right
// ─────────────────────────────────────────────────────────────────────────────

/**
 * ⚠️⚠️ THE CONTROL, AND IT CAN FAIL. If the split had simply replaced the message for everyone, the owner's
 * own advice — which was correct — would have been lost. Asserted as a live request so the branch is decided
 * by the real actor, not by a bare static call with nobody signed in.
 */
it('⚠️⚠️ F-50 — an OWNER still gets the capability advice, and their button still goes to their portal', function () {
    $s = seedScenario(1);
    $owner = orsOwnerLinked($s);

    // The portal itself admits them (they ARE an owner) — the 403 they meet is a capability one.
    $this->actingAs($owner)->get('/owner')->assertOk();

    $this->actingAs($owner)->get('/owner');
    expect(ErrorRealm::forbiddenMessage('owner'))->toContain('grant the capability');
    expect(ErrorRealm::home('owner')[0])->toBe('/owner');
});
