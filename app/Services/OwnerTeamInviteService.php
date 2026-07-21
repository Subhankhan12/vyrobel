<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Mail\OwnerTeamInviteMail;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerTeamInvite;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The OWNER-TEAM invite flow (Phase 7c · SG3) — the owner-realm parallel of {@see OwnerInviteService}
 * (operator → owner). A PRIMARY OWNER invites a sub-user into THEIR OWN team; the invitee sets a password
 * to activate and joins as an {@see OwnerUser} of that owner at the assigned {@see OwnerTeamRole}.
 *
 * It REUSES the exact security machinery of the operator invite (hashed single-use expiring tokens, no
 * enumeration, tenant isolation, session-regen on activation — the last in the controller) but with the
 * owner-realm semantics: the target is an `owner_users` membership under a SPECIFIC owner, never an Owner
 * party / units / commission, and the role is the fixed owner-team set.
 *
 * SECURITY:
 *   - The token is high-entropy random; only its sha256 hash is stored; single-use + expiring.
 *   - The invite is SCOPED to one owner_id — {@see accept()} joins the sub-user under exactly that owner, so
 *     a redeemed token can never land them under a different owner (no mis-scoping).
 *   - Only the PRIMARY OWNER may issue an invite — enforced at the ROUTE ({@see EnsurePrimaryOwner}) and,
 *     defense-in-depth, the caller passes the resolved owner_id from {@see OwnerContext},
 *     never a request id.
 *   - An email that already belongs to an ACTIVE user is rejected (never hijack a live account via invite).
 */
class OwnerTeamInviteService
{
    public const EXPIRY_DAYS = 7;

    /** The owner-only catalog role that keeps a sub-user on /owner (and OUT of /app — see EnsureBackOfficeAccess). */
    private const OWNER_ROLE = 'External Owner';

    /**
     * Create (or refresh) a pending owner-team invite for a sub-user under $ownerId, then email the tokened
     * set-password link. Creates an inactive `invited` login + an active Membership (so the tenant resolves
     * on activation); the {@see OwnerUser} membership is created on {@see accept()}, so a PENDING invite
     * grants NO access. Returns the token plaintext once (email + tests); it is never persisted.
     *
     * @return array{invite: OwnerTeamInvite, token: string}
     */
    public function invite(string $ownerId, string $name, string $email, string $role): array
    {
        if (! OwnerTeamRole::isValid($role)) {
            throw new RuntimeException("Owner-team role [{$role}] is not assignable.");
        }

        $email = Str::lower(trim($email));
        $orgId = (string) app(TenantContext::class)->id();

        // The owner must exist IN THIS TENANT (scoped query) — never trust a request id.
        $owner = Owner::query()->whereKey($ownerId)->firstOrFail();

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null && $existing->status === 'active') {
            throw new RuntimeException('A user with this email already exists.');
        }

        [$invite, $token] = DB::transaction(function () use ($name, $email, $orgId, $owner, $role, $existing): array {
            $user = $existing ?? User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),   // unusable until they set their own
                'status' => 'invited',
            ]);

            // Active membership so SetTenantForWebRequest can resolve the org once the login activates.
            Membership::firstOrCreate(
                ['organization_id' => $orgId, 'user_id' => $user->getKey()],
                ['status' => 'active', 'invited_at' => now()],
            );

            $plain = Str::random(64);

            // Refresh a still-pending invite for this (owner, email) instead of duplicating.
            $invite = OwnerTeamInvite::query()
                ->where('owner_id', $owner->getKey())->where('email', $email)->where('status', 'pending')->first()
                ?? new OwnerTeamInvite;

            $invite->fill([
                'organization_id' => $orgId,
                'owner_id' => $owner->getKey(),
                'invited_by' => Auth::id(),
                'email' => $email,
                'token_hash' => hash('sha256', $plain),
                'role' => $role,
                'status' => 'pending',
                'expires_at' => now()->addDays(self::EXPIRY_DAYS),
                'accepted_at' => null,
            ])->save();

            return [$invite, $plain];
        });

        Mail::to($email)->send(new OwnerTeamInviteMail($invite, $token, $name));

        return ['invite' => $invite, 'token' => $token];
    }

    /**
     * Resolve a REDEEMABLE invite from a plaintext token, or null. Matched by sha256 hash; a
     * non-pending / expired / unknown token returns null so the caller responds generically (no
     * enumeration). Runs super-admin — the accept flow is public.
     */
    public function findRedeemable(string $token): ?OwnerTeamInvite
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $invite = app(TenantContext::class)->runAsSuperAdmin(
            fn (): ?OwnerTeamInvite => OwnerTeamInvite::query()->where('token_hash', hash('sha256', $token))->first(),
        );

        return ($invite !== null && $invite->isRedeemable()) ? $invite : null;
    }

    /**
     * Redeem a valid invite: set the password + ACTIVATE the login, JOIN the sub-user under the invite's
     * owner at its role (active {@see OwnerUser}), assign the owner-only role, and SPEND the token
     * (single-use). Runs in the invite's org context so every write is correctly scoped. Returns the
     * now-active user.
     */
    public function accept(OwnerTeamInvite $invite, string $password): User
    {
        return app(TenantContext::class)->forOrganization((string) $invite->organization_id, function () use ($invite, $password): User {
            return DB::transaction(function () use ($invite, $password): User {
                $user = User::query()->where('email', $invite->email)->firstOrFail();

                // 'hashed' cast hashes the plaintext once. Activate the login + verify the email.
                $user->update(['password' => $password, 'status' => 'active', 'email_verified_at' => now()]);

                // JOIN under EXACTLY the invite's owner + role — the invite can't mis-scope the sub-user.
                OwnerUser::query()->updateOrCreate(
                    ['owner_id' => $invite->owner_id, 'user_id' => $user->getKey()],
                    ['organization_id' => $invite->organization_id, 'role' => $invite->role, 'status' => 'active', 'invited_at' => now()],
                );

                $this->assignOwnerRole($user, (string) $invite->organization_id);

                // SPEND the token — single-use.
                $invite->update(['status' => 'accepted', 'accepted_at' => now()]);

                return $user->refresh();
            });
        });
    }

    /** Revoke a pending invite (a spent token can never be redeemed). */
    public function revoke(OwnerTeamInvite $invite): void
    {
        if ($invite->status === 'pending') {
            $invite->update(['status' => 'revoked']);
        }
    }

    /**
     * Assign the owner-only {@see self::OWNER_ROLE} (`External Owner` — `owner_portal.view`, nothing else)
     * so the sub-user lands on /owner and is refused /app at the ROLE level too (defense-in-depth with
     * {@see EnsureBackOfficeAccess}). Not fail-by-throw: portal access comes from the
     * `owner_users` link, so a missing role is logged, not fatal (same asymmetry as OwnerInviteService).
     */
    private function assignOwnerRole(User $user, string $orgId): void
    {
        $roleName = self::OWNER_ROLE;
        $guard = (string) config('authorization.guard');
        $teamKey = (string) config('permission.column_names.team_foreign_key', 'team_id');

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($orgId);

        try {
            $provisioned = Role::query()
                ->where('name', $roleName)->where('guard_name', $guard)->where($teamKey, $orgId)->exists();

            if (! $provisioned) {
                Log::warning('owner_team_invite.owner_role_not_provisioned', [
                    'organization_id' => $orgId,
                    'role' => $roleName,
                    'note' => 'Sub-user joined WITHOUT the owner-only role; portal access is unaffected and they remain non-operator-capable.',
                ]);

                return;
            }

            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }
    }
}
