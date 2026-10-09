<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\RefuseSuspendedOrganization;
use App\Models\Membership;
use App\Models\Organization;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The person's OTHER workspaces they could move to from a page that refuses them — ONE definition, read by both
 * refusals that offer "Switch to …": the suspended workspace ({@see RefuseSuspendedOrganization}) and the lapsed
 * one ({@see EnsureSubscriptionActive}). Moved here from the first on 2026-10-09 when the second needed it, so the
 * two pages can never offer different lists.
 *
 * Only the person's OWN active memberships, and only organisations that are not themselves suspended.
 */
final class OtherWorkspaces
{
    /**
     * @return array<string, string> id => name
     */
    public static function for(?Authenticatable $user, string $currentId): array
    {
        if ($user === null) {
            return [];
        }

        $ids = Membership::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('status', 'active')
            ->where('organization_id', '!=', $currentId)
            ->pluck('organization_id');

        return Organization::query()
            ->whereIn('id', $ids)
            ->where('status', '!=', TenantLifecycle::SUSPENDED)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
