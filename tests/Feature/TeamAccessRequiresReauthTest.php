<?php

use App\Models\Owner;
use App\Models\OwnerTeamInvite;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ══ AUDIT FOLLOW-UP 2026-08-30 — RE-AUTHENTICATE BEFORE CHANGING WHO CAN GET IN ══════════════════════
//
// ⚠️ Inviting, promoting and removing changed the set of people who can reach an owner's account with NO
// password. This OUTRANKS the payout destination: a redirected bank account moves money, but a NEW
// AUTHENTICATED PRINCIPAL SURVIVES EVERY CONTROL APPLIED AFTERWARDS — the password reset, the session
// revocation, and the email change closed by H-3.
//
// ⚠️ REPRODUCED, all three with no password: an invite row created for attacker@evil.test carrying a live
// token; an `accountant` promoted to `co_owner` (a takeover needing NO invitation at all); and a member
// removed (takeover by SUBTRACTION). Each is proven separately below — the payout pass showed that covering
// only the first action leaves the real attack open.
//
// ⚠️ ORDER: capability (EnsurePrimaryOwner, route middleware) → OWNERSHIP (owner-scoped firstOrFail) →
// IDENTITY (current_password). A cross-owner member id must 404 with a valid password too, or the
// validation error becomes an existence oracle.

/** A primary owner with a known password and one sub-user to act on. */
function teamReauthActor(): array
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->changePasswordTo('secret-Passw0rd!');
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    $memberUser = User::factory()->create();
    $member = OwnerUser::create([
        'owner_id' => $ownerId, 'user_id' => $memberUser->id,
        'role' => OwnerTeamRole::ACCOUNTANT, 'status' => 'active',
    ]);

    return ['user' => $user->fresh(), 'ownerId' => $ownerId, 'member' => $member, 'scenario' => $s];
}

// ── 1. ⛔ INVITE — mints a new principal ─────────────────────────────────────────────────────────────
it('refuses a team invite without the current password', function () {
    Mail::fake();
    $c = teamReauthActor();

    $this->actingAs($c['user'])->from(route('owner.team'))
        ->post(route('owner.team.invite'), ['email' => 'attacker@evil.test', 'role' => OwnerTeamRole::CO_OWNER])
        ->assertSessionHasErrors('current_password');

    expect(OwnerTeamInvite::withoutGlobalScopes()->where('email', 'attacker@evil.test')->count())->toBe(0);
});

it('sends a team invite with the correct password', function () {
    Mail::fake();
    $c = teamReauthActor();

    $this->actingAs($c['user'])->from(route('owner.team'))
        ->post(route('owner.team.invite'), [
            'current_password' => 'secret-Passw0rd!',
            'email' => 'colleague@example.test', 'role' => OwnerTeamRole::ACCOUNTANT,
        ])->assertSessionHasNoErrors();

    expect(OwnerTeamInvite::withoutGlobalScopes()->where('email', 'colleague@example.test')->count())->toBe(1);
});

// ── 2. ⛔ ROLE CHANGE — a takeover with NO invitation at all ─────────────────────────────────────────
it('refuses a role change without the current password', function () {
    $c = teamReauthActor();

    $this->actingAs($c['user'])->from(route('owner.team'))
        ->patch(route('owner.team.members.role', $c['member']->id), ['role' => OwnerTeamRole::CO_OWNER])
        ->assertSessionHasErrors('current_password');

    expect((string) $c['member']->fresh()->role)->toBe(OwnerTeamRole::ACCOUNTANT);
});

it('changes a role with the correct password', function () {
    $c = teamReauthActor();

    $this->actingAs($c['user'])->from(route('owner.team'))
        ->patch(route('owner.team.members.role', $c['member']->id), [
            'current_password' => 'secret-Passw0rd!', 'role' => OwnerTeamRole::CO_OWNER,
        ])->assertSessionHasNoErrors();

    expect((string) $c['member']->fresh()->role)->toBe(OwnerTeamRole::CO_OWNER);
});

