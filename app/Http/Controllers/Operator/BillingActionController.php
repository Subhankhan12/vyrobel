<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\BillingService;
use App\Services\BillingWebhookService;
use App\Services\SubscriptionLifecycleService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

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
     * The SUBSCRIBE action (Phase 8 · SG1 — LIVE Stripe Billing, test-mode-first). Creates a Stripe Checkout
     * Session for the chosen tier×interval and redirects the operator to Stripe's hosted card page — the card
     * NEVER touches our server (PCI SAQ A). 🔒 This does NOT activate the org: fulfilment is on the
     * `checkout.session.completed` WEBHOOK ({@see BillingWebhookService}), so a closed tab still
     * activates once Stripe delivers the event. The redirect is UX only.
     */
    public function subscribe(Request $request, BillingService $billing): RedirectResponse
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());

        $data = $request->validate([
            'plan' => ['required', 'string', 'max:32'],
            'interval' => ['required', 'in:monthly,yearly'],
            'billing_company' => ['nullable', 'string', 'max:255'],
            'billing_vat_id' => ['nullable', 'string', 'max:64'],
        ]);

        // Capture the company/VAT for real invoicing, and set the invoice email for the Stripe customer.
        $org->update([
            'billing_company' => $data['billing_company'] ?? $org->billing_company,
            'billing_vat_id' => $data['billing_vat_id'] ?? $org->billing_vat_id,
        ]);

        try {
            $session = $billing->startCheckout($org, (string) $data['plan'], (string) $data['interval']);
        } catch (Throwable $e) {
            report($e);

            return redirect()->to(route('app.billing'))->with('billing_error',
                'We couldn’t start checkout right now — please try again, or contact support.');
        }

        // UX only — fulfilment is on the webhook. Off-site to Stripe's hosted, PCI-scoped card page.
        return redirect()->away($session->url);
    }

    /**
     * PAUSE the workspace (the wireframe's "Pause instead") — read-only for 1–3 months via the REAL
     * {@see SubscriptionLifecycleService}. Genuinely gates the workspace (the shared read-only gate the
     * Dashboard/Calendar/certified-write consume). No charge; audited.
     */
    public function pause(Request $request, SubscriptionLifecycleService $lifecycle): RedirectResponse
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());
        $months = max(1, min(3, (int) $request->input('months', 1)));

        $until = $lifecycle->pause($org, $months);

        return redirect()->to(route('app.billing'))->with('billing_notice', 'Your workspace is paused (read-only) until '.$until->format('j M Y').'. Resume any time.');
    }

    /** CANCEL — full access until the paid-through date, then read-only (data kept). REAL; audited. */
    public function cancel(Request $request, SubscriptionLifecycleService $lifecycle): RedirectResponse
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());

        $from = $lifecycle->cancel($org);

        return redirect()->to(route('app.billing'))->with('billing_notice', 'Your subscription is set to cancel. You keep full access until '.$from->format('j M Y').', then the workspace is read-only.');
    }

    /** RESUME — clear the pause / scheduled cancellation → the workspace is live again. */
    public function resume(Request $request, SubscriptionLifecycleService $lifecycle): RedirectResponse
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());

        $lifecycle->resume($org);

        return redirect()->to(route('app.billing'))->with('billing_notice', 'Welcome back — your workspace is live again.');
    }

    /**
     * ⚠️ DEV/TEST ONLY — expire the current trial to demo the access gate (an expired org → the Paywall).
     * NOT a payment or a real state change a user could reach in production: it 404s outside local/testing.
     */
    public function devExpireTrial(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local', 'testing'), 404);

        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());
        $subscription = $org->currentSubscription();
        $subscription?->update(['status' => 'trialing', 'trial_ends_at' => now()->subDay()]);

        return redirect()->to(route('app.billing'))->with('billing_notice', 'Test only: your trial was expired.');
    }
}
