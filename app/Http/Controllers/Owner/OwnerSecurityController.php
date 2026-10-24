<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Unit;
use App\Support\Auth\TrustedDeviceService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Security\ActiveSessions;
use App\Support\Security\SecurityOverview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner SECURITY writes (owner account area · Gate 2) — self-only, re-auth-gated, controller POSTs (never
 * Livewire actions). #28: everything resolves from `$request->user()`; the data export is owner-scoped.
 */
class OwnerSecurityController extends Controller
{
    /**
     * Sign out the user's OTHER sessions — the current one stays. Real: deletes the other rows from the
     * `sessions` table (database driver), so those sessions can't be restored. Re-auth with the password.
     */
    public function signOutOtherSessions(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $request->validate(['current_password' => ['required', 'current_password']]);

        // ⚠️ SESSIONS 01 — the ONE shared implementation: the other rows deleted, the password re-hashed so every
        // other session fails its hash check whatever the driver, and the remember-token rotated.
        $deleted = ActiveSessions::signOutOthers($request, $user, (string) $request->input('current_password'));

        $audit->record('owner.other_sessions_signed_out', $user, null, ['count' => $deleted]);

        return back()->with('owner_notice', $deleted > 0
            ? 'Signed out of '.$deleted.' other '.Str::plural('session', $deleted).'.'
            : 'You have no other active sessions.');
    }

    /**
     * DEACTIVATE (pause) the account — 🔒 PRIMARY-OWNER-ONLY (route-gated by EnsurePrimaryOwner, #42),
     * re-auth with the password. REVERSIBLE + NON-DESTRUCTIVE: it only flips `users.status` to 'disabled'
     * (login is active-only, so the account can no longer sign in) — bookings, statements, payouts and every
     * financial record STAY intact. Real PII erasure remains the separate anonymize() path via the management
     * team; this button never deletes anything. Reactivation restores access (status → 'active').
     */
    public function deactivate(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $request->validate(['current_password' => ['required', 'current_password']]);

        $user->forceFill(['status' => 'disabled'])->save();
        $audit->record('owner.account_deactivated', $user, null, ['fields' => ['status']]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account is paused — you’re signed out and won’t be able to sign in until it’s reactivated. Your statements and payout history are kept. Contact your management team to reactivate.');
    }

    /**
     * Revoke ONE trusted device (Wave B · GATE 4) — self-only + re-auth (a trust bypasses 2FA, so removing one
     * is a sensitive change). {@see TrustedDeviceService::revoke} scopes to the user's own rows (a cross-user id
     * is a no-op); if the revoked device is THIS browser, its cookie is forgotten too so it must pass 2FA again.
     */
    public function revokeTrustedDevice(Request $request, TrustedDeviceService $devices, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = $request->validate([
            'device_id' => ['required', 'string'],
            'current_password' => ['required', 'current_password'],
        ]);

        $wasCurrent = $devices->revoke($user, (string) $data['device_id'], $request);
        $audit->record('owner.trusted_device_revoked', $user, null, ['device_id' => (string) $data['device_id']]);

        $response = back()->with('owner_notice', 'That device was removed — it’ll need a 2FA code next time.');

        return $wasCurrent !== null ? $response->withCookie($devices->forgetCookie()) : $response;
    }

    /**
     * Turn LOGIN APPROVAL on/off (Wave B · GATE 4) — self-only + re-auth. When on, a sign-in from an untrusted
     * new device is held pending until the emailed link is clicked. Opt-in; a trusted device (or turning this
     * back off) is the recovery path, so it cannot lock the user out permanently.
     */
    public function setLoginApproval(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $request->validate([
            'enabled' => ['required', 'boolean'],
            'current_password' => ['required', 'current_password'],
        ]);

        $enabled = $request->boolean('enabled');

        // ⚠️⚠️ 2026-10-24 — ON only while email really goes out. The approval link is an email: with the mailer discarding
        //    every message (production's `log` today), a new device's sign-in would be held for a link nobody receives, and
        //    the only way in would be a device already trusted. OFF is always allowed — the way out is never blocked.
        if ($enabled && ! SecurityOverview::loginApproval($user, true)['available']) {
            return back()->with('security_error', 'Login approval can’t be turned on yet: email isn’t being sent from Vyrobel, so the approval link would never reach you and a new device couldn’t sign in.');
        }

        $user->forceFill(['require_login_approval' => $enabled])->save();
        $audit->record('owner.login_approval_changed', $user, null, ['enabled' => $enabled]);

        return back()->with('owner_notice', $enabled
            ? 'Login approval is on — a new device must be approved by email before it can sign in.'
            : 'Login approval is off.');
    }

    /**
     * "Request my data" — a real OWNER-SCOPED export (#28): the acting user's profile plus the statements,
     * payouts and units their owner access covers (the OwnerScope is already active in the /owner portal, so
     * these queries only ever return this owner's rows). Streamed as a JSON download — no external service.
     */
    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = [
            'exported_for' => [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'language' => (string) ($user->locale ?: 'en'),
            ],
            'statements' => OwnerStatement::query()->get()
                ->map(fn ($m) => $m->only(['id', 'status', 'period_start', 'period_end', 'net_payable_minor', 'currency']))->all(),
            'payouts' => Payout::query()->get()
                ->map(fn ($m) => $m->only(['id', 'status', 'amount_minor', 'currency', 'paid_at']))->all(),
            'units' => Unit::query()->get()
                ->map(fn ($m) => $m->only(['id', 'code', 'name', 'kind']))->all(),
        ];

        return response()->streamDownload(function () use ($data): void {
            echo (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, 'vyrobel-my-data.json', ['Content-Type' => 'application/json']);
    }
}
