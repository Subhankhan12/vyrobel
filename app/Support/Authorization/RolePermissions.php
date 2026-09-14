<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Models\User;
use App\Services\RolePermissionService;
use Spatie\Permission\Models\Role;

/**
 * ⚠️⚠️ THE ONE ANSWER TO "WHAT DOES THIS ROLE HOLD?" — and the source it must be answered from.
 *
 * ══ THE RULE ═══════════════════════════════════════════════════════════════════════════════════════════
 *
 *   ⚠️ **THE DATABASE IS THE LIVE STATE. `config/authorization.php` IS THE SHIPPED DEFAULT.**
 *   ⚠️ **EVERY SECURITY DECISION READS THE LIVE STATE. NO EXCEPTIONS.**
 *
 * ══ WHY THIS CLASS EXISTS (finding A-1, 2026-08-30 — an ACTIVE, DEMONSTRATED escalation) ═══════════════
 *
 * `RolePermissionService` writes the real spatie pivot, so a role's permissions are editable at runtime.
 * `RoleAssignmentService` asked what a role held by reading `config('authorization.roles')` — the file the
 * org was SEEDED from. Two definitions of one concept, and the security guard was on the stale side: once
 * any role was customised, the anti-escalation subset rule evaluated a world that no longer existed.
 *
 * ⚠️ THE FULL ROUND TRIP, REPRODUCED — not theorised:
 *
 *   1. A Property Owner grants Hotel Manager `roles.manage` + `billing.manage` through the matrix. LEGITIMATE.
 *   2. A Group Admin holding NEITHER assigns Hotel Manager to a confederate. ACCEPTED — the subset check
 *      compared against the SHIPPED 39 permissions, not the live 41.
 *   3. The confederate — now genuinely holding both — grants them straight back to Group Admin through the
 *      matrix, which reads live state and therefore believes them entitled. ACCEPTED.
 *
 * The actor ends up holding permissions they never had. That is a COMPLETE bypass of the rule whose entire
 * purpose is to stop `users.manage` becoming "mint an admin above me".
 *
 * ══ ⚠️ THE CONFIG IS NOT DEAD — IT KEEPS THREE LEGITIMATE JOBS. DO NOT "TIDY" THEM AWAY ════════════════
 *
 *   1. ⚠️ PROVISIONING — {@see OrganizationRoleProvisioner::provision()} seeds a
 *      NEW organisation's roles from it (`syncPermissions`). Asking the DB there would be circular.
 *   2. ⚠️ IDENTITY — which role NAMES exist at all (`back_office_roles`, and `OwnerInviteService`'s
 *      `array_key_exists` checks). The CATALOG of names is code-defined and never editable from the UI (#30).
 *   3. ⚠️ DESIGN TIER — {@see RolePermissionService::editableRoleNames()} asks "is this role a
 *      wildcard BY DESIGN?" and must keep reading config: lockedness is a property of the role's design, not
 *      of its current contents. Read live state there and narrowing a wildcard role ONCE would unlock it
 *      permanently — the opposite of the intent.
 *
 * ⚠️ A SEEDER OR A NAME-CHECK READING CONFIG IS CORRECT. A GUARD READING CONFIG IS THE BUG.
 *
 * ══ WHY A MAP AND NOT A PER-ROLE GETTER ════════════════════════════════════════════════════════════════
 *
 * Both callers need several roles at once (`assignableRolesFor()` walks all 11; `assertNotLastRoleManager()`
 * walks them too). A per-role getter would turn one bounded read into a query per role per request — the
 * §2 N+1 shape. {@see mapFor()} is ONE eager-loaded read (2 statements) and is threaded through the private
 * helpers rather than memoised, so there is no cache to go stale across a write.
 *
 * ⚠️ A wildcard role needs NO special case here: the provisioner expands `['*']` into all 51 permissions in
 * the DB, so a Property Owner reads back as 51 — verified, not assumed.
 */
final class RolePermissions
{
    /**
     * Every role provisioned for this organisation, mapped to the permission names it CURRENTLY holds.
     *
     * ⚠️ A role ABSENT from this map is NOT PROVISIONED for the org — which is different from a role that
     * is provisioned and holds nothing, and callers must tell them apart. An absent role read as `[]` would
     * be a subset of everything, which would make an unprovisioned role assignable by anybody.
     *
     * @return array<string, list<string>>
     */
    public static function mapFor(string $organizationId): array
    {
        $map = [];

        foreach (Role::query()->where('organization_id', $organizationId)->with('permissions:id,name')->get() as $role) {
            $map[(string) $role->name] = $role->permissions
                ->pluck('name')->map(fn ($n): string => (string) $n)->values()->all();
        }

        return $map;
    }

    /**
     * The union of what a set of roles holds — an actor's maximum privilege.
     *
     * ⚠️ Pure: it takes a map from {@see mapFor()} rather than querying, so an actor's privilege and the role
     * being managed are always read from the SAME snapshot. Comparing two reads taken at different moments is
     * how a subset check silently answers about a state that never existed.
     *
     * @param  list<string>  $roleNames
     * @param  array<string, list<string>>  $map
     * @return list<string>
     */
    public static function union(array $roleNames, array $map): array
    {
        $perms = [];

        foreach ($roleNames as $name) {
            $perms = array_merge($perms, $map[$name] ?? []);
        }

        return array_values(array_unique($perms));
    }

