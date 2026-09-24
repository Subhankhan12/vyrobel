<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\PlatformAdmin;
use App\Support\Platform\PlatformAdminSessions;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * SUPER ADMIN · GATE 0 — the platform-admin login, on the `admin` guard ONLY. Session-based, CSRF-protected,
 * rate-limited (see {@see AdminLoginRequest}). Mirrors the tenant login's hardened posture:
 *   - verify the password WITHOUT logging in, then EITHER challenge for 2FA (which is MANDATORY here) or, on
 *     the rare un-enrolled first login, log in and let the mandatory-2FA middleware force enrollment;
 *   - regenerate the session on every auth transition (fixation protection);
 *   - the login itself is audited to the platform action ledger.
 *
 * This controller authenticates ONLY platform admins — it never touches the `web`/`owner`/`guest` guards.
 */
class AdminLoginController extends Controller
{
    public function __construct(private readonly PlatformAuditLogger $audit) {}

    /** Show the admin login form (or bounce an already-authenticated admin to the console). */
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Verify the password, then EITHER hold the admin in a 2FA-pending state and challenge (if enrolled) OR log
     * them in (an un-enrolled admin is then forced to enrol by EnsurePlatformAdminTwoFactor). Session regenerated.
     */
    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $request->authenticate();   // verifies the password on the `admin` guard; does NOT log in

        $admin = PlatformAdmin::query()
            ->where('email', Str::lower(trim((string) $request->string('email'))))
            ->where('status', 'active')
            ->first();

        if (! $admin instanceof PlatformAdmin) {
            // The credential check passed but the row vanished between steps — generic failure, no enumeration.
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        // 🔐 2FA is MANDATORY. An enrolled admin is held in a PENDING state (a plain session key, NEVER the
        // guard) and challenged before login. An un-enrolled admin is logged in but immediately trapped at the
        // enrollment screen by EnsurePlatformAdminTwoFactor — they can reach nothing else until 2FA is active.
        if ($admin->hasTwoFactorEnabled()) {
            $request->session()->put('admin.2fa.pending_id', (string) $admin->getKey());
            $request->session()->regenerate();

            return redirect()->route('admin.2fa.challenge');
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        app(PlatformAdminSessions::class)->start($request, $admin);
        $admin->forceFill(['last_login_at' => now()])->saveQuietly();
        $this->audit->record('platform.login', ['two_factor' => 'enrollment_required'], actor: $admin);

        return redirect()->route('admin.2fa.setup');
    }

    /** Log out of the admin realm ONLY — invalidate + regenerate the session and token. */
    public function destroy(Request $request): RedirectResponse
    {
        app(PlatformAdminSessions::class)->endCurrent($request, 'signed_out');
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
