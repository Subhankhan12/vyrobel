<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Billing\SubscriptionStatus;
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
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // The Paywall/Checkout must stay reachable for a lapsed org to subscribe.
        if ($request->routeIs('app.billing') || $request->routeIs('app.billing.*')) {
            return $next($request);
        }

        $organizationId = app(TenantContext::class)->id();
        if ($organizationId === null) {
            return $next($request);
        }

        $organization = Organization::query()->find($organizationId);
        if ($organization !== null && $organization->billingStatus()->isLapsed()) {
            return redirect()->route('app.billing');
        }

        return $next($request);
    }
}
