<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\Owner;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

/**
 * Shared role-home routing for the auth controllers (login + password reset): an Owner-linked user →
 * the owner portal; else an active org member → the operator app; else null (no workspace). The Owner
 * lookup runs super-admin (there is no tenant context at auth time); memberships are queried directly
 * (the link table is deliberately un-scoped). One definition so login and reset never diverge.
 */
trait RedirectsByRole
{
    protected function homeFor(User $user): ?string
    {
        $isOwner = app(TenantContext::class)->runAsSuperAdmin(
            fn (): bool => Owner::query()->where('user_id', $user->getKey())->exists(),
        );

        if ($isOwner) {
            return route('owner.dashboard');
        }

        if ($user->memberships()->where('status', 'active')->exists()) {
            return route('app.dashboard');
        }

        return null;
    }
}
