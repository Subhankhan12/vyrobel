<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePlatformAdminTwoFactor;
use App\Models\PlatformAdmin;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\TwoFactor\PlatformTwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * SUPER ADMIN · GATE 0 — MANDATORY 2FA ENROLLMENT for platform admins. Reachable by an authenticated admin who
 * has not yet enrolled (the mandatory-2FA gate {@see EnsurePlatformAdminTwoFactor} sends
 * them here, and this route is exempt from that gate so there is no redirect loop). A fresh secret is generated
 * on view; a valid 6-digit code CONFIRMS it and unlocks the console. Nothing else in /admin is reachable until
 * this completes.
 */
class AdminTwoFactorController extends Controller
{
    public function __construct(
        private readonly PlatformTwoFactorService $twoFactor,
        private readonly PlatformAuditLogger $audit,
    ) {}

    /** Show the enrollment screen — QR + manual secret. Generates a fresh (unconfirmed) secret if none pending. */
    public function create(Request $request): View|RedirectResponse
    {
        $admin = $this->admin();

        if ($admin->hasTwoFactorEnabled()) {
            return redirect()->route('admin.dashboard');   // already enrolled → nothing to do here
        }

        // Generate a pending secret on first view so the QR/manual key are ready. Idempotent per unconfirmed run.
        if ($admin->two_factor_secret === null) {
            $this->twoFactor->startEnrollment($admin);
            $admin->refresh();
        }

        return view('admin.auth.two-factor-setup', [
            'qr' => $this->twoFactor->qrCodeSvg($admin),
            'secret' => $this->twoFactor->secretKey($admin),
        ]);
    }

    /** Confirm enrollment with a valid code → activate 2FA + show the one-time recovery codes. */
    public function store(Request $request): View|RedirectResponse
    {
        $admin = $this->admin();
        $request->validate(['code' => ['required', 'string']]);

        $codes = $this->twoFactor->confirm($admin, (string) $request->string('code'));

        if ($codes === null) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid. Scan the QR again and enter the current 6-digit code.',
            ]);
        }

        $this->audit->record('platform.2fa_enrolled', actor: $admin);

        return view('admin.auth.two-factor-recovery', ['codes' => $codes]);
    }

    /** The current authenticated platform admin (self-resolved from the guard, never a request id). */
    private function admin(): PlatformAdmin
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof PlatformAdmin, 403);

        return $admin;
    }
}
