<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Controllers\Owner\OwnerTeamController;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\User;

/**
 * ⚠️ THE ONE RULE FOR "SHOULD THIS LOGIN STILL EXIST?" after an owner link is taken away.
 *
 * Extracted 2026-08-31 when owner REMOVAL began cascading to sub-users. It was previously private to
 * {@see OwnerTeamController}, where the OWNER removes one member at a time.
 * Removal needed exactly the same decision for every member at once, and a second copy of a rule about
 * disabling logins is precisely the two-sources-that-drift hazard (§N.8f) — so it moved rather than doubled.
 *
 * ⚠️ WHAT IT PROTECTS, AND WHY THE CASCADE IS SAFE BECAUSE OF IT. A login is disabled ONLY when the person
 * has nothing left: no other active sub-user link, no Owner record of their own, and no back-office role
 * other than `External Owner`. So someone the OPERATOR employs — a staff member who also happens to hold
 * owner-portal access — keeps their login when an owner they were attached to is removed. The cascade takes
 * away the owner link; it never silently ends an unrelated working account.
 */
final class OwnerLoginLifecycle
{
    /** Disable a login that no longer has any reason to exist. Idempotent; never touches an inactive user. */
    public static function disableIfOrphaned(string $userId): void
    {
        $user = User::query()->find($userId);

        if ($user === null || $user->status !== 'active') {
            return;
        }

        $stillSubUser = OwnerUser::query()->where('user_id', $userId)->where('status', 'active')->exists();
        $isPrimaryOwner = Owner::query()->where('user_id', $userId)->exists();
        $hasRole = $user->roles()->where('name', '!=', 'External Owner')->exists();

        if (! $stillSubUser && ! $isPrimaryOwner && ! $hasRole) {
            $user->update(['status' => 'disabled']);
        }
    }
}
