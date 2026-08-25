<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\OperatorConnectService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * CONNECT PHASE 1 — start Stripe Connect onboarding for the CURRENT organisation.
 *
 * 🔒 The organisation is resolved from the tenant context, NEVER from the request — the same rule the rest
 * of the money surfaces follow. Route-gated `can:billing.manage`, which the matrix grants to Super Admin
 * and Property Owner only (NOT Front Desk, NOT Accountant, NOT Group Admin).
 *
 * ⚠️ NOTHING CHARGES ON THE RESULTING ACCOUNT. Onboarding is opt-in and inert: the guest checkout is
 * unchanged, and an organisation that never starts this behaves exactly as it does today.
 */
class OperatorConnectController extends Controller
{
    public function start(Request $request, OperatorConnectService $connect): RedirectResponse
    {
        $organizationId = app(TenantContext::class)->id();
        $organization = Organization::withoutGlobalScopes()->findOrFail($organizationId);

        try {
            $url = $connect->startOnboarding(
                $organization,
                email: (string) (Auth::user()->email ?? ''),
                refreshUrl: route('app.billing').'?connect=refresh',
                returnUrl: route('app.billing').'?connect=return',
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()->to(route('app.billing'))->with('error', 'We couldn’t start Stripe onboarding — please try again.');
        }

        // Off-site to Stripe's hosted onboarding — no identity data touches our server.
        return redirect()->away($url);
    }
}
