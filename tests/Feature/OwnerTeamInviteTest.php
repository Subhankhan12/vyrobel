<?php

use App\Mail\OwnerTeamInviteMail;
use App\Models\OwnerTeamInvite;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// PHASE 7c · SG3 — the OWNER-TEAM invite flow + team management. A PRIMARY OWNER (only) invites sub-users
// by email + role → a hardened, hashed, single-use, scoped invite → the invitee sets a password and joins
// THAT owner's team at THAT role. Change-role / remove revoke access cleanly. Role integrity is structural
// (EnsurePrimaryOwner + canAssign): no sub-user can manage the team or escalate.

/** Send an invite through the real route as the primary owner and return the emailed plaintext token. */
function sendTeamInvite(User $owner, string $email, string $role): string
{
    $token = null;
    Mail::fake();
    test()->actingAs($owner)->post('/owner/team/invite', ['email' => $email, 'name' => 'Sam Delegate', 'role' => $role])
        ->assertRedirect();
    Mail::assertSent(OwnerTeamInviteMail::class, function (OwnerTeamInviteMail $mail) use (&$token): bool {
        $token = $mail->token;

        return true;
    });

    return (string) $token;
}

// ── 1. 🔒 INVITE → CLAIM joins the sub-user under EXACTLY their owner at the assigned role ────────────
it('invites a sub-user who claims and joins the right owner at the right role', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    $token = sendTeamInvite($owner, 'delegate@example.test', OwnerTeamRole::ACCOUNTANT);

    // A PENDING invite grants NO access yet — no active owner_users row exists.
    expect(OwnerUser::where('owner_id', $ownerId)->where('status', 'active')->count())->toBe(0);
    $invite = OwnerTeamInvite::where('email', 'delegate@example.test')->firstOrFail();
    expect($invite->status)->toBe('pending')->and((string) $invite->owner_id)->toBe($ownerId);

    // Claim: the set-password page renders, then the POST activates + joins + logs in → /owner.
    $this->get('/team-invite/'.$token)->assertOk()->assertSee('Sam Delegate');
    $this->post('/team-invite/'.$token, ['password' => 'sub-user-pass-9', 'password_confirmation' => 'sub-user-pass-9'])
        ->assertRedirect(route('owner.dashboard'));

    // Joined under EXACTLY the invite's owner, at the invited role, ACTIVE; the token is spent.
    $member = OwnerUser::where('owner_id', $ownerId)->firstOrFail();
    expect($member->status)->toBe('active')
        ->and($member->role)->toBe(OwnerTeamRole::ACCOUNTANT)
        ->and((string) $member->organization_id)->toBe((string) $invite->organization_id);
    expect($invite->fresh()->status)->toBe('accepted');
    expect(User::where('email', 'delegate@example.test')->value('status'))->toBe('active');
});

// ── 2. 🔒 NO ENUMERATION — a bad / spent / expired token yields the generic invalid page ─────────────
it('shows a generic invalid page for a bad or spent token', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $token = sendTeamInvite($owner, 'delegate@example.test', OwnerTeamRole::VIEW_ONLY);

    // Unknown token — generic, reveals nothing.
    $this->get('/team-invite/totally-made-up-token')->assertOk()->assertSee('link')->assertDontSee('Sam Delegate');

    // Spend it, then a second use is refused with the SAME generic page (single-use).
    $this->post('/team-invite/'.$token, ['password' => 'sub-user-pass-9', 'password_confirmation' => 'sub-user-pass-9']);
    $this->get('/team-invite/'.$token)->assertOk()->assertDontSee('Sam Delegate');
    $this->post('/team-invite/'.$token, ['password' => 'again-pass-9', 'password_confirmation' => 'again-pass-9'])
        ->assertDontSee('Sam Delegate');
});

