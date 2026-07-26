<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Platform\PlatformActivity;
use App\Support\Platform\PlatformCustomers;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * SUPER ADMIN · BUILD 1 · GATE 2 — Customers: see + inspect every org, and manage its platform lifecycle.
 *
 * Every READ runs inside {@see PlatformQuery::read()} and every WRITE inside {@see PlatformQuery::mutate()} —
 * both admin-guard-gated, audited, and wrapped in the existing scope-restored `runAsSuperAdmin` closure. No
 * tenant data is ever read/written outside that envelope.
 *
 * 🔒 Impersonation / "view as tenant" is DELIBERATELY NOT built (high-risk — deferred). 🔒 #28 — only
 * operational facts are shown, never end-guests' contact PII.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly PlatformQuery $platform,
        private readonly PlatformCustomers $customers,
    ) {}

    /** The customer list — all orgs with plan/status/counts, searchable + status-filterable. */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $rows = $this->platform->read(
            'platform.customers.list',
            fn (): array => $this->customers->list($filters),
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

        // Plans offered for the "assign a plan" control (Gate 3) — the global catalog, no tenant data.
        $plans = Plan::query()->where('is_active', true)->orderBy('price_minor')->get();

        return view('admin.customers.show', ['d' => $detail, 'plans' => $plans, 'recentActivity' => $recentActivity]);
    }

    /** Suspend a customer — a real, audited platform-lifecycle status change. */
    public function suspend(string $organization): RedirectResponse
    {
        $this->platform->mutate(
            'platform.customer.suspend',
            fn () => $this->resolveOrg($organization)->update(['status' => 'suspended']),
            [],
            'organization',
            $organization,
        );

        return redirect()->route('admin.customers.show', $organization)->with('admin_notice', 'Customer suspended.');
    }

    /**
     * Assign a plan to a customer (Gate 3) — sets the org's subscription plan, which DRIVES its feature-gating
     * (`Organization::planAllows` reads the plan's `plan_features`). Audited, via mutate (Subscription is
     * tenant-scoped, so the write runs inside the scope-restored bypass with an explicit organization_id).
     *
     * 🔒 STRIPE DEFERRED (Q4): this sets the plan + features ONLY — it does NOT create/sync a Stripe Price or
     * subscription. `stripe_subscription_id` is left exactly as-is; live billing is a separate go-live step.
     */
    public function assignPlan(Request $request, string $organization): RedirectResponse
    {
        $validated = $request->validate(['plan_id' => ['required', 'string', 'exists:plans,id']]);
        $planId = (string) $validated['plan_id'];

        $this->platform->mutate(
            'platform.customer.assign_plan',
            function () use ($organization, $planId): void {
                $subscription = Subscription::withoutGlobalScopes()
                    ->where('organization_id', $organization)
                    ->latest()
                    ->first();

                if ($subscription !== null) {
                    $subscription->update(['plan_id' => $planId]);   // plan only — Stripe untouched (deferred)

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

    /** Reactivate a suspended customer. */
    public function reactivate(string $organization): RedirectResponse
    {
        $this->platform->mutate(
            'platform.customer.reactivate',
            fn () => $this->resolveOrg($organization)->update(['status' => 'active']),
            [],
            'organization',
            $organization,
        );

        return redirect()->route('admin.customers.show', $organization)->with('admin_notice', 'Customer reactivated.');
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
