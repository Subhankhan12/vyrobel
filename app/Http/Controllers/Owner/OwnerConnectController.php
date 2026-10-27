<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Payments\Stripe\StripeClientInterface;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Owner Stripe Connect onboarding (Phase 8 · SG3). The owner connects a Stripe STANDARD account — they own
 * their KYC/compliance/payouts; Stripe handles it. Connect REPLACES the manual IBAN for the LIVE payout rail.
 *
 * 🔒 SELF-ONLY: the owner is resolved by `user_id` (never a request id), so an owner can only ever onboard
 * their OWN account. Route-gated `payout.manage` (co-owner only, invariant #42 — the same money-security gate
 * as the encrypted IBAN). Redirects to Stripe's hosted onboarding; `stripe_connect_payouts_enabled` flips true
 * on the `account.updated` Connect webhook once Stripe clears the account.
 */
class OwnerConnectController extends Controller
{
    public function start(Request $request, StripeClientInterface $stripe): RedirectResponse
    {
        $user = Auth::user();
        $owner = Owner::query()->where('user_id', $user?->getKey())->firstOrFail();   // 🔒 self-only

        try {
            $accountId = $owner->stripe_connect_account_id;
            if (! is_string($accountId) || $accountId === '') {
                $account = $stripe->createConnectAccount(
                    email: (string) $owner->email,
                    metadata: ['owner_id' => (string) $owner->getKey()],
                );
                $accountId = $account->id;
                // Store outside any tenant guard concern — the owner row is BelongsToOrganization + already scoped.
                app(TenantContext::class)->forOrganization((string) $owner->organization_id, fn () => Owner::query()->whereKey($owner->getKey())->update(['stripe_connect_account_id' => $accountId]));
            }

            $link = $stripe->createConnectAccountLink(
                $accountId,
                refreshUrl: route('owner.payout-method').'?connect=refresh',
                returnUrl: route('owner.payout-method').'?connect=return',
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()->to(route('owner.payout-method'))->with('payout_method_error', __('php/http/controllers/owner/owner_connect_controller.onboarding_failed'));
        }

        // Off-site to Stripe's hosted onboarding (no identity data touches our server).
        return redirect()->away($link->url);
    }
}
