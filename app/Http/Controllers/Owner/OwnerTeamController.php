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

    /** Invite a sub-user (email + role) into the primary owner's team. */
    public function invite(Request $request, OwnerContext $context): RedirectResponse
    {
        $ownerId = $this->primaryOwnerId($context);

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
        $ownerUser->update(['role' => (string) $validated['role']]);

        return back()->with('team_status', 'Role updated.');
    }

    /** Remove a member — revokes access cleanly (and disables an orphaned login so it can't log in). */
    public function remove(OwnerContext $context, string $member): RedirectResponse
    {
        $ownerIds = $this->ownerIds($context);

        $ownerUser = OwnerUser::query()->whereKey($member)->whereIn('owner_id', $ownerIds)->firstOrFail();
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

    /**
     * Disable a login that, after a removal, has NO remaining reason to sign in — no active owner-team
     * membership anywhere, is not a primary owner, and holds no other role. This makes "removed → can't log
     * in" real. A user still tied to another owner / a real account is LEFT ACTIVE (fail-safe on shared state).
     */
    private function disableIfOrphaned(string $userId): void
    {
        $user = User::query()->find($userId);
        if ($user === null || $user->status !== 'active') {
            return;
        }

        $stillSubUser = OwnerUser::query()->where('user_id', $userId)->where('status', 'active')->exists();
        $isPrimaryOwner = Owner::query()->where('user_id', $userId)->exists();
        $hasRole = $user->roles()->where('name', '!=', 'External Owner')->exists();

        if (! $stillSubUser && ! $isPrimaryOwner && ! $hasRole) {
            $user->update(['status' => 'disabled']);
        }
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
     * The owner ids the acting primary owner controls. EnsurePrimaryOwner guarantees isPrimaryOwner(), so
     * this is non-empty; the abort is a belt-and-braces fail-closed.
     *
     * @return list<string>
     */
    private function ownerIds(OwnerContext $context): array
    {
        $ids = $context->ownerIds();
        if ($ids === []) {
            abort(403);
        }

        return $ids;
    }
}
