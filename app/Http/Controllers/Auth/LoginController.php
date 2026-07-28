<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsByRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginApprovalMail;
use App\Models\User;
use App\Support\Auth\LoginApprovalService;
use App\Support\Auth\TrustedDeviceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The single shared Vyrobel login (operator + owner). Session-based, CSRF-protected, rate-limited.
 * On success it routes by role: a user linked to an Owner → the owner portal; an org member → the
 * operator app. The tenant/owner context itself is set downstream by SetTenantForWebRequest /
 * SetOwnerScopeForPortal, which read the now-authenticated user — this controller only authenticates
 * and routes.
 */
class LoginController extends Controller
{
    use RedirectsByRole;

    /** Show the login form (or bounce an already-authenticated user to their home). */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            $home = $this->homeFor($user);

            return redirect()->to($home ?? route('login'));
        }

        return view('auth.login');
    }

    /**
     * Verify the password, then EITHER challenge for 2FA (if enabled) or log in — regenerating the session
     * (fixation protection) and redirecting by role.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();   // verifies the password; does NOT log in (Auth::validate)

        // The credentials are validated, so this active user exists. Resolved explicitly (validate leaves no
        // authenticated user on the request) by email + status ONLY — never a request-supplied id.
        $user = User::query()
            ->where('email', Str::lower(trim((string) $request->string('email'))))
            ->where('status', 'active')
            ->firstOrFail();

        $home = $this->homeFor($user);

        // No workspace at all — reject before any session/challenge. Auth::validate created no session, so
        // there is nothing to tear down; just the generic message (never a 500 into a context-less /app).
        if ($home === null) {
            throw ValidationException::withMessages([
                'email' => 'This account does not have access to any workspace yet.',
            ]);
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
     * Open a LOGIN-APPROVAL challenge (Wave B · GATE 4): create a pending approval, email the single-use signed
     * link, stash the pending state (a plain session key — NOT the auth guard), and show the "check your email"
     * screen. The sign-in cannot complete until the link is clicked (see {@see LoginApprovalController}).
     */
    private function beginLoginApproval(LoginRequest $request, User $user): RedirectResponse
    {
        [$approval, $token] = app(LoginApprovalService::class)->open($user, $request);

        $approveUrl = URL::temporarySignedRoute('login-approval.approve', now()->addMinutes(30), [
            'approval' => $approval->getKey(),
            'token' => $token,
        ]);

        Mail::to((string) $user->email)->queue(new LoginApprovalMail(
            userName: (string) $user->name,
            approveUrl: $approveUrl,
            device: $this->deviceLabel($request),
            ip: (string) $request->ip(),
            when: now()->format('j M Y, H:i'),
        ));

        $request->session()->put('login.approval.user_id', (string) $user->getKey());
        $request->session()->put('login.approval.id', (string) $approval->getKey());
        $request->session()->regenerate();

        return redirect()->route('login-approval.show');
    }

    /** A short human device label from the user-agent — for the approval email (not fingerprinting). */
    private function deviceLabel(Request $request): string
    {
        $ua = (string) $request->userAgent();
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'a browser',
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'an unknown device',
        };

        return $browser.' on '.$os;
    }

    /** Log out: invalidate + regenerate the session and token, then back to login. */
    public function destroy(Request $request): RedirectResponse
    {
        $this->teardown($request);

        return redirect()->route('login');
    }

    private function teardown(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