// ── 3. ⛔ REMOVE — takeover by SUBTRACTION ───────────────────────────────────────────────────────────
it('refuses a member removal without the current password', function () {
    $c = teamReauthActor();

    $this->actingAs($c['user'])->from(route('owner.team'))
        ->delete(route('owner.team.members.remove', $c['member']->id))
        ->assertSessionHasErrors('current_password');

    expect(OwnerUser::withoutGlobalScopes()->withTrashed()->find($c['member']->id)->trashed())->toBeFalse();
});

it('removes a member with the correct password', function () {
    $c = teamReauthActor();

    $this->actingAs($c['user'])->from(route('owner.team'))
        ->delete(route('owner.team.members.remove', $c['member']->id), ['current_password' => 'secret-Passw0rd!'])
        ->assertSessionHasNoErrors();

    expect(OwnerUser::withoutGlobalScopes()->withTrashed()->find($c['member']->id)->trashed())->toBeTrue();
});

// ── 4. ⛔ NO EXISTENCE ORACLE — ownership answers before identity ────────────────────────────────────
it('returns the same not-found for a cross-owner member with AND without a password', function () {
    $a = teamReauthActor();

    // A second owner with their own team member.
    $sB = seedScenario(1);
    $userB = ownerRoleUser($sB, 0);
    $ownerB = (string) $sB['units'][0]->fresh()->owner_id;
    $foreign = OwnerUser::create([
        'owner_id' => $ownerB, 'user_id' => User::factory()->create()->id,
        'role' => OwnerTeamRole::VIEW_ONLY, 'status' => 'active',
    ]);

    $this->actingAs($a['user'])
        ->patch(route('owner.team.members.role', $foreign->id), ['role' => OwnerTeamRole::CO_OWNER])
        ->assertNotFound();

    $this->actingAs($a['user'])
        ->patch(route('owner.team.members.role', $foreign->id), [
            'current_password' => 'secret-Passw0rd!', 'role' => OwnerTeamRole::CO_OWNER,
        ])->assertNotFound();

    $this->actingAs($a['user'])
        ->delete(route('owner.team.members.remove', $foreign->id), ['current_password' => 'secret-Passw0rd!'])
        ->assertNotFound();

    expect((string) $foreign->fresh()->role)->toBe(OwnerTeamRole::VIEW_ONLY);
    expect($foreign->fresh()->trashed())->toBeFalse();
});

// ── 5. the forms carry the field — a rule with no field always fails ────────────────────────────────
it('renders a password field for every team action', function () {
    $c = teamReauthActor();

    $html = $this->actingAs($c['user'])->get(route('owner.team'))->assertOk()->getContent();

    expect(substr_count($html, 'name="current_password"'))->toBeGreaterThanOrEqual(3);
});

// ── 6. 🔒 THE PRIMARY OWNER IS NOT A REMOVABLE TEAM MEMBER — no last-owner lockout ──────────────────
// Primary ownership is `owners.user_id`, a COLUMN, not a role: remove() only touches owner_users rows and
// updateRole() only writes owner_users.role, so the account holder can be neither removed nor demoted.
it('cannot remove or demote the primary owner, so the account can never be locked out', function () {
    $c = teamReauthActor();
    $primaryUserId = (string) Owner::withoutGlobalScopes()->find($c['ownerId'])->user_id;

    $this->actingAs($c['user'])
        ->delete(route('owner.team.members.remove', $primaryUserId), ['current_password' => 'secret-Passw0rd!'])
        ->assertNotFound();

    // ⚠️⚠️ THE STATE IS `trashed()`, AND THAT IS WHY THIS IS ASSERTED AND NOT MERE EXISTENCE.
    //    `withoutGlobalScopes()` drops the SoftDeletingScope along with the tenant one, so `find()` returns a
    //    REMOVED owner exactly as it returns a live one — this assertion passed against a removal that had
    //    gone through, DEMONSTRATED by planting a trashed row and watching it stay green. The same fix was
    //    already applied to the `OwnerUser` assertions above; the `Owner` one was missed.
    $primary = Owner::withoutGlobalScopes()->withTrashed()->find($c['ownerId']);
    expect($primary->trashed())->toBeFalse()          // not removed …
        ->and($primary->user_id)->not->toBeNull();    // … and not demoted
});
