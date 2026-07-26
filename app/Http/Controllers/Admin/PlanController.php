<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['array'],
            'features.*.enabled' => ['nullable', 'boolean'],
            'features.*.limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $model->update([
            'name' => (string) $validated['name'],
            'price_minor' => (int) round(((float) $validated['price']) * 100),   // major → integer minor (#17)
            'is_active' => $request->boolean('is_active'),
        ]);

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

        $this->audit->record('platform.plan.updated', ['plan' => $model->code], 'plan', $model->id);

        return redirect()->route('admin.plans.index')->with('admin_notice', $model->name.' updated.');
    }

    /** Toggle a plan active/inactive (a plan that is inactive is not offered on new checkouts). Audited. */
    public function toggle(Request $request, string $plan): RedirectResponse
    {
        $model = Plan::query()->findOrFail($plan);
        $model->update(['is_active' => ! $model->is_active]);

        $this->audit->record('platform.plan.toggled', ['plan' => $model->code, 'is_active' => $model->is_active], 'plan', $model->id);

        return redirect()->route('admin.plans.index')->with('admin_notice', $model->name.($model->is_active ? ' activated.' : ' deactivated.'));
    }
}
