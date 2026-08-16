<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The EDITABLE permission-matrix engine (task #102 · Decision RL1 · ⚠️ sensitive security surface). It writes the
 * REAL spatie `role_has_permissions` pivot (team-scoped), so a toggled permission GENUINELY grants/revokes a
 * capability that `$user->can()` reads at once — mutation-provable, never a fake toggle. `config/authorization.php`
 * is the one-time SEED; once a role is edited, this service reads its CURRENT permissions from the DB (not config,
 * which would be stale). Every write is guarded and audited.
 *
 * THE BOUNDS (in the service, so every caller is covered — the #27/#29/#30 discipline):
 *   • EDITABLE ROLES ONLY — the non-wildcard back-office roles. Super Admin / Property Owner (`['*']`) and the
 *     portal roles (External Owner / Guest) are LOCKED: editing a wildcard role would silently convert it to an
 *     explicit list (drift) or strip the org's top tier (lockout).
 *   • CATALOG PERMISSIONS ONLY — a toggle may only touch a permission that exists in `config('authorization.permissions')`.
 *   • THE SUBSET RULE — the actor may only GRANT a permission they already hold themselves (no self-escalation).
 *   • THE LAST-ADMIN SAFETY — an edit that would leave ZERO active org members able to manage roles is refused.
 *   • TEAM-SCOPED — every read/write pins the spatie team to the acting org, so one org's edit never touches another's.
 *
 * The ROUTE additionally gates this on `can:roles.manage` (org-admin: Property Owner + Super Admin).
 */
class RolePermissionService
{
    /** The capability whose loss would lock an org out of role management — the last-admin guard protects it. */
    private const ROLE_MANAGER_PERMISSION = 'roles.manage';

    /** D23-2 — the display DOMAIN each permission resource belongs to. Presentation only (see {@see domainFor}). */
    public const DOMAIN_OTHER = 'Other';

    /**
     * resource → display domain. ⚠️ A MAP, NOT A FILTER: a resource missing here still renders, under
     * {@see self::DOMAIN_OTHER}. The order of the domains on screen is {@see self::DOMAIN_ORDER}.
     *
     * @var array<string, string>
     */
    public const DOMAINS = [
        // Front-of-house and the daily running of the property.
        'reservations' => 'Operations',
        'availability' => 'Operations',
        'restrictions' => 'Operations',
        'rates' => 'Operations',
        'housekeeping' => 'Operations',
        'maintenance' => 'Operations',
        'schedule' => 'Operations',
        'units' => 'Operations',
        'properties' => 'Operations',
        'addons' => 'Operations',
        'amenities' => 'Operations',
        // Everything about the people staying, and what they can reach.
        'guests' => 'Guests',
        'guest_documents' => 'Guests',
        'guest_portal' => 'Guests',
        'reviews' => 'Guests',
        'corporate_accounts' => 'Guests',
        // Money in and money out.
        'payments' => 'Finance',
        'payouts' => 'Finance',
        'invoices' => 'Finance',
        'folios' => 'Finance',
        'billing' => 'Finance',
        'taxes' => 'Finance',
        'fees' => 'Finance',
        'reports' => 'Finance',
        'owner_portal' => 'Finance',
        // Who may do what, and how the system is configured.
        'users' => 'System & access',
        'roles' => 'System & access',
        'settings' => 'System & access',
        'organizations' => 'System & access',
        'api' => 'System & access',
    ];

    /** The order domains appear in — most-used first; Other last so a new resource is visible, not buried. */
    public const DOMAIN_ORDER = ['Operations', 'Guests', 'Finance', 'System & access', self::DOMAIN_OTHER];

    public function __construct(
        private TenantContext $tenant,
        private PermissionRegistrar $registrar,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * The full editable matrix for the screen: every editable role with its meta + members + its permissions
     * (from the DB), grouped into sections, each flagged granted/lockable (the actor can only toggle what they hold).
     *
     * @return array{permissions: list<array{key: string, label: string}>, sections: list<array{section: string, domain: string, permissions: list<array{key: string, label: string, resource: string}>}>, roles: list<array<string, mixed>>}
     */
    public function matrix(User $actor): array
    {
        return $this->withTeam(function () use ($actor): array {
            $catalog = $this->catalogPermissions();
            $actorPerms = $this->actorPermissions($actor);
            $sections = $this->sections($catalog);

            $roles = [];
            foreach ($this->editableRoleNames() as $name) {
                $granted = $this->currentPermissionsUnsafe($name);
                $members = User::role($name)->orderBy('name')->get()->map(fn (User $u): array => [
                    'id' => (string) $u->getKey(),
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                    'initials' => $this->initials((string) $u->name),
                ])->all();

                $roles[] = [
                    'name' => $name,
                    'desc' => self::describe($name),
                    'level' => $this->level($granted),
                    'grant_count' => count(array_values(array_intersect($granted, $catalog))),
                    'total_count' => count($catalog),
                    'granted' => array_values($granted),
                    'members' => $members,
                    'member_count' => count($members),
                ];
            }

            return [
                'permissions' => array_map(fn (string $p): array => ['key' => $p, 'label' => $this->permLabel($p)], $catalog),
                'sections' => $sections,
                'roles' => $roles,
                'lockable' => $actorPerms,   // the permissions the actor may grant (subset rule) — UI disables the rest
            ];
        });
    }

    /**
     * Save the desired granted-permission set for one editable role — diffs against the DB, grants/revokes each
     * difference through spatie (team-scoped), enforcing every bound. Audited. Idempotent (an unchanged set no-ops).
     *
     * @param  list<string>  $wantGranted
     * @return array{granted: int, revoked: int}
     */
    public function save(User $actor, string $roleName, array $wantGranted): array
    {
        if (! $this->isEditable($roleName)) {
            throw new RoleAssignmentException('That role’s permissions can’t be edited here.');
        }

        $catalog = $this->catalogPermissions();
        $want = array_values(array_intersect(array_unique($wantGranted), $catalog));   // catalog-only
        $actorPerms = $this->withTeam(fn (): array => $this->actorPermissions($actor));

        return $this->withTeam(function () use ($actor, $roleName, $want, $actorPerms): array {
            $role = $this->roleModel($roleName);
            $current = $role->permissions->pluck('name')->map(fn ($n): string => (string) $n)->all();

            $toGrant = array_values(array_diff($want, $current));
            $toRevoke = array_values(array_diff($current, $want));

            // THE SUBSET RULE — the actor may only grant a permission they already hold.
            foreach ($toGrant as $perm) {
                if (! in_array($perm, $actorPerms, true)) {
                    throw new RoleAssignmentException('You can’t grant a permission you don’t hold yourself.');
                }
            }

            // THE LAST-ADMIN SAFETY — never remove the org's ability to manage roles.
            if (in_array(self::ROLE_MANAGER_PERMISSION, $toRevoke, true)) {
                $this->assertNotLastRoleManager($roleName);
            }

            foreach ($toGrant as $perm) {
                $role->givePermissionTo($perm);
            }
            foreach ($toRevoke as $perm) {
                $role->revokePermissionTo($perm);
            }

            if ($toGrant !== [] || $toRevoke !== []) {
                // Record WHICH permissions changed (not a value dump) — the sensitive-mutation audit trail (#20).
                $this->audit->record('role.permissions_updated', null, null, [
                    'role' => $roleName,
                    'granted' => $toGrant,
                    'revoked' => $toRevoke,
                    'actor_id' => (string) $actor->getKey(),
                ]);
            }

            return ['granted' => count($toGrant), 'revoked' => count($toRevoke)];
        });
    }

    /** The non-wildcard back-office roles (the editable set) — wildcard + portal roles are locked. */
    public function editableRoleNames(): array
    {
        return array_values(array_filter(
            $this->backOfficeRoles(),
            fn (string $role): bool => $this->configPermissions($role) !== ['*'],
        ));
    }

    public function isEditable(string $roleName): bool
    {
        return in_array($roleName, $this->editableRoleNames(), true);
    }

    // ── guards / helpers ─────────────────────────────────────────────────────────────────────────

    /**
     * Refuse an edit that would leave ZERO active org members able to manage roles. Counts members who hold a
     * role OTHER than $excludeRole whose CURRENT permissions include `roles.manage` (the wildcard Property Owner
     * qualifies — its DB set contains it). Must run inside {@see withTeam()}.
     */
    private function assertNotLastRoleManager(string $excludeRole): void
    {
        foreach ($this->backOfficeRoles() as $role) {
            if ($role === $excludeRole) {
                continue;
            }
            if (! in_array(self::ROLE_MANAGER_PERMISSION, $this->currentPermissionsUnsafe($role), true)) {
                continue;
            }
            if (User::role($role)->exists()) {
                return;   // another role still grants role-management to a real member — safe
            }
        }

        throw new RoleAssignmentException('At least one role must keep permission-management access — this would lock the workspace out.');
    }

    /** A role's CURRENT permission names from the DB (team must already be pinned). */
    private function currentPermissionsUnsafe(string $roleName): array
    {
        $role = Role::query()->where('name', $roleName)->where('organization_id', $this->orgId())->first();

        return $role === null ? [] : $role->permissions->pluck('name')->map(fn ($n): string => (string) $n)->all();
    }

    private function roleModel(string $roleName): Role
    {
        $role = Role::query()->where('name', $roleName)->where('organization_id', $this->orgId())->first();
        if ($role === null) {
            throw new RoleAssignmentException('That role isn’t set up for your organization.');
        }

        return $role;
    }

    /** The union of the actor's CURRENT (DB) permissions across their roles — their max privilege. */
    private function actorPermissions(User $actor): array
    {
        $perms = [];
        foreach ($actor->getRoleNames() as $roleName) {
            $perms = array_merge($perms, $this->currentPermissionsUnsafe((string) $roleName));
        }

        return array_values(array_unique($perms));
    }

    /** @return list<string> */
    private function catalogPermissions(): array
    {
        return array_values(array_map('strval', (array) config('authorization.permissions', [])));
    }

    /** @return list<string> */
    private function configPermissions(string $roleName): array
    {
        /** @var array<string, mixed> $roles */
        $roles = (array) config('authorization.roles', []);

        return array_values(array_map('strval', (array) ($roles[$roleName] ?? [])));
    }

    /** @return list<string> */
    private function backOfficeRoles(): array
    {
        return array_values(array_map('strval', (array) config('authorization.back_office_roles', [])));
    }

    /**
     * Group the flat catalog into resource sections for the matrix (Reservations / Guests / …), each tagged
     * with the DOMAIN it displays under (D23-2).
     *
     * @param  list<string>  $catalog
     * @return list<array{section: string, domain: string, permissions: list<array{key: string, label: string, resource: string}>}>
     */
    private function sections(array $catalog): array
    {
        $grouped = [];
        foreach ($catalog as $perm) {
            [$resource] = array_pad(explode('.', $perm, 2), 2, '');
            $grouped[ucfirst(str_replace('_', ' ', $resource))][] = ['key' => $perm, 'label' => $this->permLabel($perm), 'resource' => $resource];
        }
        ksort($grouped);

        return array_map(
            fn (string $section, array $perms): array => [
                'section' => $section,
                // D23-2 — the DOMAIN this resource belongs to, for a coarser display tier above the
                // resource headings. Presentation only: it partitions the same sections, adds no permission
                // and removes none.
                // A section only exists because a permission was pushed into it, so [0] is always present.
                'domain' => self::domainFor((string) $perms[0]['resource']),
                'permissions' => $perms,
            ],
            array_keys($grouped),
            $grouped,
        );
    }

    /**
     * D23-2 — which broad DOMAIN a permission's resource belongs to, for display grouping only.
     *
     * ⚠️ THIS CHANGES WHERE A ROW IS DRAWN AND NOTHING ELSE. The catalogue, the labels, the grants and the
     * spatie-teams checks are all untouched; a role granted `units.manage` is identical whether or not
     * "Operations" is a heading. 49 permissions currently sit under **30** resource headings, 20 of which
     * hold a single row — grouped in name, flat to the eye. This is the coarser tier that makes it scannable.
     *
     * ⚠️ THE LABELS ARE DELIBERATELY NOT TOUCHED. The design's friendly names ("Manage team & roles") were
     * REFUSED by the design pass for a reason that still holds: that phrase conflates `users.manage` and
     * `roles.manage`, which this product deliberately separates — they are the two tiers gating this very
     * page. Blurring two permissions on the screen that grants them is a correctness risk, not a copy
     * improvement. Every row keeps its exact 1:1 key-derived label; only the headings above them changed.
     *
     * ⚠️ UNMAPPED RESOURCES FALL TO "Other" AND ARE STILL SHOWN. A new permission added tomorrow appears
     * under Other rather than vanishing from the matrix — an admin cannot grant what they cannot see.
     */
    public static function domainFor(string $resource): string
    {
        return self::DOMAINS[$resource] ?? self::DOMAIN_OTHER;
    }

    private function permLabel(string $perm): string
    {
        [, $action] = array_pad(explode('.', $perm, 2), 2, '');

        return ucfirst(str_replace('_', ' ', $action));
    }

    /** A derived tier label from a role's real permissions — honest, not a stored field. */
    private function level(array $granted): string
    {
        if (in_array('roles.manage', $granted, true) || in_array('organizations.manage', $granted, true)) {
            return 'Admin';
        }
        if (in_array('users.manage', $granted, true)) {
            return 'Manager';
        }
        if (in_array('rates.manage', $granted, true) || in_array('reports.view', $granted, true) || in_array('properties.manage', $granted, true)) {
            return 'Supervisor';
        }

        return 'Team';
    }

    /** A short honest description of what each real role does. */
    private static function describe(string $roleName): string
    {
        return match ($roleName) {
            'Group Admin' => 'Runs the organization — team, settings and finance, across every property.',
            'Hotel Manager' => 'Runs the property day-to-day — reservations, rates, guests and operations.',
            'Revenue Manager' => 'Owns pricing, availability and performance reporting.',
            'Front Desk' => 'Reservations, calendar, folios, guests and messages — no settings or owner finance.',
            'Reservation Agent' => 'Creates and manages reservations and guest records.',
            'Housekeeping' => 'The housekeeping board only — room statuses and their own assignments.',
            'Maintenance' => 'Maintenance tickets and the housekeeping board.',
            'Accountant' => 'Folios, payments, payouts, invoices, taxes and reports.',
            'Marketing Manager' => 'Reviews, messaging and marketing tools.',
            'Guest Support Agent' => 'Guest messages and support across reservations.',
            default => 'A back-office role.',
        };
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
