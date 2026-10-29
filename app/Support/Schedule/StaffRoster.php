<?php

declare(strict_types=1);

namespace App\Support\Schedule;

use App\Models\Membership;
use App\Models\User;
use App\Support\Authorization\RoleLabel;
use App\Support\Tenancy\TenantContext;
use Spatie\Permission\PermissionRegistrar;

/**
 * The org's REAL staff for the roster (Phase 7d · Team Schedule). ONE definition of "who is rosterable",
 * read by the grid, the assign guard and the schedule PDF — so a shift can only ever be assigned to a real
 * staff member and the grid never shows a fabricated person.
 *
 * Staff = users with an ACTIVE {@see Membership} in the active org who hold at least one BACK-OFFICE role
 * (`config('authorization.back_office_roles')`), team-scoped. Portal-only users (`External Owner` / `Guest`)
 * hold none of those roles → they are NOT staff and never appear. Each is bucketed into a {@see StaffDepartment}.
 */
final class StaffRoster
{
    /**
     * The org's real staff, name-ordered, each with their department.
     *
     * @return list<array{id: string, name: string, initials: string, department: string, role_label: string}>
     */
    public function staff(): array
    {
        $orgId = (string) app(TenantContext::class)->id();

        $userIds = Membership::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->pluck('user_id');

        $users = User::query()->whereIn('id', $userIds)->orderBy('name')->get();

        /** @var list<string> $backOffice */
        $backOffice = (array) config('authorization.back_office_roles', []);

        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($orgId);   // roles are team-scoped (team = org)

        try {
            $out = [];
            foreach ($users as $user) {
                /** @var list<string> $roles */
                $roles = $user->getRoleNames()->all();
                $staffRoles = array_values(array_intersect($roles, $backOffice));
                if ($staffRoles === []) {
                    continue;   // a portal-only user (owner/guest) — never rostered
                }

                $name = (string) $user->name;
                $out[] = [
                    'id' => (string) $user->getKey(),
                    'name' => $name,
                    'initials' => $this->initials($name),
                    'department' => StaffDepartment::forRoles($staffRoles),
                    'role_label' => RoleLabel::for($staffRoles[0]),
                ];
            }

            return $out;
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    /**
     * The set of real staff ids — the assign guard checks a submitted `user_id` against this, so a shift can
     * never be assigned to a non-staff / cross-org user.
     *
     * @return list<string>
     */
    public function staffIds(): array
    {
        return array_map(fn (array $s): string => $s['id'], $this->staff());
    }

    /**
     * The staff in the HOUSEKEEPING department only — the assignable set for a cleaning task (Batch C ·
     * GATE 1). A `Housekeeping`/`Maintenance` role maps here ({@see StaffDepartment}); managers/front-desk
     * do not. Derived from the same real {@see staff()} set, so it can never show a fabricated person.
     *
     * @return list<array{id: string, name: string, initials: string, department: string, role_label: string}>
     */
    public function housekeepers(): array
    {
        return array_values(array_filter(
            $this->staff(),
            fn (array $s): bool => $s['department'] === StaffDepartment::HOUSEKEEPING,
        ));
    }

    /**
     * The set of real housekeeper ids — the housekeeping-assign guard checks a submitted `user_id` against
     * this, so a cleaning task can never be assigned to a non-housekeeper / non-staff / cross-org user.
     *
     * @return list<string>
     */
    public function housekeeperIds(): array
    {
        return array_map(fn (array $s): string => $s['id'], $this->housekeepers());
    }

    /**
     * The ids of everyone who counts as STAFF in the active org — the assignable pool for any operational
     * work, not just a roster shift.
     *
     * ⚠️ THIS EXISTS BECAUSE THREE MAINTENANCE SURFACES BUILT THEIR OWN POOL AND GOT IT WRONG (audit
     * 2026-08-07, the parity matrix's systemic MEDIUM). The Maintenance board, the Work Order reassign
     * picker and Maintenance Settings each selected users by ACTIVE MEMBERSHIP ALONE — and a Membership is
     * NOT operator access: External Owners hold one, because it is how their organisation resolves. So all
     * three offered "Demo Owner", "Demo Owner Accountant" and "Demo Owner Viewer" as technicians.
     *
     * Most consequential on Settings, where category AUTO-ASSIGNMENT routes new work orders to the chosen
     * user: an owner could have been handed operational work and the guest-adjacent detail that comes with
     * it — a #28 perimeter breach, not merely a cosmetic list bug.
     *
     * The roster already had the correct definition (active Membership ∩ a back-office role, so portal-only
     * users are excluded); the maintenance surfaces simply never used it. Rather than repeat the role filter
     * a fourth time, they now all read THIS. Any future "who can be assigned work" question belongs here too.
     *
     * @return list<string>
     */
    public function staffUserIds(): array
    {
        return array_map(fn (array $s): string => $s['id'], $this->staff());
    }

    /**
     * The same set as {@see staffUserIds()}, shaped for a picker: id + display name, name-ordered.
     *
     * @return list<array{id: string, name: string}>
     */
    public function assignableStaff(): array
    {
        return array_map(
            fn (array $s): array => ['id' => $s['id'], 'name' => $s['name']],
            $this->staff(),
        );
    }

    /** Two letters for an avatar — the roster's, read by team chat too (one definition). */
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = array_map(fn (string $p): string => mb_substr($p, 0, 1), array_slice($parts, 0, 2));

        return mb_strtoupper(implode('', $letters)) ?: '?';
    }
}
