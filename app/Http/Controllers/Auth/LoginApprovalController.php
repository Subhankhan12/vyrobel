<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsByRole;
use App\Http\Controllers\Controller;
use App\Models\LoginApproval;
use App\Models\User;
use App\Support\Auth\LoginApprovalService;
use App\Support\Auth\TrustedDeviceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LOGIN APPROVAL (Wave B · GATE 4) — the pending "approve this new device" flow.
 *
 *   • show     — the "check your email" screen (the PENDING browser; a plain-session state, NOT authenticated);
 *   • approve  — the emailed SIGNED link (may be opened on any device — the signature IS the authorization);
 *                marks the request approved; logs NO ONE in;
 *   • continue — the pending browser completes the sign-in once approved (consumes the request, single-use). If
 *                the account also has 2FA it hands off to the 2FA challenge; otherwise it logs in.
 *
 * The pending state grants no access by construction: /app + /owner sit behind `auth`, which the not-yet-logged-in
 * pending user fails.
 */
class LoginApprovalController extends Controller
{
    use RedirectsByRole;

    public function __construct(private readonly LoginApprovalService $approvals) {}

    /** The pending screen. Restarts login if the pending state is gone. */
    public function show(Request $request): View|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            $request->session()->forget(['login.approval.user_id', 'login.approval.id']);

            return redirect()->route('login');
        }

        return view('auth.login-approval');
    }

    /** The emailed SIGNED link (`signed` middleware verifies it). Marks the request approved; logs no one in. */
    public function approve(Request $request, string $approval): View
    {
        $token = (string) $request->query('token', '');
        $model = LoginApproval::query()->find($approval);

        $ok = $model instanceof LoginApproval
            && $token !== ''
            && $this->approvals->approve($model->user, $token);

        return view('auth.login-approval-confirmed', ['ok' => $ok]);
    }

    /** The pending browser completes the sign-in — only if the request is genuinely approved. */
    public function complete(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        $approvalId = $request->session()->get('login.approval.id');

        if ($user === null || ! is_string($approvalId)) {
            $request->session()->forget(['login.approval.user_id', 'login.approval.id']);

            return redirect()->route('login');
        }

        $approval = $this->approvals->pending($user, $approvalId);
        if ($approval === null || ! $approval->isApproved()) {
            // Not approved yet — stay on the pending screen with an honest nudge.
            return redirect()->route('login-approval.show')->with('status', __('php/http/controllers/auth/login_approval_controller.not_approved_yet'));
        }

        $this->approvals->consume($approval);
        $request->session()->forget(['login.approval.user_id', 'login.approval.id']);

        // If the account ALSO has 2FA, the approval was only the device check — now do the code challenge.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.2fa.user_id', (string) $user->getKey());
            $request->session()->regenerate();

            return redirect()->route('two-factor.challenge');
        }

        // No 2FA — complete the login now.
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $response = redirect()->to($this->homeFor($user) ?? route('login'));

        // Let a login-approval user also trust this device (so they skip the email next time), same as the 2FA path.
        if ($request->boolean('trust_device')) {
            $response->withCookie(app(TrustedDeviceService::class)->issue($user, $request));
        }

        return $response;
    }

    /** The pending user — still active AND still login-approval-enabled, else null. */
    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.approval.user_id');
        if (! is_string($id) || $id === '') {
            return null;
        }

        $user = User::query()->where('status', 'active')->find($id);

        return $user !== null && $user->requiresLoginApproval() ? $user : null;
    }
}
