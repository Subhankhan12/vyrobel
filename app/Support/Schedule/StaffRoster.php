<?php

declare(strict_types=1);

namespace App\Support\Schedule;

use App\Models\Membership;
use App\Models\User;
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
                    'role_label' => $staffRoles[0],
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

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = array_map(fn (string $p): string => mb_substr($p, 0, 1), array_slice($parts, 0, 2));

        return mb_strtoupper(implode('', $letters)) ?: '?';
    }
}
