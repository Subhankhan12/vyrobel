<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Owner;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /** Authenticate, regenerate the session (fixation protection), and redirect by role. */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        /** @var User $user */
        $user = $request->user();
        $home = $this->homeFor($user);

        // Authenticated but with no workspace at all — do not leave a half-usable session. Same
        // teardown as logout, with a generic message (never a 500 into a context-less /app request).
        if ($home === null) {
            $this->teardown($request);

            throw ValidationException::withMessages([
                'email' => 'This account does not have access to any workspace yet.',
            ]);
        }

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

    /**
     * Role home: an Owner-linked user → the owner portal; else an active org member → the operator
     * app; else null (no access). The Owner lookup runs super-admin (no tenant context at login time);
     * memberships are queried directly (the link table is deliberately un-scoped).
     */
    private function homeFor(User $user): ?string
    {
        $isOwner = app(TenantContext::class)->runAsSuperAdmin(
            fn (): bool => Owner::query()->where('user_id', $user->getKey())->exists(),
        );

        if ($isOwner) {
            return route('owner.dashboard');
        }

        if ($user->memberships()->where('status', 'active')->exists()) {
            return route('app.dashboard');
        }

        return null;
    }
}
