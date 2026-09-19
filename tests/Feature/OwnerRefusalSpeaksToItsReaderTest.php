<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerTeamInviteService;
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
/**
 * ⚠️⚠️ A MEMBER OF THE ORGANISATION WHO HOLDS NO ROLE — REBUILT IN FIX 41, AND THE REBUILD IS THE POINT.
 *
 * ⚠️ WHAT THIS USED TO DO, AND WHY IT NO LONGER MAY. It called `removeRole()` on a user's only role and
 * called that "the product's own revoke shape". 🔬 F-56 made that comment false: `RoleAssignmentService
 * ::revoke()` now REFUSES to remove a member's last role, so no operator can produce this person that way.
 * The call would still have worked here — it goes straight at the model and never touches the service, so
 * the guard never fires and this file never went red — which is exactly why it had to change deliberately.
 * A fixture that manufactures a state the product forbids is a test asserting about a world that ended.
 *
 * ⚠️⚠️ AND THE READER IS STILL REACHABLE, WHICH WAS THE OPEN QUESTION. If F-56 had made this person
 * impossible, F-50's copy would address nobody and that would be a finding. It does not: 🔬 the product
 * creates an ACTIVE membership with NO role of its own accord. {@see OwnerTeamInviteService}
 * writes the membership when the invite is SENT and assigns a role only when it is ACCEPTED, and when the
 * role is not provisioned it logs `owner_team_invite.owner_role_not_provisioned` and returns having assigned
 * nothing at all — "Sub-user joined WITHOUT the owner-only role", in the product's own words.
 *
 * ⚠️ SO THIS IS NOW THE MORE FAITHFUL FIXTURE, not merely a legal one: after F-56, never-assigned is the
 * ONLY way a role-less member occurs, and that is what this builds.
 */
function orsRolelessMember(array $s): User
{
    $user = User::factory()->create(['name' => 'Unroled Uma', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // ⚠️ THE FIXTURE IS THE FINDING — assert the shape rather than trusting it, because a member who
    //    quietly held a role would send this whole file down the OTHER branch and pass for the wrong reason.
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    expect($user->fresh()->getRoleNames()->all())->toBe([], 'the reader under test must hold NO role');

    return $user->fresh();
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
    $body = $this->actingAs(orsRolelessMember($s))->get('/owner')->assertForbidden()->getContent();

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
    $user = orsRolelessMember($s);

    $this->actingAs($user)->get('/owner')->assertForbidden();

    [$href] = ErrorRealm::home('owner');
    expect($href)->toBe('/');

    // ⚠️ FOLLOWED AS THEM — not asserted as a string. A URL that refuses the holder is the finding itself.
    $this->actingAs($user)->get($href)->assertOk();
});

it('⚠️ and the doors that would have looped are genuinely shut to them — which is why `/` was chosen', function () {
    $s = seedScenario(1);
    $user = orsRolelessMember($s);

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
