<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Security\SignInProblems;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "This was me" on the sign-in problems card of BOTH Security pages (2026-10-24): the person says the refused sign-ins on
 * their address were their own, and the card stops showing them. Only refusals AFTER this moment show again
 * ({@see SignInProblems}). Self-only — it stamps the signed-in person and takes nothing from the request. Recorded with the
 * count it hid, never an address or an IP.
 */
class SecurityAlertController extends Controller
{
    public function seen(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $hidden = SignInProblems::for($user)['count'];
        $user->forceFill(['security_alerts_seen_at' => now()])->save();
        $audit->record('user.security_alert_dismissed', $user, null, ['refusals' => $hidden]);

        return back()->with('security_notice', 'Got it — that warning is cleared. A new one shows if it happens again.');
    }
}
