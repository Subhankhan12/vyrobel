<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Models\Membership;
use App\Models\User;
use App\Support\Authorization\RolePermissions;
use App\Support\Events\AuditEventRecorder;
use App\Support\Retention\RetentionPolicy;
use App\Support\Tenancy\TenantContext;
use Spatie\Permission\PermissionRegistrar;

/**
 * The operator ROLES screen's engine (Phase 1 · Sub-gate 3) — READ + ASSIGN over the FIXED, CODE-DEFINED role
 * catalog (`config/authorization.php`). It NEVER edits the catalog: role→permission mappings are the source of
 * truth in code, and this service exposes them READ-ONLY. The ONLY mutation is the user↔role ASSIGNMENT
 * (spatie `model_has_roles`, team-scoped) — the same write `OwnerInviteService` already performs for staff.
 *
 * ⚠️ THE ANTI-ESCALATION BOUND — the reason this screen is "security-first". The ROUTE gates the whole thing on
 * `can:users.manage` (only an admin may manage roles). On top of that, this service enforces a DATA bound on
 * every assignment (like the #27/#29 bounds, in the service so every caller is covered):
 *
 *   • THE PRIVILEGE-SUBSET RULE — you may only assign/revoke a role whose permissions you ALREADY HOLD IN FULL.
 *     `perms(role) ⊆ perms(actor's roles)`. So a Group Admin (no `organizations.manage`/`roles.manage`/…) CANNOT
 *     grant `Property Owner` (`['*']`) — that would escalate a user beyond the actor's own privilege. Only a
 *     `['*']` holder can grant `['*']`. This is what stops "users.manage lets me mint an admin above me."
 *     ⚠️⚠️ READ FROM THE DATABASE, NEVER FROM `config/authorization.php` — see {@see RolePermissions}. Until
 *     2026-08-30 this rule read the SHIPPED file, so once any role was customised it judged a world that no
 *     longer existed, and a demonstrated two-step round trip handed an actor permissions they never held
 *     (finding A-1). The config is the seed; the DB is the live state; a guard reads live state.
 *   • ⚠️ AND THE ROLE MUST BE PROVISIONED FOR THE ORG. An unprovisioned role holds nothing, and "nothing" is
 *     a subset of everything — so without this it would become assignable by ANY actor. Fail closed.
 *   • NO SELF-MANAGEMENT — the actor cannot change their OWN roles (belt-and-suspenders against self-escalation,
 *     and it prevents an admin locking themselves out by revoking their own last role).
 *   • TENANT — the target must be a member of the ACTING organization; another org's user is untouchable.
 *   • CATALOG ROLES ONLY — the role must be one of `back_office_roles` (Super Admin / External Owner / Guest and
 *     any invented name are refused). Roles are team-scoped (team = org).
 *
 * ⚠️ AUDITED (2026-08-30) — every assignment and revocation writes an `audit_events` row carrying the target's
 * full role set BEFORE and AFTER. See {@see recordRoleChange()} for why the shape matters.
 */
