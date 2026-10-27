<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsByRole;
use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireTwoFactorPending;
use App\Models\User;
use App\Support\Auth\TrustedDeviceService;
use App\Support\TwoFactor\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * The 2FA LOGIN CHALLENGE — shown after a correct password when the account has 2FA. The user is in the
 * PENDING state ({@see RequireTwoFactorPending}), NOT authenticated; only a valid TOTP
 * OR recovery code completes `Auth::login`. Recovery codes are single-use (consumed by the service). The code
 * entry is RATE-LIMITED (keyed on the pending user + IP) — a 6-digit code is brute-forceable otherwise. On
 * success the session is regenerated (fixation protection) and the user is routed by role.
 */
class TwoFactorChallengeController extends Controller
{
    use RedirectsByRole;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private TwoFactorService $twoFactor) {}

    /** Show the challenge screen. */
    public function create(): View
    {
        return view('auth.two-factor-challenge');
    }

    /** Verify a TOTP or recovery code → log in; else reject (rate-limited). */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            // The pending user vanished / disabled 2FA between steps — restart login.
            $request->session()->forget('login.2fa.user_id');

            return redirect()->route('login');
        }

        $this->ensureNotRateLimited($request, (string) $user->getKey());
        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->verifyChallenge($user, (string) $request->string('code'))) {
            RateLimiter::hit($this->throttleKey($request, (string) $user->getKey()));

            throw ValidationException::withMessages([
                'code' => __('php/http/controllers/auth/two_factor_challenge_controller.code_invalid'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, (string) $user->getKey()));

        // Success — NOW log in, regenerate (fixation), clear the pending marker, route by role.
        Auth::login($user);
        $request->session()->forget('login.2fa.user_id');
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $response = redirect()->to($this->homeFor($user) ?? route('login'));

        // TRUSTED DEVICE (Wave B · GATE 4) — if the user ticked "trust this device", remember this browser so it
        // can skip the challenge next time (bounded by expiry + revocation, self-only). The cookie carries a
        // random token; only its hash is stored.
        if ($request->boolean('trust_device')) {
            $response->withCookie(app(TrustedDeviceService::class)->issue($user, $request));
        }

        return $response;
    }

    /** The pending user — still active AND still 2FA-enabled, else null. */
    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.2fa.user_id');
        if (! is_string($id) || $id === '') {
            return null;
        }

        $user = User::query()->where('status', 'active')->find($id);

        return $user !== null && $user->hasTwoFactorEnabled() ? $user : null;
    }

    private function ensureNotRateLimited(Request $request, string $userId): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $userId), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $userId));

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    /** Keyed on the PENDING user + IP — brute-force protection on the 6-digit code. */
    private function throttleKey(Request $request, string $userId): string
    {
        return 'two-factor:'.$userId.'|'.$request->ip();
    }
}
