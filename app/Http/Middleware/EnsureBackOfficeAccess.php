<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * THE OPERATOR BACK-OFFICE GATE (/app) — the fix for the critical authorization hole recorded in
 * docs/DECISIONS.md 2026-07-16.
 *
 * THE HOLE THIS CLOSES: `/app` was `Route::middleware('auth')` and nothing else — the ONLY role check
 * in the whole codebase was the Horizon gate. Meanwhile {@see OwnerInviteService} gives an
 * owner an ACTIVE Membership (it is the only way {@see SetTenantForWebRequest} can resolve their org —
 * remove it and the owner portal 500s), so `auth` + a Membership silently read as "is staff". An
 * owner-role user could therefore walk into the entire operator app — rack, front desk, folios, the
 * inbox's guest conversations — and, because {@see OwnerScope} is INACTIVE off-portal
 * by design, `/app/owners/{owner}` handed them EVERY other owner's financials. That defeated owner
 * isolation (invariant #18) at the route layer.
 *
 * THE RULE: an ALLOW-LIST of back-office roles (`config('authorization.back_office_roles')`), checked
 * team-scoped against the ACTIVE organisation. Allow-list, not deny-list — so a user with no role, a
 * portal-only role (`External Owner` / `Guest`), or a role in a DIFFERENT org all fail CLOSED. It keys
 * on a role rather than a permission because no single catalog permission spans every staff role
 * (Housekeeping / Maintenance / Marketing Manager / Accountant share none).
 *
 * ORDERING (load-bearing): this MUST run after {@see SetTenantForWebRequest}, which sets spatie's
 * permissions team id — `hasAnyRole()` is team-filtered, so with no team id it matches nothing and every
 * operator would be locked out. Neither middleware is in `Router::$middlewarePriority`, so both sink to
 * the end of the pipeline in declaration order: the `web` group's SetTenantForWebRequest first, this
 * route-group middleware after. `BackOfficeAccessTest` asserts the resolved order, so a future priority
 * tweak that inverts it fails loudly instead of silently locking out staff.
 *
 * TENANT SAFETY: spatie's team filter means a role held in org A grants nothing while acting in org B —
 * the gate composes with tenant isolation rather than bypassing it.
 *
 * REJECTION (leaks nothing): an owner-linked user is REDIRECTED to their own portal (they have a home,
 * and a 403 would be a dead end for a legitimate user); everyone else gets a bare 403. Neither response
 * renders operator data or reveals whether the requested record exists.
 *
 * This gate is layer 1 of two. Layer 2 keeps owners non-operator-capable at the DATA level (they hold
 * `External Owner`, whose only permission is `owner_portal.view`), so a future route that forgets this
 * middleware still cannot hand an owner the back office.
 */
class EnsureBackOfficeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // `auth` runs first, so this is belt-and-braces — but never assume it when failing closed.
        if (! $user instanceof User) {
            abort(403);
        }

        /** @var list<string> $backOffice */
        $backOffice = config('authorization.back_office_roles', []);

        // Team-scoped by SetTenantForWebRequest: a role held in ANOTHER org does not count here.
        if ($backOffice !== [] && $user->hasAnyRole($backOffice)) {
            return $next($request);
        }

        // A linked owner — primary account holder OR an active owner-team sub-user (Phase 7c) — has a
        // real home; send them there rather than to a dead end. Resolved super-admin because the tenant
        // context may legitimately be unset (a user with no active membership never gets one), and this
        // must not throw the tenant guard on the way to a 403.
        $isOwner = app(TenantContext::class)->runAsSuperAdmin(
            fn (): bool => Owner::query()->where('user_id', $user->getKey())->exists()
                || OwnerUser::query()->where('user_id', $user->getKey())->where('status', 'active')->exists(),
        );

        if ($isOwner) {
            return redirect()->route('owner.dashboard');
        }

        abort(403);
    }
}
