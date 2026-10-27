<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Billing\PlanIntegrity;
use App\Support\Platform\AdminReason;
use App\Support\Platform\ConsoleFields;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * SUPER ADMIN · BUILD 1 · GATE 3 — Plan management. The `plans` + `plan_features` tables are a GLOBAL,
 * first-party catalog (NOT tenant-scoped), seeded from config/billing.php and now AUTHORITATIVE: the admin
 * edits them here, and `Organization::planAllows()` reads `plan_features` at runtime, so a plan's feature set
 * is what actually gates a customer's modules.
 *
 * Plans are global (no tenant scope), so these reads/writes need NO cross-tenant bypass — but every WRITE is
 * still audited to the platform action ledger, and the whole area sits behind auth:admin + mandatory 2FA.
 *
 * 🔒 STRIPE BILLING SYNC IS DEFERRED (Q4): editing a plan sets its price + features (which drive display +
 * gating); it does NOT create or update a Stripe Price / subscription. That live-billing connection is a
 * separate, careful go-live step — the `stripe_price_id` column is the seam, left untouched here.
 */
class PlanController extends Controller
{
    /** The real, gateable feature keys (as seeded from config/billing.php into plan_features). */
    private const FEATURE_KEYS = ['analytics', 'channels', 'team'];

    public function __construct(private readonly PlatformAuditLogger $audit) {}

    /** List every plan in the catalog (grouped tier × interval), with its feature entitlements. */
    public function index(): View
    {
        $plans = Plan::query()->with('features')->orderBy('price_minor')->orderBy('interval')->get();

        return view('admin.plans.index', ['plans' => $plans]);
    }

    /** The create form — both shapes (the panel's reason for existing: a per-unit plan had no in-app path). */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Create a plan of EITHER shape.
     *
     * ⚠️ A new plan is always created INACTIVE. Its Stripe Price does not exist yet — only the founder can
     * create that — so offering it immediately would put a tier on the paywall whose checkout cannot complete.
     * It becomes sellable when someone pastes a real Price id and activates it, which
     * {@see PlanIntegrity::blockedFromActivation()} enforces.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(PlanIntegrity::rules(), ConsoleFields::messages(), ConsoleFields::names());
        $reason = AdminReason::from($request);   // ⚠️ before any write — ADMIN 01 · Phase 1

        $plan = Plan::create($this->columnsFrom($validated) + [
            'code' => (string) $validated['code'],
            'is_active' => false,
        ]);

        $this->audit->record('platform.plan.created', [
            'plan' => $plan->code, 'pricing_type' => $plan->pricing_type,
        ], 'plan', $plan->id, reason: $reason);

