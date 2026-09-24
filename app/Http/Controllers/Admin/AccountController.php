<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminEmailChangeMail;
use App\Models\PlatformAdmin;
use App\Support\Auth\PasswordPolicy;
use App\Support\Platform\PlatformAdminPasswordPolicy;
use App\Support\Platform\PlatformAdminSessions;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\TwoFactor\PlatformTwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * SUPER ADMIN · ACCOUNT — the platform admin manages their OWN identity (name · password · login email · 2FA
 * recovery). The highest-privilege identity, so the tenant account-rebuild discipline is MIRRORED, not weakened:
 *
 *   🔒 SELF-ONLY — every action resolves the subject from `Auth::guard('admin')->user()`, NEVER a request id.
 *   🔒 SENSITIVE CHANGES RE-AUTH — the password change, recovery-code regeneration, AND the email-change
 *      request each require the CURRENT password (`current_password:admin`) before anything happens (anti-hijack
 *      of a left-open console session).
 *   🔒 EMAIL RE-VERIFIES — a new login email is parked in `pending_email` and only switched when a signed,
 *      expiring link opened from the NEW inbox confirms control (the `users.pending_email` pattern). The live
 *      email stays the login until then.
 *   🔒 EVERY change is AUDITED to the platform action ledger (WHICH field changed, never the value).
 *
 * A SEPARATE, parallel implementation for the admin realm — it does not reuse the tenant routes/controllers.
 */
class AccountController extends Controller
{
    public function __construct(private readonly PlatformAuditLogger $audit) {}

    /** The account page. Shows one-time recovery codes if they were just regenerated (flashed). */
    public function edit(): View
    {
        return view('admin.account.index', [
            'admin' => $this->admin(),
            'recoveryCodes' => session('admin_recovery_codes'),
        ]);
    }

    /** Edit the always-safe profile fields (name). Audited. */
    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = $this->admin();

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $admin->forceFill($data)->save();

        $this->audit->record('platform.account.profile_updated', ['fields' => array_keys($data)]);

