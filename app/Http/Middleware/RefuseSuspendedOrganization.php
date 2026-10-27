<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Platform\BreakGlass;
use App\Support\Platform\OtherWorkspaces;
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
        // ⚠️ ADMIN 01 · PHASE 3 — a READ-ONLY Vyrobel support session may look at a suspended organisation: the
        //    suspension refuses the organisation's OWN people; finding out why it was suspended is support's job.
        if ($request->routeIs(self::SWITCH_ROUTE) || $request->attributes->has(BreakGlass::ATTRIBUTE)) {
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

        $message = __('php/http/middleware/refuse_suspended_organization.suspended_json');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        return response()->view('errors.layout', [
            'code' => 403,
            'title' => __('php/http/middleware/refuse_suspended_organization.title'),
            'message' => $organization !== null
                ? __('php/http/middleware/refuse_suspended_organization.message_named', ['organization' => (string) $organization->name])
                : __('php/http/middleware/refuse_suspended_organization.message'),
            'actions' => 'errors.partials.suspended-actions',
            'otherWorkspaces' => OtherWorkspaces::for($request->user(), $organizationId),
        ], 403);
    }
}