    /** The capability whose loss cannot be repaired from inside the product — see {@see roleManagersAfter()}. */
    public const ROLE_MANAGER_PERMISSION = 'roles.manage';

    /**
     * ⚠️⚠️ THE ONE LAST-ADMIN QUESTION: WHO WOULD STILL BE ABLE TO MANAGE ACCESS AFTER THIS CHANGE?
     *
     * ⚠️ WHY `roles.manage` AND NOT SOMETHING ELSE. It is the only capability whose loss is UNRECOVERABLE from
     * inside the product. Lose `users.manage` and a `roles.manage` holder grants it back through the matrix.
     * Lose `roles.manage` and nothing can restore it: the matrix route requires it, and there is no reset path
     * (`OrganizationRoleProvisioner` runs at org creation and from seeders only). "The last wildcard holder"
     * and "the last `users.manage` holder" were both considered and REJECTED as too strict — each would block
     * an org that has deliberately given `roles.manage` to a second role from restructuring, every day, to
     * prevent something that needs a mistake or a stolen session.
     *
     * ⚠️ TWO CALLERS, TWO HYPOTHETICALS, ONE DEFINITION (§N.8f):
     *   · the MATRIX asks "if role X stopped granting `roles.manage`…"        → $withoutRole
     *   · REVOCATION asks "if user U stopped holding role X…"                 → $withoutUserRole
     * Expressing these as two guards is how they would drift; the survivor set is computed once, here.
     *
     * ⚠️ LIVE STATE ONLY. The permission side comes from the $map ({@see mapFor()}), the membership side from
     * spatie — never from `config/authorization.php`. A floor satisfiable against the shipped file would be
     * finding A-1 wearing a different hat.
     *
     * ⚠️ Must run with the spatie team pinned to this organisation: `User::role()` is team-scoped, and outside
     * a team it answers for none, which would report EVERY change as a lockout.
     *
     * ⚠️ SCOPE, STATED: this counts holders, not sign-in ability. A member whose `users.status` is `inactive`
     * still counts — the same semantics {@see RolePermissionService::assertNotLastRoleManager()}
     * has always had. No operator route deactivates a back-office user today, so the case is not currently
     * reachable from `/app`; widening it is a separate decision, deliberately not taken here.
     *
     * @param  array<string, list<string>>  $map  from {@see mapFor()}
     * @param  string|null  $withoutRole  a role treated as no longer granting `roles.manage`
     * @param  array{0: string, 1: string}|null  $withoutUserRole  [user id, role name] treated as no longer held
     * @return list<string> the ids of members who would still hold `roles.manage`
     */
    public static function roleManagersAfter(
        array $map,
        ?string $withoutRole = null,
        ?array $withoutUserRole = null,
    ): array {
        $survivors = [];

        foreach ($map as $roleName => $permissions) {
            if ($roleName === $withoutRole) {
                continue;
            }
            if (! in_array(self::ROLE_MANAGER_PERMISSION, $permissions, true)) {
                continue;   // this role grants no role-management — it cannot keep the org administrable
            }

            foreach (User::role($roleName)->pluck('id') as $id) {
                if ($withoutUserRole !== null && (string) $id === $withoutUserRole[0] && $roleName === $withoutUserRole[1]) {
                    continue;   // the very holding this change would remove
                }
                $survivors[(string) $id] = true;
            }
        }

        if ($survivors === []) {
            return [];
        }

        // ⚠️ A HOLDER IS NOT AN ADMINISTRATOR. An account that cannot sign in grants nobody the ability to
        //    act, so counting it lets the floor be propped up by a locked-out user — and an organisation left
        //    with only locked-out managers CANNOT be revived from inside the product (the password-reset
        //    broker's lookup is scoped to the same status, so they cannot even request a reset).
        //
        // ⚠️ Deactivation is reachable from ordinary operator work: removing an owner deactivates that owner's
        //    linked user, whatever back-office roles it also holds. See §0o.
        return User::query()
            ->whereIn('id', array_keys($survivors))
            ->where('status', User::SIGN_IN_STATUS)   // ⚠️ the SAME rule the login query applies
            ->pluck('id')->map(fn ($id): string => (string) $id)->values()->all();
    }

    /**
     * ⚠️ THE ONE QUESTION THAT MUST *NOT* READ LIVE STATE: is this role a TOP-TIER role BY DESIGN?
     *
     * `['*']` in `config/authorization.php` is a statement about what a role IS, not about what it currently
     * contains. Lockedness has to be read from the design, because reading it from the database would mean
     * that narrowing a wildcard role ONCE unlocks it permanently — the exact opposite of the intent.
     *
     * ⚠️ It lives here, beside the live-state readers, so that "which source answers which question" is a
     * single decision a reader meets in one file, and so the guard on the two services can be TOTAL: neither
     * of them touches `config('authorization.roles')` in executable code at all.
     */
    public static function isWildcardByDesign(string $roleName): bool
    {
        /** @var array<string, mixed> $roles */
        $roles = (array) config('authorization.roles', []);

        return array_values(array_map('strval', (array) ($roles[$roleName] ?? []))) === ['*'];
    }

    /**
     * @param  list<string>  $needle
     * @param  list<string>  $haystack
     */
    public static function isSubset(array $needle, array $haystack): bool
    {
        return array_diff($needle, $haystack) === [];
    }
}