        return redirect()->route('admin.account.edit')->with('admin_notice', 'Your profile is saved.');
    }

    /**
     * Change the password. 🔒 CURRENT password required (anti-hijack) + the real server-authoritative rule
     * ({@see PasswordPolicy}'s composition via `Password::defaults()`, confirmed) + the
     * single hash+rotate path. This session stays signed in (no re-login). ⚠️ The old note here said
     * "Password::min(8)" — true when written, false after DO13-4, and the same stale-docblock class the
     * payout enum and the schedule PDF both carried.
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $admin = $this->admin();

        $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            // ⚠️⚠️ ADMIN 01 · PHASE 1 — the SAME rule account creation uses (12+ chars, mixed case, a number). This
            //    used to be the app-wide default (8 characters), so the super-admin password could be changed to
            //    something weaker than it was allowed to be created with.
            'password' => ['required', 'confirmed', PlatformAdminPasswordPolicy::rule()],
        ]);

        $admin->changePasswordTo((string) $request->string('password'));
        // ⚠️ ADMIN 01 · Phase 1 — a changed password ends the admin's OTHER console sessions, enforced on their next
        //    request whatever the session driver (the tenant side cannot do this on production — KNOWN-LIMITS L16).
        $endedOthers = app(PlatformAdminSessions::class)->endOthersFor($admin, $request);

        // The audit row records the TRUE number of sessions ended — never a claim the code did not verify.
        $this->audit->record('platform.account.password_changed', ['other_sessions_ended' => $endedOthers]);

        return redirect()->route('admin.account.edit')->with('admin_notice', 'Your password has been changed.'
            .($endedOthers > 0 ? " {$endedOthers} other console ".Str::plural('session', $endedOthers).($endedOthers === 1 ? ' was' : ' were').' signed out.' : ''));
    }

    /** Regenerate the 2FA recovery codes (shown ONCE). 🔒 CURRENT password required; audited. */
    public function regenerateRecoveryCodes(Request $request, PlatformTwoFactorService $twoFactor): RedirectResponse
    {
        $admin = $this->admin();
        $request->validate(['current_password' => ['required', 'current_password:admin']]);

        if (! $admin->hasTwoFactorEnabled()) {
            return redirect()->route('admin.account.edit')->with('admin_notice', 'Two-factor is not active yet.');
        }

        $codes = $twoFactor->regenerateRecoveryCodes($admin);
        $this->audit->record('platform.account.recovery_codes_regenerated');

        return redirect()->route('admin.account.edit')->with('admin_recovery_codes', $codes)->with('admin_notice', 'New recovery codes generated — save them now.');
    }

    /**
     * Step 1 of an email change: park the new address + mail a signed verify link TO IT. 🔒 CURRENT password
     * required; NO switch yet — the live email stays the login until the link is opened. Audited.
     */
    public function requestEmailChange(Request $request): RedirectResponse
    {
        $admin = $this->admin();

        $data = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('platform_admins', 'email')->ignore($admin->getKey()),
                Rule::notIn([(string) $admin->email]),
            ],
        ], [], ['email' => 'email address']);

        $newEmail = (string) $data['email'];
        $admin->forceFill(['pending_email' => $newEmail])->save();

        // Signed, expiring, bound to the parked address (a later change auto-invalidates any older link).
        $url = URL::temporarySignedRoute('admin.account.email.verify', now()->addHour(), [
            'admin' => $admin->getKey(),
            'hash' => sha1($newEmail),
        ]);
        Mail::to($newEmail)->send(new AdminEmailChangeMail((string) $admin->name, $newEmail, $url));

        $this->audit->record('platform.account.email_change_requested', ['fields' => ['email']]);

        return redirect()->route('admin.account.edit')->with('admin_notice', 'Check your new inbox — we emailed a link to confirm the change. Your email stays the same until you open it.');
    }

    /** Cancel a pending email change (clears the parked address; any outstanding link then no longer matches). */
    public function cancelEmailChange(): RedirectResponse
    {
        $this->admin()->forceFill(['pending_email' => null])->save();

        return redirect()->route('admin.account.edit')->with('admin_notice', 'The email change was cancelled.');
    }

    /**
     * Step 2: the signed link is opened from the NEW inbox — verify + switch. Signed (no auth: it may be opened
     * on a device where the admin isn't signed in); the security is the HMAC signature + the pending-email hash
     * binding + the current-password re-auth already required at request time.
     */
    public function verifyEmailChange(Request $request, string $admin, string $hash): RedirectResponse
    {
        $model = PlatformAdmin::query()->find($admin);
        if ($model === null) {
            return redirect()->route('admin.login')->with('status', 'That email-change link is no longer valid. Please request a new one.');
        }

        $pending = (string) ($model->pending_email ?? '');
        if ($pending === '' || ! hash_equals(sha1($pending), $hash)) {
            return redirect()->route('admin.login')->with('status', 'That email-change link is no longer valid. Please request a new one.');
        }

        // Someone else may have taken the address since it was requested — fail closed, keep the old email.
        if (PlatformAdmin::query()->where('email', $pending)->whereKeyNot($model->getKey())->exists()) {
            $model->forceFill(['pending_email' => null])->save();

            return redirect()->route('admin.login')->with('status', 'That email address is already in use. Your email was not changed.');
        }

        $model->forceFill(['email' => $pending, 'pending_email' => null])->save();

        $this->audit->record('platform.account.email_changed', ['fields' => ['email']], actor: $model);

        return redirect()->route('admin.login')->with('status', "Your sign-in email is now {$pending}. Use that address from now on — the old one no longer works.");
    }

    /** The current authenticated platform admin (self-resolved from the guard, never a request id). */
    private function admin(): PlatformAdmin
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof PlatformAdmin, 403);

        return $admin;
    }
}
