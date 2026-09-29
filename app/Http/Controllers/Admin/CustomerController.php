<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Controller;
use App\Http\Middleware\RefuseSuspendedOrganization;
use App\Mail\OwnerInviteMail;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Services\Platform\OrganizationDeletion;
use App\Services\Platform\OrganizationDeletionRefused;
use App\Services\TenantProvisioningService;
use App\Support\Billing\StripeTaxIdSync;
use App\Support\Countries;
use App\Support\Mail\UndeliverableRecipients;
use App\Support\Platform\AdminReason;
use App\Support\Platform\ConsoleFields;
use App\Support\Platform\MailDelivery;
use App\Support\Platform\OrganizationSignIns;
use App\Support\Platform\PlatformActivity;
use App\Support\Platform\PlatformAdminRole;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformCustomers;
use App\Support\Platform\PlatformQuery;
use App\Support\Platform\TenantLifecycle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

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
 * ⚠️ ARCHIVE stays reversible. The ONE permanent path is DELETE ({@see self::destroy()}) — the founder's ruling of
 *   2026-09-26 ("delete everything, always"), Owner-only, from a suspended organisation. There is still NO prune.
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
    public function show(string $organization, PlatformActivity $activity, OrganizationSignIns $signIns, OrganizationDeletion $deletion): View
    {
        $mayDelete = (bool) auth('admin')->user()?->may(PlatformAdminRole::TENANTS_DELETE);
        [$detail, $recentActivity, $signInActivity, $deletePreview] = $this->platform->read(
            'platform.customers.view',
            function () use ($organization, $activity, $signIns, $deletion, $mayDelete): array {
                $org = $this->resolveOrg($organization);
                // ADMIN 05 — what a deletion would remove, counted only where the Delete control can be offered at all
                $deletable = $mayDelete && TenantLifecycle::lockFor($org) === null && ($org->trashed() || $org->status === TenantLifecycle::SUSPENDED);

                return [
                    $this->customers->detail($org),
                    $activity->auditSliceForOrg($organization),   // #28 — business events, not guest PII
                    $signIns->for($organization),                  // ADMIN 04 — its account holders' sign-ins, never a guest's
                    $deletable ? $deletion->preview($organization) + ['refusal' => $deletion->refusal($organization)] : null,
                ];
            },
            [],
        );

        // Plans offered for the "assign a plan" control — the global catalog, no tenant data.
        $plans = Plan::query()->where('is_active', true)->orderBy('price_minor')->get();

        return view('admin.customers.show', ['d' => $detail, 'plans' => $plans, 'recentActivity' => $recentActivity, 'signIns' => $signInActivity, 'deletePreview' => $deletePreview]);
    }

    /**
     * ⚠️⚠️ ADMIN 05 (2026-09-26) — PERMANENTLY DELETE an organisation and everything it owns: the founder's ruling,
     * "delete everything, always", which replaces the standing "no hard delete" for this one deliberate action.
     * OWNER ONLY (`tenants.delete`); only a SUSPENDED or archived organisation (archive's rule — its staff, owners and
     * storefront are already stopped); the admin's password, the organisation's exact name typed, a written reason and
     * an explicit acknowledgement. BOTH protected locks refuse outright. It REFUSES while money is moving at a third
     * party, or while another organisation's records point into this one ({@see OrganizationDeletion::refusal()}).
     * ⚠️ Audited in two rows: the intent with the reason BEFORE acting, and the result with what was removed. A failure
     * part-way rolls the whole deletion back and is recorded `.failed`.
     */
    public function destroy(Request $request, string $organization, OrganizationDeletion $deletion): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'acknowledge_delete' => ['accepted'],
        ], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);
        $org = $this->resolveOrg($organization);

        if (($lock = TenantLifecycle::lockFor($org)) !== null) {
            $this->audit->record('platform.customer.refused', ['attempted' => 'delete', 'lock' => $lock], 'organization', (string) $org->id, reason: $reason);

            return $this->back($org)->withErrors(['lock' => 'Refused — this organisation is protected and can never be deleted from the console.']);
        }
        if (! hash_equals((string) $org->name, trim((string) $request->input('confirm_name')))) {
            return $this->back($org)->withErrors(['confirm_name' => 'Type the organisation’s exact name to confirm the deletion.']);
        }
        if (! $org->trashed() && $org->status !== TenantLifecycle::SUSPENDED) {
            return $this->back($org)->withErrors(['status' => 'Suspend it first — only a suspended or archived organisation can be deleted.']);
        }
        if (($refusal = $this->platform->read('platform.customer.delete_check', fn (): ?string => $deletion->refusal((string) $org->id), [])) !== null) {
            return $this->back($org)->withErrors(['delete' => 'Nothing was deleted. '.$refusal]);
        }

        $id = (string) $org->id;
        $name = (string) $org->name;
        $slug = (string) $org->slug;
        // a large organisation is many statements in one transaction — give it the time, where the host allows it
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        try {
            $result = $this->platform->mutate(
                'platform.customer.delete',
                $reason,
                fn (): array => $deletion->delete($org),
                ['name' => $name, 'slug' => $slug],
                'organization',
                $id,
            );
        } catch (OrganizationDeletionRefused $e) {
            // nothing was deleted, and the service says why in its own plain words
            return $this->stillThere($id)
                ? $this->back($org)->withErrors(['delete' => 'Nothing was deleted. '.$e->getMessage()])
                : redirect()->route('admin.customers.index')->withErrors(['delete' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);
            // ⚠️ the service never throws after its commit — but "nothing was deleted" is CHECKED, never assumed
            if (! $this->stillThere($id)) {
                return redirect()->route('admin.customers.index')->withErrors(['delete' => '“'.$name.'” was deleted, but the console could not confirm what was removed — see the error log.']);
            }

            return $this->back($org)->withErrors(['delete' => 'Nothing was deleted — the deletion failed part-way and was rolled back. The cause is in the error log.']);
        }

        $meta = [
            'name' => $name, 'slug' => $slug,
            // ⚠️ ADMIN 06 — every row removed, and where: the same plan the confirmation counted
            'records' => $result['records'], 'tables' => $result['tables'],
            'own' => $result['own'], 'linked' => $result['linked'], 'accounts' => $result['accounts'], 'per_table' => $result['per_table'],
            'users_deleted' => $result['users_deleted'], 'users_kept' => $result['users_kept'],
            'files_deleted' => $result['files_deleted'], 'files_missing' => $result['files_missing'], 'files_shared' => $result['files_shared'],
            'files_failed' => count($result['files_failed']), 'files_failed_paths' => array_slice($result['files_failed'], 0, 50),
            'stripe' => $result['stripe'],   // ids to close in Stripe by hand — not secrets, and the only record once the rows are gone
            'survivors' => $result['survivors'],
            'warnings' => $result['warnings'],
        ];
        $ledgerNote = '';
        try {
            $this->audit->record('platform.customer.deleted', $meta, 'organization', $id, reason: $reason);
        } catch (Throwable $e) {
            // the deletion HAS happened: it is told as such, and the result goes to the log instead of a 500
            report($e);
            Log::error('ADMIN 05 — an organisation was deleted, but its result could not be written to the console ledger.', ['organization_id' => $id] + $meta);
            $ledgerNote = ' ⚠️ The result could not be written to the console ledger (the request was) — the details are in the error log.';
        }

        $failed = count($result['files_failed']);
        $atStripe = count($result['stripe']['customers']) + count($result['stripe']['subscriptions']) + count($result['stripe']['connect_accounts']);

        return redirect()->route('admin.customers.index')->with('admin_notice', 'Deleted “'.$name.'” permanently — '
            .number_format($result['records']).' '.Str::plural('record', $result['records']).' in '.$result['tables'].' '.Str::plural('table', $result['tables']).', '
            .$result['users_deleted'].' '.Str::plural('account', $result['users_deleted']).' and '
            .$result['files_deleted'].' '.Str::plural('file', $result['files_deleted']).' removed.'
            .($result['users_kept'] > 0 ? ' '.$result['users_kept'].' '.($result['users_kept'] === 1 ? 'person who also belongs elsewhere kept their account.' : 'people who also belong elsewhere kept their accounts.') : '')
            .($result['files_missing'] > 0 ? ' '.$result['files_missing'].' '.Str::plural('file', $result['files_missing']).' had already gone from storage.' : '')
            .($result['files_shared'] > 0 ? ' '.$result['files_shared'].' '.Str::plural('file', $result['files_shared']).' shared with another organisation stayed.' : '')
            .($failed > 0 ? ' ⚠️ '.$failed.' '.Str::plural('file', $failed).' could not be removed — their paths are in the ledger and the error log.' : '')
            .($atStripe > 0 ? ' ⚠️ Stripe still holds '.$atStripe.' '.Str::plural('record', $atStripe).' for it — customers, subscriptions or connected accounts; their ids are in the ledger. Close them in Stripe.' : '')
            .($result['warnings'] !== [] ? ' ⚠️ '.implode(' ', $result['warnings']) : '')
            .' The console’s own record of the deletion is kept.'.$ledgerNote);
    }

    private function stillThere(string $organizationId): bool
    {
        return DB::table('organizations')->where('id', $organizationId)->exists();
    }

    /**
     * SUSPEND — the organisation's staff and owners are refused, and its storefront closes. Password + reason, and
     * an acknowledgement of the guests it has in house or arriving. ⚠️ No money, no reservation is touched.
     */
    public function suspend(Request $request, string $organization): RedirectResponse
    {
        // ⚠️ `current_password:admin` — QUALIFIED. /admin runs on `auth:admin`; the bare rule would check the
        // `web` guard and, with no web user, validate nothing.
        $request->validate(['current_password' => ['required', 'current_password:admin']], [], ConsoleFields::NAMES);
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
        $request->validate(['current_password' => ['required', 'current_password:admin']], [], ConsoleFields::NAMES);
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
        $validated = $request->validate(['plan_id' => ['required', 'string', 'exists:plans,id']], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);
        $planId = (string) $validated['plan_id'];
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, 'assign_plan', $reason, lifecycle: false)) {
            return $refused;
        }

        // ⚠️ ADMIN 03B — the plan it is ALREADY on is refused before anything is written. Measured: re-assigning it
        //    changed no subscription row (Eloquent saves nothing when the value is unchanged) but still wrote an
        //    `assign_plan` ledger row with the admin's reason and said "Plan assigned" — a ledger claiming a change that
        //    never happened. The detail page preselects the CURRENT plan, so that was one careless click away.
        $currentPlanId = $this->platform->read(
            'platform.customer.current_plan',
            fn (): ?string => Subscription::query()->where('organization_id', $organization)->latest()->value('plan_id'),
            [],
        );
        if ($currentPlanId !== null && (string) $currentPlanId === $planId) {
            return $this->back($org)->withErrors(['plan_id' => 'This organisation is already on that plan — nothing was changed. Choose a different plan to assign.']);
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
        $validated = $request->validate(['internal' => ['required', 'boolean']], [], ConsoleFields::NAMES);
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

    /**
     * ⚠️⚠️ ADMIN 04 (2026-09-26) — UPDATE AN ORGANISATION'S DETAILS: name, country, language and billing, the
     * founder's ruling. ⚠️ Currency and the web address (slug) are NOT editable here: changing currency once prices
     * exist would corrupt money, and a slug change breaks links. A written reason, audited, the lock respected
     * (the baseline refuses; a protected organisation needs its exact name typed), and every value READ BACK.
     * ⚠️ The ledger records WHICH fields changed, never their values — a billing email is someone's contact.
     * ⚠️ A changed VAT number reaches Stripe from here too ({@see StripeTaxIdSync}), exactly as it does from the
     * operator's own two screens that edit it — otherwise which screen was used would decide the tax.
     */
    public function updateDetails(Request $request, string $organization): RedirectResponse
    {
        $org = $this->resolveOrg($organization);
        // ⚠️ `organizations.country` is an ISO code (char 2) — the ONE country list normalises a name or a
        //    lower-case code to it, and only a code that list knows is accepted (an unknown one would not fit).
        $request->merge(['country' => Countries::toCode($request->input('country'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', Rule::in(array_keys(Countries::options()))],
            // the app's own list — plus the value already stored, so a language set elsewhere survives an unrelated save
            'default_locale' => ['required', Rule::in(array_values(array_unique(array_filter([...(array) config('app.available_locales', ['en']), (string) $org->default_locale]))))],
            'billing_email' => ['nullable', 'email', 'max:255'],
            // A CHANGED value meets the operator's Settings limits — the stricter of its two writers, so it passes both;
            // an UNCHANGED one (the operator's Billing screen allows longer) only has to fit its column.
            'billing_company' => ['nullable', 'string', $this->unchanged($request, $org, 'billing_company') ? 'max:255' : 'max:160'],
            'billing_vat_id' => ['nullable', 'string', $this->unchanged($request, $org, 'billing_vat_id') ? 'max:255' : 'max:40'],
            'billing_city' => ['nullable', 'string', 'max:120'],
        ], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);

        if ($refused = $this->refusedByLock($request, $org, 'update_details', $reason)) {
            return $refused;
        }

        $changes = [];
        foreach (self::EDITABLE_DETAILS as $field) {
            $value = isset($data[$field]) ? trim((string) $data[$field]) : '';
            $value = $value === '' ? null : $value;
            if ((string) $org->getAttribute($field) !== (string) $value) {
                $changes[$field] = $value;
            }
        }

        if ($changes === []) {
            return $this->back($org)->with('admin_notice', 'Nothing changed — every value was already as entered.');
        }

        // ⚠️ The Stripe copy of a VAT number is a SIDE EFFECT of a save that has already landed: if Stripe cannot be
        //    reached, the save still stands, the ledger stays true, and the admin is told — never a 500 over a saved edit.
        $stripeOk = $this->platform->mutate(
            'platform.customer.update_details',
            $reason,
            function () use ($org, $changes): bool {
                $org->forceFill($changes)->save();
                if (! array_key_exists('billing_vat_id', $changes)) {
                    return true;
                }
                try {
                    StripeTaxIdSync::push($org->fresh() ?? $org);

                    return true;
                } catch (Throwable $e) {
                    report($e);

                    return false;
                }
            },
            ['changed' => array_keys($changes)],
            'organization',
            (string) $org->id,
        );

        // READ BACK — a write that did not land must never be reported as saved.
        $stored = Organization::withTrashed()->findOrFail($org->id);
        $missed = array_keys(array_filter($changes, fn ($value, string $field): bool => (string) $stored->getAttribute($field) !== (string) $value, ARRAY_FILTER_USE_BOTH));
        $words = array_map(fn (string $field): string => $field === 'name' ? 'name' : ConsoleFields::NAMES[$field], array_keys($changes));

        return $missed === []
            ? $this->back($org)->with('admin_notice', 'Saved — read back from the database: '.implode(', ', $words).'.'
                .($stripeOk ? '' : ' ⚠️ The new VAT number could not be sent to Stripe just now — it is saved here; save again to retry.'))
            : $this->back($org)->withErrors(['details' => 'The write did not land for: '.implode(', ', $missed).'.']);
    }

    /** ADMIN 04 — the "create an organisation" form: the same choices self-signup offers, and one founder. */
    public function create(): View
    {
        return view('admin.customers.create', [
            'currencies' => OnboardingController::CURRENCIES,
            'types' => OnboardingController::TYPES,
            'mailDiscards' => MailDelivery::discards(),
        ]);
    }

    /**
     * ⚠️⚠️ ADMIN 04 (2026-09-26) — CREATE AN ORGANISATION from the console. It goes through the ONE door every
     * organisation is created by ({@see TenantProvisioningService} — roles, founder as `Property Owner`, first
     * property and trial, all in one transaction), so it can never be a half-made tenant.
     *
     * ⚠️ THE FOUNDER GETS IN BY A SET-PASSWORD LINK, NEVER A PASSWORD TYPED HERE (the founder's ruling). The founder
     * is created with no password; the single-use link ({@see OwnerInviteService::issueFounderActivation()}) sets one
     * and verifies the address. So creation is REFUSED — before anything is written — when that link could not reach
     * them: mail is discarding, the address can never receive mail, or an account already holds the address.
     * ⚠️ Audited in two rows because the ledger writes BEFORE acting: the intent with the reason, then the result with
     * the new organisation's id (and a third for the link). A creation that throws gets its own `.failed` row.
     */
    public function store(Request $request, TenantProvisioningService $provisioner, OwnerInviteService $invites): RedirectResponse
    {
        $request->merge([
            'country' => Countries::toCode($request->input('country')),
            'founder_email' => strtolower(trim((string) $request->input('founder_email'))),
        ]);
        $data = $request->validate([
            'org_name' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(OnboardingController::CURRENCIES)],
            'country' => ['nullable', 'string', Rule::in(array_keys(Countries::options()))],
            'property_name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', Rule::in(OnboardingController::TYPES)],
            'location' => ['nullable', 'string', 'max:255'],
            'founder_name' => ['required', 'string', 'max:255'],
            'founder_email' => ['required', 'email', 'max:255'],
            'internal' => ['sometimes', 'boolean'],
        ], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);

        $refusal = match (true) {
            MailDelivery::discards() => 'Mail is not being delivered (mail is “'.MailDelivery::mailer().'”), so the founder could never receive the set-password link. Nothing was created.',
            ($why = UndeliverableRecipients::reason($data['founder_email'])) !== null => 'This address can never receive mail ('.$why.'), so the founder could never receive the set-password link. Nothing was created.',
            User::query()->where('email', $data['founder_email'])->exists() => 'An account already uses this email address. Nothing was created.',
            default => null,
        };
        if ($refusal !== null) {
            return redirect()->route('admin.customers.create')->withInput()->withErrors(['founder_email' => $refusal]);
        }

        $internal = (bool) ($data['internal'] ?? false);
        $result = $this->platform->mutate(
            'platform.customer.create',
            $reason,
            function () use ($provisioner, $data, $internal): array {
                $result = $provisioner->provision(
                    organization: ['name' => $data['org_name'], 'country' => $data['country'] ?? null, 'currency' => $data['currency']],
                    founder: ['name' => (string) $data['founder_name'], 'email' => (string) $data['founder_email'], 'password' => null],   // none — the link sets it
                    property: ['name' => $data['property_name'], 'type' => $data['property_type'], 'location' => $data['location'] ?? null],
                    signupSource: 'console',
                );
                if ($internal) {
                    $result['organization']->forceFill(['is_internal' => true])->save();
                }

                return $result;
            },
            ['internal' => $internal],
            'organization',
            null,
        );

        /** @var Organization $org */
        $org = $result['organization'];
        $this->audit->record('platform.customer.created', ['source' => 'console', 'internal' => $internal], 'organization', (string) $org->id, reason: $reason);

        [$invite, $token] = $invites->issueFounderActivation($result['user'], (string) $org->id);
        $sentTo = null;
        try {
            Mail::to($data['founder_email'])->send(new OwnerInviteMail($invite, $token, (string) $data['founder_name']));
            $sentTo = true;
        } catch (Throwable $e) {
            Log::warning('The founder set-password link could not be sent.', ['organization_id' => $org->id, 'error' => $e::class]);
            $sentTo = false;
        }
        $this->audit->record($sentTo ? 'platform.customer.founder_link' : 'platform.customer.founder_link.failed', ['handed_to_mailer' => $sentTo], 'organization', (string) $org->id, reason: $reason);

        return redirect()->route('admin.customers.show', $org->id)->with('admin_notice', $sentTo
            ? 'Created. The founder’s set-password link was handed to the mailer — it is single-use and expires in '.OwnerInviteService::EXPIRY_DAYS.' days.'
            : 'Created — but the set-password email could not be sent. Use Support → “Send password reset” for the founder once mail is working.');
    }

    /** Whether a submitted value is exactly what is already stored (so a looser, older limit may apply to it). */
    private function unchanged(Request $request, Organization $org, string $field): bool
    {
        return trim((string) $request->input($field)) === (string) $org->getAttribute($field);
    }

    /** ADMIN 04 — what "Update details" may change. Currency and slug are absent on purpose (see updateDetails). */
    private const EDITABLE_DETAILS = ['name', 'country', 'default_locale', 'billing_email', 'billing_company', 'billing_vat_id', 'billing_city'];

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
