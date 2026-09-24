<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Platform\AdminReason;
use App\Support\Platform\PlatformActivity;
use App\Support\Platform\PlatformCustomers;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * SUPER ADMIN · BUILD 1 · GATE 2 — Customers: see + inspect every org, and manage its platform lifecycle.
 *
 * Every READ runs inside {@see PlatformQuery::read()} (read-only, both scopes lifted, audited) and every WRITE
 * inside {@see PlatformQuery::mutate()} (audited WITH A WRITTEN REASON). No tenant data is read or written
 * outside that envelope, and no scope is bypassed here — the door does it, once.
 *
 * ⚠️⚠️ ADMIN 01 · PHASE 1 — "SUSPEND" IS RELABELLED "CLOSE PUBLIC STOREFRONT", BECAUSE THAT IS WHAT IT DOES.
 * 🔬 Measured: setting `organizations.status = suspended` closes the public booking pages
 * (ResolvePublicProperty::CLOSED_FOR_BOOKING) and does NOT stop the hotel's staff reaching /app. A button called
 * "Suspend" that leaves every user signed in is a misleading control. The behaviour becomes a real suspension in
 * Phase 2; until then the label says exactly what happens. The status value is unchanged (`suspended`).
 *
 * 🔒 Impersonation / "view as tenant" is not built here (break-glass is Phase 3). 🔒 #28 — only operational facts
 * are shown, never end-guests' contact PII.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly PlatformQuery $platform,
        private readonly PlatformCustomers $customers,
    ) {}

    /** The customer list — all orgs with plan/status/counts, searchable + status/kind-filterable. */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'kind' => $request->string('kind')->toString(),
        ];

        $rows = $this->platform->read(
            'platform.customers.list',
            fn () => $this->customers->list($filters),
            ['filters' => array_filter($filters)],
        );

        return view('admin.customers.index', ['rows' => $rows, 'filters' => $filters]);
    }

    /** One customer's detail — org facts + subscription/plan + counts + a recent audit slice. Read-only. */
    public function show(string $organization, PlatformActivity $activity): View
    {
        [$detail, $recentActivity] = $this->platform->read(
            'platform.customers.view',
            fn (): array => [
                $this->customers->detail($this->resolveOrg($organization)),
                $activity->auditSliceForOrg($organization),   // #28 — business events, not guest PII
            ],
            [],
        );

        // Plans offered for the "assign a plan" control — the global catalog, no tenant data.
        $plans = Plan::query()->where('is_active', true)->orderBy('price_minor')->get();

        return view('admin.customers.show', ['d' => $detail, 'plans' => $plans, 'recentActivity' => $recentActivity]);
    }

    /**
     * CLOSE THE PUBLIC STOREFRONT — sets status `suspended`, which closes the hotel's public booking pages.
     * ⚠️ Staff are NOT signed out and /app is NOT blocked (that is Phase 2). Re-auth + a written reason required.
     */
    public function suspend(Request $request, string $organization): RedirectResponse
    {
        // ⚠️ `current_password:admin` — QUALIFIED. /admin runs on `auth:admin`; the bare rule would check the
        // `web` guard and, with no web user, validate nothing.
        $request->validate(['current_password' => ['required', 'current_password:admin']]);
        $reason = AdminReason::from($request);

        $this->platform->mutate(
            'platform.customer.suspend',
            $reason,
            fn () => $this->resolveOrg($organization)->update(['status' => 'suspended']),
            ['effect' => 'public storefront closed; staff access unchanged'],
            'organization',
            $organization,
        );

        return redirect()->route('admin.customers.show', $organization)
            ->with('admin_notice', 'Public storefront closed. The hotel\'s staff can still sign in — a full suspension arrives in a later release.');
    }

    /** Reopen the public storefront (status back to `active`). A written reason required. */
    public function reactivate(Request $request, string $organization): RedirectResponse
    {
        $reason = AdminReason::from($request);

        $this->platform->mutate(
            'platform.customer.reactivate',
            $reason,
            fn () => $this->resolveOrg($organization)->update(['status' => 'active']),
            [],
            'organization',
            $organization,
        );

        return redirect()->route('admin.customers.show', $organization)->with('admin_notice', 'Public storefront reopened.');
    }

    /**
     * Assign a plan to a customer — sets the org's subscription plan, which DRIVES its feature-gating
     * (`Organization::planAllows`). Audited with a reason, via mutate.
     *
     * 🔒 STRIPE DEFERRED: this sets the plan + features ONLY — it does NOT create/sync a Stripe Price or
     * subscription. `stripe_subscription_id` is left exactly as-is; live billing is a separate go-live step.
     */
    public function assignPlan(Request $request, string $organization): RedirectResponse
    {
        $validated = $request->validate(['plan_id' => ['required', 'string', 'exists:plans,id']]);
        $reason = AdminReason::from($request);
        $planId = (string) $validated['plan_id'];

        $this->platform->mutate(
            'platform.customer.assign_plan',
            $reason,
            function () use ($organization, $planId): void {
                // ⚠️ No scope bypass — mutate() has already lifted the tenant scope for this closure.
                $subscription = Subscription::query()
                    ->where('organization_id', $organization)
                    ->latest()
                    ->first();

                if ($subscription !== null) {
                    // A plan CHANGE re-agrees the terms; the model's `updating` hook re-captures them.
                    $subscription->update(['plan_id' => $planId]);

                    return;
                }

                // No subscription yet → create one on this plan (active, no Stripe — the seam stays null).
                Subscription::create([
                    'organization_id' => $organization,
                    'plan_id' => $planId,
                    'status' => 'active',
                ]);
            },
            ['plan_id' => $planId],
            'organization',
            $organization,
        );

        return redirect()->route('admin.customers.show', $organization)->with('admin_notice', 'Plan assigned. The customer\'s features update immediately; billing (Stripe) is a separate step.');
    }

    /**
     * Mark an organisation INTERNAL (Vyrobel's own test/demo/probe org) or back to a CUSTOMER. Internal orgs are
     * excluded from every platform statistic by default. A written reason required.
     */
    public function setInternal(Request $request, string $organization): RedirectResponse
    {
        $validated = $request->validate(['internal' => ['required', 'boolean']]);
        $reason = AdminReason::from($request);
        $internal = (bool) $validated['internal'];

        $this->platform->mutate(
            $internal ? 'platform.customer.mark_internal' : 'platform.customer.mark_customer',
            $reason,
            fn () => $this->resolveOrg($organization)->forceFill(['is_internal' => $internal ? true : null])->save(),
            [],
            'organization',
            $organization,
        );

        return redirect()->route('admin.customers.show', $organization)
            ->with('admin_notice', $internal ? 'Marked internal — excluded from platform statistics.' : 'Marked as a customer — counted in platform statistics.');
    }

    /**
     * Resolve the org by id. `Organization` is NOT tenant-scoped (it is the tenant table itself), so this is a
     * plain global lookup — but it runs inside the read/mutate closure for a uniform audited envelope.
     */
    private function resolveOrg(string $organization): Organization
    {
        return Organization::query()->findOrFail($organization);
    }
}
