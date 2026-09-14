<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsByRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Forgot / reset password (Auth Phase B · finishing sweep). Real password recovery on Laravel's Password
 * broker — tokened, single-use, expiring tokens the framework owns. Same security posture as the rest of
 * auth:
 *   - NO enumeration: the request-link endpoint ALWAYS returns the identical generic message, whether or
 *     not the email matches an account (and only ACTIVE users are ever sent a link — an invited-but-not-
 *     activated user uses their invite instead, a distinction never leaked).
 *   - Rate-limited (route throttle) against abuse; CSRF on every POST (web group).
 *   - On reset: the password is hashed, the remember token rotated, the session regenerated, and the
 *     user is signed in + routed by role — the same fixation protection as login/activation.
 */
class PasswordResetController extends Controller
{
    use RedirectsByRole;

    private const GENERIC_STATUS = 'If an account exists for that email, we’ve sent a link to reset your password.';

    /** The "enter your email" screen. */
    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    /** Email a reset link — ACTIVE users only — then ALWAYS respond generically (no enumeration). */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // `status => active` scopes the broker's user lookup to activated accounts (an invited user has
        // no real password to reset). Fire-and-forget: we ignore the specific broker status so the
        // response is identical for sent / not-found / throttled — no account enumeration.
        Password::sendResetLink([
            'email' => $request->string('email')->lower()->trim()->value(),
            'status' => User::SIGN_IN_STATUS,
        ]);

        return back()->with('status', self::GENERIC_STATUS);
    }

    /** The "choose a new password" screen (token in the path, email in the query). */
    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /** Reset the password (hashed), invalidate the token, sign in, and route by role. */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // `status => active` again: a reset token only ever resolves to an active user.
        $status = Password::reset(
            [
                'email' => $request->string('email')->lower()->trim()->value(),
                'password' => (string) $request->string('password'),
                'password_confirmation' => (string) $request->string('password_confirmation'),
                'token' => (string) $request->string('token'),
                'status' => User::SIGN_IN_STATUS,
            ],
            function (User $user, string $password): void {
                // The ONE shared hash + remember-token-rotate path (also used by the in-app change-password
                // flow, ChangePasswordController) — never reimplemented here, so reset and change can't drift.
                $user->changePasswordTo($password);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Invalid / expired / already-used token — generic error, no enumeration.
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This password reset link is invalid or has expired. Please request a new one.',
            ]);
        }

        // Sign the user in on their now-active credentials + regenerate the session (fixation protection).
        $user = User::query()->where('email', $request->string('email')->lower()->trim()->value())->firstOrFail();
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->to($this->homeFor($user) ?? route('login'));
    }
}
