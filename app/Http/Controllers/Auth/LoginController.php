<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsByRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // 🔐 2FA — a password-verified but 2FA-enabled user is NOT logged in. They enter a PENDING state (a
        // plain session key, NEVER the auth guard) and are challenged. That state grants no authenticated
        // access by construction: /app + /owner sit behind `auth`, which a not-logged-in user fails. The
        // session id is regenerated for fixation protection on this transition.
        if ($user->hasTwoFactorEnabled()) {
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
