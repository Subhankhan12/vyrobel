<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\OwnerInviteMail;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Invite flow (Auth Phase B). The operator invites someone into THEIR OWN organization (this service
 * always runs inside the caller's tenant context — every record is created org-scoped, so an operator
 * can never target another org, nor grant a role there).
 *
 * TWO paths share ONE set of secure machinery (hashed single-use expiring tokens, no enumeration,
 * rate-limit, session-regen on activation, tenant isolation):
 *   - OWNER      → an external `Owner` party: owner link + portal (/owner), optional assigned units +
 *                  management agreement. NO spatie role (portal access comes from the owner link).
 *   - STAFF      → a `Membership` in the operator's org with a REAL spatie role (Hotel Manager /
 *                  Accountant) assigned on activation → the operator app (/app). NO owner record,
 *                  NO agreement, NO units.
 *
 * SECURITY: the token is high-entropy random; only its sha256 hash is stored; single-use (invite
 * `status` → accepted) and expiring. Re-inviting refreshes the pending invite's token. An email that
 * already belongs to an ACTIVE user is rejected. A staff role is assigned via the real RBAC system and
 * is VALIDATED to exist for the org before assignment (fail-safe — never grant a phantom role).
 */
class OwnerInviteService
{
    public const EXPIRY_DAYS = 7;

    /** Invite role slugs the UI/API may request. */
    private const ROLE_SLUGS = ['owner', 'manager', 'accountant'];

    /** Staff role slug → the EXACT spatie catalog role name (config/authorization.php). */
    private const STAFF_ROLE_MAP = [
        'manager' => 'Hotel Manager',
        'accountant' => 'Accountant',
    ];

