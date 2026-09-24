<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformAdmin;
use App\Support\Platform\PlatformAdminSessions;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\TwoFactor\PlatformTwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * SUPER ADMIN · GATE 0 — the admin 2FA LOGIN CHALLENGE (mirrors the tenant challenge on the `admin` guard).
 * Shown after a correct password for an enrolled admin, who is in the PENDING state ({@see
 * \App\Http\Middleware\RequirePlatformAdminTwoFactorPending}), NOT authenticated. Only a valid TOTP or a
 * single-use recovery code completes `Auth::guard('admin')->login()`. Rate-limited on (pending admin + IP).
 */
class AdminTwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly PlatformTwoFactorService $twoFactor,
        private readonly PlatformAuditLogger $audit,
    ) {}

    public function create(): View
    {
        return view('admin.auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $this->pendingAdmin($request);
        if ($admin === null) {
            $request->session()->forget('admin.2fa.pending_id');

            return redirect()->route('admin.login');
        }

        $this->ensureNotRateLimited($request, (string) $admin->getKey());
        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->verifyChallenge($admin, (string) $request->string('code'))) {
            RateLimiter::hit($this->throttleKey($request, (string) $admin->getKey()));

            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired. Try again, or use a recovery code.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, (string) $admin->getKey()));

        Auth::guard('admin')->login($admin);
        $request->session()->forget('admin.2fa.pending_id');
        $request->session()->regenerate();
        // ⚠️ AFTER regenerate — the tracking key lives in the session that will actually be used.
        app(PlatformAdminSessions::class)->start($request, $admin);
        $admin->forceFill(['last_login_at' => now()])->saveQuietly();
        $this->audit->record('platform.login', ['two_factor' => 'passed'], actor: $admin);

        return redirect()->route('admin.dashboard');
    }

    /** The pending admin — still active AND still 2FA-enabled, else null. */
    private function pendingAdmin(Request $request): ?PlatformAdmin
    {
        $id = $request->session()->get('admin.2fa.pending_id');
        if (! is_string($id) || $id === '') {
            return null;
        }

        $admin = PlatformAdmin::query()->where('status', 'active')->find($id);

        return $admin !== null && $admin->hasTwoFactorEnabled() ? $admin : null;
    }

    private function ensureNotRateLimited(Request $request, string $adminId): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $adminId), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $adminId));

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    private function throttleKey(Request $request, string $adminId): string
    {
        return 'admin-two-factor:'.$adminId.'|'.$request->ip();
    }
}
