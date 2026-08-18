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

    /**
     * The subset of an org's back-office users who ALSO hold a given permission — the audience for a
     * notification whose content is gated on that permission.
     *
     * ⚠️ WHY THIS EXISTS (operator deep audit, Finding 1 · OP-FIX-3). `operatorsForOrg()` is ONE bucket for
     * all eleven back-office roles, so a Finance-group notification carrying an owner's net payable reached a
     * housekeeper exactly as it reached the accountant. That was never a scope bug — every user genuinely owns
     * their own rows — so a route gate on `/app/notifications` would have been the wrong instrument: it would
     * block the operational notifications a housekeeper legitimately has. **The leak closes at the addressing
     * layer: a role that may not read the money is never sent it.**
     *
     * ⚠️ AND THE PERMISSION IS NOT A GUESS. Both finance notifications link to `/app/owners`, which carries
     * `reports.view` (OP-FIX-2). A recipient must be able to open the page their notification points at, or
     * the notification is the same dead affordance this audit fixed at the control layer one pass earlier.
     *
     * @return Collection<int, User>
     */
    public function operatorsForOrgWithPermission(?string $orgId, string $permission): Collection
    {
        // The team is already pinned by operatorsForOrg(); permission checks are team-scoped the same way, and
        // a null team fails SILENTLY to false — so this must never run ahead of that pinning (#30).
        return $this->operatorsForOrg($orgId)
            ->filter(fn (User $u): bool => $u->can($permission))
            ->values();
    }
}
