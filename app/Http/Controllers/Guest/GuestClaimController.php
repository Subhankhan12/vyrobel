<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\GuestAccountService;
use App\Support\Guest\GuestName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Guest account CLAIM (Phase 4a · Sub-gate 2) — the booking-tied sign-up. PUBLIC (the guest has no account yet).
 *
 * NO ENUMERATION: {@see store()} ALWAYS returns the same neutral "sent" response, whether or not the email has a
 * booking/account — the claim link is only sent when {@see GuestAccountService::claimableByEmail()} finds a
 * claimable row, but the response never reveals that. {@see verify()}/{@see claim()} run on a native HMAC-signed,
 * expiring link (the `signed` middleware) + re-check the row is still claimable; any failure is a generic
 * "invalid link". On claim: set password + verify + log in on the GUEST guard + regenerate the session.
 */
class GuestClaimController extends Controller
{
    public function __construct(private readonly GuestAccountService $accounts) {}

    /** The "enter your booking email" form. */
    public function create(): View
    {
        return view('guest.auth.claim');
    }

    /** Send the claim link IF the email is claimable — but ALWAYS the same neutral response. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $email = trim((string) $request->string('email'));

        $guest = $this->accounts->claimableByEmail($email);
        if ($guest !== null) {
            $this->accounts->sendClaimLink($guest);
        }

        // Identical response whether or not a booking/account exists — no enumeration.
        return redirect()->route('guest.claim.sent')->with('claim_email', $email);
    }

    /** The neutral "check your inbox" page (with a rate-limited resend). */
    public function sent(): View
    {
        return view('guest.auth.sent', ['email' => session('claim_email')]);
    }

    /** The set-password form for a valid signed link, or a generic "invalid link". */
    public function verify(Request $request, string $guest, string $hash): View
    {
        $model = $this->accounts->resolveClaimable($guest, $hash);
        if ($model === null) {
            return view('guest.auth.claim-invalid');
        }

        // The form POSTs back to the CURRENT signed URL, so the `signed` middleware re-validates the same signature.
        return view('guest.auth.set-password', ['name' => GuestName::display($model), 'action' => $request->fullUrl()]);
    }

    /** Set the password → verify → activate → log in on the guest guard → the guest home. Generic error otherwise. */
    public function claim(Request $request, string $guest, string $hash): View|RedirectResponse
    {
        $model = $this->accounts->resolveClaimable($guest, $hash);
        if ($model === null) {
            return view('guest.auth.claim-invalid');
        }

        $request->validate(['password' => ['required', 'confirmed', Password::min(8)]]);

        $this->accounts->claim($model, (string) $request->string('password'));

        Auth::guard('guest')->login($model);
        $request->session()->regenerate();   // session-fixation protection on activation

        return redirect()->route('guest.home');
    }
}
