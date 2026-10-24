<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationPreferences;
use App\Support\Auth\LoginApprovalService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Security\NewDeviceSignIn;
use App\Support\Security\SecurityOverview;
use App\Support\Security\SessionTimeout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The account-security settings BOTH Security pages share (2026-10-24, the founder's design) — login approval, approving a
 * waiting sign-in from here, login alerts and the session timeout. One controller for both realms: the back office's routes
 * (`app.security.*`) and the owner portal's (`owner.security.*`) reach the same code, so the two pages cannot drift.
 *
 * Self-only: every write is for the signed-in person (`$request->user()`), never a request id; switching a protection on or
 * off re-asks for the password; each change is recorded with the realm it came from.
 */
class AccountSecurityController extends Controller
{
    /**
     * Login approval on / off. ⚠️ ON only while email really goes out: the approval link is an email, and with every message
     * discarded (production's mailer today) a new device's sign-in would wait for a link nobody receives. OFF always works.
     */
    public function loginApproval(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $this->user($request);
        $request->validate([
            'enabled' => ['required', 'boolean'],
            'current_password' => ['required', 'current_password'],
        ]);

        $enabled = $request->boolean('enabled');
        if ($enabled && ! SecurityOverview::loginApproval($user, true)['available']) {
            return back()->with('security_error', 'Login approval can’t be turned on yet: email isn’t being sent from Vyrobel, so the approval link would never reach you and a new device couldn’t sign in.');
        }

        $user->forceFill(['require_login_approval' => $enabled])->save();
        $audit->record($this->realm($request).'.login_approval_changed', $user, null, ['enabled' => $enabled]);

        return back()->with($this->noticeKey($request), $enabled
            ? 'Login approval is on — a new device waits until you approve it.'
            : 'Login approval is off.');
    }

    /**
     * Approve or deny a sign-in waiting for this person's approval, from a device where they are already signed in. Only one
     * of their OWN, still-pending approvals can be decided; an approved one lets the waiting browser finish its sign-in, a
     * denied one is closed for good.
     */
    public function decideApproval(Request $request, LoginApprovalService $approvals, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $this->user($request);
        $data = $request->validate([
            'approval' => ['required', 'string', 'max:40'],
            'decision' => ['required', Rule::in(['approve', 'deny'])],
        ]);

        $approval = $approvals->pending($user, (string) $data['approval']);
        if ($approval === null || $approval->approved_at !== null) {
            return back()->with('security_error', 'That sign-in is no longer waiting — it was already decided, or it expired.');
        }

        $approve = $data['decision'] === 'approve';
        $approvals->decide($approval, $approve);
        $audit->record($this->realm($request).'.login_approval_'.($approve ? 'approved' : 'denied'), $user, null, ['approval' => (string) $approval->getKey()]);

        return back()->with($this->noticeKey($request), $approve
            ? 'Approved — that device can finish signing in now.'
            : 'Denied — that sign-in can’t continue. If it wasn’t you, change your password.');
    }

    /** Staff login alerts on / off — the SAME stored setting as Notification preferences' "Login from a new device". */
    public function loginAlerts(Request $request, NotificationPreferences $preferences, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $this->user($request);
        $request->validate(['enabled' => ['required', 'boolean']]);

        $enabled = $request->boolean('enabled');
        $preferences->setEvent($user, NewDeviceSignIn::OPERATOR_KEY, $enabled);
        $audit->record($this->realm($request).'.login_alerts_changed', $user, null, ['enabled' => $enabled]);

        return back()->with($this->noticeKey($request), $enabled
            ? 'Login alerts are on — a sign-in from a new device appears in your bell.'
            : 'Login alerts are off.');
    }

    /** The session timeout: off (the store's own lifetime) or 15 / 30 / 60 minutes without activity. */
    public function sessionTimeout(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $this->user($request);
        $data = $request->validate(['minutes' => ['nullable', Rule::in(array_map('strval', SessionTimeout::OPTIONS))]]);

        $minutes = isset($data['minutes']) && $data['minutes'] !== '' ? (int) $data['minutes'] : null;
        $user->forceFill(['session_timeout_minutes' => $minutes])->save();
        // the clock starts now, on this request — never from a time before the choice was made
        $request->session()->put(SessionTimeout::KEY, time());
        $audit->record($this->realm($request).'.session_timeout_changed', $user, null, ['minutes' => $minutes]);

        return back()->with($this->noticeKey($request), $minutes === null
            ? 'Session timeout is off — you stay signed in for '.SessionTimeout::defaultLabel().' without activity, as before.'
            : 'You’ll be signed out after '.SessionTimeout::label($minutes).' without activity.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /** Which page asked — the audit names the realm, as the passkey writes do. */
    private function realm(Request $request): string
    {
        return $request->routeIs('owner.*') ? 'owner' : 'app';
    }

    /** Each page shows its own notice key. */
    private function noticeKey(Request $request): string
    {
        return $request->routeIs('owner.*') ? 'owner_notice' : 'security_notice';
    }
}