        return redirect()->route('admin.plans.edit', $plan->id)
            ->with('admin_notice', __('php/http/controllers/admin/plan_controller.created_draft', ['plan' => $plan->name]));
    }

    /**
     * The money columns for a validated payload — the ONE mapping, shared by create and update (§N.8f).
     *
     * ⚠️ Each shape writes only its OWN basis and NULLs the other. A per-unit plan left carrying a stale
     * `price_minor` from a previous flat life would show one number in the catalogue and bill another.
     *
     * @param  array<string, mixed>  $v
     * @return array<string, mixed>
     */
    private function columnsFrom(array $v): array
    {
        $perUnit = ($v['pricing_type'] ?? null) === Plan::PRICING_PER_UNIT;

        return [
            'name' => (string) $v['name'],
            'pricing_type' => (string) $v['pricing_type'],
            'interval' => (string) $v['interval'],
            'currency' => strtoupper((string) $v['currency']),
            // major → integer minor (#17), never a float in the column.
            'price_minor' => $perUnit ? 0 : (int) round(((float) ($v['price'] ?? 0)) * 100),
            'unit_rate_minor' => $perUnit ? (int) round(((float) ($v['unit_rate'] ?? 0)) * 100) : null,
            'vat_pct' => ($v['vat_pct'] ?? null) === null ? null : (int) $v['vat_pct'],
            'stripe_price_id' => ($v['stripe_price_id'] ?? '') !== '' ? (string) $v['stripe_price_id'] : null,
        ];
    }

    /** Edit one plan — name, price, active, and its feature toggles/limits. */
    public function edit(string $plan): View
    {
        $model = Plan::query()->with('features')->findOrFail($plan);

        return view('admin.plans.edit', ['plan' => $model]);
    }

    /** Persist plan changes — integer money (#17); feature toggles drive real gating. Audited. */
    public function update(Request $request, string $plan): RedirectResponse
    {
        $model = Plan::query()->findOrFail($plan);

        // ⚠️ THE SAME RULESET AS CREATE (§N.8f). This method previously validated only `name` + `price`, so a
        // per-unit plan could be saved through it with no rate at all, and `pricing_type` was not checked to be
        // one of the two shapes the engine understands.
        $validated = $request->validate(PlanIntegrity::rules($model->id) + [
            'is_active' => ['nullable', 'boolean'],
            'features' => ['array'],
            'features.*.enabled' => ['nullable', 'boolean'],
            'features.*.limit' => ['nullable', 'integer', 'min:0'],
        ], ConsoleFields::messages(), ConsoleFields::names());
        $reason = AdminReason::from($request);   // ⚠️ before any write — ADMIN 01 · Phase 1

        $model->update($this->columnsFrom($validated));

        // ⚠️ ACTIVATION IS NOT A CHECKBOX, IT IS A GATE. A plan can only be offered once it is actually
        // sellable — see PlanIntegrity. Editing a live plan is safe by construction: existing subscribers bill
        // from the terms CAPTURED on their subscription, so nothing here reaches them.
        if ($request->boolean('is_active')) {
            $blocked = PlanIntegrity::blockedFromActivation($model->fresh() ?? $model);

            if ($blocked !== null) {
                return redirect()->route('admin.plans.edit', $model->id)
                    ->with('admin_error', __('php/http/controllers/admin/plan_controller.saved_stays_draft', ['reason' => $blocked]));
            }
        }

        $model->update(['is_active' => $request->boolean('is_active')]);

        // Upsert every known gateable feature. Iterated unconditionally (not "only present keys") because an
        // UNCHECKED checkbox submits nothing — so an absent key means DISABLED, and must still be written.
        foreach (self::FEATURE_KEYS as $key) {
            $limitRaw = $request->input("features.{$key}.limit");
            $model->features()->updateOrCreate(
                ['key' => $key],
                [
                    'enabled' => $request->boolean("features.{$key}.enabled"),
                    'limit' => ($limitRaw === null || $limitRaw === '') ? null : (int) $limitRaw,
                ],
            );
        }

        $this->audit->record('platform.plan.updated', ['plan' => $model->code], 'plan', $model->id, reason: $reason);

        return redirect()->route('admin.plans.index')->with('admin_notice', __('php/http/controllers/admin/plan_controller.updated', ['plan' => $model->name]));
    }

    /** Toggle a plan active/inactive (a plan that is inactive is not offered on new checkouts). Audited. */
    public function toggle(Request $request, string $plan): RedirectResponse
    {
        $model = Plan::query()->findOrFail($plan);
        $reason = AdminReason::from($request);   // ⚠️ before any write — ADMIN 01 · Phase 1

        // Turning a plan ON is gated; turning it OFF never is — retiring a plan must always be possible, and a
        // retired plan keeps its existing subscribers on their captured terms.
        if (! $model->is_active) {
            $blocked = PlanIntegrity::blockedFromActivation($model);

            if ($blocked !== null) {
                return redirect()->route('admin.plans.index')
                    ->with('admin_error', __('php/http/controllers/admin/plan_controller.cannot_activate', ['plan' => $model->name, 'reason' => $blocked]));
            }
        }

        $model->update(['is_active' => ! $model->is_active]);

        $this->audit->record('platform.plan.toggled', ['plan' => $model->code, 'is_active' => $model->is_active], 'plan', $model->id, reason: $reason);

        return redirect()->route('admin.plans.index')->with('admin_notice', $model->is_active ? __('php/http/controllers/admin/plan_controller.activated', ['plan' => $model->name]) : __('php/http/controllers/admin/plan_controller.deactivated', ['plan' => $model->name]));
    }
}
