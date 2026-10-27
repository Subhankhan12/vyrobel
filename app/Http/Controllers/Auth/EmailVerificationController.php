<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ⚠️⚠️ EMAIL VERIFICATION FOR THE OPERATOR REALM (GATE 01, 2026-09-23) — the door the founder ruled for:
 * **verify before `/app`**. Signup stays fully open and public; an unverified account simply does nothing
 * until the link is clicked.
 *
 * ⚠️⚠️ THESE THREE ROUTES MUST STAY *OUTSIDE* THE GATE THEY LIFT. Gating the page that lifts the gate is the
 * classic self-inflicted wound: the user is redirected to `verification.notice`, which redirects to
 * `verification.notice`, forever. They carry `auth` (you must be signed in to verify *your own* address) and
 * nothing else — deliberately.
 *
 * ⚠️ `EmailVerificationRequest` is the framework's own form request: it validates the signed URL's `{id}` and
 * `{hash}` against the authenticated user, so one user's link can never verify another's account. Reusing it
 * rather than hand-rolling the comparison is the point — this is the {@see Guest} pattern, which
 * has implemented the same contract all along.
 */
class EmailVerificationController extends Controller
{
    /** The "check your inbox" page. ⚠️ An unverified user MUST be able to reach this — it is the way out. */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()?->hasVerifiedEmail()
            ? redirect()->intended(route('app.dashboard'))
            : view('auth.verify-email');
    }

    /** The signed link itself. Marks verified, fires {@see Verified}, lands the user where they were going. */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('app.dashboard'));
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('app.dashboard'))->with('status', __('php/http/controllers/auth/email_verification_controller.email_verified'));
    }

    /**
     * Resend the link.
     *
     * ⚠️ Throttled at the route. ⚠️ And it is honest about the current state: while no mail transport is
     * configured the send silently succeeds and nothing arrives — so the VIEW says so rather than implying
     * an email is on its way. See `auth/verify-email.blade.php`.
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('app.dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
