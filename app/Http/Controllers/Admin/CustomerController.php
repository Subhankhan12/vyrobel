<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RefuseSuspendedOrganization;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Platform\AdminReason;
use App\Support\Platform\PlatformActivity;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformCustomers;
use App\Support\Platform\PlatformQuery;
use App\Support\Platform\TenantLifecycle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SUPER ADMIN · BUILD 1 · GATE 2 → ADMIN 01 · PHASE 2 — the TENANTS: directory, detail, triage, and lifecycle.
 *
 * Every READ runs inside {@see PlatformQuery::read()} (read-only, both scopes lifted, audited) and every WRITE
 * inside {@see PlatformQuery::mutate()} (audited WITH A WRITTEN REASON). No tenant data is read or written
 * outside that envelope, and no scope is bypassed here — the door does it, once. The organisation row itself is
 * read directly for the lock and state checks: `organizations` IS the tenant table and carries no tenant scope.
 *
 * ⚠️⚠️ LIFECYCLE — each action needs a written reason and is audited; the destructive two (suspend, archive) also
 * need the admin's password, and an acknowledgement when the hotel has guests in house or on the way:
 *   · SUSPEND    → its staff and owners are refused ({@see RefuseSuspendedOrganization}) and the storefront closes.
 *                  ⚠️ It touches NO MONEY and no reservation: no refund, no cancellation, no folio write.
 *   · REACTIVATE → the status it had before, restored — a trialing hotel stays trialing.
 *   · ARCHIVE    → soft delete. Only a SUSPENDED organisation can be archived, so it stays closed underneath.
 *   · RESTORE    → back from the archive, still suspended until someone reactivates it on purpose.
 * ⚠️ There is NO hard delete and NO prune — the founder's ruling, tied to retention he has not set.
 *
 * ⚠️⚠️ PROTECTED ORGANISATIONS ({@see TenantLifecycle::lockFor()}): the pinned baseline tenant refuses EVERY write
 * here, and the refusal is itself audited; an organisation locked `confirm` (Vyrobel Internal, which holds the
 * founder's own role accounts) needs its exact name typed before any lifecycle action.
 *
 * 🔒 Impersonation / "view as tenant" is not built here (break-glass is Phase 3). 🔒 #28 — only account metadata
 * is shown, never a guest.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly PlatformQuery $platform,
        private readonly PlatformCustomers $customers,
        private readonly PlatformAuditLogger $audit,
    ) {}

    /** The tenant directory — every org, searchable, filterable, sortable, paginated. Archived hidden by default. */
    public function index(Request $request): View
    {
        $filters = collect(['search', 'status', 'kind', 'source', 'verified', 'bucket', 'sort', 'dir'])
            ->mapWithKeys(fn (string $k): array => [$k => $request->string($k)->toString()])
            ->all();

        $rows = $this->platform->read(
            'platform.customers.list',
            fn () => $this->customers->list($filters),
            ['filters' => array_filter($filters)],
        );

        return view('admin.customers.index', ['rows' => $rows, 'filters' => $filters]);
    }

    /** The triage queue — counts per bucket, each with its definition and a link to the filtered directory. */
    public function triage(Request $request): View
    {
        $includeInternal = $request->query('internal') === 'include';

        [$buckets, $customers] = $this->platform->read(
            'platform.customers.triage',
            fn (): array => [$this->customers->triage($includeInternal), Organization::query()->customers()->count()],
            ['include_internal' => $includeInternal],
        );

        return view('admin.customers.triage', ['buckets' => $buckets, 'includeInternal' => $includeInternal, 'customers' => $customers]);
    }

    /** One customer's detail — account facts, subscription, account holders, the suspension's reach. Read-only. */
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
     * SUSPEND — the organisation's staff and owners are refused, and its storefront closes. Password + reason, and
     * an acknowledgement of the guests it has in house or arriving. ⚠️ No money, no reservation is touched.
     */
    public function suspend(Request $request, string $organization): RedirectResponse
    {
        // ⚠️ `current_password:admin` — QUALIFIED. /admin runs on `auth:admin`; the bare rule would check the
        // `web` guard and, with no web user, validate nothing.
        $request->validate(['current_password' => ['required', 'current_password:admin']]);
        $reason = AdminReason::from($request);
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, 'suspend', $reason)) {
            return $refused;
        }
        if ($org->trashed() || $org->status === TenantLifecycle::SUSPENDED) {
            return $this->back($org)->withErrors(['status' => 'This organisation is already suspended.']);
        }

        $impact = $this->impact($org);
        if ($refused = $this->refusedWithoutAcknowledgement($request, $org, $impact)) {
            return $refused;
        }

        $this->platform->mutate(
            'platform.customer.suspend',
            $reason,
            fn () => $org->forceFill(['suspended_from_status' => $org->status, 'status' => TenantLifecycle::SUSPENDED])->save(),
            ['from_status' => $org->status, 'in_house' => $impact['in_house'], 'upcoming' => $impact['upcoming']],
            'organization',
            $org->id,
        );

        return $this->back($org)->with('admin_notice', 'Suspended. Its staff and owners are refused and the storefront is closed. No reservation, folio or payment was touched.');
    }

    /** REACTIVATE — full access back, at the status it had before it was suspended. A written reason required. */
    public function reactivate(Request $request, string $organization): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, 'reactivate', $reason)) {
            return $refused;
        }
        if ($org->trashed()) {
            return $this->back($org)->withErrors(['status' => 'Restore it from the archive first.']);
        }
        if ($org->status !== TenantLifecycle::SUSPENDED) {
            return $this->back($org)->withErrors(['status' => 'This organisation is not suspended.']);
        }

        $to = in_array($org->suspended_from_status, ['active', 'trialing', 'cancelled'], true) ? $org->suspended_from_status : 'active';

        $this->platform->mutate(
            'platform.customer.reactivate',
            $reason,
            fn () => $org->forceFill(['status' => $to, 'suspended_from_status' => null])->save(),
            ['to_status' => $to],
            'organization',
            $org->id,
        );

        return $this->back($org)->with('admin_notice', 'Reactivated — its staff and owners can sign in again, and the storefront follows its status ('.$to.').');
    }

    /**
     * ARCHIVE — soft delete: hidden from the directory, every row retained, reversible. Only a SUSPENDED
     * organisation can be archived. Password + reason (+ the guest acknowledgement).
     */
    public function archive(Request $request, string $organization): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:admin']]);
        $reason = AdminReason::from($request);
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, 'archive', $reason)) {
            return $refused;
        }
        if ($org->trashed()) {
            return $this->back($org)->withErrors(['status' => 'This organisation is already archived.']);
        }
        if ($org->status !== TenantLifecycle::SUSPENDED) {
            return $this->back($org)->withErrors(['status' => 'Suspend it first — only a suspended organisation can be archived.']);
        }

        $impact = $this->impact($org);
        if ($refused = $this->refusedWithoutAcknowledgement($request, $org, $impact)) {
            return $refused;
        }

        $this->platform->mutate(
            'platform.customer.archive',
            $reason,
            fn () => $org->delete(),   // SoftDeletes — sets deleted_at, removes nothing
            ['in_house' => $impact['in_house'], 'upcoming' => $impact['upcoming']],
            'organization',
            $org->id,
        );

        return $this->back($org)->with('admin_notice', 'Archived — hidden from the directory, every record kept. It stays suspended; restore it to bring it back.');
    }

    /** RESTORE from the archive — visible again, still suspended until someone reactivates it on purpose. */
    public function restore(Request $request, string $organization): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, 'restore', $reason)) {
            return $refused;
        }
        if (! $org->trashed()) {
            return $this->back($org)->withErrors(['status' => 'This organisation is not archived.']);
        }

        $this->platform->mutate('platform.customer.restore', $reason, fn () => $org->restore(), [], 'organization', $org->id);

        return $this->back($org)->with('admin_notice', 'Restored from the archive. It is still suspended — reactivate it when access should return.');
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
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, 'assign_plan', $reason, lifecycle: false)) {
            return $refused;
        }

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

        return $this->back($org)->with('admin_notice', 'Plan assigned. The customer\'s features update immediately; billing (Stripe) is a separate step.');
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
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, $internal ? 'mark_internal' : 'mark_customer', $reason, lifecycle: false)) {
            return $refused;
        }

        $this->platform->mutate(
            $internal ? 'platform.customer.mark_internal' : 'platform.customer.mark_customer',
            $reason,
            fn () => $org->forceFill(['is_internal' => $internal ? true : null])->save(),
            [],
            'organization',
            $org->id,
        );

        return $this->back($org)
            ->with('admin_notice', $internal ? 'Marked internal — excluded from platform statistics.' : 'Marked as a customer — counted in platform statistics.');
    }

    /**
     * ⚠️⚠️ THE LOCK. `baseline` → refused outright, and the refusal is written to the ledger so it can be SEEN.
     * `confirm` → a lifecycle action needs the organisation's exact name typed. Null when the action may proceed.
     */
    private function refusedByLock(Request $request, Organization $org, string $action, string $reason, bool $lifecycle = true): ?RedirectResponse
    {
        $lock = TenantLifecycle::lockFor($org);

        if ($lock === TenantLifecycle::LOCK_BASELINE) {
            $this->audit->record('platform.customer.refused', ['attempted' => $action, 'lock' => $lock], 'organization', (string) $org->id, reason: $reason);

            return $this->back($org)->withErrors(['lock' => 'Refused — this is the protected baseline organisation. No console action may change it.']);
        }

        if ($lock === TenantLifecycle::LOCK_CONFIRM && $lifecycle
            && ! hash_equals((string) $org->name, trim((string) $request->input('confirm_name')))) {
            return $this->back($org)->withErrors(['confirm_name' => 'This organisation is protected. Type its exact name to confirm.']);
        }

        return null;
    }

    /** Guests in house or on the way → the admin must say they have seen that before it proceeds. */
    private function refusedWithoutAcknowledgement(Request $request, Organization $org, array $impact): ?RedirectResponse
    {
        if ($impact['in_house'] + $impact['upcoming'] > 0 && ! $request->boolean('acknowledge_guests')) {
            return $this->back($org)->withErrors(['acknowledge_guests' => 'This organisation has '.$impact['in_house'].' guest '.Str::plural('stay', $impact['in_house']).' in house and '.$impact['upcoming'].' upcoming. Confirm you have seen that.']);
        }

        return null;
    }

    /** @return array{in_house: int, upcoming: int} */
    private function impact(Organization $org): array
    {
        return $this->platform->read(
            'platform.customer.impact',
            fn (): array => TenantLifecycle::reservationImpact((string) $org->id),
            [],
        );
    }

    private function back(Organization $org): RedirectResponse
    {
        return redirect()->route('admin.customers.show', $org->id);
    }

    /**
     * The org by id, archived ones included. `Organization` is NOT tenant-scoped (it is the tenant table itself),
     * so this is a plain global lookup.
     */
    private function resolveOrg(string $organization): Organization
    {
        return Organization::withTrashed()->findOrFail($organization);
    }
}
