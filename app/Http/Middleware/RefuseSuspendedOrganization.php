<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Membership;
use App\Models\Organization;
use App\Support\Platform\TenantLifecycle;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 2 — A SUSPENDED (OR ARCHIVED) ORGANISATION'S PEOPLE ARE REFUSED. Staff at `/app`, owners at
 * `/owner`, and owner API tokens — and, registered as a Livewire PERSISTENT middleware, every component update a
 * page opened before the suspension tries to send afterwards. A plain explanation, never a blank 403.
 *
 * ⚠️ WHY THIS WAS NEEDED: until Phase 2 the `suspended` status closed only the public storefront and the hotel's
 * staff carried on working — a "Suspend" button that suspended nothing (Phase 1 relabelled it until this landed).
 *
 * ⚠️ The rule itself is {@see TenantLifecycle::refusesAccess()} — this class only applies it. It runs AFTER the
 * tenant is resolved (the `web` group's SetTenantForWebRequest; ResolveApiOwnerContext for the API). No tenant
 * means no organisation to refuse, and the gates that follow decide.
 *
 * ⚠️ THE ONE ROUTE LET THROUGH: the workspace switcher, so a person who also belongs to another, working
 * organisation can move to it instead of being stuck. It only accepts the person's OWN active memberships.
 * ⚠️ It touches nothing that belongs to guests: the guest realm and the public webhooks never run this.
 */
class RefuseSuspendedOrganization
{
    public const SWITCH_ROUTE = 'app.workspace.switch';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(self::SWITCH_ROUTE)) {
            return $next($request);
        }

        $organizationId = app(TenantContext::class)->id();
        if ($organizationId === null) {
            return $next($request);
        }

        $organization = Organization::withTrashed()->find($organizationId);
        if (! TenantLifecycle::refusesAccess($organization)) {
            return $next($request);
        }

        $message = 'Access to this organisation’s Vyrobel workspace is suspended.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        return response()->view('errors.layout', [
            'code' => 403,
            'title' => 'This workspace is suspended',
            'message' => ($organization !== null ? $organization->name.'’s' : 'This')
                .' Vyrobel workspace has been suspended, so the back office and the owner portal can’t be opened '
                .'right now. Nothing has been deleted — reservations, guests and payments are kept exactly as they '
                .'were, and guests can still reach their own bookings. To find out why, or to restore access, '
                .'contact Vyrobel.',
            'actions' => 'errors.partials.suspended-actions',
            'otherWorkspaces' => $this->otherWorkspaces($request, $organizationId),
        ], 403);
    }

    /**
     * The person's OTHER active memberships whose organisation is itself usable — so the page can offer them.
     *
     * @return array<string, string> id => name
     */
    private function otherWorkspaces(Request $request, string $currentId): array
    {
        $user = $request->user();
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
