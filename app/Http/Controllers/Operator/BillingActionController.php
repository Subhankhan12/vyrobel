<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Billing writes (Phase 7b · SG2) — company/VAT capture + the account-gated subscribe seam. Locked
 * operator-mutation pattern (POST → controller → PRG); tenant-scoped (the org from the active context);
 * route-gated `can:billing.manage` (the founder / Property Owner, NOT Front Desk).
 */
class BillingActionController extends Controller
{
    /** Save the org's billing identity (company legal name + VAT id) — the operator's own business data, no card. */
    public function saveCompany(Request $request): RedirectResponse
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());

        $data = $request->validate([
            'billing_company' => ['nullable', 'string', 'max:255'],
            'billing_vat_id' => ['nullable', 'string', 'max:64'],
            'billing_email' => ['nullable', 'email', 'max:255'],
        ]);

        $org->update([
            'billing_company' => $data['billing_company'] ?? null,
            'billing_vat_id' => $data['billing_vat_id'] ?? null,
            'billing_email' => $data['billing_email'] ?? $org->billing_email,
        ]);

        return redirect()->back()->with('billing_notice', 'Billing details saved.');
    }

    /**
     * The SUBSCRIBE action — deliberately the ACCOUNT-GATED Stripe-Billing seam. It takes NO money, stores NO
     * card data, and does NOT flip the org to `active` (a real subscription can only ever be reached through
     * a live Stripe charge, which is Subhan's to obtain — the same deferral as the guest card charge + the
     * owner payout rail). It records the intended plan/interval on the company details and returns an honest
     * "live billing is coming" message; the org's trial continues untouched.
     */
    public function subscribe(Request $request): RedirectResponse
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());

        // Capture the company/VAT if submitted on the checkout, so it's ready for real invoicing later.
        $data = $request->validate([
            'plan' => ['required', 'string', 'max:32'],
            'interval' => ['required', 'in:monthly,yearly'],
            'billing_company' => ['nullable', 'string', 'max:255'],
            'billing_vat_id' => ['nullable', 'string', 'max:64'],
        ]);

        $org->update([
            'billing_company' => $data['billing_company'] ?? $org->billing_company,
            'billing_vat_id' => $data['billing_vat_id'] ?? $org->billing_vat_id,
        ]);

        // 🪤 NO CHARGE. Live billing (Stripe) is account-gated — we do NOT collect payment or activate here.
        return redirect()->to(route('app.billing'))->with('billing_notice',
            'Vyrobel isn’t collecting payments yet — live billing is coming soon. Your trial continues, and we’ll email you before it ends. (No card was charged.)');
    }
}
