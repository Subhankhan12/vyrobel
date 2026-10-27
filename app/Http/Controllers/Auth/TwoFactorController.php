<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactor\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Two-factor ENROLLMENT (self-service) — enable → confirm → recovery codes, plus regenerate and disable. The
 * user is resolved from `auth()` ONLY (never a request id), so a session can only ever manage its OWN 2FA
 * (proven by test). Routed from BOTH the operator (`/app/security`, behind #26) and owner (`/owner/settings`)
 * portals; `back()` returns to whichever page the form was on. The login CHALLENGE is a separate flow (Step 2).
 *
 * Recovery codes are flashed (`two_factor_recovery_codes`) so the security page can show them EXACTLY ONCE;
 * they are never rendered again (only their encrypted form is stored). Disabling requires re-auth (the
 * `current_password` rule) — an open/hijacked session cannot silently remove 2FA.
 */
class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    /** Begin enrollment: generate a secret (UNCONFIRMED — does not gate login yet). The page then shows the QR. */
    public function enable(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('two_factor_status', __('php/http/controllers/auth/two_factor_controller.already_on'));
        }

        $this->twoFactor->startEnrollment($user);

        return back();
    }

    /** Confirm with a 6-digit code → ACTIVATE + show recovery codes once. An invalid code does NOT activate. */
    public function confirm(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $request->validate(['code' => ['required', 'string']]);

        $codes = $this->twoFactor->confirm($user, $this->digits($request));

        if ($codes === null) {
            // The anti-lockout guard: no confirmed code, no activation.
            throw ValidationException::withMessages([
                'code' => __('php/http/controllers/auth/two_factor_controller.code_wrong'),
            ]);
        }

        return back()
            ->with('two_factor_status', __('php/http/controllers/auth/two_factor_controller.turned_on'))
            ->with('two_factor_recovery_codes', $codes);
    }

    /** Regenerate the recovery codes (only when 2FA is on) → show the new set once; the old set is invalidated. */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        if (! $user->hasTwoFactorEnabled()) {
            return back();
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);

        return back()
            ->with('two_factor_status', __('php/http/controllers/auth/two_factor_controller.codes_regenerated'))
            ->with('two_factor_recovery_codes', $codes);
    }

    /**
     * Disable 2FA. Turning OFF active 2FA requires re-auth (current password) — an open/hijacked session must
     * not silently remove it. Cancelling an UNCONFIRMED enrollment needs no password (nothing is protecting
     * the account yet), so the same route also serves "cancel setup".
     */
    public function disable(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $wasEnabled = $user->hasTwoFactorEnabled();

        if ($wasEnabled) {
            $request->validate(['current_password' => ['required', 'current_password']]);
        }

        $this->twoFactor->disable($user);

        return back()->with('two_factor_status', $wasEnabled ? __('php/http/controllers/auth/two_factor_controller.turned_off') : __('php/http/controllers/auth/two_factor_controller.setup_cancelled'));
    }

    /** SELF only — the acting user, never a request-supplied id. */
    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /** The entered code, digits only (tolerates a pasted "123 456"). */
    private function digits(Request $request): string
    {
        return preg_replace('/\D/', '', (string) $request->string('code')) ?? '';
    }
}
