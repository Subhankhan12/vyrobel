<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TenantProvisioningService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Vyrobel ONBOARDING / self-signup (Phase 7a · Step 2) — the PUBLIC, pre-auth flow that provisions a brand
 * new operator organization and logs the founder in. It owns validation + hardening; the atomic write lives
 * entirely in {@see TenantProvisioningService} (org + roles + founder + membership + Property Owner + first
 * property in one transaction — all-or-nothing).
 *
 * HARDENING (the public surface):
 *   • rate-limited at the route (throttle) against abuse;
 *   • the founder's password is hashed by the `hashed` cast (never stored raw); no other sensitive data is kept;
 *   • NO email verification GATE — `User` is not `MustVerifyEmail`, so the founder matches every existing
 *     operator (they are logged in immediately, like accept-invite);
 *   • enumeration: ORG existence never leaks (org slugs auto-disambiguate, so two orgs may share a name — no
 *     uniqueness check is surfaced). Email uniqueness is validated with a NEUTRAL message (it does not confirm
 *     an account exists); a fully zero-enumeration accept-and-verify variant is deferred (it needs production
 *     email and trades away the immediate-login flow this product wants).
 *
 * After provisioning the founder lands on the Portfolio, where their one property honestly shows "Finish
 * setup to go live" (0 units) — the real provisioned state, never a faked "ready".
 */
class OnboardingController extends Controller
{
    /** The three property kinds the wizard offers (a subset of the real `properties.type` enum). */
    private const TYPES = ['villa', 'hotel', 'hostel'];

    private const CURRENCIES = ['EUR', 'GBP', 'USD'];

    public function create(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->to('/app/dashboard');
        }

        return view('auth.onboarding', [
            'types' => self::TYPES,
            'currencies' => self::CURRENCIES,
        ]);
    }

    public function store(Request $request, TenantProvisioningService $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'org_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'property_type' => ['required', 'in:'.implode(',', self::TYPES)],
            'currency' => ['required', 'in:'.implode(',', self::CURRENCIES)],
            'property_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            // Neutral email handling: a duplicate does NOT get a "that email is taken" message that confirms
            // an account exists — it fails with a generic, non-committal error.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.unique' => 'We couldn’t create your account with these details. If you already have one, sign in.',
        ]);

        $result = $provisioner->provision(
            organization: ['name' => $data['org_name'], 'country' => null, 'currency' => $data['currency']],
            founder: ['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']],
            property: ['name' => $data['property_name'], 'type' => $data['property_type'], 'location' => $data['location'] ?? null],
        );

        // Log the founder in on the web guard (fixation protection), exactly like accept-invite activation.
        Auth::guard('web')->login($result['user']);
        $request->session()->regenerate();

        return redirect()->to('/app/portfolio')->with('portfolio_notice',
            // ⚠️ P-6 · Gate 1 — the very first sentence a new tenant reads. It used to name three steps, none of
            // which was possible. Now it names the two that are, in the order the storefront needs them.
            'Welcome to Vyrobel! '.$result['property']->name.' is ready — add a room type and a unit, then a rate plan with prices, and you can take bookings.');
    }
}
