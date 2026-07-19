<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\Membership;
use App\Models\User;
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
 *   • NO SELF-MANAGEMENT — the actor cannot change their OWN roles (belt-and-suspenders against self-escalation,
 *     and it prevents an admin locking themselves out by revoking their own last role).
 *   • TENANT — the target must be a member of the ACTING organization; another org's user is untouchable.
 *   • CATALOG ROLES ONLY — the role must be one of `back_office_roles` (Super Admin / External Owner / Guest and
 *     any invented name are refused). Roles are team-scoped (team = org).
 */
class RoleAssignmentService
{
    public function __construct(
        private TenantContext $tenant,
        private PermissionRegistrar $registrar,
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
                    'permissions' => $this->groupedPermissions($name),
                    'permission_count' => count($this->rolePermissions($name)),
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
     * The back-office roles the actor MAY assign — those whose permissions the actor already holds in full (the
     * subset rule). The UI offers ONLY these, so an admin is never shown an option that would 403 in the service.
     *
     * @return list<string>
     */
    public function assignableRolesFor(User $actor): array
    {
        return $this->withTeam(function () use ($actor): array {
            $actorPerms = $this->permissionsOf($actor->getRoleNames()->all());

            return array_values(array_filter(
                $this->backOfficeRoles(),
                fn (string $role): bool => $this->isSubset($this->rolePermissions($role), $actorPerms),
            ));
        });
    }

    /** Assign a catalog back-office role to a target user, enforcing every anti-escalation bound. */
    public function assign(User $actor, User $target, string $roleName): void
    {
        $this->withTeam(function () use ($actor, $target, $roleName): void {
            $this->assertManageable($actor, $target, $roleName);
            $target->assignRole($roleName);
        });
    }

    /** Remove a catalog back-office role from a target user, enforcing the same bounds. */
    public function revoke(User $actor, User $target, string $roleName): void
    {
        $this->withTeam(function () use ($actor, $target, $roleName): void {
            $this->assertManageable($actor, $target, $roleName);
            $target->removeRole($roleName);
        });
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

        // ⚠️ THE ANTI-ESCALATION SUBSET RULE — you can only manage a role whose permissions you all already hold.
        $actorPerms = $this->permissionsOf($actor->getRoleNames()->all());
        if (! $this->isSubset($this->rolePermissions($roleName), $actorPerms)) {
            throw new RoleAssignmentException('You can’t assign a role with more access than your own.');
        }
    }

    private function targetInActingOrg(User $target): bool
    {
        return Membership::query()
            ->where('organization_id', $this->orgId())
            ->where('user_id', $target->getKey())
            ->exists();
    }

    /**
     * A role's permission NAMES from the fixed catalog — `['*']` expanded to the whole permission list.
     *
     * @return list<string>
     */
    private function rolePermissions(string $roleName): array
    {
        /** @var array<string, mixed> $roles */
        $roles = (array) config('authorization.roles', []);
        $perms = $roles[$roleName] ?? [];

        if ($perms === ['*']) {
            return array_values((array) config('authorization.permissions', []));
        }

        return array_values(array_map('strval', (array) $perms));
    }

    /**
     * The union of catalog permissions across a set of role names (the actor's max privilege).
     *
     * @param  list<string>  $roleNames
     * @return list<string>
     */
    private function permissionsOf(array $roleNames): array
    {
        $perms = [];
        foreach ($roleNames as $name) {
            $perms = array_merge($perms, $this->rolePermissions($name));
        }

        return array_values(array_unique($perms));
    }

    /**
     * @param  list<string>  $needle
     * @param  list<string>  $haystack
     */
    private function isSubset(array $needle, array $haystack): bool
    {
        return array_diff($needle, $haystack) === [];
    }

    /**
     * A role's permissions grouped by resource for READ-ONLY display — e.g. `['Reservations' => ['view', 'create']]`.
     *
     * @return array<string, list<string>>
     */
    private function groupedPermissions(string $roleName): array
    {
        $grouped = [];
        foreach ($this->rolePermissions($roleName) as $perm) {
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