// ── 3. 🔒 THE TEAM SCREEN + EVERY MUTATION IS PRIMARY-OWNER ONLY (a sub-user is 403) ────────────────
it('refuses the team screen and every mutation to a sub-user', function () {
    $s = seedScenario(1);
    ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $co = ownerSubUser($s, $ownerId, OwnerTeamRole::CO_OWNER);   // even a CO-OWNER can't manage the team

    $this->actingAs($co)->get('/owner/team')->assertForbidden();
    $this->actingAs($co)->post('/owner/team/invite', ['email' => 'x@example.test', 'role' => OwnerTeamRole::VIEW_ONLY])->assertForbidden();
    $this->actingAs($co)->patch('/owner/team/members/anything', ['role' => OwnerTeamRole::CO_OWNER])->assertForbidden();
    $this->actingAs($co)->delete('/owner/team/members/anything')->assertForbidden();
});

// ── 4. ✅ THE PRIMARY OWNER MANAGES THE TEAM — screen + change-role + remove ─────────────────────────
it('lets the primary owner see the team, change a role, and remove a member', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $view = ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY);
    $member = OwnerUser::where('owner_id', $ownerId)->where('user_id', $view->id)->firstOrFail();

    // The screen lists the member.
    $this->actingAs($owner)->get('/owner/team')->assertOk()->assertSee('Team & access');

    // Change role → view_only becomes accountant.
    $this->actingAs($owner)->patch('/owner/team/members/'.$member->id, ['role' => OwnerTeamRole::ACCOUNTANT])->assertRedirect();
    expect($member->fresh()->role)->toBe(OwnerTeamRole::ACCOUNTANT);

    // Remove → the membership is gone AND the orphaned login is disabled (can't log in).
    $this->actingAs($owner)->delete('/owner/team/members/'.$member->id)->assertRedirect();
    expect(OwnerUser::withTrashed()->find($member->id)?->trashed())->toBeTrue();
    expect($view->fresh()->status)->toBe('disabled');
});

// ── 5. 🔒 A REMOVED SUB-USER CANNOT LOG IN ──────────────────────────────────────────────────────────
it('blocks login for a removed sub-user', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $token = sendTeamInvite($owner, 'delegate@example.test', OwnerTeamRole::VIEW_ONLY);
    $this->post('/team-invite/'.$token, ['password' => 'sub-user-pass-9', 'password_confirmation' => 'sub-user-pass-9']);

    $member = OwnerUser::where('owner_id', $ownerId)->firstOrFail();
    $this->actingAs($owner)->delete('/owner/team/members/'.$member->id)->assertRedirect();

    // The active-only login guard now refuses them: the credentials no longer sign in.
    auth()->guard('web')->logout();
    $this->post('/login', ['email' => 'delegate@example.test', 'password' => 'sub-user-pass-9']);
    expect(auth()->guard('web')->check())->toBeFalse();
});

// ── 6. 🔒 OWNER-SCOPED — a primary owner can't touch ANOTHER owner's member (404) ───────────────────
it('scopes team mutations to the primary owner’s own owner — a cross-owner member 404s', function () {
    $s = seedScenario(2);
    $ownerA = ownerRoleUser($s, 0);
    ownerRoleUser($s, 1);
    $ownerBId = (string) $s['units'][1]->fresh()->owner_id;
    $bMember = ownerSubUser($s, $ownerBId, OwnerTeamRole::VIEW_ONLY);
    $bRow = OwnerUser::where('owner_id', $ownerBId)->where('user_id', $bMember->id)->firstOrFail();

    // Owner A tries to change / remove owner B's member — resolved owner-scoped → 404, never touches B.
    $this->actingAs($ownerA)->patch('/owner/team/members/'.$bRow->id, ['role' => OwnerTeamRole::CO_OWNER])->assertNotFound();
    $this->actingAs($ownerA)->delete('/owner/team/members/'.$bRow->id)->assertNotFound();
    expect($bRow->fresh()->role)->toBe(OwnerTeamRole::VIEW_ONLY);   // untouched
});
