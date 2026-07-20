<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Resolves WHO receives a notification (Phase 3). Keeps recipient logic in one place so every wired event routes
 * to the right, isolation-scoped audience:
 *   • operator notifications → the org's active BACK-OFFICE staff (tenant-scoped);
 *   • owner notifications → the ONE User linked to that owner (owner-scoped, resolved by the caller).
 *
 * Runs inside the event's organization context (the dispatcher wraps it), so tenant-scoped reads resolve; the
 * spatie team is pinned here before any role check.
 */
class NotificationRecipients
{
    /**
     * The active operator (back-office) users of an org — the audience for an operator-facing notification.
     * Excludes owners (who hold only `External Owner`, not a back-office role) by construction.
     *
     * @return Collection<int, User>
     */
    public function operatorsForOrg(?string $orgId): Collection
    {
        if ($orgId === null) {
            return collect();
        }

        /** @var list<string> $backOffice */
        $backOffice = config('authorization.back_office_roles', []);
        if ($backOffice === []) {
            return collect();
        }

        $userIds = Membership::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->pluck('user_id')
            ->all();

        if ($userIds === []) {
            return collect();
        }

        // Role checks are team-scoped (team = org) — pin the team before asking hasAnyRole.
        app(PermissionRegistrar::class)->setPermissionsTeamId($orgId);

        return User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->filter(fn (User $u): bool => $u->hasAnyRole($backOffice))
            ->values();
    }
}
