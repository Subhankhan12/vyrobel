<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Mail\OwnerEmailChangeMail;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\I18n\Locales;
use App\Support\Mail\MailSafetyGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Owner PER-USER self-profile writes (owner account area · Gate 1) — the acting login edits its OWN
 * name / language / avatar / login-email. Operator-mutation pattern: POST → controller → PRG.
 *
 * 🔒 #28 SELF-ONLY: every action resolves the subject as `$request->user()` — the acting login, NEVER a
 * request-supplied id. So an owner can only ever change their OWN account, and a sub-user (accountant /
 * view-only) uses this exactly like the primary owner (this is the fix for the old sub-user 404, where the
 * page tried to resolve an `Owner` row by `user_id` that a sub-user does not have). The OWNER-PARTY / business
 * details are a SEPARATE, primary-owner-only surface ({@see OwnerProfileController}).
 *
 * 🔒 EMAIL CHANGE RE-VERIFIES: a new login email is NEVER switched on submit — it is parked in
 * `users.pending_email` and a signed verify link is mailed TO THE NEW ADDRESS; the switch happens only when
 * that link is opened (proving control of the new inbox). The link's `{hash}` is `sha1($pendingEmail)`, so a
 * later change to the pending address auto-invalidates any older link (the same mechanism the guest claim
 * flow uses). Audit rows record only WHICH field changed, never the value (PII discipline, #28).
 */
class OwnerAccountController extends Controller
{
    private const AVATAR_MAX_KB = 4096; // 4 MB

    private const AVATAR_DISK = 'public';

    /** Name + language — the always-editable self fields (every owner-realm user). */
    public function update(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', Rule::in(Locales::offered())],
        ]);

        $user->forceFill($data)->save();

        $audit->record('owner.account_updated', $user, null, ['fields' => array_keys($data)]);

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_account_controller.profile_saved'));
    }

    /** Avatar upload — content-validated image, ULID filename, public disk (mirrors RoomTypePhotoController). */
    public function uploadAvatar(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::AVATAR_MAX_KB],
        ]);

        $file = $request->file('avatar');
        $ext = $file->extension() ?: 'jpg';                       // from the VALIDATED (content-guessed) file
        $name = (string) Str::ulid().'.'.$ext;                    // generated — never the client filename
        $path = $file->storeAs('avatars/'.$user->getKey(), $name, self::AVATAR_DISK);

        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => $path])->save();

        if (is_string($old) && $old !== '') {
            Storage::disk(self::AVATAR_DISK)->delete($old);
        }

        $audit->record('owner.avatar_updated', $user, null, ['fields' => ['avatar_path']]);

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_account_controller.photo_updated'));
    }

    public function removeAvatar(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $old = $user->avatar_path;
        if (is_string($old) && $old !== '') {
            Storage::disk(self::AVATAR_DISK)->delete($old);
        }
        $user->forceFill(['avatar_path' => null])->save();

        $audit->record('owner.avatar_removed', $user, null, ['fields' => ['avatar_path']]);

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_account_controller.photo_removed'));
    }

    /** Step 1 of an email change: park the new address + mail a signed verify link TO IT. No switch yet. */
    public function requestEmailChange(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        // ⚠️⚠️ RE-AUTHENTICATE BEFORE MOVING THE LOGIN IDENTITY (audit 2026-08-29 · H-3).
        //
        // This validated ONLY the address, and that made a hijacked session PERMANENT: park an attacker's
        // address, open the confirmation from that inbox (the verify route is public + `signed`, so it needs
        // no session at all), and the login email MOVES — after which a password reset goes to the attacker.
        // REPRODUCED end to end before this line existed: users.email became attacker-inbox@evil.test.
        //
        // ⚠️ THE SIBLING'S RULE, NOT A THIRD WAY (§N.8f). Admin/AccountController:111 uses
        // `current_password:admin` because the platform realm authenticates on the `admin` guard; the guest
        // account controller uses `current_password:guest`. The owner portal authenticates on the DEFAULT
        // `web` guard (routes/web.php:1257 — plain `auth`), and there is NO `owner` guard in config/auth.php,
        // so the correct rule here is the BARE `current_password`. Verified, not assumed.
        //
        // ⚠️ No owner can be locked out by this: `users.password` is NOT NULL, and the owner-team invite sets
        // a password when the sub-user accepts.
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
                Rule::notIn([(string) $user->email]),
            ],
        ], [], ['email' => __('php/http/controllers/owner/owner_account_controller.attr_email'), 'current_password' => __('php/http/controllers/owner/owner_account_controller.attr_current_password')]);

        $newEmail = (string) $data['email'];

        // ⚠️ D-MAIL-CLAIMS (2026-11-11, his "do all"): the change is confirmed by a link MAILED to the new address. While email is not sent from Vyrobel that link
        //    can never arrive — parking the address would show "we emailed a link" and leave a pending change nobody can confirm. Refused: nothing parked,
        //    the login email unchanged.
        if (! MailSafetyGuard::sendingNow()) {
            return back()->withInput($request->except('current_password'))->withErrors(['email' => __('php/http/controllers/owner/owner_account_controller.email_change_no_mail')]);
        }

        $user->forceFill(['pending_email' => $newEmail])->save();

        $url = URL::temporarySignedRoute('owner.account.email.verify', now()->addHour(), [
            'user' => $user->getKey(),
            'hash' => sha1($newEmail),
        ]);
        Mail::to($newEmail)->locale($user->preferredLocale())->send(new OwnerEmailChangeMail((string) $user->name, $newEmail, $url));

        $audit->record('owner.email_change_requested', $user, null, ['fields' => ['email']]);

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_account_controller.email_change_sent'));
    }

    /** Cancel a pending email change (clears the parked address; any outstanding link then no longer matches). */
    public function cancelEmailChange(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $user->forceFill(['pending_email' => null])->save();

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_account_controller.email_change_cancelled'));
    }

    /** Step 2: the signed link is opened from the NEW inbox — verify and switch. Signed, no auth required. */
    public function verifyEmailChange(Request $request, User $user, string $hash, AuditEventRecorder $audit): RedirectResponse
    {
        $pending = (string) ($user->pending_email ?? '');

        // The link binds to the parked address; a null/changed pending email no longer matches.
        if ($pending === '' || ! hash_equals(sha1($pending), $hash)) {
            return redirect()->route('login')->with('status', __('php/http/controllers/owner/owner_account_controller.email_link_invalid'));
        }

        // Someone else may have taken the address since it was requested — fail closed, keep the old email.
        if (User::query()->where('email', $pending)->whereKeyNot($user->getKey())->exists()) {
            $user->forceFill(['pending_email' => null])->save();

            return redirect()->route('login')->with('status', __('php/http/controllers/owner/owner_account_controller.email_in_use'));
        }

        $user->forceFill([
            'email' => $pending,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        $audit->record('owner.email_changed', $user, null, ['fields' => ['email']]);

        return redirect()->route('login')->with('status', __('php/http/controllers/owner/owner_account_controller.email_updated'));
    }
}
