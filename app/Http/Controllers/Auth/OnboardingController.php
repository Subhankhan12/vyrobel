<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TenantProvisioningService;
use App\Support\Platform\PlatformSwitches;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Throwable;

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
    /** ⚠️ Public since ADMIN 04: the platform console's "create an organisation" offers exactly these — one list. */
    public const TYPES = ['villa', 'hotel', 'hostel'];

    public const CURRENCIES = ['EUR', 'GBP', 'USD'];

    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->to('/app/dashboard');
        }

        // ⚠️⚠️ A VERIFIED PROVIDER IDENTITY WAITING IN THE SESSION — and NOWHERE ELSE. "Sign up with Google"
        //    cannot be one click: provisioning needs seven business facts and a callback supplies two. So the
        //    person authenticates, then completes THIS form with their name and email filled in and the
        //    password field gone. Until they submit it they are not an account, and if they walk away there
        //    is nothing to clean up — no user, no organisation, no trial.
        $social = $this->pendingSocialIdentity($request);

        return view('auth.onboarding', [
            'types' => self::TYPES,
            'currencies' => self::CURRENCIES,
            'social' => $social,
            // ⚠️ ADMIN 01 · PHASE 1 — a paused signup is SAID, plainly, on a normal page: not a 500, not a
            //    blank form, not a form that accepts details and then quietly does nothing.
            'signupsPaused' => ! PlatformSwitches::signupAllowed($social['provider'] ?? null),
        ]);
    }

    public function store(Request $request, TenantProvisioningService $provisioner): RedirectResponse
    {
        $social = $this->pendingSocialIdentity($request);

        // ⚠️⚠️ ADMIN 01 · PHASE 1 — THE SWITCH IS CHECKED AGAIN HERE, AT THE WRITE, not only on the form. A form
        //    loaded before a flip, or a social stash issued before one, must not provision an organisation once
        //    sign-ups are paused. Nothing is created; the page says why.
        if (! PlatformSwitches::signupAllowed($social['provider'] ?? null)) {
            return redirect()->route('signup');
        }

        if ($social !== null) {
            // ⚠️⚠️ THE ADDRESS IS OVERWRITTEN WITH WHAT THE PROVIDER ASSERTED. The form shows it, so an edited
            //    value would look accepted; trusting it would let someone authenticate as themselves and then
            //    provision an organisation under a different address with their own Google account attached —
            //    an account takeover assembled entirely out of a pre-filled field.
            $request->merge(['email' => $social['email']]);
        }

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
            // ⚠️ The password is required EXCEPT when a verified provider identity is completing this form —
            //    that account has no password by construction, and one is asked for later, only when a
            //    sensitive action first needs it (docs/PASSWORDLESS-GATE.md).
            'password' => $social === null
                ? ['required', 'confirmed', Password::defaults()]
                : ['prohibited'],
        ], [
            'email.unique' => 'We couldn’t create your account with these details. If you already have one, sign in.',
        ]);

        $result = $provisioner->provision(
            organization: ['name' => $data['org_name'], 'country' => null, 'currency' => $data['currency']],
            founder: ['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'] ?? null],
            property: ['name' => $data['property_name'], 'type' => $data['property_type'], 'location' => $data['location'] ?? null],
            // ⚠️ Written INSIDE the provisioning transaction, so an organisation can never exist with a
            //    passwordless founder and no way for them to sign in.
            identity: $social,
            // ADMIN 01 · PHASE 2 — recorded from here on; organisations created before this read "Unknown".
            signupSource: $social !== null ? (string) $social['provider'] : 'email',
        );

        // Consumed: the stash is single-use, so a back-button resubmit cannot provision a second org.
        $request->session()->forget(SocialAuthController::SIGNUP_KEY);

        // Log the founder in on the web guard (fixation protection), exactly like accept-invite activation.
        Auth::guard('web')->login($result['user']);
        $request->session()->regenerate();

        // ⚠️⚠️ TONIGHT 01 (2026-09-25) — THE FIRST VERIFICATION LINK. The page every new founder lands on says "Click
        //    the link we sent you", and nothing had sent one: the only senders were that page's own "Resend the link"
        //    and the console's support button. Invisible while production discarded mail (MAIL_MAILER=log); the
        //    first thing every real email sign-up would have met the moment mail was switched on.
        //    ⚠️ Only an UNVERIFIED founder is sent one: a social founder was verified at provisioning by the
        //    provider's own assertion, and gets none. ⚠️ A failed send never fails the sign-up — the account exists,
        //    and the page's "Resend the link" is the way out; the failure is logged, never the address.
        if (! $result['user']->hasVerifiedEmail()) {
            try {
                $result['user']->sendEmailVerificationNotification();
            } catch (Throwable $e) {
                Log::warning('The first verification link could not be sent at sign-up.', ['user_id' => $result['user']->getKey(), 'error' => $e::class]);
            }
        }

        return redirect()->to('/app/portfolio')->with('portfolio_notice',
            // ⚠️ P-6 · Gate 1 — the very first sentence a new tenant reads. It used to name three steps, none of
            // which was possible. Now it names the two that are, in the order the storefront needs them.
            'Welcome to Vyrobel! '.$result['property']->name.' is ready — add a room type and a unit, then a rate plan with prices, and you can take bookings.');
    }

    /**
     * The verified provider identity this signup is completing, if any.
     *
     * ⚠️⚠️ THE EMAIL AND NAME COME FROM THE SESSION STASH, NEVER FROM THE FORM. The form pre-fills them for
     *    the person to see, but a posted value could be edited — and if the submitted address were trusted,
     *    someone could authenticate as themselves and provision an organisation under somebody else's
     *    address, with the provider link attached to it. The stash is what the provider actually asserted.
     *
     * ⚠️ It expires. An abandoned flow resumed hours later starts again rather than silently using a stale
     *    assertion.
     *
     * @return array{provider: string, provider_user_id: string, email: string, name: string}|null
     */
    private function pendingSocialIdentity(Request $request): ?array
    {
        $stash = $request->session()->get(SocialAuthController::SIGNUP_KEY);

        if (! is_array($stash)) {
            return null;
        }

        $issued = (string) ($stash['issued_at'] ?? '');
        $provider = (string) ($stash['provider'] ?? '');
        $providerUserId = (string) ($stash['provider_user_id'] ?? '');
        $email = (string) ($stash['email'] ?? '');

        if ($provider === '' || $providerUserId === '' || $email === '' || $issued === '') {
            return null;
        }

        if (now()->parse($issued)->addMinutes(60)->isPast()) {
            $request->session()->forget(SocialAuthController::SIGNUP_KEY);

            return null;
        }

        return [
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'email' => $email,
            'name' => (string) ($stash['name'] ?? ''),
        ];
    }
}
