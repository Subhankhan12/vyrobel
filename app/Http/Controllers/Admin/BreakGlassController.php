<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BreakGlassSession;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Support\Platform\AdminReason;
use App\Support\Platform\BreakGlass;
use App\Support\Platform\ConsoleFields;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformQuery;
use App\Support\TwoFactor\PlatformTwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 3 — BREAK-GLASS: start, see, end. The mechanics live in {@see BreakGlass}; this is the
 * console's door to them.
 *
 * START REQUIRES, in this order, all server-side: the feature switched on · a written reason · a fresh 2FA CODE
 * (step-up — a TOTP from the admin's own authenticator, rate-limited) · a chosen organisation that is not archived
 * · a verified back-office user there to view as · no tenant login in this same browser (the audit trail must
 * never mix the two) · no other open session for this admin.
 *
 * ⚠️ DORMANT while `platform.break_glass.enabled` is false: every action here is a 404.
 */
class BreakGlassController extends Controller
{
    private const MAX_CODE_ATTEMPTS = 5;

    public function __construct(
        private readonly BreakGlass $breakGlass,
        private readonly PlatformQuery $platform,
        private readonly PlatformAuditLogger $audit,
        private readonly PlatformTwoFactorService $twoFactor,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureEnabled();

        [$organizations, $recent] = $this->platform->read('platform.break_glass.view', fn (): array => [
            Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
            BreakGlassSession::query()->with(['platformAdmin:id,name', 'organization:id,name'])->latest('started_at')->limit(25)->get(),
        ], []);

        return view('admin.break-glass.index', [
            'organizations' => $organizations,
            'recent' => $recent,
            'current' => $this->breakGlass->current($request),
            'minutes' => BreakGlass::minutes(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->ensureEnabled();

        $data = $request->validate([
            'organization_id' => ['required', 'string'],
            'code' => ['required', 'string'],
        ], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);

        /** @var PlatformAdmin $admin */
        $admin = Auth::guard('admin')->user();
        $back = redirect()->route('admin.break-glass.index');

        // ⚠️ STEP-UP — the admin's own authenticator, now. Rate-limited like the login challenge.
        $throttle = 'break-glass-step-up:'.$admin->id;
        if (RateLimiter::tooManyAttempts($throttle, self::MAX_CODE_ATTEMPTS)) {
            return $back->withErrors(['code' => 'Too many codes tried. Wait a minute and try again.'])->withInput($request->except('code'));
        }
        $totp = preg_replace('/\D/', '', (string) $data['code']) ?? '';
        if ($admin->two_factor_secret === null || strlen($totp) !== 6 || ! $this->twoFactor->verify((string) $admin->two_factor_secret, $totp)) {
            RateLimiter::hit($throttle);
            $this->audit->record('platform.break_glass.step_up_failed', [], 'organization', (string) $data['organization_id'], reason: $reason);

            return $back->withErrors(['code' => 'That code is not valid. Enter the current code from your authenticator app.'])->withInput($request->except('code'));
        }
        RateLimiter::clear($throttle);

        if (Auth::guard('web')->check()) {
            return $back->withErrors(['organization_id' => 'Sign out of the organisation account in this browser first — a support session must never mix with a real user\'s session.']);
        }
        if ($this->breakGlass->current($request) !== null) {
            return $back->withErrors(['organization_id' => 'You already have a support session open. End it first.']);
        }

        $organization = Organization::query()->find($data['organization_id']);
        if ($organization === null) {
            return $back->withErrors(['organization_id' => 'That organisation does not exist, or is archived.']);
        }

        $viewing = $this->breakGlass->viewingUserFor($organization);
        if ($viewing === null) {
            return $back->withErrors(['organization_id' => 'This organisation has no active, verified back-office user to view as — so there is nothing to open. (Support can mark one verified first, audited.)']);
        }

        $this->platform->mutate(
            'platform.break_glass.start',
            $reason,
            fn (): BreakGlassSession => $this->breakGlass->start($admin, $organization, $viewing, $reason, $request),
            ['viewing_user_id' => (string) $viewing->id, 'minutes' => BreakGlass::minutes()],
            'organization',
            (string) $organization->id,
        );

        return redirect()->route('app.dashboard');
    }

    public function end(Request $request): RedirectResponse
    {
        $this->ensureEnabled();

        $session = $this->breakGlass->current($request);
        if ($session !== null) {
            $this->breakGlass->end($session, 'ended');
        }
        $request->session()->forget(BreakGlass::SESSION_KEY);

        return redirect()->route('admin.break-glass.index')
            ->with('admin_notice', $session !== null ? 'Support session ended. The organisation has been told in-app.' : 'No support session was open.');
    }

    private function ensureEnabled(): void
    {
        if (! BreakGlass::enabled()) {
            throw new NotFoundHttpException;
        }
    }
}
