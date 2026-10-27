<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Guest forgot / reset password (Phase 4a · Sub-gate 3) — Laravel's Password broker on the GUEST provider/broker
 * ('guests'), the same hardened posture as the operator/owner reset:
 *   - NO enumeration: the request-link endpoint ALWAYS responds with the identical generic message.
 *   - Tokened, single-use, expiring tokens the framework owns — stored HASHED, keyed on the guest BLIND INDEX
 *     (never the plaintext email; {@see Guest::getEmailForPasswordReset()}), so no guest PII in the token table.
 *   - On reset: the one shared {@see Guest::changePasswordTo()} hash + remember-token-rotate path.
 */
class GuestPasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('guest.auth.forgot-password');
    }

    /** Email a reset link if the account exists, then ALWAYS respond generically (no enumeration). */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('guests')->sendResetLink([
            'email' => trim((string) $request->string('email')),
        ]);

        return back()->with('status', __('php/http/controllers/guest/guest_password_reset_controller.link_sent'));
    }

    /** The "choose a new password" screen — token in the path; the guest re-enters their email (never in the URL). */
    public function showReset(string $token): View
    {
        return view('guest.auth.reset-password', ['token' => $token]);
    }

    /** Reset the password (hashed) on the guest broker; generic error for any bad/expired token. */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('guests')->reset(
            [
                'email' => trim((string) $request->string('email')),
                'password' => (string) $request->string('password'),
                'password_confirmation' => (string) $request->string('password_confirmation'),
                'token' => (string) $request->string('token'),
            ],
            function (Guest $guest, string $password): void {
                $guest->changePasswordTo($password);   // the shared hash + remember-token-rotate path
                event(new PasswordReset($guest));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => __('php/http/controllers/guest/guest_password_reset_controller.link_invalid'),
            ]);
        }

        return redirect()->route('guest.login')->with('status', __('php/http/controllers/guest/guest_password_reset_controller.password_reset'));
    }
}
