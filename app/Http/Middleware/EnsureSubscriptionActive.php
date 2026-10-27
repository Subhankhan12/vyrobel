<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Operator\WorkspaceSwitchController;
use App\Models\Organization;
use App\Support\Billing\SubscriptionStatus;
use App\Support\Platform\OtherWorkspaces;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Subscription access gate (Phase 7b · SG3) — on the `/app` group, a LAPSED org (an expired trial, or a
 * canceled/past-due subscription) is redirected to the Paywall until it subscribes. Runs after the tenant is
 * resolved and after {@see EnsureBackOfficeAccess}, so it only ever sees an operator inside their own org.
 *
 * ⚠️ THE GATE BITES ONLY AFTER THE TRIAL. An in-trial or active org has full access — a founder is NEVER
 * locked out mid-onboarding or mid-trial. And it FAILS OPEN on a missing subscription (a legacy/data-gap org
 * with no billing record is never paywalled). The state comes from the derived {@see SubscriptionStatus}.
 *
 * The billing routes themselves are ALWAYS allowed through (else a lapsed org could never reach the Paywall
 * to subscribe). Logout is a separate route outside this group, so leaving always works too.
 *
 * ⚠️⚠️ ONLY SOMEONE WHO CAN RENEW IS SENT TO THE PAYWALL (2026-10-09, his report: "I can't log into any of the
 * roles … Back to dashboard is also not working"). The Paywall is `can:billing.manage`, which the catalogue gives
 * the organisation admin alone, so every other role was redirected to a page that refused them — and that 403's
 * "Back to the dashboard" led straight back here: a loop. 🔬 Production's refusal log showed one person refused
 * `/app/billing` five times in nine seconds, from the afternoon the internal test workspace's trial ended. Now:
 *   · whoever holds `billing.manage` (by the PERMISSION, so a role granted it on the Roles page counts) → the
 *     Paywall, as before;
 *   · everyone else → a plain page saying the subscription has ended and who can renew it, with Sign out and any
 *     other workspace they belong to — the suspended-workspace page's shape and actions. Still closed (#41).
 * ⚠️ The workspace switcher is let through as well — it was caught by this redirect too, so its button led back
 *    into the loop. It only accepts the person's OWN active memberships ({@see WorkspaceSwitchController}).
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // The Paywall/Checkout must stay reachable for a lapsed org to subscribe — and the switcher, so a person
        // can leave for another workspace they belong to.
        if ($request->routeIs('app.billing') || $request->routeIs('app.billing.*') || $request->routeIs(RefuseSuspendedOrganization::SWITCH_ROUTE)) {
            return $next($request);
        }

        $organizationId = app(TenantContext::class)->id();
        if ($organizationId === null) {
            return $next($request);
        }

        $organization = Organization::query()->find($organizationId);
        if ($organization === null || ! $organization->billingStatus()->isLapsed()) {
            return $next($request);
        }

        if ($request->user()?->can('billing.manage') === true) {
            return redirect()->route('app.billing');
        }

        return $this->closed($request, $organization);
    }

    /** The page for someone who cannot renew: why the workspace is closed, who can open it, and a way out. */
    private function closed(Request $request, Organization $organization): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('php/http/middleware/ensure_subscription_active.ended_json')], 403);
        }

        return response()->view('errors.layout', [
            'code' => 403,
            'title' => __('php/http/middleware/ensure_subscription_active.title'),
            'message' => __('php/http/middleware/ensure_subscription_active.message', ['organization' => (string) $organization->name]),
            'actions' => 'errors.partials.suspended-actions',
            'otherWorkspaces' => OtherWorkspaces::for($request->user(), (string) $organization->getKey()),
        ], 403);
    }
}
