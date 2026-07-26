<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * SUPER ADMIN · BUILD 1 · GATE 3 — the reusable PLAN FEATURE gate (tenant-side).
 *
 * A route protected by `plan.feature:channels` is available to an org whose current PLAN grants `channels`,
 * and gated otherwise — driven by `Organization::planAllows()` reading the DB `plan_features` the platform
 * admin controls. This is the app's plan→feature-gating pattern (7b's Pro-only channel gate) made reusable.
 *
 * 🔒 ADDITIVE + FAIL-OPEN: `planAllows()` returns TRUE for an org with no subscription or an unknown feature
 * key, so applying this gate NEVER changes behaviour for an org whose plan grants (or doesn't mention) the
 * feature — only a plan that EXPLICITLY excludes the feature gates it, to a clean "not in your plan" state.
 * If no org is resolved (no tenant context) it also passes — the platform admin never invents a restriction.
 */
class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $orgId = App::make(TenantContext::class)->id();

        if ($orgId === null) {
            return $next($request);   // no tenant context → nothing to gate (fail open, never invent a limit)
        }

        $organization = Organization::query()->find($orgId);

        if ($organization === null || $organization->planAllows($feature)) {
            return $next($request);
        }

        // Gated — a clean, honest "not in your plan" page (never a broken screen).
        return response()->view('billing.feature-locked', ['feature' => $feature], 403);
    }
}
