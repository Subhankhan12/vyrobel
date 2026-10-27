<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Concerns;

use App\Mail\LoginApprovalMail;
use App\Models\User;
use App\Support\Auth\LoginApprovalService;
use App\Support\Auth\TrustedDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * ⚠️⚠️ EVERYTHING THAT HAPPENS AFTER A CREDENTIAL IS ACCEPTED — in ONE place, for every door.
 *
 * Extracted 2026-09-04 when Google sign-in arrived. A password is not the only way to prove who you are any
 * more, but the rules that follow the proof are the same whichever way it was given: a trusted device may
 * skip the step-up factors, an opt-in user is held for email approval, and a 2FA-enabled user is challenged
 * before any session exists.
 *
 * ⚠️⚠️ WHY IT IS SHARED RATHER THAN COPIED. A second copy of this sequence is a copy that can forget the 2FA
 *    branch, and forgetting it would mean "Continue with Google" silently skipping a second factor the user
 *    deliberately turned on — a way to make an account LESS safe by using the newer button. §N.8f: one
 *    definition per concept, because two WILL drift.
 *
 * ⚠️ NOTHING HERE VERIFIES ANYTHING. The caller is responsible for having established the identity; this
 *    concern only decides what to do next, and never logs anybody in that the caller did not vouch for.
 */
trait CompletesSignIn
{
    use RedirectsByRole;

    /**
     * Carry a verified user through the step-up factors and into a session — or into the challenge that has
     * to happen first. Returns null when the account has no workspace to land in, so each caller can report
     * that in the way its own surface needs (a form error, or a redirect with a message).
     */
    protected function completeSignInOrNull(Request $request, User $user): ?RedirectResponse
    {
        $home = $this->homeFor($user);

        if ($home === null) {
            return null;
        }

        // TRUSTED DEVICE (Wave B · GATE 4) — a browser the user previously chose to trust (a live `vy_td` cookie
        // hash) SKIPS the step-up factors below (login-approval + 2FA). The password was already verified; this is
        // the standard "remember this device" pattern (bounded by expiry + revocation, self-only).
        $recognized = app(TrustedDeviceService::class)->recognizes($user, $request);

        // 🔐 LOGIN APPROVAL (Wave B · GATE 4) — an opt-in user signing in from an UNTRUSTED device is held PENDING:
        // an approval email is sent + they enter a plain-session pending state (NEVER the auth guard, so /app +
        // /owner reject them by construction) until the emailed link is clicked. Genuinely blocked, not just alerted.
        if ($user->requiresLoginApproval() && ! $recognized) {
            return $this->beginLoginApproval($request, $user);
        }

        // 🔐 2FA — a password-verified but 2FA-enabled user on an untrusted device is NOT logged in. They enter a
        // PENDING state (a plain session key, NEVER the auth guard) and are challenged. The session id is
        // regenerated for fixation protection on this transition.
        if ($user->hasTwoFactorEnabled() && ! $recognized) {
            $request->session()->put('login.2fa.user_id', (string) $user->getKey());
            $request->session()->regenerate();

            return redirect()->route('two-factor.challenge');
        }

        // No 2FA → log in now. Auth::validate + Auth::login is equivalent to the old single Auth::attempt for
        // this path, so the non-2FA login is unchanged (no regression).
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->to($home);
    }

    /**
     * The same thing for a caller that has already established the account HAS a workspace.
     *
     * @throws \LogicException if it does not — a programming error, never a user-facing state.
     */
    protected function completeSignIn(Request $request, User $user): RedirectResponse
    {
        return $this->completeSignInOrNull($request, $user)
            ?? throw new \LogicException('completeSignIn called for a user with no workspace.');
    }

    /**
     * Open a LOGIN-APPROVAL challenge (Wave B · GATE 4): create a pending approval, email the single-use signed
     * link, stash the pending state (a plain session key — NOT the auth guard), and show the "check your email"
     * screen. The sign-in cannot complete until the link is clicked (see {@see LoginApprovalController}).
     */
    protected function beginLoginApproval(Request $request, User $user): RedirectResponse
    {
        [$approval, $token] = app(LoginApprovalService::class)->open($user, $request);

        $approveUrl = URL::temporarySignedRoute('login-approval.approve', now()->addMinutes(30), [
            'approval' => $approval->getKey(),
            'token' => $token,
        ]);

        // I18N-01 — the device and time are written in the RECIPIENT's language, the same one the mail is sent in.
        $locale = $user->preferredLocale();

        Mail::to((string) $user->email)->locale($locale)->queue(new LoginApprovalMail(
            userName: (string) $user->name,
            approveUrl: $approveUrl,
            device: $this->deviceLabel($request, $locale),
            ip: (string) $request->ip(),
            when: now()->locale($locale)->translatedFormat('j M Y, H:i'),
        ));

        $request->session()->put('login.approval.user_id', (string) $user->getKey());
        $request->session()->put('login.approval.id', (string) $approval->getKey());
        $request->session()->regenerate();

        return redirect()->route('login-approval.show');
    }

    /** A short human device label from the user-agent — for the approval email (not fingerprinting). */
    protected function deviceLabel(Request $request, ?string $locale = null): string
    {
        $ua = (string) $request->userAgent();
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => __('php/http/controllers/auth/concerns/completes_sign_in.unknown_browser', [], $locale),
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => __('php/http/controllers/auth/concerns/completes_sign_in.unknown_device', [], $locale),
        };

        return __('php/http/controllers/auth/concerns/completes_sign_in.browser_on_os', ['browser' => $browser, 'os' => $os], $locale);
    }
}