class RoleAssignmentService
{
    public function __construct(
        private TenantContext $tenant,
        private PermissionRegistrar $registrar,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * The back-office roles for this org, each with its READ-ONLY permission grants (from the fixed catalog) and
     * its current members. This is the whole screen's data — nothing here is editable.
     *
     * @return list<array{name: string, permissions: array<string, list<string>>, permission_count: int, members: list<array{id: string, name: string, initials: string}>, member_count: int}>
     */
    public function overview(): array
    {
        return $this->withTeam(function (): array {
            $map = RolePermissions::mapFor($this->orgId());   // ⚠️ live state — the screen must not show the seed
            $roles = [];
            foreach ($this->backOfficeRoles() as $name) {
                $members = User::role($name)   // team-scoped → members of THIS org holding the role
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $u): array => [
                        'id' => (string) $u->getKey(),
                        'name' => (string) $u->name,
                        'initials' => $this->initials((string) $u->name),
                    ])
                    ->all();

                $roles[] = [
                    'name' => $name,
                    'permissions' => $this->groupedPermissions($name, $map),
                    'permission_count' => count($map[$name] ?? []),
                    'members' => $members,
                    'member_count' => count($members),
                ];
            }

            return $roles;
        });
    }

    /**
     * The org's active members — the candidates an admin may assign a role to.
     *
     * @return list<array{id: string, name: string}>
     */
    public function orgMembers(): array
    {
        $userIds = Membership::query()
            ->where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => ['id' => (string) $u->getKey(), 'name' => (string) $u->name])
            ->all();
    }

    /**
     * ⚠️ WHO CURRENTLY HOLDS WHAT — the data behind the REVOCATION control (M-14, 2026-08-30).
     *
     * ⚠️ Until this existed the Roles page told an operator to *"move a person off a role, revoke it from them
     * in the assignment section"* while no such control existed anywhere: `app.roles.revoke` was routed, gated
     * and reachable ONLY by a hand-made POST. A page naming a capability nobody can reach is the same defect
     * class as the period-close (H-2) — and here the missing button was also the accidental mitigation for the
     * missing last-admin floor, which is why the floor landed first, in the same change.
     *
     * ⚠️ NAME ONLY. Staff, not guests, but there is no reason for a role roster to carry contact details.
     *
     * @return list<array{user_id: string, name: string, role: string, is_self: bool}>
     */
    public function currentAssignments(User $actor): array
    {
        return $this->withTeam(function () use ($actor): array {
            $rows = [];

            foreach ($this->backOfficeRoles() as $role) {
                foreach (User::role($role)->orderBy('name')->get() as $holder) {
                    $rows[] = [
                        'user_id' => (string) $holder->getKey(),
                        'name' => (string) $holder->name,
                        'role' => $role,
                        'is_self' => $actor->is($holder),
                    ];
                }
            }

            usort($rows, fn (array $a, array $b): int => [$a['name'], $a['role']] <=> [$b['name'], $b['role']]);

            return $rows;
        });
    }

    /**
     * The back-office roles the actor MAY assign — those whose permissions the actor already holds in full (the
     * subset rule). The UI offers ONLY these, so an admin is never shown an option that would 403 in the service.
     *
     * @return list<string>
     */
    public function assignableRolesFor(User $actor): array
    {
        return $this->withTeam(function () use ($actor): array {
            // ⚠️ ONE map, ONE snapshot: the actor's privilege and every candidate role are read from the same
            //    live read. It is also what keeps this to a bounded query instead of one per role (§2 N+1).
            $map = RolePermissions::mapFor($this->orgId());
            $actorPerms = RolePermissions::union($actor->getRoleNames()->all(), $map);

            return array_values(array_filter(
                $this->backOfficeRoles(),
                fn (string $role): bool => isset($map[$role])
                    && RolePermissions::isSubset($map[$role], $actorPerms),
            ));
        });
    }

    /** Assign a catalog back-office role to a target user, enforcing every anti-escalation bound. Audited. */
    public function assign(User $actor, User $target, string $roleName): void
    {
        $this->withTeam(function () use ($actor, $target, $roleName): void {
            $this->assertManageable($actor, $target, $roleName);
            $before = $this->currentRoleNames($target);
            $target->assignRole($roleName);
            $this->recordRoleChange('user.role_assigned', $actor, $target, $roleName, $before);
        });
    }

    /** Remove a catalog back-office role from a target user, enforcing the same bounds. Audited. */
    public function revoke(User $actor, User $target, string $roleName): void
    {
        $this->withTeam(function () use ($actor, $target, $roleName): void {
            $this->assertManageable($actor, $target, $roleName);
            $this->assertNotLastRoleManager($target, $roleName);
            $this->assertNotLastRole($target, $roleName);
            $before = $this->currentRoleNames($target);
            $target->removeRole($roleName);
            $this->recordRoleChange('user.role_revoked', $actor, $target, $roleName, $before);
        });
    }

    /**
     * ⚠️⚠️ AN ACCESS CHANGE IS A SECURITY EVENT — and until 2026-08-30 this one wrote NOTHING.
     *
     * ⚠️ Both siblings already did: {@see RolePermissionService} records `role.permissions_updated`, and
     * {@see PropertyScopeService} records `user.property_scope_changed` with full before/after
     * sets. `OwnerManageController::remove`, one file away, records `owner.archived`. The gap sat inside one
     * feature — moving a USER between roles was the one access change with no trace beyond the spatie pivot.
     *
     * ⚠️ THE ROW RECORDS WHAT CHANGED, NOT THAT SOMETHING DID. "role updated" is invisible to the
     * reconstruction the ledger exists for, so this carries the target's COMPLETE role set before and after
     * plus the role operated on. `audit_events` is deliberately NEVER pruned ({@see RetentionPolicy}),
     * and that decision only pays if the rows are written in the first place.
     *
     * ⚠️ NO PII. Role names and the subject's id only — never the target's name or e-mail. An encrypted column
     * is undone the moment its value is copied into a table no erasure clears.
     *
     * ⚠️ The actor is passed EXPLICITLY into the real `actor_id` column rather than left to `auth()`: this
     * service is reachable from tests and console callers where there is no authenticated user, and an access
     * change attributed to "system" would be worse than useless.
     *
     * A no-op (assigning a role already held, revoking one not held) changes nothing and records nothing —
     * the same rule the permission matrix follows.
     *
     * @param  list<string>  $before
     */
    private function recordRoleChange(string $action, User $actor, User $target, string $roleName, array $before): void
    {
        $after = $this->currentRoleNames($target);

        if ($before === $after) {
            return;
        }

        $this->audit->record(
            $action,
            $target,
            ['roles' => $before],
            ['roles' => $after, 'role' => $roleName],
            actorType: 'user',
            actorId: (string) $actor->getKey(),
        );
    }

    /**
     * The target's CURRENT role names in the acting org, sorted. Must run inside {@see withTeam()} — outside it
     * spatie answers for no team and would record an empty set as if the user held nothing.
     *
     * @return list<string>
     */
    private function currentRoleNames(User $target): array
    {
        $fresh = $target->fresh();

        return $fresh === null
            ? []
            : $fresh->getRoleNames()->map(fn ($n): string => (string) $n)->sort()->values()->all();
    }

    /**
     * The full guard for any assignment or revocation (the DATA bounds; `users.manage` is the ROUTE gate).
     * Must run inside {@see withTeam()} so role reads are team-scoped.
     */
    private function assertManageable(User $actor, User $target, string $roleName): void
    {
        if (! in_array($roleName, $this->backOfficeRoles(), true)) {
            throw new RoleAssignmentException('That role can’t be managed here.');
        }

        if ($actor->is($target)) {
            throw new RoleAssignmentException('You can’t change your own role — ask another administrator.');
        }

        if (! $this->targetInActingOrg($target)) {
            throw new RoleAssignmentException('That user isn’t in your organization.');
        }

        // ⚠️⚠️ THE ANTI-ESCALATION SUBSET RULE, AGAINST LIVE STATE (A-1). One map, so the role's permissions
        //    and the actor's own privilege come from the SAME read — comparing two reads taken at different
        //    moments is how a subset check silently answers about a state that never existed.
        $map = RolePermissions::mapFor($this->orgId());

        // ⚠️ FAIL CLOSED on a role the org never had provisioned: it holds nothing, and nothing is a subset
        //    of everything, so treating it as an empty set would make it assignable by anybody.
        if (! isset($map[$roleName])) {
            throw new RoleAssignmentException('That role isn’t set up for your organization.');
        }

        $actorPerms = RolePermissions::union($actor->getRoleNames()->all(), $map);
        if (! RolePermissions::isSubset($map[$roleName], $actorPerms)) {
            throw new RoleAssignmentException('You can’t assign a role with more access than your own.');
        }
    }

    /**
     * ⚠️⚠️ THE LAST-ADMIN FLOOR ON REVOCATION — made EXPLICIT 2026-08-30.
     *
     * ⚠️ UNTIL NOW THE FLOOR WAS AN ACCIDENT. Nothing here checked it; the only thing stopping an organisation
     * emptying itself was the NO-SELF-MANAGEMENT rule two checks up — two admins can each strip the other down
     * to one, and the last one standing cannot strip themselves. That is a side effect of a rule that answers a
     * completely different question, and it would have vanished silently the day that rule was relaxed.
     *
     * ⚠️ THE TWO RULES ANSWER DIFFERENT QUESTIONS, and both are kept:
     *   · NO SELF-MANAGEMENT (`$actor->is($target)`) — "may I change MY OWN access?" No, regardless of who else
     *     exists. It is an anti-self-escalation and anti-mis-click rule about the ACTOR.
     *   · THIS FLOOR — "would the ORGANISATION still have someone able to manage access?" It is about the org,
     *     and it fires against any target, including one the actor may legitimately manage.
     *   ⚠️ The floor does NOT make the older rule redundant: an actor is only ever the last manager in one of
     *     the two, and self-management is refused even when ten other managers exist.
     *
     * ⚠️ IT MUST ALSO BE ASKED WHEREVER ELSE ACCESS IS REMOVED. Verified against spatie's source rather than
     * the method's name: `assignRole()` is `attach(array_diff($roles, $currentRoles))` — purely ADDITIVE, it
     * never detaches. So `revoke()` is the only path here that removes a role, and this is its only caller.
     */
    private function assertNotLastRoleManager(User $target, string $roleName): void
    {
        $survivors = RolePermissions::roleManagersAfter(
            RolePermissions::mapFor($this->orgId()),
            withoutUserRole: [(string) $target->getKey(), $roleName],
        );

        if ($survivors === []) {
            throw new RoleAssignmentException(
                'That’s the last person who can manage access — give someone else a role that includes it first, or your workspace would be locked out.',
            );
        }
    }

    /**
     * ⚠⚠ F-56 — A MEMBER ALWAYS HOLDS AT LEAST ONE ROLE (founder ruling, 2026-09-19).
     *
     * ⚠⚠ THE STATE THIS FORBIDS IS NOT HYPOTHETICAL — THE PRODUCT COULD REACH IT, AND ONE WAS BUILT.
     * {@see assertNotLastRoleManager()} above asks whether the ORGANISATION keeps someone able to manage
     * access; nothing asked whether the TARGET keeps anything. So an admin could strip a colleague's only
     * role and leave them an active member of the organisation holding nothing — a person who logs in, is
     * refused `/app` by {@see EnsureBackOfficeAccess} (an allow-list, so no role
     * fails closed) and refused `/owner` as well. 🔬 FIX 40 manufactured exactly that person on
     * purpose, by calling `removeRole()` on a user's last role, to reach the 403 copy F-50 rewrote.
     *
     * ⚠ THE RULING IS THAT THE STATE IS WRONG, NOT THAT THE SCREEN IS. Membership and role answer two
     * different questions — "are they in this organisation" and "what may they do here" — and a member
     * holding no role is an answer to the first with none to the second. Removing access is removing the
     * MEMBERSHIP; that path exists and is where this refusal points.
     *
     * ⚠ THE REFUSAL NAMES THE WAY OUT. A wall with no door teaches an operator to route around it, and
     * the route around this one is worse than the thing it forbids.
     *
     * ⚠ ORDER IS DELIBERATE: this runs AFTER the last-role-manager floor, so a revocation that would
     * lock the workspace out still reports THAT, which is the more serious of the two and names a
     * different remedy. Every case the older guard already refused keeps its existing message byte for
     * byte; this one speaks only where the old path used to succeed.
     *
     * ⚠ AND IT MUST NOT FIRE WHEN OTHERS REMAIN — that is the positive control. A guard that refused
     * every revocation would satisfy a deny-direction test perfectly while breaking the feature.
     */
    private function assertNotLastRole(User $target, string $roleName): void
    {
        // ⚠ The CURRENT set less the one being removed — read inside {@see withTeam()} like every other
        //    role read here, because spatie answers for no team outside it and would report an empty set,
        //    turning this guard into an unconditional refusal.
        $remaining = array_values(array_diff($this->currentRoleNames($target), [$roleName]));

        if ($remaining !== []) {
            return;
        }

        throw new RoleAssignmentException(
            'That’s this member’s only role, and every member must keep at least one. To take their access away entirely, remove them from the organisation instead.',
        );
    }

    private function targetInActingOrg(User $target): bool
    {
        return Membership::query()
            ->where('organization_id', $this->orgId())
            ->where('user_id', $target->getKey())
            ->exists();
    }

    // ⚠️ `rolePermissions()`, `permissionsOf()` and `isSubset()` USED TO LIVE HERE, reading
    //    `config('authorization.roles')`. They are gone, not moved: the ONE definition is {@see RolePermissions},
    //    shared with {@see RolePermissionService}. Re-adding a local reader here re-opens A-1.

    /**
     * A role's permissions grouped by resource for READ-ONLY display — e.g. `['Reservations' => ['view', 'create']]`.
     *
     * @return array<string, list<string>>
     */
    private function groupedPermissions(string $roleName, array $map): array
    {
        $grouped = [];
        foreach ($map[$roleName] ?? [] as $perm) {
            [$resource, $action] = array_pad(explode('.', $perm, 2), 2, '');
            $label = ucfirst(str_replace('_', ' ', $resource));
            $grouped[$label][] = $action;
        }

        return $grouped;
    }

    /** @return list<string> */
    private function backOfficeRoles(): array
    {
        return array_values(array_map('strval', (array) config('authorization.back_office_roles', [])));
    }

    private function orgId(): string
    {
        $id = $this->tenant->id();
        if ($id === null) {
            throw new RoleAssignmentException('No organization context is set.');
        }

        return $id;
    }

    /**
     * Run a callback with the spatie permission team pinned to the acting org (roles are team-scoped), restoring
     * the previous team afterward.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withTeam(callable $callback): mixed
    {
        $previous = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId($this->orgId());

        try {
            return $callback();
        } finally {
            $this->registrar->setPermissionsTeamId($previous);
        }
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = array_map(fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)), array_filter($parts));

        return implode('', array_slice($letters, 0, 2)) ?: '·';
    }
}
