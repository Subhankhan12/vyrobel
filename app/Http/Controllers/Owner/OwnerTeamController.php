<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePrimaryOwner;
use App\Models\Owner;
use App\Models\OwnerTeamInvite;
use App\Models\OwnerUser;
use App\Models\User;
use App\Services\OwnerTeamInviteService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLoginLifecycle;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * OWNER TEAM management writes (Phase 7c · SG3). The PRIMARY OWNER invites sub-users, changes their role,
 * removes them, and revokes pending invites. Every route in this controller is behind
 * {@see EnsurePrimaryOwner} — team management is held by NO owner-team role (not even a
 * co-owner), so it is gated on identity, not capability. This is the ROUTE layer of
 * {@see OwnerTeamRole::canAssign()}: self-escalation is structurally impossible.
 *
 * OWNER SCOPING: the target owner + every {member}/{invite} is resolved from {@see OwnerContext} and the
 * owner ids it holds — NEVER trusted from the request — so a mutation can only ever touch this owner's own
 * team. {@see OwnerUser}/{@see OwnerTeamInvite} are tenant-scoped; the owner_id filter adds the owner boundary.
 */
class OwnerTeamController extends Controller
{
    public function __construct(private readonly OwnerTeamInviteService $invites) {}

    /**
     * ⚠️⚠️ RE-AUTHENTICATE BEFORE CHANGING WHO CAN GET IN (audit follow-up, 2026-08-30).
     *
     * Inviting, promoting and removing all changed the set of people who can reach this owner's account with
     * NO password — gated only by {@see EnsurePrimaryOwner}. A hijacked session could mint a NEW AUTHENTICATED
     * PRINCIPAL, which SURVIVES every control applied afterwards: the password reset, the session revocation,
     * and the email change closed by H-3. That is why this outranks the payout destination.
     *
     * ⚠️ REPRODUCED, all three with no password: an invite row created for attacker@evil.test with a live
     * token; an `accountant` promoted to `co_owner` (a takeover needing NO invitation at all); and a member
     * removed (takeover by SUBTRACTION).
     *
     * ⚠️ THE BARE `current_password` — config/auth.php defines web · guest · admin and NO `owner` guard, and
     * /owner runs on plain `auth`, so a guard-qualified rule would silently match nothing. Same rule and same
     * shape as {@see OwnerPayoutMethodController} (§N.8f).
     *
     * ⚠️ CALLED AFTER OWNERSHIP IS RESOLVED, NEVER BEFORE. A cross-owner member id must 404 whether or not a
     * password was sent; validating first would make "wrong password" mean "that member exists and belongs to
     * someone" — an existence oracle. Order: capability (route middleware) → ownership → identity.
     */
    private function reauthenticate(Request $request): void
    {
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [],
            ['current_password' => 'current password'],
        );
    }

    /** Invite a sub-user (email + role) into the primary owner's team. */
    public function invite(Request $request, OwnerContext $context): RedirectResponse
    {
        $ownerId = $this->primaryOwnerId($context);
        $this->reauthenticate($request);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
            'role' => ['required', Rule::in(OwnerTeamRole::ALL)],
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $name = trim((string) ($validated['name'] ?? '')) ?: Str::before($email, '@');

        // Already an active member of THIS owner? Say so cleanly rather than re-inviting.
        $alreadyMember = OwnerUser::query()
            ->where('owner_id', $ownerId)->where('status', 'active')
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->exists();
        if ($alreadyMember) {
            return back()->with('team_error', 'That person is already on your team.');
        }

        try {
            $this->invites->invite($ownerId, $name, $email, (string) $validated['role']);
        } catch (RuntimeException $e) {
            return back()->with('team_error', $e->getMessage());
        }

        return back()->with('team_status', "Invite sent to {$email}.");
    }

    /** Change a member's owner-team role. */
    public function updateRole(Request $request, OwnerContext $context, string $member): RedirectResponse
    {
        $ownerIds = $this->ownerIds($context);

        $validated = $request->validate([
            'role' => ['required', Rule::in(OwnerTeamRole::ALL)],
        ]);

        $ownerUser = OwnerUser::query()->whereKey($member)->whereIn('owner_id', $ownerIds)->firstOrFail();
        $this->reauthenticate($request);   // ⚠️ AFTER the 404 — no existence oracle on a cross-owner member

        $ownerUser->update(['role' => (string) $validated['role']]);

        return back()->with('team_status', 'Role updated.');
    }

    /** Remove a member — revokes access cleanly (and disables an orphaned login so it can't log in). */
    public function remove(Request $request, OwnerContext $context, string $member): RedirectResponse
    {
        $ownerIds = $this->ownerIds($context);

        $ownerUser = OwnerUser::query()->whereKey($member)->whereIn('owner_id', $ownerIds)->firstOrFail();
        $this->reauthenticate($request);   // ⚠️ AFTER the 404 — removal is takeover by subtraction

        $userId = (string) $ownerUser->user_id;

        DB::transaction(function () use ($ownerUser, $userId): void {
            $ownerUser->delete();   // soft-remove — the owner scope no longer resolves them
            $this->disableIfOrphaned($userId);
        });

        return back()->with('team_status', 'Member removed.');
    }

    /** Revoke a pending invite (its token can never be redeemed). */
    public function revokeInvite(OwnerContext $context, string $invite): RedirectResponse
    {
        $ownerIds = $this->ownerIds($context);

        $row = OwnerTeamInvite::query()->whereKey($invite)->whereIn('owner_id', $ownerIds)->firstOrFail();
        $this->invites->revoke($row);

        return back()->with('team_status', 'Invite revoked.');
    }

    /** Re-send a pending invite — a fresh token + extended expiry, emailed again (owner-scoped, pending only). */
    public function resendInvite(OwnerContext $context, string $invite): RedirectResponse
    {
        $ownerIds = $this->ownerIds($context);

        $row = OwnerTeamInvite::query()->whereKey($invite)->whereIn('owner_id', $ownerIds)->where('status', 'pending')->firstOrFail();
        $this->invites->resend($row);

        return back()->with('team_status', 'Invite re-sent to '.$row->email.'.');
    }

    /**
     * Disable a login that, after a removal, has NO remaining reason to sign in — no active owner-team
     * membership anywhere, is not a primary owner, and holds no other role. This makes "removed → can't log
     * in" real. A user still tied to another owner / a real account is LEFT ACTIVE (fail-safe on shared state).
     */
    /**
     * ⚠️ MOVED, NOT COPIED (2026-08-31). Owner REMOVAL now makes the same decision for every sub-user at once,
     * so the rule lives in {@see OwnerLoginLifecycle} and both paths ask it. Two copies of "may this login be
     * disabled?" is exactly the drift §N.8f forbids.
     */
    private function disableIfOrphaned(string $userId): void
    {
        OwnerLoginLifecycle::disableIfOrphaned($userId);
    }

    /**
     * The single owner the primary owner invites INTO. A primary owner nearly always holds one Owner; if
     * several, we invite into the first (the team screen lists members across all their owners).
     */
    private function primaryOwnerId(OwnerContext $context): string
    {
        $ids = $this->ownerIds($context);

        return $ids[0];
    }

    /**
     * The owner ids the acting user may manage the TEAM of — ONLY the owners they are the PRIMARY of (P1-1).
     * Team management is per-owner: a user who is primary of Owner A but merely a sub-user of Owner B can never
     * touch B's team, even though EnsurePrimaryOwner (a coarse "primary of some owner" gate) let the route
     * through. A {member}/{invite} outside this set resolves to a 404 (firstOrFail), fail-closed.
     *
     * @return list<string>
     */
    private function ownerIds(OwnerContext $context): array
    {
        $ids = $context->primaryOwnerIds();
        if ($ids === []) {
            abort(403);
        }

        return $ids;
    }
}