    /**
     * Create (or refresh) a pending invite, then email the tokened set-password link.
     *
     * OWNER: an inactive `invited` user + a pending owner + membership. STAFF: an inactive `invited`
     * user + membership only (no owner; owner-only fields are ignored). Returns the token plaintext once
     * (for the email + tests); it is never persisted.
     *
     * @param  list<string>  $unitIds  owner-only — units to assign on acceptance (ignored for staff)
     * @return array{invite: OwnerInvite, token: string}
     */
    public function invite(string $name, string $email, array $unitIds = [], ?float $commissionPct = null, string $role = 'owner'): array
    {
        if (! in_array($role, self::ROLE_SLUGS, true)) {
            throw new RuntimeException("Role [{$role}] invites are not available.");
        }

        $isOwner = $role === 'owner';
        $email = Str::lower(trim($email));
        $orgId = (string) app(TenantContext::class)->id();

        // Owner-only fields never ride a staff invite (defense-in-depth with the modal hiding them).
        if (! $isOwner) {
            $unitIds = [];
            $commissionPct = null;
            // Fail EARLY + CLEAN (before any writes): the staff role must map to the catalog AND be
            // provisioned for this org — so the operator gets a graceful error, not the invitee a 500.
            $this->resolveStaffRole($role, $orgId);
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null && $existing->status === 'active') {
            // Don't hijack a live account through the invite path.
            throw new RuntimeException('A user with this email already exists.');
        }

        [$invite, $token] = DB::transaction(function () use ($name, $email, $orgId, $unitIds, $commissionPct, $role, $isOwner, $existing): array {
            // Inactive login (status=invited → cannot pass the active-only login) with an UNUSABLE
            // random placeholder password until the invitee sets their own on acceptance.
            $user = $existing ?? User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'status' => 'invited',
            ]);

            // OWNER path only: a pending owner party (inactive → active on acceptance), linked to the login.
            $ownerId = null;
            if ($isOwner) {
                $owner = Owner::query()->where('user_id', $user->getKey())->first() ?? Owner::create([
                    'type' => 'individual',
                    'name' => $name,
                    'email' => $email,
                    'payout_currency' => 'EUR',
                    'user_id' => $user->getKey(),
                    'status' => 'inactive',
                ]);
                $ownerId = $owner->getKey();
            }

            // Membership (active so the tenant context resolves once the login is activated) — BOTH paths.
            Membership::firstOrCreate(
                ['organization_id' => $orgId, 'user_id' => $user->getKey()],
                ['status' => 'active', 'invited_at' => now()],
            );

            $plain = Str::random(64);

            // Refresh a still-pending invite for this (org, email) instead of duplicating.
            $invite = OwnerInvite::query()->where('email', $email)->where('status', 'pending')->first()
                ?? new OwnerInvite;

            $invite->fill([
                'organization_id' => $orgId,
                'owner_id' => $ownerId,
                'invited_by' => Auth::id(),
                'email' => $email,
                'token_hash' => hash('sha256', $plain),
                'role' => $role,
                'assigned_unit_ids' => $unitIds === [] ? null : $unitIds,
                'commission_pct' => $commissionPct,
                'status' => 'pending',
                'expires_at' => now()->addDays(self::EXPIRY_DAYS),
                'accepted_at' => null,
            ])->save();

            return [$invite, $plain];
        });

        Mail::to($email)->send(new OwnerInviteMail($invite, $token, $name));

        return ['invite' => $invite, 'token' => $token];
    }

    /**
     * Resolve a REDEEMABLE invite from a plaintext token, or null. Matched by sha256 hash (O(1),
     * plaintext never stored); a non-pending / expired / unknown token returns null so the caller
     * responds generically (no enumeration). Runs super-admin — the accept flow is public.
     */
    public function findRedeemable(string $token): ?OwnerInvite
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $invite = app(TenantContext::class)->runAsSuperAdmin(
            fn (): ?OwnerInvite => OwnerInvite::query()->where('token_hash', hash('sha256', $token))->first(),
        );

        return ($invite !== null && $invite->isRedeemable()) ? $invite : null;
    }

    /**
     * Redeem a valid invite: set the password + ACTIVATE the login, then apply the role's effects, and
     * SPEND the token (single-use). Runs in the invite's org context so every write is correctly scoped.
     *
     *   OWNER → activate the owner, assign units, create a management agreement.
     *   STAFF → assign the real spatie role for the org (validated to exist). No owner/agreement/units.
     *
     * Returns the now-active user.
     */
    public function accept(OwnerInvite $invite, string $password): User
    {
        return app(TenantContext::class)->forOrganization((string) $invite->organization_id, function () use ($invite, $password): User {
            return DB::transaction(function () use ($invite, $password): User {
                $user = $this->userForInvite($invite);

                // 'hashed' cast hashes the plaintext once. Activate the login + verify the email.
                $user->update(['password' => $password, 'status' => 'active', 'email_verified_at' => now()]);

                if ($invite->owner_id !== null) {
                    $this->activateOwner($invite);
                } else {
                    $this->assignStaffRole($user, $invite);
                }

                // SPEND the token — single-use. A reused/expired token can never activate again.
                $invite->update(['status' => 'accepted', 'accepted_at' => now()]);

                return $user->refresh();
            });
        });
    }

    /** Revoke a pending invite (a spent token can never be redeemed). */
    public function revoke(OwnerInvite $invite): void
    {
        if ($invite->status === 'pending') {
            $invite->update(['status' => 'revoked']);
        }
    }

    /** Resolve the invited login. Owner invites carry an owner→user link; staff invites go by email. */
    private function userForInvite(OwnerInvite $invite): User
    {
        if ($invite->owner_id !== null) {
            $owner = Owner::query()->findOrFail($invite->owner_id);

            return User::query()->findOrFail($owner->user_id);
        }

        return User::query()->where('email', $invite->email)->firstOrFail();
    }

    /** OWNER activation: activate the owner + apply the operator's assigned units + commission agreement. */
    private function activateOwner(OwnerInvite $invite): void
    {
        $owner = Owner::query()->findOrFail($invite->owner_id);
        $owner->update(['status' => 'active']);

        $unitIds = $invite->assigned_unit_ids ?? [];
        if ($unitIds !== []) {
            Unit::query()->whereIn('id', $unitIds)->update(['owner_id' => $owner->getKey()]);
        }

        if ($invite->commission_pct !== null && ! ManagementAgreement::query()->where('owner_id', $owner->getKey())->where('status', 'active')->exists()) {
            ManagementAgreement::create([
                'owner_id' => $owner->getKey(),
                'unit_id' => null,
                'commission_pct' => $invite->commission_pct,
                'payout_currency' => (string) ($owner->payout_currency ?? 'EUR'),
                'payout_schedule' => 'monthly',
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'status' => 'active',
            ]);
        }
    }

    /**
     * STAFF activation: assign the REAL spatie catalog role for the invite's org. The role is re-validated
     * (mapped + provisioned) inside the accept transaction — defense-in-depth with the invite-time check —
     * so if it somehow isn't assignable the whole activation rolls back rather than half-activating a login
     * with no role.
     */
    private function assignStaffRole(User $user, OwnerInvite $invite): void
    {
        $orgId = (string) $invite->organization_id;
        $roleName = $this->resolveStaffRole((string) $invite->role, $orgId);

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($orgId);   // roles are team-scoped (team = org)

        try {
            $user->assignRole($roleName);
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }
    }

    /**
     * Resolve a staff role slug to its EXACT spatie catalog role name, FAIL-SAFE: the slug must map to a
     * role defined in config/authorization.php AND that role must actually be provisioned for this org
     * (team-scoped). Never returns a name that can't be assigned. Used at invite time (fail early/clean)
     * and again at accept time (defense-in-depth).
     */
    private function resolveStaffRole(string $slug, string $orgId): string
    {
        $roleName = self::STAFF_ROLE_MAP[$slug] ?? null;
        /** @var array<string, mixed> $catalog */
        $catalog = (array) config('authorization.roles', []);

        if ($roleName === null || ! array_key_exists($roleName, $catalog)) {
            throw new RuntimeException("Staff role [{$slug}] does not map to a known RBAC catalog role.");
        }

        $guard = (string) config('authorization.guard');
        // Roles are team-scoped; the team foreign key is configured (spatie teams mode = organization_id).
        $teamKey = (string) config('permission.column_names.team_foreign_key', 'team_id');
        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($orgId);

        try {
            $exists = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->where($teamKey, $orgId)
                ->exists();
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }

        if (! $exists) {
            throw new RuntimeException("Role [{$roleName}] is not provisioned for organization [{$orgId}].");
        }

        return $roleName;
    }
}
