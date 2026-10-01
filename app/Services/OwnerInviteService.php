<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Mail\OwnerInviteMail;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Unit;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
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

    /**
     * ⚠️ ADMIN 04 — the founder of an organisation created in the platform console. NOT a slug the operator UI may
     * request (it is absent from ROLE_SLUGS on purpose): only {@see self::issueFounderActivation()} writes it.
     */
    public const FOUNDER_ROLE = 'founder';

    /** Invite role slugs the UI/API may request. */
    private const ROLE_SLUGS = ['owner', 'manager', 'accountant'];

    /** Staff role slug → the EXACT spatie catalog role name (config/authorization.php). */
    private const STAFF_ROLE_MAP = [
        'manager' => 'Hotel Manager',
        'accountant' => 'Accountant',
    ];

    /**
     * The OWNER-ONLY catalog role: `owner_portal.view` and nothing else — deliberately NOT in
     * `config('authorization.back_office_roles')`, so an owner is refused the operator app at the ROLE
     * level as well as by the route gate (defense-in-depth; see EnsureBackOfficeAccess).
     *
     * ⚠️ NOT `Property Owner` — that is the ORG ADMIN (`['*']`, all 44 permissions). The names are a
     * trap; `DemoSeeder` fell into it and gave a unit owner every permission, including payments.refund.
     */
    private const OWNER_ROLE = 'External Owner';

    /**
     * Create (or refresh) a pending invite, then email the tokened set-password link.
     *
     * OWNER: an inactive `invited` user + a pending owner + membership. STAFF: an inactive `invited`
     * user + membership only (no owner; owner-only fields are ignored). Returns the token plaintext once
     * (for the email + tests); it is never persisted.
     *
     * @param  list<string>  $unitIds  owner-only — units to assign on acceptance (ignored for staff)
     * @param  string  $trail  the audit action this send is recorded as (`owner_invite.sent`, or `.resent` from {@see resend()})
     * @return array{invite: OwnerInvite, token: string}
     */
    public function invite(string $name, string $email, array $unitIds = [], ?float $commissionPct = null, string $role = 'owner', string $trail = 'owner_invite.sent'): array
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

        [$invite, $token] = DB::transaction(function () use ($name, $email, $orgId, $unitIds, $commissionPct, $role, $isOwner, $existing, $trail): array {
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

            // ⚠️ 2026-10-01 — an invite mints a principal and recorded NOTHING. Figures and ids only: the address stays off
            //    the trail (it is personal data, and the invite row already holds it).
            app(AuditEventRecorder::class)->record($trail, $invite, null, $this->inviteState($invite));

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

        return ($invite !== null && $invite->isRedeemable() && ! $this->wouldReplaceAPassword($invite)) ? $invite : null;
    }

    /**
     * ⚠️⚠️ ADMIN 06 (#5) — A FOUNDER'S LINK SETS A FIRST PASSWORD; IT NEVER REPLACES ONE. The founder is created with no
     * password and the link is theirs to set it — but it stays valid for {@see self::EXPIRY_DAYS} days, and in that
     * time the founder may set one another way (a password reset). Redeeming the link after that would silently
     * overwrite the password they chose, so a founder's link whose account already has a password is not redeemable.
     */
    private function wouldReplaceAPassword(OwnerInvite $invite): bool
    {
        return $invite->role === self::FOUNDER_ROLE
            && User::query()->where('email', $invite->email)->whereNotNull('password')->exists();
    }

    /**
     * ⚠️⚠️ ADMIN 04 (2026-09-26) — THE FOUNDER'S SET-PASSWORD LINK for an organisation the platform console created.
     * The founder already exists — {@see TenantProvisioningService} made them, with the organisation's roles and
     * `Property Owner`, and with NO password — so this writes ONLY the single-use tokened link, through the same
     * hardened discipline as every invite: a random token held as its sha256 hash, {@see self::EXPIRY_DAYS} days,
     * spent on use. Redeeming it sets the password and VERIFIES the address — clicking a mailed link is proof of
     * ownership — and assigns no role (the founder has theirs). Returns [invite, plaintext token]; the caller mails it.
     *
     * @return array{0: OwnerInvite, 1: string}
     */
    public function issueFounderActivation(User $founder, string $organizationId): array
    {
        return app(TenantContext::class)->forOrganization($organizationId, function () use ($founder, $organizationId): array {
            $plain = Str::random(64);
            $invite = new OwnerInvite;
            $invite->fill([
                'organization_id' => $organizationId,
                'owner_id' => null,
                'invited_by' => null,   // the platform console, not an account holder of this organisation
                'email' => (string) $founder->email,
                'token_hash' => hash('sha256', $plain),
                'role' => self::FOUNDER_ROLE,
                'status' => 'pending',
                'expires_at' => now()->addDays(self::EXPIRY_DAYS),
            ])->save();

            return [$invite, $plain];
        });
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
                // ⚠️ ADMIN 06 (#5) — re-checked HERE, under a lock on the account: a password set a moment after the
                // link was opened is still never overwritten (the page's own check is only the friendly one).
                // ⚠️ The row is locked by its KEY and the password read AFTER: a `FOR UPDATE` whose WHERE also tests the
                // password releases a non-matching row at once under READ COMMITTED — the lock would hold nothing
                if ($invite->role === self::FOUNDER_ROLE
                    && User::query()->whereKey($user->getKey())->lockForUpdate()->first(['id', 'password'])?->getAuthPassword() !== null) {
                    throw new InviteNoLongerRedeemable('This set-password link is no longer valid — the account already has a password.');
                }

                // 'hashed' cast hashes the plaintext once. Activate the login + verify the email.
                //
                // ⚠️⚠️ `email_verified_at` IS SET OUTSIDE THE MASS ASSIGNMENT, AND THAT IS A BUG FIX, NOT A
                //    STYLE CHOICE. 🔬 Measured 2026-09-23 (GATE 01): the column is **not in `User::$fillable`**,
                //    so passing it to `update()` made Eloquent **silently drop it** — this line claimed to
                //    "verify the email" and never did. Nothing noticed for as long as nothing READ the column
                //    for a `User`; the moment `verified` went on `/app`, every invited user would have been
                //    locked out of the product on their first login.
                // ⚠️ Clicking a mailed invite link IS proof of email ownership, so verifying here is correct —
                //    it was the mechanism that was broken, never the intent.
                // ⚠️ `forceFill` rather than widening `$fillable`: a verification timestamp should not become
                //    mass-assignable just to make one call site shorter.
                $user->update(['password' => $password, 'status' => 'active']);
                $user->forceFill(['email_verified_at' => now()])->save();

                if ($invite->owner_id !== null) {
                    $this->activateOwner($invite);
                    $this->assignOwnerRole($user, $invite);
                } elseif ($invite->role !== self::FOUNDER_ROLE) {
                    $this->assignStaffRole($user, $invite);
                }
                // ⚠️ ADMIN 04 — a FOUNDER gets no role here: provisioning already made them the organisation's
                //    `Property Owner`, and granting a second, staff role would only be a way to widen it.

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

    /**
     * Send a waiting (or expired) invite again — a fresh link, the same person, role, units and rate (2026-10-01). It goes
     * through {@see invite()}, so the token discipline, the active-account refusal and the email are the same ones; units
     * that have gained an owner since are left out (an invite never moves a unit's owner).
     *
     * @return array{invite: OwnerInvite, token: string}
     */
    public function resend(OwnerInvite $invite): array
    {
        if (! in_array($invite->status, ['pending', 'expired'], true) || $invite->role === self::FOUNDER_ROLE) {
            throw new InviteNoLongerRedeemable('That invite has already been used or cancelled.');
        }

        $owner = $invite->owner_id !== null ? Owner::withTrashed()->find($invite->owner_id) : null;
        $name = (string) ($owner->name ?? User::query()->where('email', $invite->email)->value('name') ?? $invite->email);
        $unitIds = Unit::query()->whereIn('id', $invite->assigned_unit_ids ?? [])->whereNull('owner_id')
            ->pluck('id')->map(fn ($id): string => (string) $id)->all();

        // recorded ONCE, as a resend — invite() writes the trail inside its own transaction
        return $this->invite($name, (string) $invite->email, $unitIds, $invite->commission_pct !== null ? (float) $invite->commission_pct : null,
            (string) $invite->role, 'owner_invite.resent');
    }

    /**
     * Cancel a waiting invite (2026-10-01): the link stops working, and an owner record made for it that was never activated
     * leaves the list — the same conservative cleanup {@see pruneExpired()} applies to an expired one. Returns false for an
     * invite that is no longer waiting (used, cancelled or expired).
     */
    public function cancel(OwnerInvite $invite): bool
    {
        if ($invite->status !== 'pending' || $invite->role === self::FOUNDER_ROLE) {
            return false;
        }

        DB::transaction(function () use ($invite): void {
            $invite->update(['status' => 'revoked']);

            $owner = $invite->owner_id !== null ? Owner::query()->find($invite->owner_id) : null;
            $login = $owner?->user_id !== null ? User::query()->find($owner->user_id) : null;
            // never remove an owner who is active, or whose login is (an inconsistency — leave it alone)
            if ($owner !== null && $owner->status === 'inactive' && ($login === null || $login->status !== 'active')) {
                $owner->delete();
            }

            app(AuditEventRecorder::class)->record('owner_invite.cancelled', $invite, ['status' => 'pending'], ['status' => 'revoked']);
        });

        return true;
    }

    /**
     * An invite's audit shape — ids and figures, never the address.
     *
     * @return array<string, mixed>
     */
    private function inviteState(OwnerInvite $invite): array
    {
        return [
            'role' => (string) $invite->role,
            'owner_id' => $invite->owner_id !== null ? (string) $invite->owner_id : null,
            'units' => count($invite->assigned_unit_ids ?? []),
            'commission_pct' => $invite->commission_pct !== null ? (float) $invite->commission_pct : null,
            'expires_at' => $invite->expires_at?->toDateTimeString(),
        ];
    }

    /**
     * Prune genuinely EXPIRED, NEVER-ACCEPTED invites and their never-activated artifacts. CONSERVATIVE
     * by design — over-cleanup is the only real risk, so every guard errs toward LEAVING things alone:
     *
     *   - Only invites that are `pending` AND `accepted_at IS NULL` AND past `expires_at` are marked
     *     `expired`. (Accepted / revoked / still-valid invites are never touched.)
     *   - The pending OWNER tied to such an invite is soft-removed ONLY if it is still `inactive` (never
     *     activated) and its linked login is not active. (`owners.status` is an enum, so it is soft-deleted
     *     rather than re-labelled.)
     *   - The invited LOGIN is marked `expired` ONLY if it is still `invited` (never activated) AND has NO
     *     remaining redeemable invite anywhere AND no accepted invite (so a login shared with a live/
     *     accepted invite in another org is left intact — fail-safe on shared state).
     *
     * Runs as an explicit platform (super-admin) maintenance pass — cross-tenant, the documented escape
     * hatch. Idempotent: a second run finds nothing to change.
     *
     * @return array{invites: int, owners: int, logins: int}
     */
    public function pruneExpired(): array
    {
        return app(TenantContext::class)->runAsSuperAdmin(function (): array {
            $expired = OwnerInvite::query()
                ->where('status', 'pending')
                ->whereNull('accepted_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->get();

            $ownersRemoved = 0;
            foreach ($expired as $invite) {
                $invite->update(['status' => 'expired']);

                if ($invite->owner_id === null) {
                    continue;   // staff invite — no owner artifact
                }

                $owner = Owner::query()->find($invite->owner_id);
                if ($owner === null || $owner->status !== 'inactive') {
                    continue;   // already activated / already gone → leave it
                }

                // Never soft-remove an owner whose login is somehow active (inconsistent → fail safe).
                $linkedUser = $owner->user_id !== null ? User::query()->find($owner->user_id) : null;
                if ($linkedUser !== null && $linkedUser->status === 'active') {
                    continue;
                }

                $owner->delete();   // soft-remove (enum status can't carry an 'expired' marker)
                $ownersRemoved++;
            }

            $loginsMarked = 0;
            foreach ($expired->pluck('email')->unique()->all() as $email) {
                $user = User::query()->where('email', $email)->first();
                if ($user === null || $user->status !== 'invited') {
                    continue;   // never touch an active / already-activated login
                }

                $hasLiveInvite = OwnerInvite::query()
                    ->where('email', $email)->where('status', 'pending')
                    ->whereNotNull('expires_at')->where('expires_at', '>=', now())->exists();
                $hasAccepted = OwnerInvite::query()->where('email', $email)->where('status', 'accepted')->exists();

                if ($hasLiveInvite || $hasAccepted) {
                    continue;   // shared with a live/accepted invite → leave the login intact
                }

                $user->update(['status' => 'expired']);
                $loginsMarked++;
            }

            return ['invites' => $expired->count(), 'owners' => $ownersRemoved, 'logins' => $loginsMarked];
        });
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
            // ⚠️⚠️ ONLY A UNIT WITH NO OWNER (2026-10-01). A unit's owner decides who is paid for it; this wrote the new
            //    owner over whoever owned the unit when the invite was ACCEPTED — the invite dialog even offered owned units —
            //    so an invite silently moved another owner's income. A unit that gained an owner meanwhile keeps it.
            Unit::query()->whereIn('id', $unitIds)->whereNull('owner_id')->update(['owner_id' => $owner->getKey()]);
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
     * OWNER activation: assign the owner-only {@see self::OWNER_ROLE} (`External Owner` —
     * `owner_portal.view`, nothing else) for the invite's org.
     *
     * WHY: an owner necessarily holds an ACTIVE Membership (the only way SetTenantForWebRequest can
     * resolve their org — remove it and /owner 500s). Before the back-office gate, that Membership read
     * as "is staff" and handed them the whole operator app. Giving them an explicit owner-only role makes
     * their capability POSITIVE rather than "happens to hold no role", so the
     * `config('authorization.back_office_roles')` allow-list has nothing to admit even if a future route
     * forgets {@see EnsureBackOfficeAccess}. Layer 2 of two.
     *
     * NOT FAIL-SAFE-BY-THROW, unlike {@see assignStaffRole()} — deliberately, and the asymmetry is the
     * point. A staff member without their role is BROKEN (locked out of /app), so that path rolls the
     * activation back. An owner's portal access comes from the `owners.user_id` link, NOT from this role:
     * an owner with no role reaches /owner exactly the same and is EQUALLY non-operator-capable (the
     * allow-list admits neither). So if the role is not provisioned for the org, we log and carry on
     * rather than blocking a legitimate owner from activating over an unrelated org-setup gap. The
     * security property holds in both branches; only the explicitness differs. (A role missing from the
     * CATALOG is a different thing — a code/config bug — and DOES throw.)
     */
    private function assignOwnerRole(User $user, OwnerInvite $invite): void
    {
        $orgId = (string) $invite->organization_id;
        $roleName = self::OWNER_ROLE;

        /** @var array<string, mixed> $catalog */
        $catalog = (array) config('authorization.roles', []);

        // A catalog without the owner role is a code/config defect, not an environment gap — fail loudly.
        if (! array_key_exists($roleName, $catalog)) {
            throw new RuntimeException("Owner role [{$roleName}] is not defined in the RBAC catalog.");
        }

        $guard = (string) config('authorization.guard');
        $teamKey = (string) config('permission.column_names.team_foreign_key', 'team_id');

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($orgId);   // roles are team-scoped (team = org)

        try {
            $provisioned = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->where($teamKey, $orgId)
                ->exists();

            if (! $provisioned) {
                // Safe, but worth surfacing: the org never ran OrganizationRoleProvisioner.
                Log::warning('owner_invite.owner_role_not_provisioned', [
                    'organization_id' => $orgId,
                    'role' => $roleName,
                    'owner_id' => $invite->owner_id,
                    'note' => 'Owner activated WITHOUT the owner-only role; portal access is unaffected and they remain non-operator-capable.',
                ]);

                return;
            }

            $user->assignRole($roleName);
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
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
